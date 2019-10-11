<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Areas extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'yx_areas';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
        'update_time' => 'timestamp:Y-m-d H:i:s',//更新时间
    ];
    private $level = [1 => '省', 2 => '市', 3 => '区'];

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

//    public function getLevelAttr($value) {
//        return $this->level[$value];
//    }

    public function setLevelAttr($value) {
        if (!in_array($value, $this->level)) {
            return $value;
        }
        $level = array_flip($this->level);
        return $level[$value];
    }
}