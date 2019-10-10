<?php
namespace app\facade;

use think\Facade;

class DbProduct extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\index\DbProduct';
    }
}