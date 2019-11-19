<?php

namespace upload;

use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Config;

class Fileupload {
    /**
     * @desc 用于签名的公钥
     */
    private $accessKey;

    /**
     * @desc 用于签名的私钥
     */
    private $secretKey;

    /**
     * @desc 存储空间
     */
    private $bucket;

    /**
     * @desc 七牛用户自定义访问域名
     */
    private $domain;

    private $auth;
    private $token;

    function __construct($config = array()) {
        $this->accessKey = Config::get('qiniu.accessKey');          //用于签名的公钥
        $this->secretKey = Config::get('qiniu.secretKey');     //用于签名的私钥
        $this->bucket    = Config::get('qiniu.excelbucket');          //存储空间
        $this->domain    = Config::get("qiniu.exceldomain");     //七牛用户自定义访问域名
        if (!$this->accessKey) {
            throw new \Exception("需要七牛accessKey参数");
        }
        if (!$this->secretKey) {
            throw new \Exception("需要七牛secretKey参数");
        }
        if (!$this->bucket) {
            throw new \Exception("需要七牛bucket参数");
        }
        $auth = new Auth($this->accessKey, $this->secretKey);//构建鉴权对象
        if (!$auth) {
            throw new \Exception("验证失败");
        }
//        $this->cdnManager = new CdnManager($auth);

        $token = $auth->uploadToken($this->bucket);
        if (!$token) {
            throw new \Exception("七牛Token获取失败");
        }
        $this->auth  = $auth;
        $this->token = $token;
    }

    public function getNewName($filename) {
        $suffix = $this->getSuffix($filename);
        $name   = uniqid(md5(time() . $filename));
        return $name . $suffix;
    }

    public function getSuffix($filename) {
        $index = strripos($filename, '.', 0);
        if ($index === false) {
            return '';
        }
        return substr($filename, $index);//文件后缀名
    }

    /**
     * @desc 远程文件上传
     * @author zyr
     * @param $fileContent  图片内容
     * @param $fileName 文件名
     * @throws
     * @return boolean
     */
    public function uploadFile($fileContent, $fileName) {
//        $fileSuffix = substr($fileOldName, strripos($fileOldName, '.', 0));//文件后缀名
        $uploadMgr = new UploadManager();// 初始化 UploadManager 对象并进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($this->token, $fileName, $fileContent);// 调用 UploadManager 的 putFile 方法进行文件的上传
        if ($err !== null) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @desc 图片删除
     * @author zyr
     * @param $fileName 图片路径名称
     * @return bool
     */
    public function deleteFile($fileName) {
        $config        = new \Qiniu\Config();//加载配置文件  大家看sdk就明白了
        $bucketManager = new BucketManager($this->auth, $config);//实例化资源管理类
        $result        = $bucketManager->delete($this->bucket, $fileName);
        if ($result == null) {
            return true;
        } else {
            return $result->message();
        }
    }
}