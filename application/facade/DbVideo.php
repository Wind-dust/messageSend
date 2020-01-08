<?php

namespace app\facade;

use think\Facade;

class DbVideo extends Facade
{
    protected static function getFacadeClass()
    {
        return 'app\common\db\other\DbVideo';
    }
}
