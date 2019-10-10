<?php
namespace app\facade;

use think\Facade;

class DbApplicationCase extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\index\DbApplicationCase';
    }
}