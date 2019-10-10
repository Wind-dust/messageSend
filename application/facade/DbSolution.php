<?php
namespace app\facade;

use think\Facade;

class DbSolution extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\index\DbSolution';
    }
}