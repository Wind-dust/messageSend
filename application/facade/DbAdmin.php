<?php
namespace app\facade;

use think\Facade;

class DbAdmin extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\user\DbAdmin';
    }
}