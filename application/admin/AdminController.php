<?php

namespace app\admin;

use cache\Phpredis;
use think\App;
use think\Controller;
use Env;
use Config;

class AdminController extends Controller {

    public function __construct(App $app = null) {
        parent::__construct($app);
        $this->headers();
        $checkRes = $this->checkApi();
        if ($checkRes['code'] !== 200) {
            exit(json_encode($checkRes));
        }
    }

    private function headers() {
        if (Config::get('app.deploy') == 'development') {
            header('Access-Control-Allow-Origin:*');
            header("Access-Control-Allow-Methods:GET,POST");
            header('Access-Control-Allow-Headers:content-type,token,id');
            header("Access-Control-Request-Headers: Origin, X-Requested-With, content-Type, Accept, Authorization");
        }
        if (Config::get('deploy') == 'production') {//生产环境
            $allow_origin = array(
                'https://sj.cms.pzlive.vip',
                'https://cms.pzlive.vip',
            );
            $origin       = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
            if (in_array($origin, $allow_origin)) {
                header('Access-Control-Allow-Origin:' . $origin);
            }
            header("Access-Control-Allow-Methods:GET,POST");
            header('Access-Control-Allow-Headers:content-type,token,id');
            header("Access-Control-Request-Headers: Origin, X-Requested-With, content-Type, Accept, Authorization");
        }
    }

    /**
     * api验证
     * @return array
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
        $cmsConId = trim($this->request->param('cms_con_id'));
        if (!empty($cmsConId) && strlen($cmsConId) == 32) {
            $res = $this->app->admin->isLogin($cmsConId);//判断是否登录
            if ($res['code'] == '200') {
                return;
            }
            exit(json_encode($res));
        }
        exit(json_encode(['code' => '5000']));
    }

    /**
     * 接口日志
     * @param $apiName
     * @param $param
     * @param $code
     * @param $cmsConId
     * @return mixed
     * @author rzc
     */
    protected function apiLog($apiName, $param, $code, $cmsConId) {
        $result = $this->app->adminLog->apiRequestLog($apiName, $param, $code, $cmsConId);
        return $result;
    }

    /**
     * 权限验证
     * @param $cmsConId
     * @param $apiName
     * @return mixed
     * @author rzc
     */
    protected function checkPermissions($cmsConId, $apiName) {
        return $this->app->admin->checkPermissions($cmsConId, $apiName);
    }
}