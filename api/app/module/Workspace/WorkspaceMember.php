<?php
namespace app\module\Workspace;

use app\common\exception\BusinessException;
use app\common\toolkit\ModuleTrait;
use app\module\BaseModule;
use app\module\Workspace\models\WorkspaceMember as WorkspaceMemberModel;

class WorkspaceMember extends BaseModule
{
    use ModuleTrait;

    public function getWorkspaceMembers($uuid, $page = 1, $limit = 20, $fields = ['*'])
    {
        $workspace = $this->getWorkspaceModule()->getByUuid($uuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workpsace.not_found');
        }

        $userIds = [];
        $userRoles = [];
        $joinTimes = [];
        $creatorIds = [];
        $memberAndCreator = [];
        $workspaceMembers = WorkspaceMemberModel::where('id', $workspace->id)
            ->orderBy('id', 'desc')
            ->get(['member_id', 'role', 'created_time', 'creator_id']);

        foreach ($workspaceMembers as $m) {
            $userIds[] = $m->member_id;
            $creatorIds[] = $m->creator_id;
            $memberAndCreator[$m->member_id] = $m->creator_id;
            $userRoles[$m->member_id] = $m->role;
            $joinTimes[$m->member_id] = $m->created_time;
        }
        if (!$userIds) {
            return [];
        }

        $indexCreators = [];
        $creators = $this->getUserModule()->getByUserIds($creatorIds, ['id', 'name', 'email']);
        foreach ($creators as $c) {
            $indexCreators[$c->id] = $c;
        }

        $members = $this->getUserModule()->search(['user_ids' => $userIds], [], $page, $limit, $fields);

        foreach ($members as &$member) {
            unset($member->passhash);
            $member->workspace_role = $userRoles[$member->id] ?? '';
            $member->creator = $indexCreators[$memberAndCreator[$m->member_id]] ?? [];
        }
        return $members;
    }

}
