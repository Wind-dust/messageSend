<?php

namespace app\workerman\controller;

use think\worker\Server;
use \Workerman\Connection\AsyncTcpConnection;

class LogApi extends Server {
    protected $socket = 'http://0.0.0.0:12100';
    protected $worker;
    protected $option = [];
    protected $context = [];
    protected $event = ['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerReload', 'onWebSocketConnect'];

    public function __construct() {
        parent::__construct();
        $this->worker->count = 4;
        $this->worker->name  = '接口日志';
    }

    function onWorkerStart() {
    }

    function onMessage($connection, $data) {
        $param           = $data['post'];
        $task_connection = new AsyncTcpConnection('Text://127.0.0.1:12200');
        $task_connection->connect();
        $task_data = array(
            'sql' => sprintf("insert into pz_log_api (api_name,param,stype,admin_id,code,create_time) values ('%s','%s','%d','%d','%s','%d')", $param['api_name'], $param['param'], $param['stype'], $param['admin_id'], $param['code'], time()),
        );
        $task_connection->send(json_encode($task_data));
        $task_connection->onMessage = function ($task_connection, $task_result) use ($connection) {
            // 获得结果后记得关闭异步连接
            $task_connection->close();
            // 通知对应的websocket客户端任务完成
//            print_r($task_result);
            $connection->send('task end');
        };
        $connection->send('end');


//        $host    = $data['post']['url'];
//        $loop    = Worker::getEventLoop();
//        $client  = new \React\HttpClient\Client($loop);
//        $request = $client->request('GET', trim($host));
//        $request->on('error', function (Exception $e) use ($connection) {
//            $connection->send($e);
//        });
//        $request->on('response', function ($response) use ($connection) {
//            $response->on('data', function ($data) use ($connection) {
////                $redis = new \Redis();
////                $redis->connect('127.0.0.1', '6379');
////                $redis->set('worker:setid', $data);
//            });
//        });
//        $connection->send('gun');
//        $request->end();
    }
}