<?php
namespace app\module\Workspace;

use app\common\toolkit\ModuleTrait;
use app\module\BaseModule;
use app\module\Workspace\models\Workspace as WorkspaceModel;
use app\module\Workspace\models\WorkspaceMember;
use app\module\Workspace\models\WorkspaceProject;
use app\module\Workspace\models\WorkspaceTaskType;
use Ramsey\Uuid\Uuid;

class Workspace extends BaseModule
{
    use ModuleTrait;

    private $allTaskType = [
        [
            'name' => '任务',
            'code' => 'task',
            'icon' => 'task',
            'color' => 'deep-purple-6'
        ],
        [
            'name' => '需求',
            'code' => 'demand',
            'icon' => 'work_outline',
            'color' => 'blue-6'
        ],
        [
            'name' => '里程碑',
            'code' => 'milestone',
            'icon' => 'adjust',
            'color' => 'indigo-6'
        ],
        [
            'name' => 'Issue',
            'code' => 'issue',
            'icon' => 'bug_report',
            'color' => 'red-6'
        ], 
        [
            'name' => '目标',
            'code' => 'target',
            'icon' => 'flag_circle',
            'color' => 'teal-6'
        ], 
        [
            'name' => '关键指标',
            'code' => 'objective',
            'icon' => 'star',
            'color' => 'cyan-6'
        ], 
        [
            'name' => '关键结果',
            'code' => 'key_result',
            'icon' => 'key',
            'color' => 'green-6'
        ], 
        [
            'name' => '账号',
            'code' => 'account',
            'icon' => 'account_balance',
            'color' => 'pink-6'
        ],
        [
            'name' => '资源',
            'code' => 'source',
            'icon' => 'source',
            'color' => 'purple-6'
        ]
    ];

    public function createWorkspace($name, $userId)
    {
        $defaultTypes = array_slice($this->allTaskType, 0, 3);
        
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

    public function getAllTaskTypes()
    {
        return $this->allTaskType;
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

}
