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

class WorkspaceSpace extends Base
{
    public function get($uuid)
    {
        return $this->getWorkspaceModule()->getByUuid($uuid);
    }

    public function add(Request $request)
    {

    }

    public function list($uuid)
    {

        $user = $this->getUser();
    }

    public function put(Request $request, $uuid)
    {

    }
}
