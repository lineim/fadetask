<?php
namespace app\controller\workspace;

use app\common\exception\AccessDeniedException;
use app\controller\Base;
use support\Request;

class SpaceList extends Base

{
    public function list(Request $request, $uuid, $spaceUuid)
    {
    }

    public function add(Request $request, $uuid, $spaceUuid)
    {
        $user = $this->getUser();
        $name = $request->post('name');
        $desc = $request->post('desc');
        $color = $request->post('color');

        return $this->getKanbanModule()->create($spaceUuid, $name, $desc, $color, $user['id']);
    }

    public function get(Request $request, $uuid, $spaceUuid, $listUuid)
    {
        $user = $this->getUser();
        if (!$this->getProjectModule()->hasPermission($spaceUuid, $user['id'])) {
            throw new AccessDeniedException();
        }
        $list = $this->getKanbanModule()->getByUuid($listUuid, ['id', 'name', 'uuid']);
        return $this->json($list);
    }

    public function update(Request $request, $uuid, $spaceUuid, $listUuid)
    {
    }

    public function delete(Request $request, $uuid, $spaceUuid, $listUuid)
    {
    }
   
}