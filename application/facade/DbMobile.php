<?php
namespace app\facade;

use think\Facade;

class DbMobile extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\index\DbMobile';
    }
}