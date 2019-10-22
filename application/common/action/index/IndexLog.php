<?php

namespace app\common\action\index;
class IndexLog extends CommonIndex {
    public function apiRequestLog($apiName, $param, $code, $conId) {
        if ($code == '200') {
            return;
        }
        $uid = $this->getUidByConId($conId);
//        $user    = new LogApi();
//        $user->save([
//            'api_name' => $apiName,
//            'param'    => json_encode($param),
//            'stype'    => 1,
//            'code'     => $code,
//            'admin_id' => $uid,
//        ]);

        //待用异步workerman接口写日志,需开启进程
        sendRequest('http://127.0.0.1:12100', 'post', [
            'api_name' => $apiName,
            'param'    => json_encode($param),
            'stype'    => 1,
            'code'     => $code,
            'admin_id' => $uid,
        ]);
    }
}