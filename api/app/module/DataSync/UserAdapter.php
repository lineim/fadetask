<?php
namespace app\module\DataSync;

abstract class UserAdapter
{
    protected static $inst;
    protected $type = '';
    protected $userMap = [];
    protected $userBindMap = [];

    public static function inst()
    {
        if (!self::$inst) {
            self::$inst = new static;
        }
        return self::$inst;
    }

    public function convert(array $data)
    {
        $bind = [];
        $user = [];
        foreach ($data as $k => $v) {
            if (isset($this->userBindMap[$k])) {
                $bindKey = $this->userBindMap[$k];
                $bind[$bindKey] = $this->filterVal($bindKey, $v);
            }
            if (isset($this->userMap[$k])) {
                $userKey = $this->userMap[$k];
                $user[$userKey] = $this->filterVal($userKey, $v);
            }
        }
        $bind['type'] = $this->type;
        return ['bind' => $bind, 'user' => $user];
    }

    protected function filterVal($field, $v)
    {
        if (method_exists($this, $field . 'Filter')) {
            return $this->{$field . 'Filter'}($v);
        }
        return $v;
    }

}