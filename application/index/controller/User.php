<?php

namespace app\index\controller;

use app\index\MyController;

class User extends MyController
{
    protected $beforeActionList = [
        //        'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => 'login,quickLogin,userRegistered,resetPassword,sendVercode,wxaccredit,wxregister,getUserSupMessageTemplateStatus'], //除去getFirstCate其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 用户注册
     * @apiDescription   userRegistered
     * @apiGroup         index_user
     * @apiName          userRegistered
     * @apiParam (入参) {String} nick_name 用户姓名
     * @apiParam (入参) {String} company_name 公司名称
     * @apiParam (入参) {Number} user_type 用户类型1.个人账户2.企业账户
     * @apiParam (入参) {String} passwd 密码
     * @apiParam (入参) {String} mobile 手机号
     * @apiParam (入参) {String} email 邮箱
     * @apiParam (入参) {String} vercode 验证码
     * @apiSuccess (返回) {String} code 200:成功 / 3000:手机号格式错误 / 3002:passwd密码强度不够//6-16个字符，至少1个字母和1个数字，其他可以是任意字符 / 3003:邮箱格式错误 / 3004:验证码错误 / 3005:该手机号已注册用户 / 3006:用户类型错误 / 3007:nick_name不能为空
     * @apiSampleRequest /index/user/userRegistered
     * @author rzc
     */
    public function userRegistered()
    {
        $apiName   = classBasename($this) . '/' . __function__;
        $nick_name = trim($this->request->post('nick_name'));
        $company_name = trim($this->request->post('company_name'));
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
        // if (checkEmail($email) === false) {
        //     return ['code' => '3003'];
        // }
        if (!in_array($user_type, [1, 2])) {
            return ['code' => '3006'];
        }
        if (empty($nick_name)) {
            return ['code' => '3007'];
        }
        $result = $this->app->user->userRegistered($nick_name, $user_type, $passwd, $mobile, $email, $vercode, $company_name);
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
    public function resetPassword()
    {
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
    public function sendVercode()
    {
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
    public function getUser()
    {
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
    public function quickLogin()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $mobile  = trim($this->request->post('mobile'));
        $vercode = trim($this->request->post('vercode'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001']; //手机号格式错误
        }
        if (checkVercode($vercode) === false) {
            return ['code' => '3004'];
        }

        $result = $this->app->user->quickLogin($mobile, $vercode);
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
     * @apiParam (入参) {String} nick_name 账户名
     * @apiParam (入参) {String} password 密码
     * @apiSuccess (返回) {String} code 200:成功  3001:账户名为空 / 3002:账号不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/login
     * @return array
     * @author rzc
     */
    public function login()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $nick_name   = trim($this->request->post('nick_name'));
        $password = trim($this->request->post('password'));
        if (empty($nick_name)) {
            return ['code' => '3001']; //手机号格式错误
        }
        if (checkPassword($password) === false) {
            return ['code' => '3005'];
        }
        $res = $this->app->user->login($nick_name, $password);
        $this->apiLog($apiName, [$nick_name, $password], $res['code'], '');
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
     * @apiParam (入参) {String} passwd 密码
     * @apiParam (入参) {String} [email] 手机号
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:手机号格式错误 / 3002:缺少con_id / 3003:conId有误查不到uid
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSuccess (data) {String} id 用户加密id
     * @apiSampleRequest /index/user/apportionSonUser
     * @return array
     * @author rzc
     */

    public function apportionSonUser()
    {
        $apiName   = classBasename($this) . '/' . __function__;
        $conId     = trim($this->request->post('con_id'));
        $nick_name = trim($this->request->post('nick_name'));
        $company_name = trim($this->request->post('company_name'));
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
        $result = $this->app->user->apportionSonUser($conId, $nick_name, $user_type, $passwd, $mobile, $email, $company_name);
        $this->apiLog($apiName, [$conId, $nick_name, $user_type, $passwd, $mobile, $email], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 设置子账户用户服务项目
     * @apiDescription   seetingUserEquities
     * @apiGroup         index_user
     * @apiName          seetingUserEquities
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Int} nick_name 账户名
     * @apiParam (入参) {Int} business_id 服务id
     * @apiParam (入参) {Int} [agency_price] 代理价格，默认统一代理商服务价格
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号格式错误 / 3002:agency_price格式错误 / 3003:母账户无该项服务业务 / 3004:代理价格不能低于服务商价格 / 3005:该服务已添加 / 3006:子账户服务无法设置 / 3007:business_id格式错误或者不存在 / 3008:该账户非此账户的子账户
     * @apiSampleRequest /index/user/seetingUserEquities
     * @author rzc
     */
    public function seetingUserEquities()
    {
        $conId        = trim($this->request->post('con_id'));
        $nick_name       = trim($this->request->post('nick_name'));
        $business_id  = trim($this->request->post('business_id'));
        $agency_price = trim($this->request->post('agency_price'));
        if (!empty($agency_price) && !is_numeric($agency_price)) {
            return ['code' => '3002'];
        }
        if (empty($nick_name)) {
            return ['code' => '3001']; //手机号格式错误
        }
        $agency_price = floatval($agency_price);
        $result = $this->app->user->seetingUserEquities($conId, $nick_name, $business_id, $agency_price);
        // $this->apiLog($apiName, [$cmsConId, $uid, $business_id, $agency_price], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 账户资质提交
     * @apiDescription   recordUserQualification
     * @apiGroup         index_user
     * @apiName          recordUserQualification
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} id
     * @apiParam (入参) {String} company_name 主办单位或者主办人全称
     * @apiParam (入参) {Int} company_type 主办单位性质:1,国防机构;2,政府机关;3,事业单位;4,企业;5,个人;6社会团体;7,民办非企业单位;8,基金会;9,律师执业机构;10,外国在华文化中心;11,群众性团体组织;12,司法鉴定机构;13,宗教团体;14,境外机构;15,医疗机构;16,公证机构
     * @apiParam (入参) {Int} company_certificate_type 主办单位证件类型:1,营业执照（个人或企业）;3,组织机构代码证;4,事业单位法人证书;5,部队代号;9,组织机构代码证;12,组织机构代码证;13,统一社会信用代码证书;23,军队单位对外有偿服务许可证;27,外国企业常驻代表机构登记证
     * @apiParam (入参) {String} company_certificate_num 主办单位证件号码
     * @apiParam (入参) {Int} province_id 省份id
     * @apiParam (入参) {Int} city_id 城市id
     * @apiParam (入参) {Int} county_id 地区id
     * @apiParam (入参) {String} organizers_name 主办单位或主办人名称
     * @apiParam (入参) {String} identity_address 主办单位证件住所
     * @apiParam (入参) {String} mailingAddress_address 主办单位通讯地址(地区级)
     * @apiParam (入参) {String} user_supp_address 主办单位通讯地址(街道门牌号级)
     * @apiParam (入参) {String} investor 投资人或主管单位
     * @apiParam (入参) {String} entity_responsible_person_name 负责人姓名
     * @apiParam (入参) {Int} entity_responsible_person_identity_types 负责人证件类型(参照【主办单位证件类型】)
     * @apiParam (入参) {Int} entity_responsible_person_identity_num 负责人证件号码
     * @apiParam (入参) {Int} entity_responsible_person_mobile_phone 联系方式1
     * @apiParam (入参) {Int} entity_responsible_person_phone 联系方式2
     * @apiParam (入参) {Int} entity_responsible_person_msn 应急联系电话
     * @apiParam (入参) {Int} entity_responsible_person_email 电子邮件地址
     * @apiParam (入参) {Int} [entity_remark] 留言
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id不存在或者不为数字 / 3002:price格式错误 / 3003:price不能小于0 / 3004:登录失败
     * @apiSampleRequest /index/user/recordUserQualification
     * @return array
     * @author rzc
     */
    public function recordUserQualification()
    {
        $apiName                                  = classBasename($this) . '/' . __function__;
        $conId                                    = trim($this->request->post('con_id'));
        $company_name                             = trim($this->request->post('company_name'));
        $company_type                             = trim($this->request->post('company_type'));
        $company_certificate_type                 = trim($this->request->post('company_certificate_type'));
        $company_certificate_num                  = trim($this->request->post('company_certificate_num'));
        $province_id                              = trim($this->request->post('province_id'));
        $city_id                                  = trim($this->request->post('city_id'));
        $county_id                                = trim($this->request->post('county_id'));
        $organizers_name                          = trim($this->request->post('organizers_name'));
        $identity_address                         = trim($this->request->post('identity_address'));
        $mailingAddress_address                   = trim($this->request->post('mailingAddress_address'));
        $user_supp_address                        = trim($this->request->post('user_supp_address'));
        $investor                                 = trim($this->request->post('investor'));
        $entity_responsible_person_name           = trim($this->request->post('entity_responsible_person_name'));
        $entity_responsible_person_identity_types = trim($this->request->post('entity_responsible_person_identity_types'));
        $entity_responsible_person_identity_num   = trim($this->request->post('entity_responsible_person_identity_num'));
        $entity_responsible_person_mobile_phone   = trim($this->request->post('entity_responsible_person_mobile_phone'));
        $entity_responsible_person_phone          = trim($this->request->post('entity_responsible_person_phone'));
        $entity_responsible_person_msn            = trim($this->request->post('entity_responsible_person_msn'));
        $entity_responsible_person_email          = trim($this->request->post('entity_responsible_person_email'));
        $entity_remark                            = trim($this->request->post('entity_remark'));

        $data = [];
        $data = [
            'company_name'                             => $company_name,
            'company_type'                             => $company_type,
            'company_certificate_type'                 => $company_certificate_type,
            'company_certificate_num'                  => $company_certificate_num,
            'province_id'                              => $province_id,
            'city_id'                                  => $city_id,
            'county_id'                                => $county_id,
            'organizers_name'                          => $organizers_name,
            'identity_address'                         => $identity_address,
            'mailingAddress_address'                   => $mailingAddress_address,
            'user_supp_address'                        => $user_supp_address,
            'investor'                                 => $investor,
            'entity_responsible_person_name'           => $entity_responsible_person_name,
            'entity_responsible_person_identity_types' => $entity_responsible_person_identity_types,
            'entity_responsible_person_identity_num'   => $entity_responsible_person_identity_num,
            'entity_responsible_person_mobile_phone'   => $entity_responsible_person_mobile_phone,
            'entity_responsible_person_phone'          => $entity_responsible_person_phone,
            'entity_responsible_person_msn'            => $entity_responsible_person_msn,
            'entity_responsible_person_email'          => $entity_responsible_person_email,
            'entity_remark'                            => $entity_remark,
        ];
        $result = $this->app->user->recordUserQualification($conId, $data);
        // $this->apiLog($apiName, [$conId, $nick_name, $user_type, $passwd, $mobile, $email], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 查询账户服务项目
     * @apiDescription   getUserEquitises
     * @apiGroup         index_user
     * @apiName          getUserEquitises
     * @apiParam (入参) {String} con_id
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:账号不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserEquitises
     * @return array
     * @author rzc
     */
    public function getUserEquitises()
    {
        $conId        = trim($this->request->post('con_id'));
        $result = $this->app->user->getUserEquitises($conId);
        // $this->apiLog($apiName, [$conId, $nick_name, $user_type, $passwd, $mobile, $email], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 完善信息
     * @apiDescription   completeInformation
     * @apiGroup         index_user
     * @apiName          completeInformation
     * @apiParam (入参) {String} businesslicense 营业执照图片地址
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} logo logo图片地址
     * @apiSuccess (返回) {String} code 200:成功  3001:logo为空或者未上传成功/ 3002:businesslicense为空或者未上传成功 / 3003:用户不存在 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/completeInformation
     * @return array
     * @author rzc
     */
    public function completeInformation()
    {
        $conId        = trim($this->request->post('con_id'));
        $businesslicense        = trim($this->request->post('businesslicense'));
        $logo        = trim($this->request->post('logo'));
        if (empty($logo)) {
            return ['code' => '3001'];
        }
        if (empty($businesslicense)) {
            return ['code' => '3002'];
        }
        $result = $this->app->user->completeInformation($conId, $businesslicense, $logo);
        // $this->apiLog($apiName, [$conId, $nick_name, $user_type, $passwd, $mobile, $email], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 查询用户提交任务(营销)
     * @apiDescription   getUserSubmitTask
     * @apiGroup         index_user
     * @apiName          getUserSubmitTask
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} page 
     * @apiSuccess (返回) {String} code 200:成功   3003:用户不存在 / 
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserSubmitTask
     * @return array
     * @author rzc
     */
    public function getUserSubmitTask()
    {
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->user->getUserSubmitTask($page, $pageNum, $ConId);
        return $result;
    }

    /**
     * @api              {post} / 查询用户提交任务详情(营销)
     * @apiDescription   getUserSubmitTaskInfo
     * @apiGroup         index_user
     * @apiName          getUserSubmitTaskInfo
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} id 任务id
     * @apiSuccess (返回) {String} code 200:成功  3001:logo为空或者未上传成功/ 3002:businesslicense为空或者未上传成功 / 3003:用户不存在 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserSubmitTaskInfo
     * @return array
     * @author rzc
     */
    public function getUserSubmitTaskInfo()
    {
        $ConId = trim($this->request->post('con_id'));
        $id = trim($this->request->post('id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->user->getUserSubmitTaskInfo($page, $pageNum, $ConId, $id);
        return $result;
    }

    /**
     * @api              {post} / 查询用户提交任务(行业)
     * @apiDescription   getUserBusinessSubmitTask
     * @apiGroup         index_user
     * @apiName          getUserBusinessSubmitTask
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} page 
     * @apiSuccess (返回) {String} code 200:成功   3003:用户不存在 / 
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserBusinessSubmitTask
     * @return array
     * @author rzc
     */
    public function getUserBusinessSubmitTask()
    {
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->user->getUserBusinessSubmitTask($page, $pageNum, $ConId);
        return $result;
    }

    /**
     * @api              {post} / 查询用户提交任务详情(行业)
     * @apiDescription   getUserBusinessSubmitTaskInfo
     * @apiGroup         index_user
     * @apiName          getUserBusinessSubmitTaskInfo
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} id 任务id
     * @apiSuccess (返回) {String} code 200:成功  3001:logo为空或者未上传成功/ 3002:businesslicense为空或者未上传成功 / 3003:用户不存在 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserBusinessSubmitTaskInfo
     * @return array
     * @author rzc
     */
    public function getUserBusinessSubmitTaskInfo()
    {
        $ConId = trim($this->request->post('con_id'));
        $id = trim($this->request->post('id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->user->getUserBusinessSubmitTaskInfo($page, $pageNum, $ConId, $id);
        return $result;
    }

    /**
     * @api              {post} / 查询用户提交任务(彩信)
     * @apiDescription   getUserMultimediaMessageTask
     * @apiGroup         index_user
     * @apiName          getUserMultimediaMessageTask
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} title 标题模糊查询 
     * @apiParam (入参) {String} start_time 创建开始时间
     * @apiParam (入参) {String} end_time 创建开始时间
     * @apiSuccess (返回) {String} code 200:成功   3003:用户不存在 / 
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserMultimediaMessageTask
     * @return array
     * @author rzc
     */
    public function getUserMultimediaMessageTask()
    {
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $title  = trim($this->request->post('title'));
        $start_time  = trim($this->request->post('start_time'));
        $end_time  = trim($this->request->post('end_time'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        if (strtotime($start_time) == false && !empty($start_time)) {
            return ['code' => '3003'];
        }
        if (strtotime($end_time)  == false && !empty($end_time)) {
            return ['code' => '3004'];
        }
        $result = $this->app->user->getUserMultimediaMessageTask($page, $pageNum, $ConId, $start_time, $end_time, $title);
        return $result;
    }

    /**
     * @api              {post} / 查询用户提交任务详情(彩信)
     * @apiDescription   getUserMultimediaMessageTaskInfo
     * @apiGroup         index_user
     * @apiName          getUserMultimediaMessageTaskInfo
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} title 标题模糊查询 
     * @apiParam (入参) {String} start_time 创建开始时间
     * @apiParam (入参) {String} end_time 创建开始时间
     * @apiSuccess (返回) {String} code 200:成功   3003:用户不存在 / 
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserMultimediaMessageTaskInfo
     * @return array
     * @author rzc
     */
    public function getUserMultimediaMessageTaskInfo()
    {
        $ConId = trim($this->request->post('con_id'));
        $id = trim($this->request->post('id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->user->getUserMultimediaMessageTaskInfo($page, $pageNum, $ConId, $id);
        return $result;
    }

    /**
     * @api              {post} / 获取子账户列表
     * @apiDescription   getUserSonAccount
     * @apiGroup         index_user
     * @apiName          getUserSonAccount
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} pageNum 
     * @apiSuccess (返回) {String} code 200:成功  3001:logo为空或者未上传成功/ 3002:businesslicense为空或者未上传成功 / 3003:用户不存在 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserSonAccount
     * @return array
     * @author rzc
     */
    public function getUserSonAccount()
    {
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->user->getUserSonAccount($page, $pageNum, $ConId);
        return $result;
    }

    /**
     * @api              {post} / 获取已报备的模板
     * @apiDescription   getUserModel
     * @apiGroup         index_user
     * @apiName          getUserModel
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} business_id 5,6,7,9 
     * @apiSuccess (返回) {String} code 200:成功  3001:logo为空或者未上传成功/ 3002:business错误 / 3003:用户不存在 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserModel
     * @return array
     * @author rzc
     */
    public function getUserModel()
    {
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $business_id  = trim($this->request->post('business_id'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        if (!in_array($business_id, [5, 6, 7, 9])) {
            return ['code' => '3002'];
        }
        $result = $this->app->user->getUserModel($page, $pageNum, $ConId, $business_id);
        return $result;
    }

    /**
     * @api              {post} / 获取已报备的签名
     * @apiDescription   getUserSignature
     * @apiGroup         index_user
     * @apiName          getUserSignature
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} business_id 5,6,7,9 
     * @apiSuccess (返回) {String} code 200:成功  3001:logo为空或者未上传成功/ 3002:business错误 / 3003:用户不存在 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserSignature
     * @return array
     * @author rzc
     */
    public function getUserSignature()
    {
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $business_id  = trim($this->request->post('business_id'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        if (!in_array($business_id, [5, 6, 7, 9])) {
            return ['code' => '3002'];
        }
        $result = $this->app->user->getUserSignature($page, $pageNum, $ConId, $business_id);
        return $result;
    }

    /**
     * @api              {post} / 获取已绑定的签名
     * @apiDescription   getUserDevelopCode
     * @apiGroup         index_user
     * @apiName          getUserDevelopCode
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} business_id 5,6,7,9 
     * @apiSuccess (返回) {String} code 200:成功  3001:logo为空或者未上传成功/ 3002:business错误 / 3003:用户不存在 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getUserDevelopCode
     * @return array
     * @author rzc
     */
    public function getUserDevelopCode()
    {
        $ConId = trim($this->request->post('con_id'));
        $business_id  = trim($this->request->post('business_id'));
        if (!in_array($business_id, [5, 6, 7, 9])) {
            return ['code' => '3002'];
        }
        $result = $this->app->user->getUserDevelopCode($ConId, $business_id);
        return $result;
    }

    /**
     * @api              {post} / 获取用户统计年度
     * @apiDescription   getUserStatisticsYear
     * @apiGroup         index_user
     * @apiName          getUserStatisticsYear
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} business_id 类型
     * @apiParam (入参) {String} [start_timekey] 开始时间标记（如 2019）
     * @apiParam (入参) {String} [end_timekey] 开始时间标记（如 2020）
     * @apiSuccess (返回) {String} code 200:成功  
     * @apiSuccess (返回) {Array} data 
     * @apiSuccess (data) {string} id 
     * @apiSuccess (data) {string} uid 
     * @apiSuccess (data) {string} business_id 服务类型 5，营销；6，行业 7，网贷服务，8图文彩信 9，游戏 
     * @apiSuccess (data) {string} timekey 时间标记
     * @apiSuccess (data) {string} num 总数
     * @apiSuccess (data) {string} success 成功总数
     * @apiSuccess (data) {string} unknown 未知总数
     * @apiSuccess (data) {string} default 失败总数
     * @apiSuccess (data) {string} ratio 成功比例
     * @apiSuccess (data) {string} update_time 更新时间
     * @apiSuccess (data) {string} create_time 初次入库时间
     * @apiSampleRequest /index/user/getUserStatisticsYear
     * @return array
     * @author rzc
     */
    public function getUserStatisticsYear()
    {
        $ConId = trim($this->request->post('con_id'));
        $start_timekey = trim($this->request->post('start_timekey'));
        $end_timekey = trim($this->request->post('end_timekey'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $business_id  = trim($this->request->post('business_id'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        intval($business_id);
        if (!empty($start_timekey) && !empty($end_timekey) && ($end_timekey < $start_timekey)) {
            return ['code' => '3002', 'Msg' => '结束时间段不能小于开始时间段'];
        }
        $result = $this->app->user->getUserStatisticsYear($page, $pageNum, $ConId, $start_timekey, $end_timekey, $business_id);
        return $result;
    }
    /**
     * @api              {post} / 获取用户统计(月度)
     * @apiDescription   getUserStatisticsMonth
     * @apiGroup         index_user
     * @apiName          getUserStatisticsMonth
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} business_id 类型
     * @apiParam (入参) {String} [start_timekey] 开始时间标记（如 202001）
     * @apiParam (入参) {String} [end_timekey] 开始时间标记（如 202011）
     * @apiSuccess (返回) {String} code 200:成功 
     * @apiSuccess (返回) {Array} data 
     * @apiSuccess (data) {string} id 
     * @apiSuccess (data) {string} uid 
     * @apiSuccess (data) {string} business_id 服务类型 5，营销；6，行业 7，网贷服务，8图文彩信 9，游戏 
     * @apiSuccess (data) {string} timekey 时间标记
     * @apiSuccess (data) {string} num 总数
     * @apiSuccess (data) {string} success 成功总数
     * @apiSuccess (data) {string} unknown 未知总数
     * @apiSuccess (data) {string} default 失败总数
     * @apiSuccess (data) {string} ratio 成功比例
     * @apiSuccess (data) {string} update_time 更新时间
     * @apiSuccess (data) {string} create_time 初次入库时间
     * @apiSampleRequest /index/user/getUserStatisticsMonth
     * @return array
     * @author rzc
     */
    public function getUserStatisticsMonth()
    {
        $ConId = trim($this->request->post('con_id'));
        $start_timekey = trim($this->request->post('start_timekey'));
        $end_timekey = trim($this->request->post('end_timekey'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $business_id  = trim($this->request->post('business_id'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        intval($business_id);
        if (!empty($start_timekey) && !empty($end_timekey) && ($end_timekey < $start_timekey)) {
            return ['code' => '3002', 'Msg' => '结束时间段不能小于开始时间段'];
        }
        $result = $this->app->user->getUserStatisticsMonth($page, $pageNum, $ConId, $start_timekey, $end_timekey, $business_id);
        return $result;
    }

    /**
     * @api              {post} / 获取用户统计（日）
     * @apiDescription   getUserStatisticsDay
     * @apiGroup         index_user
     * @apiName          getUserStatisticsDay
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} business_id 类型
     * @apiParam (入参) {String} [start_timekey] 开始时间标记（如 20200101）
     * @apiParam (入参) {String} [end_timekey] 开始时间标记（如 20200131）
     * @apiSuccess (返回) {String} code 200:成功 
     * @apiSuccess (返回) {Array} data 
     * @apiSuccess (data) {string} id 
     * @apiSuccess (data) {string} uid 
     * @apiSuccess (data) {string} business_id 服务类型 5，营销；6，行业 7，网贷服务，8图文彩信 9，游戏 
     * @apiSuccess (data) {string} timekey 时间标记
     * @apiSuccess (data) {string} num 总数
     * @apiSuccess (data) {string} success 成功总数
     * @apiSuccess (data) {string} unknown 未知总数
     * @apiSuccess (data) {string} default 失败总数
     * @apiSuccess (data) {string} ratio 成功比例
     * @apiSuccess (data) {string} update_time 更新时间
     * @apiSuccess (data) {string} create_time 初次入库时间
     * @apiSampleRequest /index/user/getUserStatisticsDay
     * @return array
     * @author rzc
     */
    public function getUserStatisticsDay()
    {
        $ConId = trim($this->request->post('con_id'));
        $start_timekey = trim($this->request->post('start_timekey'));
        $end_timekey = trim($this->request->post('end_timekey'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $business_id  = trim($this->request->post('business_id'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        intval($business_id);
        if (!empty($start_timekey) && !empty($end_timekey) && ($end_timekey < $start_timekey)) {
            return ['code' => '3002', 'Msg' => '结束时间段不能小于开始时间段'];
        }
        $result = $this->app->user->getUserStatisticsDay($page, $pageNum, $ConId, $start_timekey, $end_timekey, $business_id);
        return $result;
    }

    /**
     * @api              {post} / 条数划拨
     * @apiDescription   allocateAgentNumber
     * @apiGroup         index_user
     * @apiName          allocateAgentNumber
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} nick_name 划拨对象名称
     * @apiParam (入参) {String} number 划拨数量
     * @apiParam (入参) {String} business_id 划拨服务类型
     * @apiSuccess (返回) {String} code 200:成功 
     * @apiSampleRequest /index/user/allocateAgentNumber
     * @return array
     * @author rzc
     */
    public function allocateAgentNumber()
    {
        $ConId = trim($this->request->post('con_id'));
        $nick_name = trim($this->request->post('nick_name'));
        $number = trim($this->request->post('number'));
        $business_id = trim($this->request->post('business_id'));
        intval($number);
        intval($business_id);
        $result = $this->app->user->allocateAgentNumber($nick_name, $number, $ConId, $business_id);
        return $result;
    }

    /**
     * @api              {post} / 获取用户条数划拨
     * @apiDescription   getAllocateAgentNumber
     * @apiGroup         index_user
     * @apiName          getAllocateAgentNumber
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} [business_id] 划拨服务类型
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiSuccess (返回) {String} code 200:成功 
     * @apiSampleRequest /index/user/getAllocateAgentNumber
     * @return array
     * @author rzc
     */
    public function getAllocateAgentNumber()
    {
        $ConId = trim($this->request->post('con_id'));
        $business_id = trim($this->request->post('business_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        intval($business_id);
        $result = $this->app->user->getAllocateAgentNumber($page, $pageNum, $ConId, $business_id);
        return $result;
    }

    /**
     * @api              {post} / 获取所有用户模板（彩信）
     * @apiDescription   getUserMultimediaTemplate
     * @apiGroup         index_user
     * @apiName          getUserMultimediaTemplate
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} status 状态:1,提交申请;2,审核通过3,审核不通过;
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /index/user/getUserMultimediaTemplate
     * @return array
     * @author rzc
     */
    public function getUserMultimediaTemplate()
    {
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $status  = trim($this->request->post('status'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        intval($status);
        $result = $this->app->user->getUserMultimediaTemplate($ConId, $page, $pageNum, $status);
        return $result;
    }

    /**
     * @api              {post} / 获取用户上行
     * @apiDescription   getUserUpriver
     * @apiGroup         index_user
     * @apiName          getUserUpriver
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiParam (入参) {String} start_time 开始时间
     * @apiParam (入参) {String} end_time 结束时间
     * @apiParam (入参) {String} business_id 类型
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /index/user/getUserUpriver
     * @return array
     * @author rzc
     */
    public function getUserUpriver()
    {
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $start_time  = trim($this->request->post('start_time'));
        $end_time  = trim($this->request->post('end_time'));
        $business_id  = trim($this->request->post('business_id'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        if (strtotime($start_time) == false && !empty($start_time)) {
            return ['code' => '3003'];
        }
        if (strtotime($end_time)  == false && !empty($end_time)) {
            return ['code' => '3004'];
        }
        intval($page);
        intval($pageNum);
        $result = $this->app->user->getUserUpriver($ConId, $page, $pageNum, $start_time, $end_time, $business_id);
        return $result;
    }
    /**
     * @api              {post} / 获取丝芙兰报表
     * @apiDescription   getSflReport
     * @apiGroup         index_user
     * @apiName          getSflReport
     * @apiParam (入参) {String} con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /index/user/getSflReport
     * @return array
     * @author jackiwu
     */
    public function getSflReport(){
        $ConId = trim($this->request->post('con_id'));
        $result = $this->app->user->getSflReportLog();
        return $result;
    }

    /**
     * @api              {post} / 获取所有用户模板（视频短信）
     * @apiDescription   getUserSupMessageTemplate
     * @apiGroup         index_user
     * @apiName          getUserSupMessageTemplate
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} status 状态:1,提交申请;2,审核通过3,审核不通过;
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /index/user/getUserSupMessageTemplate
     * @return array
     * @author rzc
     */
    public function getUserSupMessageTemplate()
    {
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $status  = trim($this->request->post('status'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        intval($status);
        $result = $this->app->user->getUserSupMessageTemplate($ConId, $page, $pageNum, $status);
        return $result;
    }
    
    /**
     * @api              {post} / 获取用户视频短信发送日志
     * @apiDescription   getUserSupMessageLog
     * @apiGroup         index_user
     * @apiName          getUserSupMessageLog
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} status 状态:1,提交申请;2,审核通过3,审核不通过;
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /index/user/getUserSupMessageLog
     * @return array
     * @author rzc
     */
    public function getUserSupMessageLog(){
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->user->getUserSupMessageLog($ConId, $page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 获取用户模板报备情况
     * @apiDescription   getUserSupMessageTemplate
     * @apiGroup         index_user
     * @apiName          getUserSupMessageTemplate
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {String} template_id 模板id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /index/user/getUserSupMessageTemplate
     * @return array
     * @author rzc
     */
    public function getUserSupMessageTemplateStatus()
    {
        $appid = trim($this->request->post('appid'));
        $appkey = trim($this->request->post('appkey'));
        $template_id     = trim($this->request->post('template_id'));
        // intval($status);
        $result = $this->app->user->getUserSupMessageTemplateStatus($appid, $appkey, $template_id);
        return $result;
    }

    /**
     * @api              {post} / 设置余额提醒额度
     * @apiDescription   setUserBalance
     * @apiGroup         index_user
     * @apiName          setUserBalance
     * @apiParam (入参) {String} con_id con_id
     * @apiParam (入参) {String} appkey balance
     * @apiSuccess (返回) {String} code 200:成功 / 3001:balance不是数字 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /index/user/setUserBalance
     * @return array
     * @author rzc
     */
    public function setUserBalance(){
        $ConId = trim($this->request->post('con_id'));
        $balance = trim($this->request->post('balance'));
        if (!is_numeric($balance) || $balance < 0) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->setUserBalance($ConId, $balance);
        return $result;
    
    }

    /**
     * @api              {post} / 添加提醒手机号
     * @apiDescription   setNotifications
     * @apiGroup         index_user
     * @apiName          setNotifications
     * @apiParam (入参) {String} con_id con_id
     * @apiParam (入参) {String} mobile mobile
     * @apiSuccess (返回) {String} code 200:成功 / 3001:mobile格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /index/user/setNotifications
     * @return array
     * @author rzc
     */
    public function setNotifications(){
        $ConId = trim($this->request->post('con_id'));
        $mobile = trim($this->request->post('mobile'));
        if (checkMobile($mobile) == false) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->setNotifications($ConId, $mobile);
        return $result;
    }

    /**
     * @api              {post} / 获取已设置提醒手机号
     * @apiDescription   getNotifications
     * @apiGroup         index_user
     * @apiName          getNotifications
     * @apiParam (入参) {String} con_id con_id
     * @apiParam (入参) {String} page 
     * @apiParam (入参) {String} pageNum 
     * @apiSuccess (返回) {String} code 200:成功 / 3001:mobile格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /index/user/getNotifications
     * @return array
     * @author rzc
     */
    public function getNotifications(){
        $ConId = trim($this->request->post('con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        $result = $this->app->user->getNotifications($ConId, $page, $pageNum);
        return $result;
    }


    /**
     * @api              {post} / 修改提醒手机号
     * @apiDescription   updateNotifications
     * @apiGroup         index_user
     * @apiName          updateNotifications
     * @apiParam (入参) {String} con_id con_id
     * @apiParam (入参) {String} mobile mobile
     * @apiParam (入参) {String} id 
     * @apiSuccess (返回) {String} code 200:成功 / 3001:mobile格式错误 / 3002:id格式错误 / 3003:数据不存在
     * @apiSampleRequest /index/user/updateNotifications
     * @return array
     * @author rzc
     */
    public function updateNotifications(){
        $ConId = trim($this->request->post('con_id'));
        $mobile = trim($this->request->post('mobile'));
        $id = trim($this->request->post('id'));
        if (checkMobile($mobile) == false) {
            return ['code' => '3001'];
        }
        if (!is_numeric($id) || $id < 0) {
            return ['code' => '3002'];
        }
        $result = $this->app->user->updateNotifications($ConId, $mobile , $id);
        return $result;
    }

    /**
     * @api              {post} / 删除提醒手机号
     * @apiDescription   delNotifications
     * @apiGroup         index_user
     * @apiName          delNotifications
     * @apiParam (入参) {String} con_id con_id
     * @apiParam (入参) {String} id 
     * @apiSuccess (返回) {String} code 200:成功 / 3001:mobile格式错误 / 3002:id格式错误 / 3003:数据不存在
     * @apiSampleRequest /index/user/delNotifications
     * @return array
     * @author rzc
     */
    public function delNotifications() {
        $ConId = trim($this->request->post('con_id'));
        $id = trim($this->request->post('id'));
        if (!is_numeric($id) || $id < 0) {
            return ['code' => '3002'];
        }
        $result = $this->app->user->delNotifications($ConId,  $id);
        return $result;
    }
}