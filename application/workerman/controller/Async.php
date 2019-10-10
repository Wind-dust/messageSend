<?php

namespace app\workerman\controller;

use think\worker\Server;
use \Workerman\MySQL\Connection;
use Env;

class Async extends Server {
    protected $socket = 'Text://0.0.0.0:12200';
    protected $worker;
    protected $option = [];
    protected $context = [];
    protected $event = ['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerReload', 'onWebSocketConnect'];

    public function __construct() {
        parent::__construct();
        $this->worker->count = 4;
        $this->worker->name  = '异步日志添加处理';
    }

    function onConnect() {
        global $db;
        $db = new Connection(Env::get('database.hostname'), 3306, Env::get('database.username'), Env::get('database.password'), Env::get('database.database2'));
    }

    function onMessage($connection, $task_data) {
//        sleep(5);
        // 假设发来的是json数据
//        $connection->send(json_encode($task_data));die;
        $task_data = json_decode($task_data, true);
//        $redis     = new \Redis();
//        $redis->connect('127.0.0.1', '6379');
//        $redis->set('worker:setid', '123');
//        print_r($task_data);die;
        // 根据task_data处理相应的任务逻辑.... 得到结果，这里省略....
        // 通过全局变量获得db实例
        global $db;
        // 执行SQL
//        $sql="insert into pz_log_api (api_name,param,stype,admin_id,code,create_time) values ('/goods/getonegoods','[\"32\",[\"1\",\"2\",\"3\",\"4\"]]','2','1','200','1558091397')";
        $all_tables = $db->query($task_data['sql']);
        // $end        = microtime(true);
        // $f          = $end - $start;
        $connection->send(json_encode($all_tables));
    }
}