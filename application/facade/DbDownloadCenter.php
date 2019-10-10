<?php
namespace app\facade;

use think\Facade;

class DbDownloadCenter extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\index\DbDownloadCenter';
    }
}