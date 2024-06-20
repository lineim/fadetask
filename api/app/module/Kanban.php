<?php
namespace app\module;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\model\CustomField as ModelCustomField;
use app\model\CustomFieldOption;
use \app\model\Kanban as KanbanModel;
use app\model\KanbanFavorite;
use app\model\KanbanLabel;
use app\model\KanbanList as KanbanListModel;
use \app\model\KanbanMember as KanbanMemberModel;
use app\module\CustomField\CustomField;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use support\Db as SupportDb;

class Kanban extends BaseModule
{
    const FREE_USER_MAX_COUNT = 8;

    const MEMBER_ROLE_OWNER = 0;
    const MEMBER_ROLE_ADMIN = 1;
    const MEMBER_ROLE_USER  = 2;

    const MEMBER_LEAVED = 1;
    const MEMBER_UNLEAVED = 0;

    const MAX_NAME_LEN = 32;
    const MAX_DESC_LEN = 128;

    const KANBAN_CLOSED = 1;
    const KANBAN_NOT_CLOSED = 0;

    const KANBAN_MAX_WIP = 99;

    const KANBAN_VIEW_RANK_KEY_PRE = 'kb:v:rank:';

    const LIST_ARCHIVED = 1;
    const LIST_UNARCHIVED = 0;

    const IS_COMPLETED_LIST = 1;
    const NOT_COMPLETED_LIST = 0;

    public function get($id, array $fields = ['*'])
    {
        return KanbanModel::where('id', (int) $id)->first($fields);
    }

    public function getByIds(array $ids, array $fields = ['*'])
    {
        return KanbanModel::whereIn('id', $ids)
            ->where('is_closed', self::KANBAN_NOT_CLOSED)
            ->orderBy('id', 'desc')
            ->get($fields);
    }

    public function getByUuid($uuid, array $fields = ['*'])
    {
        return KanbanModel::where('uuid', $uuid)->first($fields);
    }

    public function viewKanban($id, $userId, $timestamp = 0)
    {
        if (!$timestamp) {
            $timestamp = time();
        }
        $redis = $this->getStorageRedis();
        $rankKey = self::KANBAN_VIEW_RANK_KEY_PRE . $userId;
        $redis->zAdd($rankKey, $timestamp, $id);
    }

    public function getRecentlyViewedKanbans($userId, $num = 5)
    {
        $redis = $this->getStorageRedis();
        $rankKey = self::KANBAN_VIEW_RANK_KEY_PRE . $userId;

        $viewKanbanIds = $redis->zRevRange($rankKey, 0, $num - 1);
        if (!$viewKanbanIds) {
            return [];
        }
        $kanbans = $this->getByIds($viewKanbanIds, ['id', 'uuid', 'name']);

        $indexKanbans = [];
        foreach ($kanbans as $kanban) {
            $indexKanbans[$kanban->id] = $kanban;
        }
        $sortKanbans = [];
        foreach ($viewKanbanIds as $id) {
            if (!isset($indexKanbans[$id])) {
                continue;
            }
            $kanban = [
                'uuid' => $indexKanbans[$id]->uuid,
                'name' => $indexKanbans[$id]->name,
            ];
            $sortKanbans[] = $kanban;
        }

        return $sortKanbans;
    }

    public function list(array $fields = ['*'])
    {
        return KanbanModel::all($fields);
    }

    public function create($name, $desc, $userId)
    {
        $this->validateName($name);
        if (!$userId) {
            throw new InvalidParamsException('user id required!');
        }
        if (!$this->getUserModule()->isSysAdmin($userId) && !$this->canUserCreate($userId)) {
            throw new BusinessException('kanban.create.create_limited');
        }

        $desc = $this->formatDesc($desc);

        $time = time();

        $kanban = new KanbanModel();
        $kanban->name = $name;
        $kanban->uuid = Uuid::uuid4()->toString();
        $kanban->desc = $desc;
        $kanban->user_id = $userId;
        $kanban->created_time = $time;
        $kanban->save();

        $member = new KanbanMemberModel();
        $member->kanban_id = $kanban->id;
        $member->member_id = $userId;
        $member->role = self::MEMBER_ROLE_OWNER;
        $member->created_time = $kanban->created_time;
        $member->save();

        // init list
        $initList = [
            ['name' => '新', 'kanban_id' => $kanban->id, 'sort' => 0, 'created_time' => $time],
            ['name' => '进行中', 'kanban_id' => $kanban->id, 'sort' => 1, 'created_time' => $time],
            ['name' => '已完成', 'kanban_id' => $kanban->id, 'sort' => 2, 'created_time' => $time]
        ];
        KanbanListModel::insert($initList);

        // init labels
        $this->getKanbanLabelModule()->kanbanInit($kanban->id, $userId);

        return $kanban;
    }

    public function createFrom($fromUuid, $name, $desc, $operator)
    {
        $this->validateName($name);
        $desc = $this->formatDesc($desc);

        $kanban = KanbanModel::where('uuid', $fromUuid)->first();
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }
        if ($kanban->is_closed) {
            throw new BusinessException('kanban.closed_err_msg');
        }
        if (!$this->isAdmin($kanban->id, $operator)) {
            throw new AccessDeniedException();
        }
        if (!$this->getUserModule()->isSysAdmin($operator) && !$this->canUserCreate($operator)) {
            throw new BusinessException('kanban.create.create_limited');
        }

        $lists = $this->getKanbanList($kanban->id);
        $labels = $this->getKanbanLabelModule()->getKanbanLabels($kanban->id);
        $members = $this->getKanbanMemberModule()->getMembers($kanban->id);
        $customeFields = $this->getCustomFieldModule()->getKanbanCustomFields($kanban->id);

        $this->beginTransaction();
        try {
            $kanban = new KanbanModel();
            $kanban->name = $name;
            $kanban->uuid = Uuid::uuid4()->toString();;
            $kanban->desc = $desc;
            $kanban->user_id = $operator;
            $kanban->created_time = time();
            $kanban->save();

            $newMembers = [];
            foreach ($members as $member) {
                if ($member->leaved) {
                    continue;
                }
                $role = KanbanMember::MEMBER_ROLE_USER;
                if ($member->member_id == $operator) {
                    $role = KanbanMember::MEMBER_ROLE_OWNER;
                } else {
                    if (in_array($member->role, [KanbanMember::MEMBER_ROLE_ADMIN, KanbanMember::MEMBER_ROLE_OWNER])) {
                        $role = KanbanMember::MEMBER_ROLE_ADMIN;
                    }
                }
                $newMembers[] = [
                    'kanban_id' => $kanban->id,
                    'member_id' => $member->member_id,
                    'role' => $role,
                    'leaved' => 0,
                    'created_time' => time(),
                ];
            }
            if ($members) {
                $insertMembers = KanbanMemberModel::insert($newMembers);
                if (!$insertMembers) {
                    throw new \Exception('Copy member Error!');
                }
            }

            $newLabels = [];
            foreach ($labels as $label) {
                $newLabels[] = [
                    'kanban_id' => $kanban->id,
                    'sort' => $label->sort,
                    'name' => $label->name,
                    'color' => $label->color,
                    'creator_id' => $operator,
                    'created_time' => time()
                ];
            }
            if ($newLabels) {
                $insertNewLabels = KanbanLabel::insert($newLabels);
                if (!$insertNewLabels) {
                    throw new \Exception('Copy label Error!');
                }
            }

            $newLists = [];
            foreach ($lists as $list) {
                $newLists[] = [
                    'kanban_id' => $kanban->id,
                    'name' => $list->name,
                    'task_count' => 0,
                    'wip' => $list->wip,
                    'sort' => $list->sort,
                    'archived' => $list->archived,
                    'completed' => $list->completed,
                    'created_time' => time()
                ]; 
            }
            if ($newLists) {
                $insertNewLists = KanbanListModel::insert($newLists);
                if (!$insertNewLists) {
                    throw new \Exception('Copy list Error!');
                }
            }

            $newGeneralFields = [];
            $newDropdownFields = [];
            foreach ($customeFields as $customeField) {
                $fields = [
                    'kanban_id' => $kanban->id,
                    'name' => $customeField->name,
                    'type' => $customeField->type,
                    'show_on_card_front' => $customeField->show_on_card_front,
                    'sort' => $customeField->sort,
                    'user_id' => $operator,
                    'created_time' => time(),
                ];
                if ($fields['type'] == CustomField::SUPPORT_TYPE_DROPDOWN) {
                    $fields['options'] = $customeField->options;
                    $newDropdownFields[] = $fields;
                } else {
                    $newGeneralFields[] = $fields;
                }
            }
            if ($newGeneralFields) {
                $insertGeneralFields = ModelCustomField::insert($newGeneralFields);
                if (!$insertGeneralFields) {
                    throw new \Exception('Copy general fields error!');
                }
            }
            if ($newDropdownFields) {
                foreach ($newDropdownFields as $dropdownField) {
                    $options = $dropdownField['options'];
                    $newOptions = [];
                    unset($dropdownField['options']);
                    $dropdownFieldId = ModelCustomField::insertGetId($dropdownField);
                    if (!$dropdownFieldId) {
                        throw new \Exception('Copy dropdown field error!');
                    }
                    foreach ($options as $option) {
                        $newOptions[] = [
                            'field_id' => $dropdownFieldId,
                            'val' => $option['val'],
                            'color' => $option['color'],
                            'user_id' => $operator,
                            'updated_time' => 0,
                            'created_time' => time()
                        ];
                    }
                    if ($newOptions) {
                        $insertOptions = CustomFieldOption::insert($newOptions);
                        if (!$insertOptions) {
                            throw new \Exception('Copy dropdown options error!');
                        }
                    }
                }
            }
            $this->commit();
            return $kanban;
        } catch (\Exception $e) {
            $this->rollback();
            $this->getLogger()->error($e->getMessage());
            throw $e;
        }
    }

    private function validateName($name)
    {
        if (!trim($name) || mb_strlen($name) > self::MAX_NAME_LEN) {
            throw new InvalidParamsException("kanban.name.error");
        }
    }

    private function formatDesc($desc)
    {
        return mb_substr($desc, 0, self::MAX_DESC_LEN);
    }

    public function canUserCreate($userId) 
    {
        $count = KanbanModel::where('user_id', $userId)
            ->where('is_closed', self::KANBAN_NOT_CLOSED)
            ->count();
        if ($count >= self::FREE_USER_MAX_COUNT) {
            return false;
        }
        return true;
    }

    public function updateKanbanNameAndDesc($kanbanUuid, $name, $desc, $operator)
    {
        $kanban = $this->getByUuid($kanbanUuid, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $kanbanId = $kanban->id;
        if (!$this->isAdmin($kanbanId, $operator)) {
            throw new AccessDeniedException();
        }
        $this->validateName($name);
        $kanban = KanbanModel::where('id', (int) $kanbanId)->first();
        $kanban->name = $name;
        $kanban->desc = $this->formatDesc($desc);
        return $kanban->save();
    }

    public function joinKanban($kanbanId, $userId, $role)
    {
        $kanban = $this->get($kanbanId, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException("kanban.not_found");
        }
        if (!in_array($role, $this->getMemberRoles())) {
            throw new InvalidParamsException("invalid role");
        }
        if ($this->isMember($kanbanId, $userId)) {
            return true;
        }
        $member = new KanbanMemberModel();
        $member->kanban_id = $kanban->id;
        $member->member_id = $userId;
        $member->role = $role;
        $member->created_time = time();
        return $member->save();
    }

    public function deleteKanbanMember($kanbanId, $userId, $operator)
    {
        if (!$this->isAdmin($kanbanId, $operator)) {
            throw new AccessDeniedException();
        }
        $member = KanbanMemberModel::where('kanban_id', $kanbanId)
            ->where('member_id', $userId)
            ->first(['role']);
        if (!$member) {
            throw new BusinessException("kanban.member.msg_not_found");
        }
        if (self::MEMBER_ROLE_OWNER == $member->role) {
            throw new AccessDeniedException();
        }

        $kanbanTasks = $this->getKanbanTaskModule()->getKanbanTasks($kanbanId, ['id'], true);
        $kanbanTaskIds = $kanbanTasks->pluck('id')->toArray();

        // 从看板的所有卡片中，移除该成员
        if ($kanbanTaskIds) {
            $this->getKanbanTaskModule()->rmMemberFromTasks($userId, $kanbanTaskIds);
        }
        
        return KanbanMemberModel::where('kanban_id', $kanbanId)->where('member_id', $userId)->delete();
    }

    /**
     * 将一个用户从所有看板中移除.
     */
    public function deleteMemberInKanbans($userId, array $kanbanIds)
    {
        if (!$kanbanIds) {
            return false;
        }
        $kanbanTasks = $this->getKanbanTaskModule()->getKanbansTasks($kanbanIds, ['id'], true);
        $kanbanTaskIds = $kanbanTasks->pluck('id')->toArray();

        // 从看板的所有卡片中，移除该成员
        if ($kanbanTaskIds) {
            $this->getKanbanTaskModule()->rmMemberFromTasks($userId, $kanbanTaskIds);
        }
        return KanbanMemberModel::whereIn('kanban_id', $kanbanIds)->where('member_id', $userId)->delete();
    }

    public function associateToProject($kanbanUuid, $projectUuid, $userId)
    {
        $kanban = $this->getByUuid($kanbanUuid, ['id']);
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }
        $kanbanId = $kanban->id;
        if (!$this->isAdmin($kanbanId, $userId)) {
            throw new AccessDeniedException();
        }
        return $this->getProjectModule()->addKanbanToProject($kanbanId, $projectUuid, $userId);
    }

    public function close($kanbanId)
    {
        $kanban = $this->getByUuid($kanbanId, ['id', 'is_closed']);
        if (!$kanban) {
            throw new ResourceNotFoundException("kanban.not_found");
        }
        $kanban->is_closed = self::KANBAN_CLOSED;
        $closed = $kanban->save();
        if ($closed) {
            $this->getProjectModule()->onKanbanClose($kanban->id);
        }
        return $closed;
    }

    public function closeByIds($kanbanIds)
    {
        if (!$kanbanIds) {
            return true;
        }
        $rowCount = KanbanModel::whereIn('id', $kanbanIds)
            ->where('is_closed', self::KANBAN_NOT_CLOSED)
            ->update(['is_closed' => self::KANBAN_CLOSED]);
        
        return $rowCount;
    }

    public function unclose($kanbanId) 
    {
        $kanban = $this->get($kanbanId, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException("kanban.not_found");
        }
        $kanban->is_closed = self::KANBAN_NOT_CLOSED;
        $opened = $kanban->save();
        if ($opened) {
            $this->getProjectModule()->onKanbanOpen($kanbanId);
        }
        return $opened;
    }

    public function favorite($kanbanId, $userId)
    {
        if (!$this->isMember($kanbanId, $userId)) {
            throw new AccessDeniedException();
        }
        if ($this->isFavorited($kanbanId, $userId)) {
            return true;
        }
        $kanban = $this->get($kanbanId, ['is_closed']);
        if ($kanban->is_closed) {
            throw new BusinessException('kanban.closed_err_msg');
        }
        $f = [
            'user_id' => $userId,
            'kanban_id' => $kanbanId,
            'created_time' => time(),
        ];
        return KanbanFavorite::insert($f);
    }

    public function unfavorite($kanbanId, $userId)
    {
        if (!$this->isMember($kanbanId, $userId)) {
            throw new AccessDeniedException();
        }
        return KanbanFavorite::where('user_id', $userId)
            ->where('kanban_id', $kanbanId)
            ->delete();
    }

    public function getUserFavorites($userId, $fields = ['*'])
    {
        $kanbanIds = KanbanFavorite::where('user_id', $userId)
            ->orderBy('id', 'DESC')->get('kanban_id')
            ->pluck('kanban_id')
            ->toArray();
        
        $kanbanIds = array_unique($kanbanIds);
        
        if (!$kanbanIds) {
            return [];
        }
        return KanbanModel::whereIn('id', $kanbanIds)
            ->where('is_closed', self::KANBAN_NOT_CLOSED)
            ->orderByRaw(SupportDb::raw("FIELD(id, '" . join("', '",$kanbanIds) . "')"))
            ->get($fields);
    }

    public function isFavorited($kanbanId, $userId)
    {
        return KanbanFavorite::where('user_id', $userId)
            ->where('kanban_id', $kanbanId)
            ->exists();
    }

    public function getMemberIds($id) : Collection
    {
        return KanbanMemberModel::where('kanban_id', $id)->pluck('member_id');
    }

    public function getMembers($id, array $fields = ['*']) : Collection
    {
        return KanbanMemberModel::where('kanban_id', $id)
            ->orderBy('role', 'ASC')
            ->get($fields);
    }

    public function getMembersWithUserInfo($id, array $fields = ['*']) : array
    {
        $members = KanbanMemberModel::where('kanban_id', $id)
            ->orderBy('role', 'ASC')
            ->get([ 'member_id', 'role']);

        $userIds = [];
        foreach ($members as $m) {
            $userIds[] = $m->member_id;
        }

        $users = $this->getUserModule()->getByUserIds($userIds, $fields);
        $usersIndexed = [];
        foreach ($users as $user) {
            $usersIndexed[$user->id] = $user;
        }

        $returnUsers = [];
        foreach ($members as $m) {
            if (isset($usersIndexed[$m->member_id])) {
                $user = $usersIndexed[$m->member_id];
                $user->kanban_id = $id;
                $user->memeber_role = $m->role;
                $returnUsers[] = $user->toArray();
            }
        }

        return $returnUsers;
    }

    public function isAdmin($kanbanId, $userId)
    {
        if ($this->getUserModule()->isSysAdmin($userId)) {
            return true;
        }
        $member = KanbanMemberModel::where('kanban_id', $kanbanId)
            ->where('member_id', $userId)
            ->first(['role']);
        if (!$member) {
            return false;
        }
        return in_array($member->role, $this->getMemberAdminRoles());
    }

    public function isMember($kanbanId, $userId)
    {
        if ($this->getUserModule()->isSysAdmin($userId)) {
            return true;
        }
        $member = KanbanMemberModel::where('kanban_id', $kanbanId)
            ->where('member_id', $userId)
            ->first(['role']);
        return $member ? true : false;
    }

    public function isMembers($kanbanId, array $userIds)
    {
        $members = KanbanMemberModel::where('kanban_id', $kanbanId)
            ->whereIn('member_id', $userIds)
            ->get(['role']);
        return count($userIds) == count($members) ? true : false;
    }

    public function setMemberAsAdmin($kanbanId, $userId, $operator) : bool
    {
        if (!$this->isAdmin($kanbanId, $operator)) {
            throw new AccessDeniedException();
        }
        $member = KanbanMemberModel::where('kanban_id', $kanbanId)
            ->where('member_id', $userId)
            ->first(['id']);
        if (!$member) {
            throw new BusinessException("kanban.member.msg_not_found");
        }
        $member->role = self::MEMBER_ROLE_ADMIN;

        return false !== $member->save();
    }

    public function removeMemberAdminPermission($kanbanId, $userId, $operator) : bool
    {
        if (!$this->isAdmin($kanbanId, $operator)) {
            throw new AccessDeniedException();
        }
        $member = KanbanMemberModel::where('kanban_id', $kanbanId)
            ->where('member_id', $userId)
            ->first(['id', 'role']);
        if (!$member) {
            throw new BusinessException("kanban.member.msg_not_found");
        }
        if ($member->role == self::MEMBER_ROLE_OWNER) {
            throw new AccessDeniedException();
        }
        if ($member->role == self::MEMBER_ROLE_USER) {
            return true;
        }
        $member->role = self::MEMBER_ROLE_USER;
        return false !== $member->save();
    }

    public function removeMember($kanbanId, $userId, $operator) : bool
    {
        if (!$this->isAdmin($kanbanId, $operator)) {
            throw new AccessDeniedException();
        }
        $member = KanbanMemberModel::where('kanban_id', $kanbanId)
            ->where('member_id', $userId)
            ->first(['id', 'role']);
        if (!$member) {
            return true;
        }
        if ($member->role == self::MEMBER_ROLE_OWNER) {
            throw new AccessDeniedException();
        }
        return KanbanMemberModel::where('kanban_id', $kanbanId)
            ->where('member_id', $userId)
            ->delete();
    }

    public function getKanbanFirstList($kanbanId)
    {
        $firstList = KanbanListModel::where('kanban_id', $kanbanId)
            ->where('archived', self::LIST_UNARCHIVED)
            ->orderBy('sort', 'ASC')
            ->offset(0)
            ->limit(1)
            ->first();
        return $firstList ? true : false;
    }

    public function getKanbanList($kanbanId)
    {
        return KanbanListModel::where('kanban_id', $kanbanId)
            ->where('archived', self::LIST_UNARCHIVED)
            ->orderBy('sort', 'ASC')
            ->get();
    }

    public function getKanbansList(array $kanbanIds, array $fields = ['*'])
    {
        return KanbanListModel::whereIn('kanban_id', $kanbanIds)
            ->where('archived', self::LIST_UNARCHIVED)
            ->orderBy('sort', 'ASC')
            ->get($fields);
    }

    public function getKanbanListsByKanbanIds(array $kanbanIds)
    {
        return KanbanListModel::whereIn('kanban_id', $kanbanIds)
            ->where('archived', self::LIST_UNARCHIVED)
            ->get();
    }

    public function isListBelongKanban($listId, $kanbanId)
    {
        $list = KanbanListModel::where('kanban_id', $kanbanId)
            ->where('id', $listId)
            ->first(['id']);
        return $list ? true : false;
    }

    /**
     * @return Collection
     */
    public function getList($id, $fields = ['*'])
    {
        return KanbanListModel::where('id', $id)
            ->where('archived', self::LIST_UNARCHIVED)
            ->first($fields);
    }

    public function getListByIds(array $ids, $fields = ['*'])
    {
        $ids = array_unique($ids);
        return KanbanListModel::whereIn('id', $ids)
            ->where('archived', self::LIST_UNARCHIVED)
            ->get($fields);
    }

    public function getUserKanbans($userId, $keyword="", $fields = ['*'])
    {
        $kanbanIds = KanbanMemberModel::where('member_id', $userId)
            ->where('leaved', self::MEMBER_UNLEAVED)
            ->get(['kanban_id'])
            ->pluck('kanban_id');
        $model = KanbanModel::whereIn('id', $kanbanIds)
            ->where('is_closed', self::KANBAN_NOT_CLOSED);
        
        if ($keyword) {
            $model->where('name', 'like', '%'.$keyword.'%');
        }
        return $model->orderBy('id', 'DESC')
            ->get($fields);
    }

    public function getUserManageKanbans($userId, $fields = ['*'])
    {
        $kanbanIds = KanbanMemberModel::where('member_id', $userId)
            ->where('leaved', self::MEMBER_UNLEAVED)
            ->whereIn('role', [KanbanMember::MEMBER_ROLE_ADMIN, KanbanMember::MEMBER_ROLE_OWNER])
            ->get(['kanban_id'])
            ->pluck('kanban_id');
        return KanbanModel::whereIn('id', $kanbanIds)
            ->where('is_closed', self::KANBAN_NOT_CLOSED)
            ->orderBy('name', 'asc')
            ->get($fields);
    }

    public function getAllKanbans($keyword="", $fields = ['*'])
    {
        $model = KanbanModel::where('is_closed', self::KANBAN_NOT_CLOSED);
        if ($keyword) {
            $model->where('name', 'like', '%'.$keyword.'%');
        }
        return $model->orderBy('id', 'DESC')
            ->get($fields);
    }

    public function searchUserKanbans($userId, $keyword, $fields = ['*'], $start = 0, $limit = 10)
    {
        $kanbans = $this->getUserKanbans($userId, '', ['id']);
        if (!$kanbans) {
            return [];
        }
        $kanbanIds = $kanbans->pluck('id')->toArray();

        $model = KanbanModel::whereIn('id', $kanbanIds);
        if ($keyword) {
            $model = $model->where('name', 'like', '%' . $keyword . '%');
        }
            
        return $model->where('is_closed', self::KANBAN_NOT_CLOSED)
            ->offset($start)
            ->limit($limit)
            ->get($fields);
    }

    public function getUserClosedKanbans($userId, $fields = ['*'])
    {
        $kanbanIds = KanbanMemberModel::where('member_id', $userId)
            ->where('leaved', self::MEMBER_UNLEAVED)
            ->get(['kanban_id'])
            ->pluck('kanban_id');
        return KanbanModel::whereIn('id', $kanbanIds)->where('is_closed', self::KANBAN_CLOSED)->get($fields);
    }

    public function createList($kanbanId, $name, $sort = 0)
    {
        $kanban = $this->get($kanbanId);
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }
        $this->validateListName($name);
        if (!$sort) {
            $lastSort = KanbanListModel::where('kanban_id', $kanbanId)
                ->orderBy('sort', 'DESC')
                ->first(['sort']);
            $sort = $lastSort ? $lastSort->sort+1 : 0;
        }
        $list = new KanbanListModel();
        $list->kanban_id = $kanbanId;
        $list->name = $name;
        $list->sort = $sort;
        $list->created_time = time();
        $list->save();
        return $list->id;
    }

    public function changeListName($id, $name, $userId)
    {
        $this->validateListName($name);
        $list = KanbanListModel::where('id', $id)->first(['kanban_id', 'name']);
        if (!$list) {
            throw new ResourceNotFoundException('List Not Found!');
        }
        if (!$this->isMember($list->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        if ($list->name == $name) {
            return true;
        }
        return false !== KanbanListModel::where('id', $id)->update(['name' => $name]);
    }

    /**
     * 看板list排序.
     * 
     * @param intger $id 看板id.
     * @param array  $sortInfo 排序信息, [listId1=>sort, listId2 => sort].
     * @param intger $userId 操作用户id.
     * 
     * @return boolean
     */
    public function kanbanListSort($id, $sortInfo, $userId) : bool
    {
        if (!$this->isMember($id, $userId)) {
            throw new AccessDeniedException("Access Denied!");
        }
        $sortInfo = new Collection($sortInfo);
        $listIds = $sortInfo->keys();
        $lists = KanbanListModel::whereIn('id', $listIds->toArray())->get(['kanban_id']);
        foreach ($lists as $l) {
            if ($l->kanban_id != $id) {
                throw new InvalidParamsException(sprintf('list must belong the kanban %s.', $l->kanban_id));
            }
        }
        foreach ($sortInfo as $listId => $sort) {
            KanbanListModel::where('id', $listId)->update(['sort' => $sort]);
        }
        return true;
    }

    public function archiveList($listId, $userId)
    {
        $list = KanbanListModel::where('id', $listId)
            ->first(['kanban_id', 'archived']);
        if (!$list) {
            throw new ResourceNotFoundException('List Not Found');
        }
        if (!$this->isMember($list->kanban_id, $userId)) {
            throw new AccessDeniedException('Access Denied!');
        }

        if ($list->archived) {
            return true;
        }

        return KanbanListModel::where('id', $listId)->update(['archived' => self::LIST_ARCHIVED]);
    }

    public function unarchiveList($listId, $userId)
    {
        $list = KanbanListModel::where('id', $listId)
            ->first(['kanban_id', 'archived']);
        if (!$list) {
            throw new ResourceNotFoundException('List Not Found');
        }
        if (!$this->isMember($list->kanban_id, $userId)) {
            throw new AccessDeniedException('Access Denied!');
        }

        if (!$list->archived) {
            return true;
        }

        return KanbanListModel::where('id', $listId)->update(['archived' => self::LIST_UNARCHIVED]);
    }

    public function getKanbanArchivedList($kanbanId, array $fields = ['*'])
    {
        return KanbanListModel::where('kanban_id', $kanbanId)
            ->where('archived', self::LIST_ARCHIVED)
            ->get($fields);
    }

    public function getUserInKanbansRole($userId, array $kanbanIds)
    {
        $members = KanbanMemberModel::where('member_id', $userId)
            ->whereIn('kanban_id', $kanbanIds)
            ->get(['kanban_id', 'role']);
        
        $userRoles = [];
        foreach ($members as $m) {
            $userRoles[$m->kanban_id] = $m->role;
        }
        return $userRoles;
    }

    public function setWip($listId, $wip, $operator)
    {
        $list = $this->getList($listId);
        if (!$list) {
            throw new BusinessException('List not found!');
        }
        $kanbanId = $list->kanban_id;
        $wip = $wip < 0 ? 0 : (int)$wip;
        // wip值不能小于当前列表中的任务数量
        if ($wip > 0) {
            $wip = $wip < $list->task_count ? $list->task_count : $wip;
        }
        
        if ($wip > self::KANBAN_MAX_WIP) {
            throw new InvalidParamsException('task.wip.too_large');
        }
        if (!$this->isAdmin($kanbanId, $operator)) {
            throw new AccessDeniedException();
        }

        return false !== KanbanListModel::where('id', $listId)->update(['wip' => $wip]);
    }

    public function getMemberRoles()
    {
        return [
            self::MEMBER_ROLE_OWNER,
            self::MEMBER_ROLE_ADMIN,
            self::MEMBER_ROLE_USER,
        ];
    }

    public function getMemberAdminRoles()
    {
        return [self::MEMBER_ROLE_OWNER, self::MEMBER_ROLE_ADMIN];
    }

    public function isCompletedList($listId) 
    {
        $list = KanbanListModel::where('id', $listId)
            ->first(['completed']);
        if (!$list) {
            throw new BusinessException('kanban.list.not_found');
        }
        return boolval($list->completed);
    }

    public function setListCompleted($listId, $userId, $completed)
    {
        if (!in_array($completed, [self::IS_COMPLETED_LIST, self::NOT_COMPLETED_LIST])) {
            throw new InvalidParamsException('invalid_params');
        }
        $list = KanbanListModel::where('id', $listId)->first(['kanban_id', 'completed']);
        if (!$list) {
            throw new ResourceNotFoundException('list.not_found');
        }

        if (!$this->isMember($list->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }

        if ($completed == $list->completed) {
            return true;
        }

        return KanbanListModel::where('id', $listId)->update(['completed' => $completed]);
    }

    protected function validateListName($name)
    {
        if (!trim($name) || mb_strlen($name) > 64) {
            throw new InvalidParamsException("name error");
        }
    }

}