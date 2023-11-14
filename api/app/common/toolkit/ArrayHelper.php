<?php
namespace app\common\toolkit;

class ArrayHelper
{

    public static function parts(array $array, array $keys) : array
    {
        $parts = [];
        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $parts[$key] = $array[$key];
            }
        }
        return $parts;
    }

    public static function hasKeys(array $array, array $keys) : bool
    {
        $arrayKeys = array_keys($array);

        $missedKeys = array_diff($keys, $arrayKeys);

        return !$missedKeys;
    }

    public static function index(array $items, string $key) : array
    {
        $indexArray = [];
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item[$key])) {
                continue;
            }
            $keyVal = $item[$key];
            $indexArray[$keyVal] = $item;
        }
        return $indexArray;
    }

    public static function group(array $items, string $key) : array
    {
        $groupArray = [];
        foreach ($items as $item) {
            $keyVal = $item[$key];
            $groupArray[$keyVal][] = $item;
        }
        return $groupArray;
    }

}