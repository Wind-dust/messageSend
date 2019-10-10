<?php

namespace app\workerman\controller;

use think\worker\Server;
use \Workerman\MySQL\Connection;

class Worksocket extends Server {
    protected $socket = 'websocket://0.0.0.0:12101';
    protected $worker;
    protected $option = [];
    protected $context = [];
    protected $event = ['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerReload', 'onWebSocketConnect'];

    public function __construct() {
        parent::__construct();
        $this->worker->count = 2;
        $this->worker->name  = 'thinkphp1';
    }

    function onWorkerStart() {
    }

    function onMessage($connection, $data) {

        // 与远程task服务建立异步连接，ip为远程task服务的ip，如果是本机就是127.0.0.1，如果是集群就是lvs的ip
        $task_connection = new AsyncTcpConnection('Text://127.0.0.1:12200');
        // 任务及参数数据
//        $task_data = array(
//            'function' => 'send_mail',
//            'args'     => array('from' => 'xxx', 'to' => 'xxx', 'contents' => 'xxx'),
//        );
        // 发送数据
//        $task_connection->send(json_encode($task_data));
        // 异步获得结果
        $task_connection->onMessage = function ($task_connection, $task_result) use ($connection) {
            // 结果
            var_dump($task_result);
            // 获得结果后记得关闭异步连接
            $task_connection->close();
            // 通知对应的websocket客户端任务完成
            $connection->send('task end');
        };
        $connection->send(json_encode($data));
        // 执行异步连接
//        $task_connection->connect();

    }
}