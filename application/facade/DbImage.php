<?php

namespace app\facade;

use think\Facade;

class DbImage extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\other\DbImage';
    }
}