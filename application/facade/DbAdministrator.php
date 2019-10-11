<?php
namespace app\facade;

use think\Facade;

class DbAdministrator extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\index\DbAdministrator';
    }
}