<?php

namespace app\facade;

use think\Facade;

class DbProvinces extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\other\DbProvinces';
    }
}