<?php
namespace app\facade;

use think\Facade;

class DbAboutus extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\index\DbAboutus';
    }
}