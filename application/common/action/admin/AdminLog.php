<?php

namespace app\common\action\admin;

//use app\common\model\LogApi;

class AdminLog extends CommonIndex {
    public function apiRequestLog($apiName, $param, $code, $cmsConId) {
        $adminId = $this->getUidByConId($cmsConId);
//        $user    = new LogApi();
//        $user->save([
//            'api_name' => $apiName,
//            'param'    => json_encode($param),
//            'stype'    => 2,
//            'code'     => $code,
//            'admin_id' => $adminId,
//        ]);

        //待用异步workerman接口写日志,需开启进程
        sendRequest('http://127.0.0.1:12100','post',[
            'api_name'=>$apiName,
            'param'=>json_encode($param),
            'stype'=>2,
            'code'=>$code,
            'admin_id'=>$adminId,
        ]);
    }
}