<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\controller\workspace;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\controller\Base;
use support\Request;

class Workspace extends Base
{
    public function get($uuid)
    {
        $workspace = $this->getWorkspaceModule()->getByUuid($uuid);

        return $this->json($workspace);
    }

    public function add(Request $request)
    {

    }

    public function list()
    {
        $user = $this->getUser();
    }

    public function delete($uuid)
    {
        $user = $this->getUser();
        $this->getWorkspaceModule()->deleteByUuid($uuid, $user['id']);
        return $this->json(['success' => true]);
    }

    public function put(Request $request, $uuid)
    {
        $user = $this->getUser();
        $name = $request->post('name', '');
        $workspace = $this->getWorkspaceModule()->getByUuid($uuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }
        if (!$this->getWorkspaceModule()->hasAdminPermission($user['id'], $workspace->id)) {
            throw new AccessDeniedException();
        }
        $name = trim(mb_substr($name, 0, 64));
        if (!$name) {
            throw new BusinessException('workspace.name_empty');
        }
        $this->getWorkspaceModule()->updateByUuid($uuid, ['name' => $name]);
        $workspace = $this->getWorkspaceModule()->getByUuid($uuid);

        return $this->json($workspace);
    }
}
