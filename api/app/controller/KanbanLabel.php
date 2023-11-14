<?php
namespace app\controller;

use support\Request;

class KanbanLabel extends Base
{

    public function get($request, $kanbanId, $id)
    {
        return $this->json([$id]);
    }

    public function add($request, $kanbanId)
    {
        $user = $this->getUser($request);
        $color = $request->post('color', '');
        $name = $request->post('name', '');

        if (empty($color)) {
            return $this->json(false, 1, 'color empty!');
        }

        $label = $this->getKanbanLabelModule()->newLabel($kanbanId, $name, $color, $user['id']);

        return $this->json($label);
    }

    public function edit($request, $kanbanId, $id)
    {
        $user = $this->getUser($request);
        $color = $request->post('color', '');
        $name = $request->post('name', '');

        $label = $this->getKanbanLabelModule()->updateLabel($id, $name, $color, $user['id']);

        return $this->json($label);
    }

    public function delete($request, $kanbanId, $id)
    {
        $user = $this->getUser($request);

        $deleted = $this->getKanbanLabelModule()->deleteLabel($id, $user['id']);
        return $this->json($deleted);
    }

    public function sort(Request $request, $kanbanId)
    {
        $user = $this->getUser($request);
        $labelIdsStr = $request->post('label_ids');

        $labelIds = explode(',', $labelIdsStr);

        $sort = $this->getKanbanLabelModule()->sortLabels($kanbanId, $labelIds, $user['id']);

        return $this->json($sort);
    }

}
