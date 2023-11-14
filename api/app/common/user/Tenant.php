<?php
/**
 * This file is part of fadetask kanban project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\common\user;

class Tenant
{
    /**
     * 判断是否是免费用户.
     *
     * @param $user
     *
     * @return void
     */
    public static function isFreeUser($user) : bool
    {
        return true;
    }

    /**
     * 判断是否是团队版付费用户.
     *
     * @param $user
     *
     * @return void
     */
    public static function isTeamUser($user) : bool
    {
        return false;
    }
}
