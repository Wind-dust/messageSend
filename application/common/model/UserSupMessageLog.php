<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
use Config;

class UserSupMessageLog extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'yx_user_sup_message_log';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s', //更新时间
        'delete_time' => 'timestamp:Y-m-d H:i:s', //更新时间
        'update_time' => 'timestamp:Y-m-d H:i:s', //更新时间
    ];
    private $sendStatus = [1 => '待发送', 2 => '已发送', 3 => '成功', 4 => '失败',]; //1.上架 2.下架

    // 模型初始化
    protected static function init()
    {
        //TODO:初始化内容
    }

    public function setSendStatusAttr($value)
    {
        if (!in_array($value, $this->sendStatus)) {
            return $value;
        }
        $sendStatus = array_flip($this->sendStatus);
        return $sendStatus[$value];
    }
}
