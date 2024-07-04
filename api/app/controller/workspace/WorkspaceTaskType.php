<?php
namespace app\controller\workspace;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\controller\Base;
use support\Request;

class WorkspaceTaskType extends Base
{
    public function list($uuid)
    {
        $user = $this->getUser();
        $workspace = $this->getWorkspaceModule()->getByUuid($uuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }
        if (!$this->getWorkspaceModule()->isUserBelongWorkspace($user['id'], $workspace->id)) {
            throw new AccessDeniedException();
        }
        $allTaskTypes = $this->getWorkspaceModule()->getAllTaskTypes();
        $workspaceTaskTypes = $this->getWorkspaceModule()->getWorkspaceTaskTypes($uuid);
        $workspaceTaskTypeCodes = [];
        $creatorIds = [];
        foreach ($workspaceTaskTypes as $ty) {
            $workspaceTaskTypeCodes[] = $ty->code;
            $creatorIds[] = $ty->creator_id;
        }
        foreach ($allTaskTypes as $k => $v) {
            if (in_array($v['code'], $workspaceTaskTypeCodes)) {
                unset($allTaskTypes[$k]);
            }
        }

        $creators = $this->getUserModule()->getByUserIds($creatorIds, ['id', 'name', 'email']);
        $indexCreators = [];
        foreach ($creators as $c) {
            $indexCreators[$c->id] = $c;
        }
        foreach ($workspaceTaskTypes as &$t) {
            $t->creator = $indexCreators[$t->creator_id] ?? [];
        }

        return $this->json(['used_task_types' => $workspaceTaskTypes, 'unused_task_types' => array_values($allTaskTypes)]);
    }

    public function add(Request $request, $uuid)
    {
        $user = $this->getUser();
        $workspace = $this->getWorkspaceModule()->getByUuid($uuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }
        if (!$this->getWorkspaceModule()->userHasManagePermission($user['id'], $workspace->id)) {
            throw new AccessDeniedException();
        }
    }

    public function put(Request $request, $uuid)
    {

    }
}
