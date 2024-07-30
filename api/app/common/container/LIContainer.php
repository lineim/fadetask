<?php
namespace app\common\container;

use Webman\Container;

class LIContainer extends Container
{

    public function set($name, $inst)
    {
        $this->instances[$name] = $inst;
    }

}
