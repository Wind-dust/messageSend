<?php
namespace app\facade;

use think\Facade;

class DbBanner extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\index\DbBanner';
    }
}