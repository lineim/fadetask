<?php
/**
 * This file is part of fadetask kanban project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\controller;

use app\common\oss\OssFactory;
use support\Request;

class Oss extends Base
{
    public function stsToken(Request $request)
    {
        $client = OssFactory::aliyun();
        $stsToken = $client->getWriteKeys(3600);

        return $this->json($stsToken);
    }
}
