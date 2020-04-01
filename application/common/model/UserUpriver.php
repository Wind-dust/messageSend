<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class UserUpriver extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'yx_user_upriver';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $updateTime = false; //关闭update_time
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s', //注册时间
        'delete_time' => 'timestamp:Y-m-d H:i:s', //最后更新时间
    ];
    // 模型初始化
    protected static function init()
    {
        //TODO:初始化内容
    }
}
