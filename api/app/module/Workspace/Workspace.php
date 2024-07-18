<?php
namespace app\module\Workspace;

use app\common\exception\BusinessException;
use app\common\toolkit\ModuleTrait;
use app\module\BaseModule;
use app\module\Workspace\models\Workspace as WorkspaceModel;
use app\module\Workspace\models\WorkspaceMember;
use app\module\Workspace\models\WorkspaceTaskType;
use Ramsey\Uuid\Uuid;

class Workspace extends BaseModule
{
    use ModuleTrait;

    public function createWorkspace($name, $userId)
    {
        $defaultTypes = array_slice($this->getAllTaskTypes(), 0, 3);
        
        $member = new WorkspaceMember();
        $member->member_id = $userId;
        $member->creator_id = $userId;
        $member->role = WorkspaceMember::ROLE_OWNER;
        $member->created_time = time();

        $workspace = new WorkspaceModel();
        $workspace->name = mb_substr($name, 0, 128);
        $workspace->uuid =  Uuid::uuid4()->toString();
        $workspace->creator_id = $userId;
        $workspace->member_count = 1;
        $workspace->pay_plan = WorkspaceModel::PAY_PLAN_FREE;
        $workspace->created_time = time();

        $this->beginTransaction();
        try {
            $workspace->save();
            $setTaskTypeDefault = false;
            foreach ($defaultTypes as &$t) {
                unset($t['desc'], $t['use_case']);
                if (!$setTaskTypeDefault) {
                    $t['is_default'] = 1;
                    $setTaskTypeDefault = true;
                } else {
                    $t['is_default'] = 0;
                }
                $t['creator_id'] = $userId;
                $t['workspace_id'] = $workspace->id;
                $t['created_time'] = time();
            }
            $member->workspace_id = $workspace->id;
            WorkspaceTaskType::insert($defaultTypes);
            $member->save();
            $this->commit();
            return $workspace;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getWorkspaceTaskTypes($uuid)
    {
        $workspace = $this->getByUuid($uuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }
        return WorkspaceTaskType::where('workspace_id', $workspace->id)->get();
    }

    public function getAllTaskTypes()
    {
        return TaskTypes::$types;
    }

    public function addTaskType($uuid, array $data, $userId)
    {
        $workspace = $this->getByUuid($uuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }
        $taskType = [];
        if (!empty($data['code'])) {
            $code = $data['code'];
            $taskType = WorkspaceTaskType::where('workspace_id', $workspace->id)->where('code', $code)->first();
            if ($taskType) {
                throw new BusinessException('workspace.task_type_exists');
            }
            foreach ($this->getAllTaskTypes() as $t) {
                if ($t['code'] == $code) {
                    $taskType = $t;
                    break;
                }
            }
            if (!$taskType) {
                throw new BusinessException('workspace.task_type_not_found');
            }
        } else {

        }
        $taskType['creator_id'] = $userId;
        $taskType['workspace_id'] = $workspace->id;
        $taskType['created_time'] = time();
        return WorkspaceTaskType::insertGetId($taskType);
    }

    public function getById($id, $fields = ['*'])
    {
        return WorkspaceModel::where('id', $id)->first($fields);
    }

    public function getByUuid($uuid, $fields = ['*'])
    {
        return WorkspaceModel::where('uuid', $uuid)->first($fields);
    }

    public function updateByUuid($uuid, array $data)
    {
        $updateData = [];
        if (isset($data['name'])) {
            if (empty(trim($data['name']))) {
                throw new BusinessException('Invalid name!');
            }
            $updateData['name'] = mb_substr($data['name'], 0, 128);
        }
        if ($updateData) {
            return WorkspaceModel::where('uuid', $uuid)->update($updateData);
        }
        return false;
    }

    public function getUserWorkspaces($userId, $fields = ['*'])
    {
        $members = WorkspaceMember::where('member_id', $userId)->where('deleted', 0)->get(['workspace_id']);
        $workspaceIds = [];
        foreach ($members as $member) {
            $workspaceIds[] = $member->workspace_id;
        }
        return WorkspaceModel::whereIn('id', $workspaceIds)->orderBy('id', 'DESC')->get($fields);
    }

    public function incrementMemberCount($workspaceId)
    {
        return WorkspaceModel::where('id', $workspaceId)->increment('member_count');
    }

    public function decrementMemberCount($workspaceId)
    {
        return WorkspaceModel::where('id', $workspaceId)->decrement('member_count');
    }

    public function getUserCreatedWorkspaces($userId, $fields = ['*'])
    {
        return WorkspaceModel::where('creator_id', $userId)
            ->orderBy('id', 'ASC')
            ->get($fields);
    }

    public function isUserBelongWorkspace($userId, $workspaceId)
    {
        return WorkspaceMember::where('member_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->where('deleted', 0)
            ->exists();
    }

    public function hasAdminPermission($userId, $workspaceId)
    {
        return WorkspaceMember::where('member_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->where('member_id', $userId)
            ->whereIn('role', [WorkspaceMember::ROLE_OWNER, WorkspaceMember::ROLE_ADMIN])
            ->where('deleted', 0)
            ->exists();
    }

    public function userHasManagePermission($userId, $workspaceId)
    {
        return WorkspaceMember::where('member_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->whereIn('role', ['owner', 'admin'])
            ->where('deleted', 0)
            ->exists();
    }

}
