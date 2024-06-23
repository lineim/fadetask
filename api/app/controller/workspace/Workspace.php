<?php
namespace app\controller\workspace;

use app\controller\Base;
use support\Request;

class Workspace extends Base
{
    public function get($uuid)
    {
        return $this->getWorkspaceModule()->getByUuid($uuid);
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
    }
}
