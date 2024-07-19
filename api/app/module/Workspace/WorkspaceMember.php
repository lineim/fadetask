<?php
namespace app\module\Workspace;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\toolkit\ModuleTrait;
use app\module\BaseModule;
use app\module\Workspace\models\WorkspaceMember as WorkspaceMemberModel;
use Throwable;

class WorkspaceMember extends BaseModule
{
    use ModuleTrait;

    public function getMemberRole($userId, $workspaceId)
    {
        $member = WorkspaceMemberModel::where('workspace_id', $workspaceId)
            ->where('member_id', $userId)
            ->first(['role']);
        if (!$member) {
            return false;
        }
        return $member->role;
    }

    public function getWorkspaceMembersCount($uuid, $keywords = '')
    {
        $workspace = $this->getWorkspaceModule()->getByUuid($uuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workpsace.not_found');
        }

        $userIdsGotByKeywords = [];
        if (trim($keywords)) {
            $allMembers = WorkspaceMemberModel::where('workspace_id', $workspace->id)
                ->where('deleted', 0)
                ->get(['member_id']);

            $allMemberIds = $allMembers->pluck('member_id')->toArray();
            $userIdsGotByKeywords = $this->getUserModule()->searchUserByIdsAndKeywords($allMemberIds, $keywords, ['id'])->pluck('id');
        }
        $model = WorkspaceMemberModel::where('workspace_id', $workspace->id)->where('deleted', 0);
        if (trim($keywords)) {
            $model->whereIn('member_id', $userIdsGotByKeywords);
        }
        return $model->count();
    }

    public function getWorkspaceMembers($uuid, $keywords = '', $page = 1, $limit = 20, $fields = ['*'])
    {
        $workspace = $this->getWorkspaceModule()->getByUuid($uuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workpsace.not_found');
        }

        $userIdsGotByKeywords = [];
        if (trim($keywords)) {
            $allMembers = WorkspaceMemberModel::where('workspace_id', $workspace->id)
                ->where('deleted', 0)
                ->get(['member_id']);

            $allMemberIds = $allMembers->pluck('member_id')->toArray();
            $userIdsGotByKeywords = $this->getUserModule()->searchUserByIdsAndKeywords($allMemberIds, $keywords, ['id'])->pluck('id');
        }

        $userIds = [];
        $userRoles = [];
        $joinTimes = [];
        $creatorIds = [];
        $memberAndCreator = [];

        $model = WorkspaceMemberModel::where('workspace_id', $workspace->id)->where('deleted', 0);
        if (trim($keywords)) {
            $model->whereIn('member_id', $userIdsGotByKeywords);
        }
        $workspaceMembers = $model->orderBy('id', 'desc')->get(['member_id', 'role', 'created_time', 'creator_id']);

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
        if (!in_array('id', $fields) && !in_array('*', $fields)) {
            $fields[] = 'id';
        }
        $members = $this->getUserModule()->search(['user_ids' => $userIds], [], $page, $limit, $fields);

        foreach ($members as &$member) {
            unset($member->passhash);
            $member->workspace_role = $userRoles[$member->id] ?? '';
            $member->creator = $indexCreators[$memberAndCreator[$m->member_id]] ?? [];
            $member->join_time = $joinTimes[$member->id] ?? 0;
        }
        return $members;
    }

    public function joinByToken($userId, $token)
    {
        $tokenInfo = $this->veirfyToken($token);
        $workspaceUuid = $tokenInfo['workspace_uuid'];
        $workspace = $this->getWorkspaceModule()->getByUuid($workspaceUuid, ['id', 'uuid']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }
        if (!in_array($tokenInfo['role'], [WorkspaceMemberModel::ROLE_MEMBER, WorkspaceMemberModel::ROLE_ADMIN])) {
            throw new BusinessException('workspace.member_role_error');
        }
        
        $exist = WorkspaceMemberModel::where('workspace_id', $workspace->id)->where('member_id', $userId)->first();
        $this->beginTransaction();
        try {
            if ($exist) { // 处理老成员：更新角色，取消删除状态。
                $updateData = ['deleted' => 0];
                if ($exist->role == WorkspaceMemberModel::ROLE_MEMBER) { // 如果用户原本是管理员或者Owner，就不修改角色
                    $updateData['role'] = $tokenInfo['role'];
                }
                WorkspaceMemberModel::where('workspace_id', $workspace->id)
                    ->where('member_id', $userId)
                    ->update($updateData);
                if ($exist->deleted) { // 如果之前被删除了，则需要重新更新工作空间的成员数量字段
                    $this->getWorkspaceModule()->incrementMemberCount($workspace->id);
                }
            } else {
                $role = $tokenInfo['role'];
                $inviterId = $tokenInfo['inviter_id'];
            
                $member = new WorkspaceMemberModel();
                $member->workspace_id = $workspace->id;
                $member->member_id = $userId;
                $member->role = $role;
                $member->creator_id = $inviterId;
                $member->created_time = time();
                $member->save();
                $this->getWorkspaceModule()->incrementMemberCount($workspace->id);
            }
            $this->commit();
            return $workspace;
        } catch (Throwable $t) {
            $this->rollback();
            $this->getLogger()->error('join workspace by token failed: ' .$t->getMessage(), [
                'workspace_uuid' => $workspaceUuid,
                'user_id' => $userId,
                'token' => $token,
                'exception_trace' => $t->getTraceAsString(),
            ]);
            throw $t;
        }
        
        $this->getStorageRedis()->del($token);
        return $workspace;
    }

    public function inviteUrl($frontUrl, $userId, $workspaceUuid, $role = WorkspaceMemberModel::ROLE_MEMBER)
    {
        $workspace = $this->getWorkspaceModule()->getByUuid($workspaceUuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }
        if (!$this->getWorkspaceModule()->hasAdminPermission($workspace->id, $userId)) {
            throw new AccessDeniedException();
        }
    
        $token = $this->makeInviteToken($workspaceUuid, $userId);        
        $redis = $this->getStorageRedis();
        $expireAt = time() + 8 * 3600;
        $tokenInfo = [
            'workspace_uuid' => $workspaceUuid,
            'role' => $role,
            'expire_at' => $expireAt,
            'inviter_id' => $userId,
            'invite_time' => time()
        ];
        $redis->hMSet($token, $tokenInfo);
        $redis->expireAt($token, $expireAt);

        return $frontUrl . '?' . http_build_query(['token' => $token, 'workspace_uuid' => $workspaceUuid]);
    }

    public function veirfyToken($token)
    {
        $redis = $this->getStorageRedis();
        $tokenInfo = $redis->hGetAll($token);
        if (!$tokenInfo) {
            throw new BusinessException('token.invalid');
        }
        $tokenInfo['expire_at'] = $tokenInfo['expire_at'] ?? 0;
        if ($tokenInfo['expire_at'] < time()) {
            throw new BusinessException('token.expired');
        }
        return $tokenInfo;
    }

    public function deleteMember($workspaceUuid, $userId, $operatorId)
    {
        $workspace = $this->getWorkspaceModule()->getByUuid($workspaceUuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }
        if (!$this->getWorkspaceModule()->hasAdminPermission($operatorId, $workspace->id)) {
            throw new AccessDeniedException();
        }
        $this->beginTransaction();
        try {
            $count = WorkspaceMemberModel::where('workspace_id', $workspace->id)
                ->where('member_id', $userId)
                ->where('deleted', 0)
                ->update([
                    'deleted' => 1,
                ]);
            if ($count) {
                $this->getWorkspaceModule()->decrementMemberCount($workspace->id);
            }
            $this->commit();
            return $count;
        } catch (\Exception $e) {
            $this->rollback();
            $this->getLogger()->error('delete workspace member failed: ' . $e->getMessage(), [
                'workspace_uuid' => $workspaceUuid,
                'user_id' => $userId,
                'operator_id' => $operatorId,
                'exception_trace' => $e->getTraceAsString(),
            ]);
            throw new BusinessException('workspace.member.delete_failed');
        }
    }

    public function changeMemberRole($workspaceUuid, $userId, $role, $operatorId) : bool
    {
        $workspace = $this->getWorkspaceModule()->getByUuid($workspaceUuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }
        if (!$this->getWorkspaceModule()->hasAdminPermission($operatorId, $workspace->id)) {
            throw new AccessDeniedException();
        }
        if (!in_array($role, [WorkspaceMemberModel::ROLE_MEMBER, WorkspaceMemberModel::ROLE_ADMIN])) {
            throw new BusinessException('workspace.member_role_error');
        }
        if ($role == WorkspaceMemberModel::ROLE_OWNER) {
            throw new BusinessException('workspace.member_cannot_change_to_owner_role');
        }
        $member = WorkspaceMemberModel::where('workspace_id', $workspace->id)
            ->where('member_id', $userId)
            ->where('deleted', 0)
            ->first();
        if (!$member) {
            throw new BusinessException('workspace.member_not_found');
        }
        if ($member->role == WorkspaceMemberModel::ROLE_OWNER) {
            throw new BusinessException('workspace.member_cannot_change_owner_role');
        }
        if ($member->role == $role) {
            return true;
        }

        $this->beginTransaction();
        try {
            $count = WorkspaceMemberModel::where('workspace_id', $workspace->id)
                ->where('member_id', $userId)
                ->where('deleted', 0)
                ->update([
                    'role' => $role,
                ]);
            $this->commit();
            return !!$count;
        } catch (\Exception $e) {
            $this->rollback();
            $this->getLogger()->error('change workspace member role failed: ' . $e->getMessage(), [
                'workspace_uuid' => $workspaceUuid,
                'user_id' => $userId,
                'operator_id' => $operatorId,
                'exception_trace' => $e->getTraceAsString(),
            ]);
            throw new BusinessException('workspace.member_role_change_failed');
        }
    }

}
