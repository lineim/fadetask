<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\module;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\ResourceNotFoundException;
use app\controller\ProjectMember;
use \app\model\Project as ProjectModel;
use app\model\ProjectKanban;
use \app\model\ProjectMember as ProjectMemberModel;
use Ramsey\Uuid\Uuid;
use stdClass;

class Project extends BaseModule
{

    const DELETED = 1;
    const UN_DELETED = 0;

    const CLOSED = 1;
    const UN_CLOSED = 0;

    const INVITE_TYPE_LINK = 'link';
    const INVITE_TYPE_EMAIL = 'email';

    const PROJECT_VIEW_RANK_KEY_PRE = 'project:v:rank:';

    // 角色: 0 Owner; 1 管理员; 2 普通成员;
    const MEMBER_ROLE_OWNER = 0;
    const MEMBER_ROLE_MANAGER = 1;
    const MEMBER_ROLE_MEMBER = 2;

    private $managerRoles = [
        self::MEMBER_ROLE_OWNER,
        self::MEMBER_ROLE_MANAGER,
    ];

    public function getManagerRoles()
    {
        return $this->managerRoles;
    }

    public function getProjectById($id, array $fields = ['*'])
    {
        return ProjectModel::where('id', $id)
            ->where('is_deleted', self::UN_DELETED)
            ->first($fields);
    }

    public function getProjectByIds(array $ids, array $fields = ['*'])
    {
        if (!$ids) {
            return [];
        }
        return ProjectModel::whereIn('id', $ids)
            ->where('is_deleted', self::UN_DELETED)
            ->get($fields);
    }

    public function getProjectByUuid($uuid, array $fields = ['*'])
    {
        return ProjectModel::where('uuid', $uuid)
            ->where('is_deleted', self::UN_DELETED)
            ->first($fields);
    }

    public function viewProject($id, $userId, $timestamp = 0)
    {
        if (!$timestamp) {
            $timestamp = time();
        }
        $redis = $this->getStorageRedis();
        $rankKey = self::PROJECT_VIEW_RANK_KEY_PRE . $userId;
        $redis->zAdd($rankKey, $timestamp, $id);
    }

    public function getRecentlyViewedProjects($userId, $num = 5)
    {
        $redis = $this->getStorageRedis();
        $rankKey = self::PROJECT_VIEW_RANK_KEY_PRE . $userId;

        $viewedProjectIds = $redis->zRevRange($rankKey, 0, $num - 1);
        if (!$viewedProjectIds) {
            return [];
        }
        $projects = $this->getProjectByIds($viewedProjectIds, ['id', 'uuid', 'name']);

        $indexedProjects = [];
        foreach ($projects as $project) {
            $indexedProjects[$project->id] = $project;
        }
        $sortProjects = [];
        foreach ($viewedProjectIds as $id) {
            if (!isset($indexedProjects[$id])) {
                continue;
            }
            $project = [
                'uuid' => $indexedProjects[$id]->uuid,
                'name' => $indexedProjects[$id]->name,
            ];
            $sortProjects[] = $project;
        }

        return $sortProjects;
    }

    public function createProject($data, $userId)
    {
        $uuid = Uuid::uuid4();
        $project = new ProjectModel();
        $project->uuid = $uuid->toString();
        $project->member_num = 1;
        $project->name = $data['name'];
        $project->description = $data['desc'] ? $data['desc'] : '';
        $project->user_id = $userId;
        $project->created_time = time();
        $project->updated_time = time();

        $member = new ProjectMemberModel();
        try {
            $project->save();
            $member->project_id = $project->id;
            $member->user_id = $userId;
            $member->role = ProjectMemberModel::ROLE_OWNER;
            $member->join_time = time();
            $member->created_time = time();
            $member->save();
            return $project->uuid;
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage(), $e->getTrace());
            throw $e;
        }
    }

    public function updateProject($uuid, array $updateData)
    {

    }

    public function open($uuid, $operator) : bool
    {
        if (!$this->isManager($uuid, $operator)) {
            throw new AccessDeniedException();
        }
        $project = $this->getProjectByUuid($uuid, ['id', 'is_closed']);
        if (!$project->is_closed) {
            return true;
        }
        $project->is_closed = self::UN_CLOSED;
        return (bool) $project->save();
    }

    public function close($uuid, $operator, $closeKanban = false) : bool
    {
        if (!$this->isManager($uuid, $operator)) {
            throw new AccessDeniedException();
        }
        $project = $this->getProjectByUuid($uuid, ['id', 'is_closed']);
        if ($project->is_closed) {
            return true;
        }
        $project->is_closed = self::CLOSED;
        if (!$closeKanban) {
            return (bool) $project->save();
        }

        $kanbans = $this->getProjectKanbans($uuid, ['id']);
        if (empty($kanbans)) {
            return (bool) $project->save();
        }

        $kanbanIds = $kanbans->pluck('id')->toArray();
        $rowCount = $this->getKanbanModule()->closeByIds($kanbanIds);
        $this->waveKanbanNum($project->id, 0 - $rowCount);
        $project->save();
        return true;
    }

    /**
     * 获取用户的项目.
     * 
     * @param integer $userId 用户id.
     * @param boolean $closed 为true时，获取已经关闭的、且用户可管理的项目；为false时，获取未关闭的所有项目.
     * 
     * @return array 
     */
    public function getUserProject($userId, $closed, $offset = 0, $limit = 10)
    {
        $userMemberModel = ProjectMemberModel::where('user_id', $userId);
        if ($closed) {
            $userMemberModel->whereIn('role', [self::MEMBER_ROLE_OWNER, self::MEMBER_ROLE_MANAGER]);
        }
        $userProjectIds = $userMemberModel->pluck('project_id')->toArray();

        if (!$userProjectIds) {
            return [
                'projects' => [],
                'total_count' => 0
            ];
        }

        $closedVal = $closed ? self::CLOSED : self::UN_CLOSED;

        $projects = ProjectModel::whereIn('id', $userProjectIds)
            ->where('is_deleted', self::UN_DELETED)
            ->where('is_closed', $closedVal)
            ->orderBy('id', 'DESC')
            ->offset($offset)
            ->limit($limit)
            ->get(['user_id', 'uuid', 'name', 'kanban_num', 'member_num', 'created_time']);

        $totalProject = ProjectModel::whereIn('id', $userProjectIds)
            ->where('is_deleted', self::UN_DELETED)
            ->where('is_closed', $closedVal)
            ->count();

        $creatorIds = [];
        foreach ($projects as $p) {
            $creatorIds[] = $p->user_id;
        }
    
        $indexedCreators = [];
        if ($creatorIds) {
            $users = $this->getUserModule()->getByUserIds($creatorIds, ['name', 'id']);
            foreach ($users as $u) {
                $indexedCreators[$u->id] = $u;
                unset($indexedCreators[$u->id]->id);
            }
        }
        foreach ($projects as &$project) {
            $project->key = $project->uuid;
            $project->create_datetime = date('Y-m-d H:i:s', $project['created_time']);
            $project->creator = $indexedCreators[$project->user_id] ?? new stdClass();
        }

        return ['projects' => $projects, 'total_count' => $totalProject];
    }

    public function isManager($uuid, $userId)
    {
        $project = $this->getProjectByUuid($uuid, ['uuid', 'id']);
        if (!$project) {
            throw new ResourceNotFoundException('project.not_found');
        }
        return ProjectMemberModel::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->whereIn('role', [self::MEMBER_ROLE_OWNER, self::MEMBER_ROLE_MANAGER])
            ->where('is_delete', self::UN_DELETED)
            ->exists();
    }

    public function addKanbanToProject($kanbanId, $projectUuid, $userId)
    {
        if (!$this->isManager($projectUuid, $userId)) {
            throw new AccessDeniedException();
        }
        $project = $this->getProjectByUuid($projectUuid, ['id']);
        $projectKanban = ProjectKanban::where('kanban_id', $kanbanId)->first();
        if ($projectKanban) {
            if ($projectKanban->project_id == $project->id) {
                return true;
            }
            $projectKanban->project_id = $project->id;
            $projectKanban->save();
            return true;
        }

        $projectKanban = new ProjectKanban();
        $projectKanban->project_id = $project->id;
        $projectKanban->kanban_id = $kanbanId;
        $projectKanban->user_id = $userId;
        $projectKanban->created_time = time();

        $added = $projectKanban->save();
        if ($added) {
            $this->waveKanbanNum($project->id, 1);
        }
        return $added;
    }

    public function copyProjectMemberToKanban($projectUuid, $kanbanId, $userId)
    {
        if (!$this->isManager($projectUuid, $userId)) {
            throw new AccessDeniedException();
        }
        $project = $this->getProjectByUuid($projectUuid, ['id']);
        $members = $members = ProjectMemberModel::where('project_id', $project->id)->get(['user_id', 'role', 'join_time']);
        if (empty($members)) {
            return true;
        }
        foreach ($members as $member) {
            $memberId = $member->user_id;
            $role = in_array($member->role, $this->getManagerRoles()) ? KanbanMember::MEMBER_ROLE_ADMIN : KanbanMember::MEMBER_ROLE_USER;
            $this->getKanbanMemberModule()->joinKanban($kanbanId, $memberId, $role);
        }
        return true;
    }

    public function rmKanban($projectUuid, $kanbanUUid, $userId)
    {
        if (!$this->isManager($projectUuid, $userId)) {
            throw new AccessDeniedException();
        }
        $project = $this->getProjectByUuid($projectUuid, ['id']);
        $kanban = $this->getKanbanModule()->getByUuid($kanbanUUid, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $kanbanId = $kanban->id;
        if (!ProjectKanban::where('project_id', $project->id)->where('kanban_id', $kanbanId)->exists()) {
            return true;
        }
        $removed = (bool) ProjectKanban::where('project_id', $project->id)->where('kanban_id', $kanbanId)->delete();
        if ($removed) {
            $this->waveKanbanNum($project->id, -1);
        }
        return $removed;
    }

    public function isMember($uuid, $userId)
    {
        $project = $this->getProjectByUuid($uuid, ['uuid', 'id']);
        if (!$project) {
            throw new ResourceNotFoundException('project.not_found');
        }
        return ProjectMemberModel::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->whereIn('role', [self::MEMBER_ROLE_OWNER, self::MEMBER_ROLE_MANAGER, self::MEMBER_ROLE_MEMBER])
            ->where('is_delete', self::UN_DELETED)
            ->exists();
    }

    public function inviteLink($uuid, $operator, $frontUrl)
    {
        if (!$this->isManager($uuid, $operator)) {
            throw new AccessDeniedException();
        }
        $token = $this->makeInviteToken($uuid, $operator);
        $redis = $this->getStorageRedis();
        $expireAt = time() + 8 * 3600;
        $tokenInfo = [
            'invite_type' => self::INVITE_TYPE_LINK,
            'project_uuid' => $uuid,
            'expire_at' => $expireAt
        ];
        $redis->hMSet($token, $tokenInfo);
        $redis->expireAt($token, $expireAt);

        return $frontUrl . '?' . http_build_query(['token' => $token, 'project_uuid' => $uuid]);
    }

    public function joinByToken($userId, $token)
    {
        $token = $this->verifyInviteToken($token);
        if (!$token) {
            throw new BusinessException('invalid.request');
        }
        $projectUUid = $token['project_uuid'];
        if ($this->isMember($projectUUid, $userId)) {
            return true;
        }
        return $this->joinProject($userId, $projectUUid);
    }

    protected function joinProject($userId, $uuid)
    {
        $project = $this->getProjectByUuid($uuid, ['id']);
        $member = [
            'project_id' => $project->id,
            'user_id' => $userId,
            'role' => self::MEMBER_ROLE_MEMBER,
            'join_time' => time(),
            'created_time' => time(),
        ];
        $member = new ProjectMemberModel();
        $member->project_id = $project->id;
        $member->user_id = $userId;
        $member->role = self::MEMBER_ROLE_MEMBER;
        $member->join_time = time();
        $member->created_time = time();
        $joined = $member->save();
        if ($joined) {
            $this->waveMemberNum($project->id, 1);
        }
        return $joined;
    }

    protected function makeInviteToken($uuid, $makerId)
    {
        $strs = ['1', 'a', '@', '$', 'c', '?', '&', '>', '(', 'G', '*'];
        shuffle($strs);
        $salt = array_slice($strs, 0, 8);
        $salt = implode('', $salt);

        return hash('sha512', sprintf('invite:tk:%d%d%s%d', $uuid, $makerId, $salt, time()));
    }

    protected function verifyInviteToken($token)
    {
        $redis = $this->getStorageRedis();
        $tokenInfo = $redis->hGetAll($token);
        if (!$tokenInfo) {
            return false;
        }
        $expireAt = $tokenInfo['expire_at'] ?? 0;
        if (time() > $expireAt) {
            return false;
        }
        // 防止链接泄露给其他人，其他人通过链接加入看板，因此需要校验访问者是否在邀请列表中.
        if ($tokenInfo['invite_type'] == self::INVITE_TYPE_EMAIL) {
            $emails = $tokenInfo['emails'] ? json_decode($tokenInfo['emails'], true) : [];
            $user = $this->getUserModule()->getByUserId($userId, ['email']);
            if (!$user || !in_array($user->email, $emails)) {
                return false;
            }
        }
        return $tokenInfo;
    }

    public function getProjectKanbans($uuid, $fields = ['*'])
    {
        $project = $this->getProjectByUuid($uuid, ['id']);
        if (!$project) {
            throw new ResourceNotFoundException('project.not_found');
        }
        $kanbanIds = ProjectKanban::where('project_id', $project->id)->get()->pluck('kanban_id')->toArray();
        if (empty($kanbanIds)) {
            return [];
        }
        return $this->getKanbanModule()->getByIds($kanbanIds, $fields);
    }

    public function getProjectMembers($uuid, $fields = ['*'], $offset = 0, $limit = 10)
    {
        $project = $this->getProjectByUuid($uuid, ['id']);
        if (!$project) {
            throw new ResourceNotFoundException('project.not_found');
        }
        $members = ProjectMemberModel::where('project_id', $project->id)->get(['user_id', 'role', 'join_time']);
        if (empty($members)) {
            return [];
        }
        $userIds = [];
        $indexedMembers = [];
        foreach ($members as $m) {
            $userIds[] = $m->user_id;
            $indexedMembers[$m->user_id] = $m;
        }
        $users = $this->getUserModule()->getByUserIds($userIds, $fields, $offset, $limit);
        foreach ($users as $u) {
            $member = $indexedMembers[$u->id];
            $u->project_role = $member->role;
            $u->project_join_time = $member->join_time;
            $u->project_join_date = date('Y-m-d H:i:s', $member->join_time);
        }
        return $users;
    }

    public function getProjectMembersCount($uuid)
    {
        $project = $this->getProjectByUuid($uuid, ['id']);
        if (!$project) {
            throw new ResourceNotFoundException('project.not_found');
        }
        $userIds = ProjectMemberModel::where('project_id', $project->id)->get()->pluck('user_id')->toArray();
        if (empty($userIds)) {
            return 0;
        }
        return $this->getUserModule()->getCountByUserIds($userIds);
    }

    public function searchKanbans($uuid, $fields = ['*'], $offset = 0, $limit = 10)
    {
        $project = $this->getProjectByUuid($uuid, ['id']);
        if (!$project) {
            throw new ResourceNotFoundException('project.not_found');
        }

        $kanbanIds = ProjectKanban::where('project_id', $project->id)
            ->get()
            ->pluck('kanban_id')
            ->toArray();
        
        if (empty($kanbanIds)) {
            return [];
        }
        $kanbans = $this->getKanbanModule()->getByIds($kanbanIds, $fields);
        $kanbanIds = $kanbans->pluck('id')->toArray();
        $statModule = $this->getProjectStatModule();
        $statModule->setKanbanIds($kanbanIds);
        $kanbansOverview = $statModule->kanbanOverdue();
        $kanbansOverviewIndexed = [];
        foreach ($kanbansOverview as $overview) {
            $kanbanId = $overview['id'];
            unset($overview['id']);
            $kanbansOverviewIndexed[$kanbanId] = $overview;
        }

        if (!in_array('id', $fields) && !in_array('*', $fields)) {
            array_push($fields, 'id');
        }

        foreach ($kanbans as &$kanban) {
            $statsData = [
                'total' => 0,
                'overdue' => 0,
                'done' => 0
            ];
            if (isset($kanbansOverviewIndexed[$kanban->id])) {
                $statsData = $kanbansOverviewIndexed[$kanban->id];
            }
            $kanban->overview = $statsData;
            unset($kanban->id);
        }
        return $kanbans;
    }

    public function changeUserRole($uuid, $userId, $role, $operator)
    {
        $allowRoles = [self::MEMBER_ROLE_MANAGER, self::MEMBER_ROLE_MEMBER];
        if (!$this->isManager($uuid, $operator)) {
            throw new AccessDeniedException();
        }
        $project = $this->getProjectByUuid($uuid, ['id']);
        $member = ProjectMemberModel::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->first(['id', 'role']);
        if (!$member) {
            throw new BusinessException('project.member.not_found');
        }
        if ($member->role == self::MEMBER_ROLE_OWNER) {
            throw new AccessDeniedException();
        }
        if (!in_array($role, $allowRoles)) {
            throw new BusinessException('project.member.role_not_allowed');
        }
        if ($member->role == $role) {
            return true;
        }
        $member->role = $role;
        return (bool) $member->save();
    }

    /**
     * removeFromKanban 参数暂时不生效，因为在移除看板成员时，
     * 需要考虑成员是不是看板的创建者，创建者不能被移除.
     */
    public function removeMember($uuid, $userId, $operator, $removeFromKanban = false)
    {
        if (!$this->isManager($uuid, $operator)) {
            throw new AccessDeniedException();
        }
        $project = $this->getProjectByUuid($uuid, ['id']);
        $member = ProjectMemberModel::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->first(['id', 'role']);
        if (!$member) {
            throw new BusinessException('project.member.not_found');
        }
        if ($member->role == self::MEMBER_ROLE_OWNER) {
            throw new AccessDeniedException();
        }
        $projectKanbanIds = $this->getProjectKanbans($uuid, ['id'])->pluck('id')->toArray();

        $this->beginTransaction();
        try {
            ProjectMemberModel::where('id', $member->id)->delete();
            $this->waveMemberNum($project->id, -1);
            // if ($removeFromKanban && $projectKanbanIds ) {
            //     $this->getKanbanModule()->deleteMemberInKanbans($userId, $projectKanbanIds );
            // }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getProjectByKanbanId($kanbanId, $fields = ['*'])
    {
        $projectKanban = ProjectKanban::where('kanban_id', $kanbanId)
            ->orderBy('id', 'desc')
            ->first(['project_id']);
        if (!$projectKanban) {
            return new stdClass();
        }
        $project = $this->getProjectById($projectKanban->project_id, $fields);
        if (!$project) {
            return new stdClass();
        }
        return $project;
    }

    public function getKanbansProjects(array $kanbanIds, $fields = ['*'])
    {
        if (empty($kanbanIds)) {
            return [];
        }
        $projectKanbans = ProjectKanban::whereIn('kanban_id', $kanbanIds)
            ->orderBy('id', 'desc')
            ->get(['project_id', 'kanban_id']);
        if (!$projectKanbans) {
            return [];
        }
        $projectIds = [];
        $projectIdToKanbanId = [];
        foreach ($projectKanbans as $projectKanban) {
            $projectIds[] = $projectKanban->project_id;
            $projectIdToKanbanId[$projectKanban->project_id] = $projectKanban->kanban_id;
        }
        if (!$projectIds) {
            return [];
        }
        $projects = $this->getProjectByIds($projectIds, $fields);
        if (!$projects) {
            return [];
        }
        $returnData = [];
        foreach ($projects as $project) {
            $returnData[$projectIdToKanbanId[$project->id]] = $project;
        }
        return $returnData;
    }

    public function getUserManageProjects($userId, $fields = ['*'])
    {
        $projectIds = ProjectMemberModel::where('user_id', $userId)
            ->where('is_delete', self::UN_DELETED)
            ->whereIn('role', [self::MEMBER_ROLE_MANAGER, self::MEMBER_ROLE_OWNER])
            ->get(['project_id'])
            ->pluck('project_id')
            ->toArray();
        
        if (!$projectIds) {
            return [];
        }
        return ProjectModel::whereIn('id', $projectIds)
            ->where('is_deleted', self::UN_DELETED)
            ->where('is_closed', self::UN_CLOSED)
            ->orderBy('id', 'desc')
            ->get($fields);
    }

    public function getUserProjects($userId, $fields = ['*'])
    {
        $projectIds = ProjectMemberModel::where('user_id', $userId)
            ->where('is_delete', self::UN_DELETED)
            ->get(['project_id'])
            ->pluck('project_id')
            ->toArray();
        
        if (!$projectIds) {
            return [];
        }
        return ProjectModel::whereIn('id', $projectIds)
            ->where('is_deleted', self::UN_DELETED)
            ->where('is_closed', self::UN_CLOSED)
            ->orderBy('id', 'desc')
            ->get($fields);
    }

    public function onKanbanClose($kanbanId)
    {
        $kanbanProject = ProjectKanban::where('kanban_id', $kanbanId)->first('project_id');
        if (!$kanbanProject) {
            return true;
        }
        return $this->waveKanbanNum($kanbanProject->project_id, -1);
    }

    public function onKanbanOpen($kanbanId)
    {
        $kanbanProject = ProjectKanban::where('kanban_id', $kanbanId)->first('project_id');
        if (!$kanbanProject) {
            return true;
        }
        return $this->waveKanbanNum($kanbanProject->project_id, 1);
    }

    public function waveKanbanNum($projectId, $step = 1)
    {
        if ($step == 0) {
            return;
        }
        if ($step < 0) {
            return ProjectModel::where('id', $projectId)->decrement('kanban_num', abs($step));
        }
        return ProjectModel::where('id', $projectId)->increment('kanban_num', $step);
    }

    public function waveMemberNum($projectId, $step = 1)
    {
        if ($step == 0) {
            return;
        }
        if ($step < 0) {
            return ProjectModel::where('id', $projectId)->decrement('member_num', abs($step));
        }
        return ProjectModel::where('id', $projectId)->increment('member_num', $step);
    }

}
