<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\common\toolkit;

class DateTime
{

    public static function sampleDate($timestamp)
    {
        $thisYearStartTime = strtotime(date('Y-01-01 00:00:00'));
        if ($timestamp >= $thisYearStartTime) {
            return date('m月d日H:i', $timestamp);
        }
        return date('Y年m月d日H:i', $timestamp);
    }

    public static function firstDayThisWeek()
    {
        $day = date('w');
        return date('Y-m-d', strtotime('-'.$day.' days'));
    }

    public static function lastDayThisWeek()
    {
        $day = date('w');
        return date('Y-m-d', strtotime('+'.(6-$day).' days'));
    }

    public static function firstDayNextWeek()
    {
        $day = date('w');
        $stepDays = 6-$day+1;

        return date('Y-m-d', strtotime(sprintf('+%d days', $stepDays)));
    }

    public static function lastDayNextWeek()
    {
        $day = date('w');
        $stepDays = 6-$day+7;
        return date('Y-m-d', strtotime(sprintf('+%d days', $stepDays)));
    }

}