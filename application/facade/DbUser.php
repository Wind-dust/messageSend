<?php
namespace app\facade;

use think\Facade;

class DbUser extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\user\DbUser';
    }
}