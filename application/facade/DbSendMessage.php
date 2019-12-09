<?php
namespace app\facade;

use think\Facade;

class DbSendMessage extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\index\DbSendMessage';
    }
}