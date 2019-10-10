<?php

namespace app\index;

use think\App;
use think\Controller;
use Env;
use Config;

class MyController extends Controller {

    public function __construct(App $app = null) {
        parent::__construct($app);
        $this->headers();
        $checkRes = $this->checkApi();
        if ($checkRes['code'] !== 200) {
            exit(json_encode($checkRes));
        }
    }

    private function headers() {
        if (Config::get('deploy') == 'development') {
            header('Access-Control-Allow-Origin:*');
            header("Access-Control-Allow-Methods:GET,POST");
            header('Access-Control-Allow-Headers:content-type,token,id');
            header("Access-Control-Request-Headers: Origin, X-Requested-With, content-Type, Accept, Authorization");
        }
        if (Config::get('deploy') == 'production') {//生产环境
            header('Access-Control-Allow-Origin:*');
            header("Access-Control-Allow-Methods:GET,POST");
            header('Access-Control-Allow-Headers:content-type,token,id');
            header("Access-Control-Request-Headers: Origin, X-Requested-With, content-Type, Accept, Authorization");
        }
    }

    /**
     * api验证
     * @return array
     * @author zyr
     */
    private function checkApi() {
        $params = $this->request->param();
        if (Env::get('debug.checkTimestamp')) {
            if (!isset($params['timestamp']) || !$this->checkTimestamp($params['timestamp'])) {
                return ['code' => 2000, 'msg' => '请求超时'];
            }
        }
        if (Env::get('debug.checkSign')) {//签名验证
            if (!isset($params['sign']) || !$this->checkSign($params['sign'], $params)) {
                return ['code' => 2001, 'msg' => '签名错误'];
            }
        }
        return ['code' => 200];
    }

    /**
     * 接口时间戳验证
     * @param int $timestamp
     * @return bool
     * @author zyr
     */
    private function checkTimestamp($timestamp = 0) {
        $nowTime  = time();
        $timeDiff = bcsub($nowTime, $timestamp, 0);
        if ($timeDiff > Config::get('conf.timeout') || $timeDiff < 0) {
            return false;
        }
        return true;
    }

    /**
     * 接口签名验证
     * @param $sign
     * @param $params
     * @return bool
     * @author zyr
     */
    private function checkSign($sign, $params) {
        unset($params['timestamp']);
        unset($params['sign']);
        $requestString = '';
        foreach ($params as $k => $v) {
            if (!is_array($v)) {
                $requestString .= $k . $v;
            }
        }
        $paramHash = hash_hmac('sha1', $requestString, 'pzlife');
        if ($paramHash === $sign) {
            return true;
        }
        return false;
    }

    /**
     * 验证con_id登录
     */
    protected function isLogin() {
        $this->headers();
        $conId = trim($this->request->param('con_id'));
        if (!empty($conId) && strlen($conId) == 32) {
            $res = $this->app->user->isLogin($conId);//判断是否登录
            if ($res['code'] == '200') {
                return;
            }
            exit(json_encode($res));
        }
        exit(json_encode(['code' => '5000']));
    }

    /**
     * 验证是否为boss
     */
    protected function isBoss() {
        $conId = trim($this->request->param('con_id'));
        $res   = $this->app->user->getUser($conId);
        if ($res['code'] == '200') {
            if ($res['data']['user_identity'] == 4) {
                return;
            } else {
                exit(json_encode(['code' => '6000']));
            }
        }
        exit(json_encode(['code' => '5000']));
    }

    /**
     * 接口日志
     * @param $apiName
     * @param $param
     * @param $code
     * @param $conId
     * @return mixed
     * @author zyr
     */
    protected function apiLog($apiName, $param, $code, $conId) {
        $result = $this->app->indexLog->apiRequestLog($apiName, $param, $code, $conId);
        return $result;
    }
}