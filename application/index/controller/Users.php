<?php
namespace app\index\controller;
use app\index\MyController;

class Users extends MyController {
    protected $beforeActionList = [
        //        'isLogin',//所有方法的前置操作
                'isLogin' => ['except' => 'login,quickLogin,userRegistered,resetPassword,sendVercode,wxaccredit,wxregister'], //除去getFirstCate其他方法都进行second前置操作
                //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
            ];

    /**
     * @api              {post} / 用户注册
     * @apiDescription   userRegistered
     * @apiGroup         index_user
     * @apiName          userRegistered
     * @apiParam (入参) {String} nick_name 用户姓名
     * @apiParam (入参) {Number} user_type 用户类型1.个人账户2.企业账户
     * @apiParam (入参) {String} passwd 密码
     * @apiParam (入参) {String} mobile 手机号
     * @apiParam (入参) {String} email 邮箱
     * @apiParam (入参) {String} vercode 验证码
     * @apiSuccess (返回) {String} code 200:成功 / 3000:手机号格式错误 / 3002:passwd密码强度不够 / 3003:邮箱格式错误 / 3004:验证码错误 / 3005:该手机号已注册用户 / 3006:用户类型错误 / 3007:nick_name不能为空
     * @apiSampleRequest /index/user/userRegistered
     * @author rzc
     */
    public function userRegistered() {
        $apiName   = classBasename($this) . '/' . __function__;
        $nick_name = trim($this->request->post('nick_name'));
        $user_type = trim($this->request->post('user_type'));
        $passwd    = trim($this->request->post('passwd'));
        $mobile    = trim($this->request->post('mobile'));
        $email     = trim($this->request->post('email'));
        $vercode   = trim($this->request->post('vercode'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];
        }
        if (checkPassword($passwd) === false) {
            return ['code' => '3002'];
        }
        if (checkEmail($email) === false) {
            return ['code' => '3003'];
        }
        if (!in_array($user_type,[ 1, 2])) {
            return ['code' => '3006'];
        }
        if (empty($nick_name)) {
            return ['code' => '3007'];
        }
        $result = $this->app->users->userRegistered($nick_name, $user_type, $passwd, $mobile, $email, $vercode);
        // $this->apiLog($apiName, [$Banner_id, $source], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 重置密码
     * @apiDescription   resetPassword
     * @apiGroup         index_user
     * @apiName          resetPassword
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {Number} vercode 验证码
     * @apiParam (入参) {Number} password 新密码
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:该手机未注册 / 3003:更新失败 / 3004:验证码格式有误 / 3005:密码强度不够 / 3006:验证码错误
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/resetpassword
     * @return array
     * @author rzc
     */
    public function resetPassword() {
        $apiName  = classBasename($this) . '/' . __function__;
        $mobile   = trim($this->request->post('mobile'));
        $vercode  = trim($this->request->post('vercode'));
        $password = trim($this->request->post('password'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001']; //手机号格式错误
        }
        if (checkVercode($vercode) === false) {
            return ['code' => '3004'];
        }
        if (checkPassword($password) === false) {
            return ['code' => '3005'];
        }
        $result = $this->app->user->resetPassword($mobile, $vercode, $password);
        $this->apiLog($apiName, [$mobile, $vercode, $password], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 发送验证码短信
     * @apiDescription   sendVercode
     * @apiGroup         index_user
     * @apiName          sendVercode
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {Number} stype 验证码类型 1.注册 2.修改密码 3.快捷登录 4.银行卡绑卡验证 5.报名手机验证码
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:发送类型有误 / 3003:一分钟内不能重复发送 / 3004:短信发送失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/sendvercode
     * @return array
     * @author zyr
     */
    public function sendVercode() {
        $apiName  = classBasename($this) . '/' . __function__;
        $stypeArr = [1, 2, 3, 4, 5];
        $mobile   = trim($this->request->post('mobile'));
        $stype    = trim($this->request->post('stype'));
        if (!checkMobile($mobile)) {
            return ['code' => '3001']; //手机格式有误
        }
        if (!in_array($stype, $stypeArr)) {
            return ['code' => '3002']; //手机格式有误
        }
        $result = $this->app->user->sendVercode($mobile, $stype);
        $this->apiLog($apiName, [$mobile, $stype], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 通过con_id获取用户信息
     * @apiDescription   getUser
     * @apiGroup         index_user
     * @apiName          getuser
     * @apiParam (入参) {String} con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:conId有误查不到uid
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSuccess (data) {String} id 用户加密id
     * @apiSuccess (data) {Number} user_type 1.普通账户2.总店账户
     * @apiSuccess (data) {String} nick_name 微信昵称
     * @apiSuccess (data) {String} true_name 真实姓名
     * @apiSuccess (data) {String} brithday 生日
     * @apiSuccess (data) {Date} create_time 注册时间
     * @apiSuccess (data) {Double} money 剩余金额（现金）
     * @apiSampleRequest /index/user/getuser
     * @return array
     * @author rzc
     */
    public function getUser() {
        $apiName = classBasename($this) . '/' . __function__;
        $conId   = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $res = $this->app->user->getUser($conId);
        $this->apiLog($apiName, [$conId], $res['code'], $conId);
        return $res;
    }

    /**
     * @api              {post} / 手机快捷登录
     * @apiDescription   quickLogin
     * @apiGroup         index_user
     * @apiName          quickLogin
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {String} vercode 验证码
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误  / 3002:code码错误 / 3004:验证码格式有误 / 3005:新用户需授权 / 3006:验证码错误 / 3009:该微信号已绑定手机号
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/quicklogin
     * @return array
     * @author rzc
     */
    public function quickLogin() {
        $apiName       = classBasename($this) . '/' . __function__;
        $mobile        = trim($this->request->post('mobile'));
        $vercode       = trim($this->request->post('vercode'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001']; //手机号格式错误
        }
        if (checkVercode($vercode) === false) {
            return ['code' => '3004'];
        }
        
        $result   = $this->app->user->quickLogin($mobile, $vercode);
        $this->apiLog($apiName, [$mobile, $vercode], $result['code'], '');
//        $dd       = [$result, ['mobile' => $mobile, 'vercode' => $vercode, 'buid' => $buid]];
        //        Db::table('pz_log_error')->insert(['title' => '/index/user/getintegraldetail', 'data' => json_encode($dd)]);
        return $result;
    }

    /**
     * @api              {post} / 账号密码登录
     * @apiDescription   login
     * @apiGroup         index_user
     * @apiName          login
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {String} password 密码
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:账号不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/login
     * @return array
     * @author rzc
     */
    public function login() {
        $apiName  = classBasename($this) . '/' . __function__;
        $mobile   = trim($this->request->post('mobile'));
        $password = trim($this->request->post('password'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001']; //手机号格式错误
        }
        if (checkPassword($password) === false) {
            return ['code' => '3005'];
        }
        $res = $this->app->user->login($mobile, $password);
        $this->apiLog($apiName, [$mobile, $password], $res['code'], '');
        return $res;
    }


    /**
     * @api              {post} / 开通分配子账户
     * @apiDescription   apportionSonUser
     * @apiGroup         index_user
     * @apiName          apportionSonUser
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} nick_name 子账户用户名
     * @apiParam (入参) {String} user_type 账户类型1.个人账户2.企业账户
     * @apiParam (入参) {String} mobile 手机号
     * @apiParam (入参) {String} [email] 手机号
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:conId有误查不到uid
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSuccess (data) {String} id 用户加密id
     * @apiSampleRequest /index/user/apportionSonUser
     * @return array
     * @author rzc
     */

     public function apportionSonUser(){
        $apiName = classBasename($this) . '/' . __function__;
        $conId   = trim($this->request->post('con_id'));
        $nick_name = trim($this->request->post('nick_name'));
        $user_type = trim($this->request->post('user_type'));
        $passwd    = trim($this->request->post('passwd'));
        $mobile    = trim($this->request->post('mobile'));
        $email     = trim($this->request->post('email'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (checkMobile($mobile) === false) {
            return ['code' => '3001']; //手机号格式错误
        }
        if (checkPassword($passwd) === false) {
            return ['code' => '3005'];
        }
        $result  = $this->app->user->apportionSonUser($conId, $nick_name, $user_type, $passwd, $mobile, $email);
        $this->apiLog($apiName, [$conId, $nick_name, $user_type, $passwd, $mobile, $email], $result['code'], $conId);
        return $result;
     }
}
