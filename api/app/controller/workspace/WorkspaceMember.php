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

class WorkspaceMember extends Base
{
    public function get($uuid)
    {
        return $this->getWorkspaceModule()->getByUuid($uuid);
    }

    public function add(Request $request)
    {

    }

    public function list(Request $request, $uuid)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('query', 20);        
        $members = $this->getWorkspaceMemberModule()->getWorkspaceMembers($uuid, $page, $pageSize, ['*']);

        return $this->json($members);
    }

    public function put(Request $request, $uuid)
    {

    }
}
