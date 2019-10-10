<?php

namespace app\common\model;

use think\Model;

class Menu extends Model {
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'yx_menu';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;//关闭update_time

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }
}