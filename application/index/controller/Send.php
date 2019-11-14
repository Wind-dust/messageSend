<?php
namespace app\index\controller;
use app\index\MyController;

class Send extends MyController {
    protected $beforeActionList = [
        //        'isLogin',//所有方法的前置操作
        // 'isLogin' => ['except' => 'cmppSendTest,smsBatch,getBalanceSmsBatch,getReceiveSmsBatch'], //除去getFirstCate其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 发送短信测试（对外客户）
     * @apiDescription   cmppSendTest
     * @apiGroup         index_send
     * @apiName          cmppSendTest
     * @apiParam (入参) {Number} phone 手机号
     * @apiParam (入参) {Number} code 验证码
     * @apiParam (入参) {String} vercode 验证码
     * @apiSuccess (返回) {String} code 200:成功 / 3000:手机号格式错误 / 3002:passwd密码强度不够 / 3003:邮箱格式错误 / 3004:验证码错误 / 3005:该手机号已注册用户 / 3006:用户类型错误 / 3007:nick_name不能为空
     * @apiSampleRequest /index/send/cmppSendTest
     * @author rzc
     */
    public function cmppSendTest() {
        $apiName   = classBasename($this) . '/' . __function__;
        $phone = trim($this->request->post('phone'));//手机号
        // if (!checkMobile($phone)) {
        //     return ['code' => 3001];
        // }
        // echo phpinfo();die;
        $code   = trim($this->request->post('code'));//验证码
        $result = $this->app->send->cmppSendTest($phone, $code);
        // $this->apiLog($apiName, [$Banner_id, $source], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 短信发送提交（对外客户）
     * @apiDescription   SmsBatch
     * @apiGroup         index_send
     * @apiName          SmsBatch
     * @apiParam (入参) {String} username 登录名
     * @apiParam (入参) {String} password 登陆密码
     * @apiParam (入参) {String} content 短信内容
     * @apiParam (入参) {String} mobile 接收手机号码
     * @apiParam (入参) {String} dstime 发送时间
     * @apiSuccess (返回) {String} code 200:成功 / 3000:手机号格式错误 / 3002:passwd密码强度不够 / 3003:邮箱格式错误 / 3004:验证码错误 / 3005:该手机号已注册用户 / 3006:用户类型错误 / 3007:nick_name不能为空
     * @apiSampleRequest /index/send/smsBatch
     * @author rzc
     */
    public function smsBatch() {
        $Username = trim($this->request->post('username'));//登录名
        $Password = trim($this->request->post('password'));//登陆密码
        $Content = trim($this->request->post('content'));//短信内容
        $Mobile = trim($this->request->post('mobile'));//接收手机号码
        $Dstime = trim($this->request->post('dstime'));//手机号
        $ip = trim($this->request->ip());
        $Mobiles = explode(',',$Mobile);
        if (empty($Username)) {
            return -1;
        }
        if (empty($Password)) {
            return -1;
        }
        // echo phpinfo();die;
        if (empty($Mobiles)) {
            return 2;
        }
        if (count($Mobiles) > 100){
            return 4;
        }
        if (strtotime($Dstime)== false && !empty($Dstime)) {
            return 7;
        }
        if (strtotime($Dstime) < time() && !empty($Dstime)) {
            return 8;
        }
        if (empty($Content) || strlen($Content) > 500) {
            return 3;
        }
        // echo mb_strpos($Content,'】') - mb_strpos($Content,'【');die;
        if ( mb_strpos($Content,'】') - mb_strpos($Content,'【') < 2 || mb_strpos($Content,'】') - mb_strpos($Content,'【') > 8) {
            return 6;
        }
        $result = $this->app->send->smsBatch($Username,$Password,$Content,$Mobiles,$Dstime,$ip);
        //特殊处理输出值
        echo $result;
        die;
        // return $result;
    }
 
    /**
     * @api              {post} / 余额查询（对外客户）
     * @apiDescription   getBalanceSmsBatch
     * @apiGroup         index_send
     * @apiName          getBalanceSmsBatch
     * @apiParam (入参) {String} username 登录名
     * @apiParam (入参) {String} password 登陆密码
     * @apiSuccess (返回) {String} code 200:成功 / 3000:手机号格式错误 / 3002:passwd密码强度不够 / 3003:邮箱格式错误 / 3004:验证码错误 / 3005:该手机号已注册用户 / 3006:用户类型错误 / 3007:nick_name不能为空
     * @apiSampleRequest /index/send/getBalanceSmsBatch
     * @author rzc
     */
    public function getBalanceSmsBatch(){
        $Username = trim($this->request->post('username'));//登录名
        $Password = trim($this->request->post('password'));//登陆密码
        if (empty($Username)) {
            return -1;
        }
        if (empty($Password)) {
            return -1;
        }
        $result = $this->app->send->getBalanceSmsBatch($Username,$Password);
        return $result;
    }

    /**
     * @api              {post} / 状态报告提取（对外客户）
     * @apiDescription   getReceiveSmsBatch
     * @apiGroup         index_send
     * @apiName          getReceiveSmsBatch
     * @apiParam (入参) {String} username 登录名
     * @apiParam (入参) {String} password 登陆密码
     * @apiSuccess (返回) {String} code 200:成功 / 3000:手机号格式错误 / 3002:passwd密码强度不够 / 3003:邮箱格式错误 / 3004:验证码错误 / 3005:该手机号已注册用户 / 3006:用户类型错误 / 3007:nick_name不能为空
     * @apiSampleRequest /index/send/getReceiveSmsBatch
     * @author rzc
     */
    public function getReceiveSmsBatch(){
        $Username = trim($this->request->post('username'));//登录名
        $Password = trim($this->request->post('password'));//登陆密码
        if (empty($Username)) {
            return -1;
        }
        if (empty($Password)) {
            return -1;
        }
        $result = $this->app->send->getReceiveSmsBatch($Username,$Password);
        return $result;
    }

    /**
     * @api              {post} / 回复内容接口（对外客户）
     * @apiDescription   getReceiveSmsBatch
     * @apiGroup         index_send
     * @apiName          getReceiveSmsBatch
     * @apiParam (入参) {String} username 登录名
     * @apiParam (入参) {String} password 登陆密码
     * @apiSuccess (返回) {String} code 200:成功 / 3000:手机号格式错误 / 3002:passwd密码强度不够 / 3003:邮箱格式错误 / 3004:验证码错误 / 3005:该手机号已注册用户 / 3006:用户类型错误 / 3007:nick_name不能为空
     * @apiSampleRequest /index/send/getReceiveSmsBatch
     * @author rzc
     */
    public function getReplaySmsBatch(){
        $Username = trim($this->request->post('username'));//登录名
        $Password = trim($this->request->post('password'));//登陆密码
        $result = $this->app->send->getReplaySmsBatch($Username,$Password);
        return $result;
    }

    /**
     * @api              {post} / 短信任务接收接口（营销业务）（对外客户）
     * @apiDescription   getSmsTask
     * @apiGroup         index_send
     * @apiName          getSmsTask
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} content 短信内容
     * @apiParam (入参) {String} taskname 任务名称
     * @apiParam (入参) {String} mobile 接收手机号码
     * @apiParam (入参) {String} dstime 发送时间
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:手机号格式错误 / 3002:单批次手机号码不能超过1000个 / 3003:dstime发送时间格式错误 / 3004:预约发送时间小于当前时间 / 3005:短信内容为空或者短信内容超出500字符 / 3006:签名长度为2~8个字 / 3007:task_name 短信标题不能为空
     * @apiSampleRequest /index/send/getSmsTask
     * @author rzc
     */
    public function getSmsMarketingTask() {
        $appid = trim($this->request->post('appid'));//登录名
        $appkey = trim($this->request->post('appkey'));//登陆密码
        $Content = trim($this->request->post('content'));//短信内容
        $task_name = trim($this->request->post('taskname'));//任务名称
        $Mobile = trim($this->request->post('mobile'));//接收手机号码
        $Dstime = trim($this->request->post('dstime'));//手机号
        $ip = trim($this->request->ip());
        $Mobiles = explode(',',$Mobile);
        
        // echo phpinfo();die;
        if (empty($Mobiles)) {
            return ['code' => '3001'];
        }
        if (count($Mobiles) > 1000){
            return ['code' => '3002'];
        }
        if (strtotime($Dstime)== false && !empty($Dstime)) {
            return ['code' => '3003'];
        }
        if (strtotime($Dstime) < time() && !empty($Dstime)) {
            return ['code' => '3004'];
        }
        if (empty($Content) || strlen($Content) > 500) {
            return ['code' => '3005'];
        }
        // echo mb_strpos($Content,'】') - mb_strpos($Content,'【');die;
        if ( mb_strpos($Content,'】') - mb_strpos($Content,'【') < 2 || mb_strpos($Content,'】') - mb_strpos($Content,'【') > 8) {
            return ['code' => '3006'];
        }
        if (empty($task_name)) {
            return ['code' => '3007'];
        }
        $result = $this->app->send->getSmsMarketingTask($appid,$appkey,$Content,$Mobiles,$Dstime,$ip,$task_name);
        return $result;
    }

    /**
     * @api              {post} / 短信任务接收接口（行业）（对外客户）
     * @apiDescription   getSmsBuiness
     * @apiGroup         index_send
     * @apiName          getSmsBuiness
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} content 短信内容
     * @apiParam (入参) {String} mobile 接收手机号码
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误 / 3001:手机号格式错误 / 3002:短信内容为空或者短信内容超出500字符 / 3003:签名长度为2~8个字 / 3004:该账户已被停用 / 3005:该账户没有此项服务 / 3006:短信余额不足，请先充值 / 3009 :系统错误
     * @apiSampleRequest /index/send/getSmsBuiness
     * @author rzc
     */
    public function getSmsBuiness(){
        $appid = trim($this->request->post('appid'));//登录名
        $appkey = trim($this->request->post('appkey'));//登陆密码
        $Content = trim($this->request->post('content'));//短信内容
        $Mobile = trim($this->request->post('mobile'));//接收手机号码
        $ip = trim($this->request->ip());
        // $Mobiles = explode(',',$Mobile);
        
        // echo phpinfo();die;
        if (empty($Mobile) || checkMobile($Mobile) === false) {
            return ['code'=>'3001'];
        }
        if (empty($Content) || strlen($Content) > 500) {
            return ['code'=>'3002'];
        }
        // echo mb_strpos($Content,'】') - mb_strpos($Content,'【');die;
        if ( mb_strpos($Content,'】') - mb_strpos($Content,'【') < 2 || mb_strpos($Content,'】') - mb_strpos($Content,'【') > 8) {
            return ['code'=>'3003'];
        }
        $result = $this->app->send->getSmsBuiness($appid,$appkey,$Content,$Mobile,$ip);
        return $result;
    }
}
