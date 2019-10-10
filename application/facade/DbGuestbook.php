<?php
namespace app\facade;

use think\Facade;

class DbGuestbook extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\index\DbGuestbook';
    }
}