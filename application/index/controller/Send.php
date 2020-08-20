<?php

namespace app\index\controller;

use app\index\MyController;

class Send extends MyController
{
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
    public function cmppSendTest()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $phone   = trim($this->request->post('phone')); //手机号
        // if (!checkMobile($phone)) {
        //     return ['code' => 3001];
        // }
        // echo phpinfo();die;
        $a = '感谢您对于CellCare的信赖和支持，为了给您带来更好的服务体验，特邀您针对本次服务进行评价https://www.wenjuan.com/s/6rqIZz/ ，请您在24小时内提交此问卷，谢谢配合。期待您的反馈！如需帮助，敬请致电400-8206-142【美丽田园】';

        $code = trim($this->request->post('code')); //验证码
        //图片函数测试
        stream_context_set_default([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);
        $head = get_headers($code, 1);
        print_r($head);
        die;
        $code       = mb_convert_encoding($code, 'GBK', 'UTF-8');
        $length     = mb_strlen($code);
        $strdata    = [];
        $ascii_code = '';
        $code_data  = [];
        $de_code    = '';
        for ($i = 0; $i < $length; $i++) {
            $str = mb_substr($code, $i, 1);
            $ascii_code .= ord($str);
            $val['str']     = $str;
            $val['ASCII']   = ord($str);
            $val['deascii'] = chr(ord($str));
            $de_code .= chr(ord($str));
            $code_data[] = $val;
            // $strdata[] = mb_substr($code,$i,1);
        }
        $encode = mb_detect_encoding($de_code, array("ASCII", 'UTF-8', 'GB2312', 'GBK', 'BIG5'));

        $de_code = mb_convert_encoding($de_code, 'UTF-8', 'CP936');
        print_r($de_code);
        die;
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
    public function smsBatch()
    {
        $Username = trim($this->request->post('username')); //登录名
        $Password = trim($this->request->post('password')); //登陆密码
        $Content  = trim($this->request->post('content')); //短信内容
        $Mobile   = trim($this->request->post('mobile')); //接收手机号码
        $Dstime   = trim($this->request->post('dstime')); //手机号
        $ip       = trim($this->request->ip());
        $Mobiles  = explode(',', $Mobile);
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
        if (count($Mobiles) > 100) {
            return 4;
        }
        if (strtotime($Dstime) == false && !empty($Dstime)) {
            return 7;
        }
        if (strtotime($Dstime) < time() && !empty($Dstime)) {
            return 8;
        }
        if (empty($Content) || strlen($Content) > 500) {
            return 3;
        }
        // echo mb_strpos($Content,'】') - mb_strpos($Content,'【');die;
        if (mb_strpos($Content, '】') - mb_strpos($Content, '【') < 2 || mb_strpos($Content, '】') - mb_strpos($Content, '【') > 8) {
            return 6;
        }
        $result = $this->app->send->smsBatch($Username, $Password, $Content, $Mobiles, $Dstime, $ip);
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
    public function getBalanceSmsBatch()
    {
        $Username = trim($this->request->post('username')); //登录名
        $Password = trim($this->request->post('password')); //登陆密码
        if (empty($Username)) {
            return -1;
        }
        if (empty($Password)) {
            return -1;
        }
        $result = $this->app->send->getBalanceSmsBatch($Username, $Password);
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
    public function getReceiveSmsBatch()
    {
        $Username = trim($this->request->post('username')); //登录名
        $Password = trim($this->request->post('password')); //登陆密码
        if (empty($Username)) {
            return -1;
        }
        if (empty($Password)) {
            return -1;
        }
        $result = $this->app->send->getReceiveSmsBatch($Username, $Password);
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
    public function getReplaySmsBatch()
    {
        $Username = trim($this->request->post('username')); //登录名
        $Password = trim($this->request->post('password')); //登陆密码
        $result   = $this->app->send->getReplaySmsBatch($Username, $Password);
        return $result;
    }

    /**
     * @api              {post} / 短信任务接收接口（营销业务）（对外客户）
     * @apiDescription   getSmsMarketingTask
     * @apiGroup         index_send
     * @apiName          getSmsMarketingTask
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} content 短信内容
     * @apiParam (入参) {String} taskname 任务名称
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} [develop_no] 拓展号
     * @apiParam (入参) {String} mobile 接收手机号码
     * @apiParam (入参) {String} dstime 发送时间
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:手机号提交手机号为空 / 3002:单批次手机号码为空 / 3003:dstime发送时间格式错误 / 3004:预约发送时间小于当前时间 / 3005:短信内容为空或者短信内容超出500字符 / 3006:签名长度为2~20个字 / 3007:task_name 短信标题不能为空 / 3008:该用户没有此项服务 / 3009:该用户已停用 / 3010:该签名未审核通过  / 3011:拓展码格式错误 / 3012:服务器错误
     * @apiSampleRequest /index/send/getSmsMarketingTask
     * @author rzc
     */
    public function getSmsMarketingTask()
    {
        $appid     = trim($this->request->post('appid')); //登录名
        $appkey    = trim($this->request->post('appkey')); //登陆密码
        $Content   = trim($this->request->post('content')); //短信内容
        $task_name = trim($this->request->post('taskname')); //任务名称
        $Mobile    = trim($this->request->post('mobile')); //接收手机号码
        $Dstime    = trim($this->request->post('dstime')); //手机号
        $develop_no  = trim($this->request->post('develop_no')); //拓展码号
        $signature_id  = trim($this->request->post('signature_id')); //接收手机号码
        $ip        = trim($this->request->ip());
        $Mobiles   = explode(',', $Mobile);
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        // echo phpinfo();die;
        if (empty($Mobiles)) {
            return ['code' => '3001'];
        }
        if (count($Mobiles) < 1) {
            return ['code' => '3002'];
        }
        if (strtotime($Dstime) == false && !empty($Dstime)) {
            return ['code' => '3003'];
        }
        if (strtotime($Dstime) < time() && !empty($Dstime)) {
            return ['code' => '3004'];
        }
        // if (empty($Content) || strlen($Content) > 600) {
        //     return ['code' => '3005'];
        // }
        // echo mb_strpos($Content,'】') - mb_strpos($Content,'【');die;
        if (empty($signature_id)) {
            if (mb_strpos($Content, '】') - mb_strpos($Content, '【') < 2 || mb_strpos($Content, '】') - mb_strpos($Content, '【') > 20) {
                return ['code' => '3006'];
            }
        }
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3011'];
        }
        // print_r($task_name);die;
        // if (empty($task_name)) {
        //     return ['code' => '3007'];
        // }
        $result = $this->app->send->getSmsMarketingTask($appid, $appkey, $Content, $Mobiles, $Dstime, $ip, $task_name, $signature_id, $develop_no);
        return $result;
    }

    /**
     * @api              {post} / 短信任务接收接口（营销业务）（对外客户msg_id）
     * @apiDescription   getSmsMarketingTaskMsgId
     * @apiGroup         index_send
     * @apiName          getSmsMarketingTaskMsgId
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} content 短信内容
     * @apiParam (入参) {String} msg_id 客户msg_id
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} [develop_no] 拓展号
     * @apiParam (入参) {String} mobile 接收手机号码
     * @apiParam (入参) {String} dstime 发送时间
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:手机号提交手机号为空 / 3002:单批次手机号码为空 / 3003:dstime发送时间格式错误 / 3004:预约发送时间小于当前时间 / 3005:短信内容为空或者短信内容超出500字符 / 3006:签名长度为2~20个字 / 3007:task_name 短信标题不能为空 / 3008:该用户没有此项服务 / 3009:该用户已停用 / 3010:该签名未审核通过  / 3011:拓展码格式错误 / 3012:服务器错误
     * @apiSampleRequest /index/send/getSmsMarketingTaskMsgId
     * @author rzc
     */
    public function getSmsMarketingTaskMsgId()
    {
        $appid     = trim($this->request->post('appid')); //登录名
        $appkey    = trim($this->request->post('appkey')); //登陆密码
        $Content   = trim($this->request->post('content')); //短信内容
        $msg_id = trim($this->request->post('msg_id')); //任务名称
        $Mobile    = trim($this->request->post('mobile')); //接收手机号码
        $Dstime    = trim($this->request->post('dstime')); //手机号
        $develop_no  = trim($this->request->post('develop_no')); //拓展码号
        $signature_id  = trim($this->request->post('signature_id')); //接收手机号码
        $ip        = trim($this->request->ip());
        $Mobiles   = explode(',', $Mobile);
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        // echo phpinfo();die;
        if (empty($Mobiles)) {
            return ['code' => '3001'];
        }
        if (count($Mobiles) < 1) {
            return ['code' => '3002'];
        }
        if (strtotime($Dstime) == false && !empty($Dstime)) {
            return ['code' => '3003'];
        }
        if (strtotime($Dstime) < time() && !empty($Dstime)) {
            return ['code' => '3004'];
        }
        // if (empty($Content) || strlen($Content) > 600) {
        //     return ['code' => '3005'];
        // }
        // echo mb_strpos($Content,'】') - mb_strpos($Content,'【');die;
        if (empty($signature_id)) {
            if (mb_strpos($Content, '】') - mb_strpos($Content, '【') < 2 || mb_strpos($Content, '】') - mb_strpos($Content, '【') > 20) {
                return ['code' => '3006'];
            }
        }
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3011'];
        }
        // print_r($task_name);die;
        // if (empty($task_name)) {
        //     return ['code' => '3007'];
        // }
        $task_name = '';
        $result = $this->app->send->getSmsMarketingTask($appid, $appkey, $Content, $Mobiles, $Dstime, $ip, $task_name, $signature_id, $develop_no, $msg_id);
        return $result;
    }


    /**
     * @api              {post} / 短信任务接收接口（行业）（对外客户）
     * @apiDescription   getSmsBuiness
     * @apiGroup         index_send
     * @apiName          getSmsBuiness
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} [develop_no] 拓展号
     * @apiParam (入参) {String} content 短信内容
     * @apiParam (入参) {String} mobile 接收手机号码
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误 / 3001:手机号格式错误 / 3002:短信内容为空或者短信内容超出500字符 / 3003:签名长度为2~20个字 / 3004:该账户已被停用 / 3006:该账户没有此项服务 / 3007:短信余额不足，请先充值 / 3008:签名ID错误 / 3009 :系统错误 / 3010:签名未审核通过 / 3011:develop_no(拓展码)错误
     * @apiSampleRequest /index/send/getSmsBuiness
     * @author rzc
     */
    public function getSmsBuiness()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $Content = trim($this->request->post('content')); //短信内容
        $Mobile  = trim($this->request->post('mobile')); //接收手机号码
        $develop_no  = trim($this->request->post('develop_no')); //拓展码号
        $signature_id  = trim($this->request->post('signature_id')); //接收手机号码
        $ip      = trim($this->request->ip());
        $Mobiles = explode(',', $Mobile);

        // print_r($Content);die;
        // echo phpinfo();die;
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        // if (empty($Mobile) || checkMobile($Mobile) === false) {
        //     return ['code'=>'3001'];
        // }
        if (empty($Content) || mb_strlen($Content) > 500) {
            return ['code' => '3002'];
        }
        // echo mb_strpos($Content,'】') - mb_strpos($Content,'【');die;
        if (empty($signature_id)) {
            if (mb_strpos($Content, '】') - mb_strpos($Content, '【') < 2 || mb_strpos($Content, '】') - mb_strpos($Content, '【') > 20) {
                return ['code' => '3003'];
            }
        }
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3011'];
        }
        $result = $this->app->send->getSmsBuiness($appid, $appkey, $Content, $Mobiles, $ip, $signature_id, $develop_no);

        return $result;
    }

    /**
     * @api              {post} / 短信任务接收接口（行业）（对外客户msg_id）
     * @apiDescription   getSmsBuinessMsgId
     * @apiGroup         index_send
     * @apiName          getSmsBuinessMsgId
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} [develop_no] 拓展号
     * @apiParam (入参) {String} content 短信内容
     * @apiParam (入参) {String} mobile 接收手机号码
     * @apiParam (入参) {String} msg_id msg_id
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误 / 3001:手机号格式错误 / 3002:短信内容为空或者短信内容超出500字符 / 3003:签名长度为2~20个字 / 3004:该账户已被停用 / 3006:该账户没有此项服务 / 3007:短信余额不足，请先充值 / 3008:签名ID错误 / 3009 :系统错误 / 3010:签名未审核通过 / 3011:develop_no(拓展码)错误
     * @apiSampleRequest /index/send/getSmsBuinessMsgId
     * @author rzc
     */
    public function getSmsBuinessMsgId()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $Content = trim($this->request->post('content')); //短信内容
        $Mobile  = trim($this->request->post('mobile')); //接收手机号码
        $develop_no  = trim($this->request->post('develop_no')); //拓展码号
        $signature_id  = trim($this->request->post('signature_id')); //接收手机号码
        $msg_id  = trim($this->request->post('msg_id')); //接收手机号码
        $ip      = trim($this->request->ip());
        $Mobiles = explode(',', $Mobile);

        // print_r($Content);die;
        // echo phpinfo();die;
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        // if (empty($Mobile) || checkMobile($Mobile) === false) {
        //     return ['code'=>'3001'];
        // }
        if (empty($Content) || mb_strlen($Content) > 500) {
            return ['code' => '3002'];
        }
        // echo mb_strpos($Content,'】') - mb_strpos($Content,'【');die;
        if (empty($signature_id)) {
            if (mb_strpos($Content, '】') - mb_strpos($Content, '【') < 2 || mb_strpos($Content, '】') - mb_strpos($Content, '【') > 20) {
                return ['code' => '3003'];
            }
        }
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3011'];
        }
        $result = $this->app->send->getSmsBuiness($appid, $appkey, $Content, $Mobiles, $ip, $signature_id, $develop_no, $msg_id);
        // Log::write(json_encode($result), 'info');
        return $result;
    }

    /**
     * @api              {post} / 获取表格中手机号，第一列
     * @apiDescription   readFileContent
     * @apiGroup         index_send
     * @apiName          readFileContent
     * @apiParam (入参) {String} filename 文件名称
     * @apiSuccess (返回) {String} code 200:成功  / 3001:文件名为空
     * @apiSampleRequest /index/send/readFileContent
     * @author rzc
     */
    public function readFileContent()
    {
        $filename = trim($this->request->post('filename')); //登录名
        if (empty($filename)) {
            return ['code' => '3001'];
        }
        $result = $this->app->send->readFileContent($filename);
        return $result;
    }

    /**
     * @api              {post} / 短信上行查询
     * @apiDescription   upGoing
     * @apiGroup         index_send
     * @apiName          upGoing
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/upGoing
     * @author rzc
     */
    public function upGoing()
    {
        $appid  = trim($this->request->post('appid')); //登录名
        $appkey = trim($this->request->post('appkey')); //登陆密码
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        $result = $this->app->send->upGoing($appid, $appkey);
        return $result;
    }

    /**
     * @api              {post} / 余额查询
     * @apiDescription   balanceEnquiry
     * @apiGroup         index_send
     * @apiName          balanceEnquiry
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/balanceEnquiry
     * @author rzc
     */
    public function balanceEnquiry()
    {
        $appid  = trim($this->request->post('appid')); //登录名
        $appkey = trim($this->request->post('appkey')); //登陆密码
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        $result = $this->app->send->balanceEnquiry($appid, $appkey);
        return $result;
    }

    /**
     * @api              {post} / 营销短信日志查询
     * @apiDescription   marketingReceive
     * @apiGroup         index_send
     * @apiName          marketingReceive
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {Number} page 页码 默认1
     * @apiParam (入参) {Number} pagenum 每页数量 默认10,最大不超过100
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/marketingReceive
     * @author rzc
     */
    public function marketingReceive()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $page    = trim($this->request->post('page')); //登陆密码
        $pageNum = trim($this->request->post('pagenum')); //登陆密码
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if ($pageNum > 100) {
            return ['code' => '3001'];
        }
        $result = $this->app->send->marketingReceive($appid, $appkey, $page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 行业短信日志查询
     * @apiDescription   businessReceive
     * @apiGroup         index_send
     * @apiName          businessReceive
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {Number} page 页码 默认1
     * @apiParam (入参) {Number} pagenum 每页数量 默认10,最大不超过100
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/businessReceive
     * @author rzc
     */
    public function businessReceive()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $page    = trim($this->request->post('page')); //登陆密码
        $pageNum = trim($this->request->post('pagenum')); //登陆密码
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        $ip      = trim($this->request->ip());
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if ($pageNum > 100) {
            return ['code' => '3001'];
        }
        $result = $this->app->send->businessReceive($appid, $appkey, $page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 号码区分
     * @apiDescription   getMobilesDetail
     * @apiGroup         index_send
     * @apiName          getMobilesDetail
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {file} phone 表格名称 支持文件格式 txt,xlsx,csv,xls
     * @apiSuccess (返回) {String} code 200:成功  / 3001:电话号码为空
     * @apiSuccess (data) {Number} submit_num 上传数量
     * @apiSuccess (data) {Number} real_num 真实有效数量
     * @apiSuccess (data) {Number} mobile_num 移动手机号数量
     * @apiSuccess (data) {Number} unicom_num 联通手机号数量
     * @apiSuccess (data) {Number} telecom_num 电信手机号数量
     * @apiSuccess (data) {Number} virtual_num 虚拟运营商手机号数量
     * @apiSuccess (data) {Number} unknown_num 未知归属运营商手机号数量
     * @apiSuccess (data) {Number} mobile_phone 移动手机号码包
     * @apiSuccess (data) {Number} unicom_phone 联通手机号码包
     * @apiSuccess (data) {Number} telecom_phone 电信手机号码包
     * @apiSuccess (data) {Number} virtual_phone 虚拟运营商手机号码包
     * @apiSuccess (data) {Number} error_phone 错号包
     * @apiSuccess (data) {String} realphone 真实手机号结果
     * @apiSampleRequest /index/send/getMobilesDetail
     * @author rzc
     */
    public function getMobilesDetail()
    {
        $appid      = trim($this->request->post('appid')); //登录名
        $appkey     = trim($this->request->post('appkey')); //登陆密码
        $phone      = trim($this->request->post('phone')); //登陆密码
        $phone_data = explode(',', $phone);
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($phone_data)) {
            return ['code' => '3001'];
        }

        $result = $this->app->send->getMobilesDetail($appid, $appkey, $phone_data);
        return $result;
    }

    /**
     * @api              {post} / 短信任务接收接口（彩信业务）（对外客户）
     * @apiDescription   getSmsMultimediaMessageTask
     * @apiGroup         index_send
     * @apiName          getSmsMultimediaMessageTask
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {Array} content_data 短信内容
     * @apiParam (入参) {String} title 任务名称
     * @apiParam (入参) {String} mobile_content 电话号码集合,多个用','，分开，最多支持50000
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} [send_time] 预约发送时间 示例： 2019-12-08 17:02:25
     * @apiParam (content_data) {String} content 单个帧文字内容
     * @apiParam (content_data) {String} image_path 单个帧图片路径,必须已上传的文件
     * @apiParam (content_data) {String} num 顺序 按自然数排列 从小到大 必传
     * @apiParam (content_data) {String} name 对应帧数 如第一帧
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:余额不足 / 3002:单批次手机号码为空 / 3003:send_time发送时间格式错误 / 3004:预约发送时间小于当前时间 / 3005:该账户没有此项服务 / 3006:余额不足 / 3007:title 彩信标题不能为空 / 3008:无效的图片 / 3009:彩信文件长度超过80KB或内容为空 / 3010 图片未上传过 / 3011:服务器错误 / 3012:该签名不存在 / 3013:该签名未被审核通过 
     * @apiSampleRequest /index/send/getSmsMultimediaMessageTask
     * @author rzc
     */
    public function getSmsMultimediaMessageTask()
    {
        $appid          = trim($this->request->post('appid')); //登录名
        $appkey         = trim($this->request->post('appkey')); //登陆密码
        $signature_id         = trim($this->request->post('signature_id')); //登陆密码
        $title          = trim($this->request->post('title')); //短信标题
        $content_data   = $this->request->post('content_data'); //短信内容
        $send_time      = trim($this->request->post('send_time')); //预约发送时间
        $mobile_content = trim($this->request->post('mobile_content')); //接收手机号码
        $ip             = trim($this->request->ip());
        $mobile_content = explode(',', $mobile_content); //短信数组
        // $content_data   = json_decode($content_data, true);
        // print_r($content_data);die;
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($mobile_content)) {
            return ['code' => '3002'];
        }
        if (empty($content_data)) {
            return ['code' => '3009'];
        }
        if (strtotime($send_time) == false && !empty($send_time)) {
            return ['code' => '3003'];
        }
        if (strtotime($send_time) < time() && !empty($send_time)) {
            return ['code' => '3004'];
        }
        if (empty($title)) {
            return ['code' => '3007'];
        }
        $result = $this->app->send->getSmsMultimediaMessageTask($appid, $appkey, $content_data, $mobile_content, $send_time, $ip, $title, $signature_id);
        return $result;
    }

    /**
     * @api              {post} / 短信任务接收接口（彩信业务）（对外客户MsgId,及段落的格式）
     * @apiDescription   getSmsMultimediaMessageTaskMsgId
     * @apiGroup         index_send
     * @apiName          getSmsMultimediaMessageTaskMsgId
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} msg_id msg_id
     * @apiParam (入参) {Array} content_data 短信内容
     * @apiParam (入参) {String} title 任务名称
     * @apiParam (入参) {String} mobile_content 电话号码集合,多个用','，分开，最多支持50000
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} develop_no 扩展号
     * @apiParam (入参) {String} [send_time] 预约发送时间 示例： 2019-12-08 17:02:25
     * @apiParam (content_data) {Array} paragraph 单个帧文字内容
     * @apiParam (content_data) {String} num 顺序 按自然数排列 从小到大 必传
     * @apiParam (content_data) {String} name 对应帧数 如第一帧
     * @apiParam (content_data[paragraph]) {String} num 对应段落数 如第一段
     * @apiParam (content_data[paragraph]) {String} type 类型 1文本，2图片
     * @apiParam (content_data[paragraph]) {String} content 内容
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:余额不足 / 3002:单批次手机号码为空 / 3003:send_time发送时间格式错误 / 3004:预约发送时间小于当前时间 / 3005:该账户没有此项服务 / 3006:余额不足 / 3007:title 彩信标题不能为空 / 3008:无效的图片 / 3009:彩信文件长度超过80KB或内容为空 / 3010 图片未上传过 / 3011:服务器错误 / 3012:该签名不存在 / 3013:该签名未被审核通过 
     * @apiSampleRequest /index/send/getSmsMultimediaMessageTaskMsgId
     * @author rzc
     */
    public function getSmsMultimediaMessageTaskMsgId()
    {
        $appid          = trim($this->request->post('appid')); //登录名
        $appkey         = trim($this->request->post('appkey')); //登陆密码
        $msg_id         = trim($this->request->post('msg_id')); //登陆密码
        $signature_id         = trim($this->request->post('signature_id')); //登陆密码
        $title          = trim($this->request->post('title')); //短信标题
        $content_data   = $this->request->post('content_data'); //短信内容
        $send_time      = trim($this->request->post('send_time')); //预约发送时间
        $mobile_content = trim($this->request->post('mobile_content')); //接收手机号码
        $develop_no  = trim($this->request->post('develop_no')); //拓展码号
        $ip             = trim($this->request->ip());
        $mobile_content = explode(',', $mobile_content); //短信数组
        // $content_data   = json_decode($content_data, true);
        // print_r($content_data);die;
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($mobile_content)) {
            return ['code' => '3002'];
        }
        if (empty($content_data)) {
            return ['code' => '3009'];
        }
        if (strtotime($send_time) == false && !empty($send_time)) {
            return ['code' => '3003'];
        }
        if (strtotime($send_time) < time() && !empty($send_time)) {
            return ['code' => '3004'];
        }
        if (empty($title)) {
            return ['code' => '3007'];
        }
        // print_r($content_data);die;
        $result = $this->app->send->getSmsMultimediaMessageTaskNew($appid, $appkey, $content_data, $mobile_content, $send_time, $ip, $title, $signature_id, $msg_id);
        return $result;
    }

    /**
     * @api              {post} / 详情查询接口（彩信业务）注:平台
     * @apiDescription   getSmsMultimediaMessageTaskLog
     * @apiGroup         index_send
     * @apiName          getSmsMultimediaMessageTaskLog
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} task_no
     * @apiParam (入参) {Number} page 页码 默认1
     * @apiParam (入参) {Number} pagenum 每页数量 默认10
     * @apiParam (入参) {String} [mobile]
     * @apiParam (入参) {String} [status] ,2:已发送;3:成功;4:失败
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:task_no任务编号 / 3002:手机号码格式错误
     * @apiSampleRequest /index/send/getSmsMultimediaMessageTaskLog
     * @author rzc
     */
    public function getSmsMultimediaMessageTaskLog()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $task_no = trim($this->request->post('task_no')); //短信标题
        $page    = trim($this->request->post('page')); //短信标题
        $pageNum = trim($this->request->post('pagenum')); //短信标题
        $mobile  = trim($this->request->post('mobile')); //短信内容
        $status  = trim($this->request->post('status')); //短信内容
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($task_no)) {
            return ['code' => '3001'];
        }
        if (!empty($mobile) && checkMobile($mobile) == false) {
            return ['code' => '3002'];
        }
        if (!empty($status) && !in_array($status, [2, 3, 4])) {
            return ['code' => '3002'];
        }
        $result = $this->app->send->getSmsMultimediaMessageTaskLog($appid, $appkey, $task_no, $page, $pageNum, $mobile, $status);
        return $result;
    }

    /**
     * @api              {post} / 短信状态查询接口（彩信业务）（对外客户）
     * @apiDescription   getSmsMultimediaMessageTaskStatus
     * @apiGroup         index_send
     * @apiName          getSmsMultimediaMessageTaskStatus
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/getSmsMultimediaMessageTaskStatus
     * @author rzc
     */
    public function getSmsMultimediaMessageTaskStatus()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        $result = $this->app->send->getSmsMultimediaMessageTaskStatus($appid, $appkey);
        return $result;
    }



    /**
     * @api              {post} / 文本类模板报备接口（不支持彩信和视频短信）
     * @apiDescription   textTemplateSignatureReport
     * @apiGroup         index_send
     * @apiName          textTemplateSignatureReport
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} type 业务场景 5营销，6行业，7网贷，9游戏
     * @apiParam (入参) {String} title 模板标题
     * @apiParam (入参) {String} connect 模板内容 格式【签名】+内容+{{var1}}+内容+{{var2}}+内容+...
     * @apiSuccess (返回) {String} code 200:成功 / 3000:appid 或者appkey 为空 / 3001:业务场景错误 / 3002:签名长度小于2个字 / 3003:该用户没有此项服务
     * @apiSampleRequest /index/send/textTemplateSignatureReport
     * @return array
     * @author rzc
     */
    public function textTemplateSignatureReport()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $type  = trim($this->request->post('type')); //业务场景
        $title  = trim($this->request->post('title')); //业务场景
        $connect  = trim($this->request->post('connect')); //业务场景
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (!in_array($type, [5, 6, 7, 9])) {
            return ['code' => '3001'];
        }
        if (mb_strpos($connect, '】') - mb_strpos($connect, '【') < 2) {
            return ['code' => '3002'];
        }
        $result = $this->app->send->textTemplateSignatureReport($appid, $appkey, $type, $title, $connect);
        return $result;
    }

    /**
     * @api              {post} / 批量自定义短信提交接口（行业）
     * @apiDescription   submitBatchCustomBusiness
     * @apiGroup         index_send
     * @apiName          submitBatchCustomBusiness
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} [develop_no] 拓展码
     * @apiParam (入参) {String} template_id template_id报备的template_id 如果传template_id 则内容替换为模板中内容变量
     * @apiParam (入参) {String} connect 组合包内容(template组合方式：变量,变量,...:手机号;变量,变量,...:手机号;...  无模板组合方式:内容:手机号;内容:手机号;...)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:提交内容为空
     * @apiSampleRequest /index/send/submitBatchCustomBusiness
     * @return array
     * @author rzc
     */

    public function submitBatchCustomBusiness()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $template_id  = trim($this->request->post('template_id'));
        $develop_no  = trim($this->request->post('develop_no')); //拓展码号
        $signature_id  = trim($this->request->post('signature_id'));
        $connect  = trim($this->request->post('connect'));
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($connect)) {
            return ['code' => '3001'];
        }
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3011'];
        }
        $ip       = trim($this->request->ip());
        $result = $this->app->send->submitBatchCustomBusinessMsgId($appid, $appkey, $template_id, $connect, $ip, $signature_id, '', $develop_no);
        return $result;
    }

    /**
     * @api              {post} / 批量自定义短信提交接口（行业）[平台]
     * @apiDescription   submitBatchCustomBusinessTerrace
     * @apiGroup         index_send
     * @apiName          submitBatchCustomBusinessTerrace
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} [develop_no] 拓展码
     * @apiParam (入参) {String} template_id template_id报备的template_id 如果传template_id 则内容替换为模板中内容变量
     * @apiParam (入参) {String} connect 组合包内容(template组合方式：变量,变量,...:手机号;变量,变量,...:手机号;...  无模板组合方式:内容:手机号;内容:手机号;...)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:提交内容为空
     * @apiSampleRequest /index/send/submitBatchCustomBusinessTerrace
     * @return array
     * @author rzc
     */

    public function submitBatchCustomBusinessTerrace()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $template_id  = trim($this->request->post('template_id'));
        $develop_no  = trim($this->request->post('develop_no')); //拓展码号
        $signature_id  = trim($this->request->post('signature_id'));
        $connect  = trim($this->request->post('connect'));
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($connect)) {
            return ['code' => '3001'];
        }
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3011'];
        }
        $ip       = trim($this->request->ip());
        $result = $this->app->send->submitBatchCustomBusiness($appid, $appkey, $template_id, $connect, $ip, $signature_id, '', $develop_no);
        return $result;
    }

    /**
     * @api              {post} / 批量自定义短信提交接口（行业MsgId）
     * @apiDescription   submitBatchCustomBusinessMsgId
     * @apiGroup         index_send
     * @apiName          submitBatchCustomBusinessMsgId
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} msg_id msg_id
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} [develop_no] 拓展码
     * @apiParam (入参) {String} template_id template_id报备的template_id 如果传template_id 则内容替换为模板中内容变量
     * @apiParam (入参) {String} connect 组合包内容(template组合方式：变量,变量,...:手机号;变量,变量,...:手机号;...  无模板组合方式:内容:手机号;内容:手机号;...) 【内容和变量base64编码】
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:提交内容为空
     * @apiSampleRequest /index/send/submitBatchCustomBusinessMsgId
     * @return array
     * @author rzc
     */

    public function submitBatchCustomBusinessMsgId()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $msg_id  = trim($this->request->post('msg_id')); //msg_id
        $template_id  = trim($this->request->post('template_id'));
        $develop_no  = trim($this->request->post('develop_no')); //拓展码号
        $signature_id  = trim($this->request->post('signature_id'));
        $connect  = trim($this->request->post('connect'));
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($connect)) {
            return ['code' => '3001'];
        }
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3011'];
        }
        $ip       = trim($this->request->ip());
        $result = $this->app->send->submitBatchCustomBusinessMsgId($appid, $appkey, $template_id, $connect, $ip, $signature_id, $msg_id, $develop_no);
        return $result;
    }

    /**
     * @api              {post} / 批量自定义短信提交接口（营销）
     * @apiDescription   submitBatchCustomMarketing
     * @apiGroup         index_send
     * @apiName          submitBatchCustomMarketing
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} [develop_no] 拓展码
     * @apiParam (入参) {String} template_id template_id报备的template_id 如果传template_id 则内容替换为模板中内容变量
     * @apiParam (入参) {String} connect 组合包内容(template组合方式：变量,变量,...:手机号;变量,变量,...:手机号;...  无模板组合方式:内容:手机号;内容:手机号;...)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:提交内容为空
     * @apiSampleRequest /index/send/submitBatchCustomMarketing
     * @return array
     * @author rzc
     */

    public function submitBatchCustomMarketing()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $template_id  = trim($this->request->post('template_id'));
        $develop_no  = trim($this->request->post('develop_no'));
        $signature_id  = trim($this->request->post('signature_id'));
        $connect  = trim($this->request->post('connect'));
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($connect)) {
            return ['code' => '3001'];
        }
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3011'];
        }
        $ip       = trim($this->request->ip());
        $result = $this->app->send->submitBatchCustomMarketingMsgId($appid, $appkey, $template_id, $connect, $ip, $signature_id, '', $develop_no);
        return $result;
    }

    /**
     * @api              {post} / 批量自定义短信提交接口（营销）[平台]
     * @apiDescription   submitBatchCustomMarketingTerrace 
     * @apiGroup         index_send
     * @apiName          submitBatchCustomMarketingTerrace 
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} [develop_no] 拓展码
     * @apiParam (入参) {String} template_id template_id报备的template_id 如果传template_id 则内容替换为模板中内容变量
     * @apiParam (入参) {String} connect 组合包内容(template组合方式：变量,变量,...:手机号;变量,变量,...:手机号;...  无模板组合方式:内容:手机号;内容:手机号;...)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:提交内容为空
     * @apiSampleRequest /index/send/submitBatchCustomMarketingTerrace 
     * @return array
     * @author rzc
     */

    public function submitBatchCustomMarketingTerrace()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $template_id  = trim($this->request->post('template_id'));
        $develop_no  = trim($this->request->post('develop_no'));
        $signature_id  = trim($this->request->post('signature_id'));
        $connect  = trim($this->request->post('connect'));
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($connect)) {
            return ['code' => '3001'];
        }
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3011'];
        }
        $ip       = trim($this->request->ip());
        $result = $this->app->send->submitBatchCustomMarketing($appid, $appkey, $template_id, $connect, $ip, $signature_id, '', $develop_no);
        return $result;
    }

    /**
     * @api              {post} / 批量自定义短信提交接口（营销）
     * @apiDescription   submitBatchCustomMarketingMsgId
     * @apiGroup         index_send
     * @apiName          submitBatchCustomMarketingMsgId
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} msg_id msg_id
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} template_id template_id报备的template_id 如果传template_id 则内容替换为模板中内容变量
     * @apiParam (入参) {String} connect 组合包内容(template组合方式：变量,变量,...:手机号;变量,变量,...:手机号;...  无模板组合方式:内容:手机号;内容:手机号;...)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:提交内容为空
     * @apiSampleRequest /index/send/submitBatchCustomMarketingMsgId
     * @return array
     * @author rzc
     */

    public function submitBatchCustomMarketingMsgId()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $msg_id  = trim($this->request->post('msg_id')); //登陆密码
        $develop_no  = trim($this->request->post('develop_no'));
        $template_id  = trim($this->request->post('template_id'));
        $signature_id  = trim($this->request->post('signature_id'));
        $connect  = trim($this->request->post('connect'));
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($connect)) {
            return ['code' => '3001'];
        }
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3011'];
        }
        $ip       = trim($this->request->ip());
        $result = $this->app->send->submitBatchCustomMarketingMsgId($appid, $appkey, $template_id, $connect, $ip, $signature_id, $msg_id, $develop_no);
        return $result;
    }

    /**
     * @api              {post} / 签名报备接口
     * @apiDescription   SignatureReport
     * @apiGroup         index_send
     * @apiName          SignatureReport
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} type 业务场景 5营销，6行业，7网贷，9游戏
     * @apiParam (入参) {String} title 签名内容
     * @apiSuccess (返回) {String} code 200:成功 / 3000:appid 或者appkey 为空 / 3001:业务场景错误 / 3002:签名长度小于2个字 / 3003:该用户没有此项服务
     * @apiSampleRequest /index/send/SignatureReport
     * @return array
     * @author rzc
     */

    public function SignatureReport()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $type  = trim($this->request->post('type')); //业务场景
        $title  = trim($this->request->post('title')); //业务场景
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (!in_array($type, [5, 6, 7, 9])) {
            return ['code' => '3001'];
        }
        if (mb_strpos($title, '】') - mb_strpos($title, '【') < 2) {
            return ['code' => '3002'];
        }
        $result = $this->app->send->SignatureReport($appid, $appkey, $type, $title);
        return $result;
    }

    /**
     * @api              {post} / 彩信模板报备接口
     * @apiDescription   multimediaTemplateSignatureReport
     * @apiGroup         index_send
     * @apiName          multimediaTemplateSignatureReport
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {Array} content_data 短信内容
     * @apiParam (入参) {String} title 模板主题
     * @apiParam (入参) {String} name 模板别名
     * @apiParam (content_data) {String} content 单个帧文字内容
     * @apiParam (content_data) {String} image_path 单个帧图片路径,必须已上传的文件
     * @apiParam (content_data) {String} num 顺序 按自然数排列 从小到大 必传
     * @apiParam (content_data) {String} name 对应帧数 如第一帧
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:手机号格式错误 / 3002:单批次手机号码为空 / 3003:send_time发送时间格式错误 / 3004:预约发送时间小于当前时间 / 3005:该账户没有此项服务 / 3006:余额不足 / 3007:title 短信标题不能为空 / 3008:无效的图片 / 3009:彩信文件长度超过100KB或内容为空 / 3010 图片未上传过 / 3011:服务器错误
     * @apiSampleRequest /index/send/multimediaTemplateSignatureReport
     * @author rzc
     */
    public function multimediaTemplateSignatureReport()
    {
        $appid          = trim($this->request->post('appid')); //登录名
        $appkey         = trim($this->request->post('appkey')); //登陆密码
        $title          = trim($this->request->post('title')); //短信标题
        $name          = trim($this->request->post('name')); //短信标题
        $content_data   = $this->request->post('content_data'); //短信内容
        // $content_data   = json_decode($content_data, true);
        // print_r($content_data);die;
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($content_data)) {
            return ['code' => '3001'];
        }
        if (empty($title)) {
            return ['code' => '3002'];
        }
        if (empty($name)) {
            return ['code' => '3003', 'msg' => '别名为空'];
        }
        $result = $this->app->send->multimediaTemplateSignatureReport($appid, $appkey, $content_data, $title, $name);
        return $result;
    }

    /**
     * @api              {post} / 彩信模板报备接口段落类型
     * @apiDescription   multimediaTemplateSignatureReport
     * @apiGroup         index_send
     * @apiName          multimediaTemplateSignatureReport
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {Array} content_data 短信内容
     * @apiParam (入参) {String} title 模板主题
     * @apiParam (入参) {String} name 模板别名
     * @apiParam (content_data) {Array} paragraph 单个帧文字内容
     * @apiParam (content_data) {String} num 顺序 按自然数排列 从小到大 必传
     * @apiParam (content_data) {String} name 对应帧数 如第一帧
     * @apiParam (content_data[paragraph]) {String} num 对应段落数 如第一段
     * @apiParam (content_data[paragraph]) {String} type 类型 1文本，2图片
     * @apiParam (content_data[paragraph]) {String} content 内容
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:手机号格式错误 / 3002:单批次手机号码为空 / 3003:send_time发送时间格式错误 / 3004:预约发送时间小于当前时间 / 3005:该账户没有此项服务 / 3006:余额不足 / 3007:title 短信标题不能为空 / 3008:无效的图片 / 3009:彩信文件长度超过100KB或内容为空 / 3010 图片未上传过 / 3011:服务器错误
     * @apiSampleRequest /index/send/multimediaTemplateSignatureReport
     * @author rzc
     */
    public function multimediaTemplateSignatureReportForParagraph()
    {
        $appid          = trim($this->request->post('appid')); //登录名
        $appkey         = trim($this->request->post('appkey')); //登陆密码
        $title          = trim($this->request->post('title')); //短信标题
        $name          = trim($this->request->post('name')); //短信标题
        $content_data   = $this->request->post('content_data'); //短信内容
        // $content_data   = json_decode($content_data, true);
        // print_r($content_data);die;
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($content_data)) {
            return ['code' => '3001'];
        }
        if (empty($title)) {
            return ['code' => '3002'];
        }
        if (empty($name)) {
            return ['code' => '3003', 'msg' => '别名为空'];
        }
        $result = $this->app->send->multimediaTemplateSignatureReportForParagraph($appid, $appkey, $content_data, $title, $name);
        return $result;
    }

    /**
     * @api              {post} / 模板变量彩信提交
     * @apiDescription   submitBatchCustomMultimediaMessage
     * @apiGroup         index_send
     * @apiName          submitBatchCustomMultimediaMessage
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} [msg_id] 客户提交msg_id非必填
     * @apiParam (入参) {String} template_id 彩信模板报备的template_id 内容替换为模板中文字内容变量
     * @apiParam (入参) {Array} connect 变量数组
     * @apiParam (connect) {String} mobile 手机号
     * @apiParam (connect) {String} var1变量1内容
     * @apiParam (connect) {String} ar2
     * @apiParam (connect) {String} var10 变量10内容
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:手机号格式错误 / 3002:单批次手机号码为空 / 3003:send_time发送时间格式错误 / 3004:预约发送时间小于当前时间 / 3005:该账户没有此项服务 / 3006:余额不足 / 3007:title 短信标题不能为空 / 3008:无效的图片 / 3009:彩信文件长度超过100KB或内容为空 / 3010 图片未上传过 / 3011:服务器错误
     * @apiSampleRequest /index/send/submitBatchCustomMultimediaMessage
     * @author rzc
     */

    public function submitBatchCustomMultimediaMessage()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $template_id  = trim($this->request->post('template_id'));
        // $signature_id  = trim($this->request->post('signature_id'));
        $connect  = $this->request->post('connect');
        $msg_id  = trim($this->request->post('msg_id'));
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($connect)) {
            return ['code' => '3001'];
        }
        if (empty($template_id)) {
            return ['code' => '3003'];
        }
        $ip       = trim($this->request->ip());
        $result = $this->app->send->submitBatchCustomMultimediaMessage($appid, $appkey, $template_id, $connect, $ip, $msg_id);
        return $result;
    }

    /**
     * @api              {post} / 模板彩信提交
     * @apiDescription   submitTemplateMultimediaMessage
     * @apiGroup         index_send
     * @apiName          submitTemplateMultimediaMessage
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} [msg_id] 客户提交msg_id非必填
     * @apiParam (入参) {String} template_id 通过接口或者平台报备的template_id
     * @apiParam (入参) {String} mobile_content 电话号码集合,多个用','，分开，最多支持50000
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误 / 3001:template_id为空 / 3002:手机号码为空
     * @apiSampleRequest /index/send/submitTemplateMultimediaMessage
     * @author rzc
     */
    public function submitTemplateMultimediaMessage()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $template_id  = trim($this->request->post('template_id'));
        $mobile_content = trim($this->request->post('mobile_content')); //接收手机号码
        $ip             = trim($this->request->ip());
        $msg_id  = trim($this->request->post('msg_id'));
        $mobile_content = explode(',', $mobile_content); //短信数组
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($template_id)) {
            return ['code' => '3001'];
        }
        if (empty($mobile_content)) {
            return ['code' => '3002'];
        }
        $result = $this->app->send->submitTemplateMultimediaMessage($appid, $appkey, $template_id, $mobile_content, $ip, $msg_id);
        return $result;
    }

    /**
     * @api              {post} / 彩信回执
     * @apiDescription   multimediaReceive
     * @apiGroup         index_send
     * @apiName          multimediaReceive
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/multimediaReceive
     * @author rzc
     */
    public function multimediaReceive()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        $result = $this->app->send->multimediaReceive($appid, $appkey);
        return $result;
    }

    /**
     * @api              {post} / 创蓝彩信回调接口
     * @apiDescription   chuangLanMmsCallBack
     * @apiGroup         index_send
     * @apiName          chuangLanMmsCallBack
     * @apiParam (入参) {String} code 返回码
     * @apiParam (入参) {String} desc 状态说明
     * @apiParam (入参) {String} ext_id 彩信任务id
     * @apiParam (入参) {String} phone 号码
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/chuangLanMmsCallBack
     * @author rzc
     */
    public function chuangLanMmsCallBack()
    {
        $code   = trim($this->request->post('code')); //返回码
        $desc  = trim($this->request->post('desc')); //状态说明
        $task_id  = trim($this->request->post('ext_id')); //登陆密码
        $phone  = trim($this->request->post('phone')); //登陆密码
        $result = $this->app->send->chuangLanMmsCallBack($code, $desc, $task_id, $phone);
        if ($result == 'OK') {
            echo 'OK';
            exit;
        } else {
            echo 'error';
            exit;
        }
    }

    /**
     * @api              {post} / 创蓝彩信SFTP回调接口
     * @apiDescription   chuangLanMmsSftpCallBack
     * @apiGroup         index_send
     * @apiName          chuangLanMmsSftpCallBack
     * @apiParam (入参) {String} code 返回码
     * @apiParam (入参) {String} desc 状态说明
     * @apiParam (入参) {String} ext_id 彩信任务id
     * @apiParam (入参) {String} phone 号码
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/chuangLanMmsSftpCallBack
     * @author rzc
     */
    public function chuangLanMmsSftpCallBack()
    {
        $code   = trim($this->request->post('code')); //返回码
        $desc  = trim($this->request->post('desc')); //状态说明
        $task_id  = trim($this->request->post('ext_id')); //登陆密码
        $phone  = trim($this->request->post('phone')); //登陆密码
        $result = $this->app->send->chuangLanMmsSftpCallBack($code, $desc, $task_id, $phone);
        if ($result == 'OK') {
            echo 'OK';
            exit;
        } else {
            echo 'error';
            exit;
        }
    }

     /**
     * @api              {post} / 薇格彩信平台回调接口
     * @apiDescription   chuangLanMmsSftpCallBack
     * @apiGroup         index_send
     * @apiName          chuangLanMmsSftpCallBack
     * @apiParam (入参) {String} code 返回码
     * @apiParam (入参) {String} desc 状态说明
     * @apiParam (入参) {String} ext_id 彩信任务id
     * @apiParam (入参) {String} phone 号码
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/chuangLanMmsSftpCallBack
     * @author rzc
     */
    public function weigeMmsCallBack()
    {
        // print_r($this->request->post());die;
       $receiptBack = $this->request->post();
        $result = $this->app->send->weigeMmsCallBack($receiptBack);
        if ($result == 'SUCCESS') {
            echo 'SUCCESS';
            exit;
        } else {
            echo 'error';
            exit;
        }
    }

    /**
     * @api              {post} / 空号检测接口
     * @apiDescription   numberDetection
     * @apiGroup         index_send
     * @apiName          numberDetection
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} phone 号码, 多个号码用半角,隔开
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/numberDetection
     * @author rzc
     */
    public function numberDetection()
    {
        $mobile         = trim($this->request->post('mobile'));

        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        // return $this->encrypt($mobile, $secret_id);
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        $result = $this->app->send->numberDetection($appid, $appkey, $mobile);
        return $result;
    }

    /**
     * @api              {post} / 彩信信上行查询
     * @apiDescription   upMmsGoing
     * @apiGroup         index_send
     * @apiName          upMmsGoing
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/upMmsGoing
     * @author rzc
     */
    public function upMmsGoing()
    {
        $appid  = trim($this->request->post('appid')); //登录名
        $appkey = trim($this->request->post('appkey')); //登陆密码
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        $result = $this->app->send->upMmsGoing($appid, $appkey);
        return $result;
    }

    /**
     * @api              {post} / 创蓝彩信信上行回调
     * @apiDescription   upGoingFCL
     * @apiGroup         index_send
     * @apiName          upGoingFCL
     * @apiParam (入参) {String} account account 账户
     * @apiParam (入参) {String} phone 上行手机号 
     * @apiParam (入参) {String} msg 上行内容 
     * @apiParam (入参) {String} moTime 上行时间 
     * @apiParam (入参) {String} extendCode 上行端口
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/upGoingFCL
     * @author rzc
     */
    public function upGoingFCL()
    {
        $account  = trim($this->request->post('account')); //登录名
        $phone = trim($this->request->post('phone')); //登陆密码
        $msg = trim($this->request->post('msg')); //登陆密码
        $moTime = trim($this->request->post('moTime')); //登陆密码
        $extendCode = trim($this->request->post('extendCode')); //登陆密码
        $result = $this->app->send->upGoingForChuangLan($account, $phone, $msg, $moTime, $extendCode);
        if ($result == 'OK') {
            echo 'OK';
            exit;
        } else {
            echo 'error';
            exit;
        }
    }

    /**
     * @api              {post} / 视频短信模板报备接口
     * @apiDescription   supMessageTemplateSignatureReport
     * @apiGroup         index_send
     * @apiName          supMessageTemplateSignatureReport
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {Array} content_data 短信内容
     * @apiParam (入参) {String} title 模板主题
     * @apiParam (入参) {String} name 模板别名
     * @apiParam (入参) {String} signature 签名
     * @apiParam (content_data) {String} content 内容
     * @apiParam (content_data) {String} type 类型 1,文本;2,图片;3,音频;4,视频
     * @apiParam (content_data) {String} num 顺序 按自然数排列 从小到大 必传
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:模板内容为空 / 3002:模板主题为空 / 3003:别名为空 / 3004:签名为空 / 3005:该账户没有此项服务 / 3006:余额不足 / 3007:title 短信标题不能为空 / 3008:资源内容为空 / 3009:彩信文件长度超过100KB或内容为空 / 3010 图片未上传过 / 3011:服务器错误
     * @apiSampleRequest /index/send/supMessageTemplateSignatureReport
     * @author rzc
     */
    public function supMessageTemplateSignatureReport()
    {
        $appid          = trim($this->request->post('appid')); //登录名
        $appkey         = trim($this->request->post('appkey')); //登陆密码
        $title          = trim($this->request->post('title')); //短信标题
        $name          = trim($this->request->post('name')); //短信标题
        $signature          = trim($this->request->post('signature')); //短信标题
        $content_data   = $this->request->post('content_data'); //短信内容
        // $content_data   = json_decode($content_data, true);
        // print_r($content_data);die;
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($content_data)) {
            return ['code' => '3001'];
        }
        if (empty($title)) {
            return ['code' => '3002'];
        }
        if (empty($signature)) {
            return ['code' => '3004'];
        }
        if (empty($name)) {
            return ['code' => '3003', 'msg' => '别名为空'];
        }
        $result = $this->app->send->supMessageTemplateSignatureReport($appid, $appkey, $content_data, $title, $signature, $name);
        return $result;
    }

        /**
     * @api              {post} / 模板视频短信提交
     * @apiDescription   submitTemplateSupMessage
     * @apiGroup         index_send
     * @apiName          submitTemplateSupMessage
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} [msg_id] 客户提交msg_id非必填
     * @apiParam (入参) {String} template_id 通过接口或者平台报备的template_id
     * @apiParam (入参) {String} mobile_content 电话号码集合,多个用','，分开，最多支持50000
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误 / 3001:template_id为空 / 3002:手机号码为空
     * @apiSampleRequest /index/send/submitTemplateSupMessage
     * @author rzc
     */
    public function submitTemplateSupMessage()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $template_id  = trim($this->request->post('template_id'));
        $mobile_content = trim($this->request->post('mobile_content')); //接收手机号码
        $ip             = trim($this->request->ip());
        $msg_id  = trim($this->request->post('msg_id'));
        $mobile_content = explode(',', $mobile_content); //短信数组
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($template_id)) {
            return ['code' => '3001'];
        }
        if (empty($mobile_content)) {
            return ['code' => '3002'];
        }
        $result = $this->app->send->submitTemplateSupMessage($appid, $appkey, $template_id, $mobile_content, $ip, $msg_id);
        return $result;
    }

    /**
     * @api              {post} / 三体视频短信回执回调接口
     * @apiDescription   sanTiSupMessageCallBack
     * @apiGroup         index_send
     * @apiName          sanTiSupMessageCallBack
     * @apiParam (入参) {String} code 返回码
     * @apiParam (入参) {String} desc 状态说明
     * @apiParam (入参) {String} ext_id 彩信任务id
     * @apiParam (入参) {String} phone 号码
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/sanTiSupMessageCallBack
     * @author rzc
     */
    public function sanTiSupMessageCallBack()
    {
        $sign   = trim($this->request->post('sign')); //返回码
        $report  = $this->request->post('report'); //状态说明
        // $task_id  = trim($this->request->post('ext_id')); //登陆密码
        // $phone  = trim($this->request->post('phone')); //登陆密码
        
        $report = str_replace('&quot;','"',$report);
        // print_r($report);die;
        $report = json_decode($report,true);
        
        $result = $this->app->send->sanTiSupMessageCallBack($sign, $report);
        if ($result == 'OK') {
            echo 'SUCCESS';
            exit;
        } else {
            echo 'FAIL';
            exit;
        }
    }

    /**
     * @api              {post} / 视频短信回执
     * @apiDescription   supmessageReceive
     * @apiGroup         index_send
     * @apiName          supmessageReceive
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /index/send/supmessageReceive
     * @author rzc
     */
    public function supmessageReceive()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        $result = $this->app->send->supmessageReceive($appid, $appkey);
        return $result;
    }
}
