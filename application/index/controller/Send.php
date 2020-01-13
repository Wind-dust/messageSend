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
     * @apiParam (入参) {String} mobile 接收手机号码
     * @apiParam (入参) {String} dstime 发送时间
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:手机号格式错误 / 3002:单批次手机号码为空 / 3003:dstime发送时间格式错误 / 3004:预约发送时间小于当前时间 / 3005:短信内容为空或者短信内容超出500字符 / 3006:签名长度为2~8个字 / 3007:task_name 短信标题不能为空
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
        if (mb_strpos($Content, '】') - mb_strpos($Content, '【') < 2 || mb_strpos($Content, '】') - mb_strpos($Content, '【') > 10) {
            return ['code' => '3006'];
        }
        // print_r($task_name);die;
        // if (empty($task_name)) {
        //     return ['code' => '3007'];
        // }
        $result = $this->app->send->getSmsMarketingTask($appid, $appkey, $Content, $Mobiles, $Dstime, $ip, $task_name, $signature_id);
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
     * @apiParam (入参) {String} content 短信内容
     * @apiParam (入参) {String} mobile 接收手机号码
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误 / 3001:手机号格式错误 / 3002:短信内容为空或者短信内容超出500字符 / 3003:签名长度为2~8个字 / 3004:该账户已被停用 / 3005:该账户没有此项服务 / 3006:短信余额不足，请先充值 / 3009 :系统错误
     * @apiSampleRequest /index/send/getSmsBuiness
     * @author rzc
     */
    public function getSmsBuiness()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
        $Content = trim($this->request->post('content')); //短信内容
        $Mobile  = trim($this->request->post('mobile')); //接收手机号码
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
        if (empty($Content) || strlen($Content) > 500) {
            return ['code' => '3002'];
        }
        // echo mb_strpos($Content,'】') - mb_strpos($Content,'【');die;
        if (mb_strpos($Content, '】') - mb_strpos($Content, '【') < 2 || mb_strpos($Content, '】') - mb_strpos($Content, '【') > 8) {
            return ['code' => '3003'];
        }
        $result = $this->app->send->getSmsBuiness($appid, $appkey, $Content, $Mobiles, $ip, $signature_id);
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
     * @api              {post} / 上行查询
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
     * @apiParam (入参) {String} [send_time] 预约发送时间 示例： 2019-12-08 17:02:25
     * @apiParam (content_data) {String} content 单个帧文字内容
     * @apiParam (content_data) {String} image_path 单个帧图片路径,必须已上传的文件
     * @apiParam (content_data) {String} num 顺序 按自然数排列 从小到大 必传
     * @apiParam (content_data) {String} name 对应帧数 如第一帧
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:手机号格式错误 / 3002:单批次手机号码为空 / 3003:send_time发送时间格式错误 / 3004:预约发送时间小于当前时间 / 3005:该账户没有此项服务 / 3006:余额不足 / 3007:title 短信标题不能为空 / 3008:无效的图片 / 3009:彩信文件长度超过100KB或内容为空 / 3010 图片未上传过 / 3011:服务器错误
     * @apiSampleRequest /index/send/getSmsMultimediaMessageTask
     * @author rzc
     */
    public function getSmsMultimediaMessageTask()
    {
        $appid          = trim($this->request->post('appid')); //登录名
        $appkey         = trim($this->request->post('appkey')); //登陆密码
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
        $result = $this->app->send->getSmsMultimediaMessageTask($appid, $appkey, $content_data, $mobile_content, $send_time, $ip, $title);
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
        $ip       = trim($this->request->ip());
        $result = $this->app->send->submitBatchCustomBusiness($appid, $appkey, $template_id, $connect, $ip, $signature_id);
        return $result;
    }

    /**
     * @api              {post} / 批量自定义短信提交接口（营销）
     * @apiDescription   submitBatchCustomBusiness
     * @apiGroup         index_send
     * @apiName          submitBatchCustomBusiness
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} signature_id 已报备签名ID
     * @apiParam (入参) {String} template_id template_id报备的template_id 如果传template_id 则内容替换为模板中内容变量
     * @apiParam (入参) {String} connect 组合包内容(template组合方式：变量,变量,...:手机号;变量,变量,...:手机号;...  无模板组合方式:内容:手机号;内容:手机号;...)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户名或密码错误 / 3001:提交内容为空
     * @apiSampleRequest /index/send/submitBatchCustomBusiness
     * @return array
     * @author rzc
     */

    public function submitBatchCustomMarketing()
    {
        $appid   = trim($this->request->post('appid')); //登录名
        $appkey  = trim($this->request->post('appkey')); //登陆密码
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
        $ip       = trim($this->request->ip());
        $result = $this->app->send->submitBatchCustomMarketing($appid, $appkey, $template_id, $connect, $ip,$signature_id);
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
}
