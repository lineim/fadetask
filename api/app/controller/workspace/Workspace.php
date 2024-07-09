<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\controller\workspace;

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

    public function put(Request $request, $uuid)
    {
        $name = $request->post('name', '');

        $this->getWorkspaceModule()->updateByUuid($uuid, ['name' => $name]);
        $workspace = $this->getWorkspaceModule()->getByUuid($uuid);

        return $this->json($workspace);
    }
}
