<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\controller;

use app\common\exception\AccessDeniedException;
use support\Request;

class ProjectOverview extends Base
{

    public function get(Request $request, $uuid)
    {
        $user = $this->getUser($request);
        if (!$this->getProjectModule()->isMember($uuid, $user['id'])) {
            throw new AccessDeniedException();
        }

        $overview = $this->getProjectStatModule()->overview($uuid);

        return $this->json($overview);
    }

}