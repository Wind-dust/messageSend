<?php

namespace app\console\com;

use app\console\Pzlife;
use Env;

class Curl extends Pzlife {
    public function api($method, $url, $params) {
        $paramsArr = explode('/', $params);
        if (isset($paramsArr['sign'])) {
            unset($paramsArr['sign']);
        }
        if (isset($paramsArr['timestamp'])) {
            unset($paramsArr['timestamp']);
        }
        if ($method == 'get') {
            $urlParam = '';
            foreach ($paramsArr as $k => $v) {
                if ($k % 2 == 1) {
                    $urlParam .= $v;
                }
            }
            $requestUrl = $url . '/' . $urlParam;
            if (Env::get('debug.checkSign')) {
                $requestString = implode('', $paramsArr);
                $paramHash     = hash_hmac('sha1', $requestString, 'pzlife');
                $requestUrl    .= '/' . $paramHash;
            }
            if (Env::get('debug.checkTimestamp')) {
                $requestUrl .= '/' . time();
            }
            $this->get($requestUrl);
        } else if ($method == 'post') {
            $arr1 = [];
            $arr2 = [];
            foreach ($paramsArr as $k => $v) {
                if ($k % 2 == 1) {
                    array_push($arr2, $v);
                } else {
                    array_push($arr1, $v);
                }
            }
            $paramRes = array_combine($arr1, $arr2);
            if (Env::get('debug.checkSign')) {
                $requestString    = implode('', $paramsArr);
                $paramHash        = hash_hmac('sha1', $requestString, 'pzlife');
                $paramRes['sign'] = $paramHash;
            }
            if (Env::get('debug.checkTimestamp')) {
                $paramRes['timestamp'] = time();
            }
            $this->post($url, $paramRes);
        }
    }
}