<?php
namespace app\index\controller;
use app\index\MyController;

class User extends MyController {
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
     * @apiSuccess (返回) {String} code 200:成功 / 3000:手机号格式错误 / 3002:passwd密码强度不够//6-16个字符，至少1个字母和1个数字，其他可以是任意字符 / 3003:邮箱格式错误 / 3004:验证码错误 / 3005:该手机号已注册用户 / 3006:用户类型错误 / 3007:nick_name不能为空
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
        // if (checkEmail($email) === false) {
        //     return ['code' => '3003'];
        // }
        if (!in_array($user_type, [1, 2])) {
            return ['code' => '3006'];
        }
        if (empty($nick_name)) {
            return ['code' => '3007'];
        }
        $result = $this->app->user->userRegistered($nick_name, $user_type, $passwd, $mobile, $email, $vercode);
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
    public function login() {
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

    public function apportionSonUser() {
        $apiName   = classBasename($this) . '/' . __function__;
        $conId     = trim($this->request->post('con_id'));
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
        $result = $this->app->user->apportionSonUser($conId, $nick_name, $user_type, $passwd, $mobile, $email);
        $this->apiLog($apiName, [$conId, $nick_name, $user_type, $passwd, $mobile, $email], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 设置子账户用户服务项目
     * @apiDescription   seetingUserEquities
     * @apiGroup         index_user
     * @apiName          seetingUserEquities
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Int} mobile 账户手机号
     * @apiParam (入参) {Int} business_id 服务id
     * @apiParam (入参) {Int} [agency_price] 代理价格，默认统一代理商服务价格
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号格式错误 / 3002:agency_price格式错误 / 3003:母账户无该项服务业务 / 3004:代理价格不能低于服务商价格 / 3005:该服务已添加 / 3006:子账户服务无法设置 / 3007:business_id格式错误或者不存在 / 3008:该账户非此账户的子账户
     * @apiSampleRequest /index/user/seetingUserEquities
     * @author rzc
     */
    public function seetingUserEquities() {
        $conId        = trim($this->request->post('con_id'));
        $mobile       = trim($this->request->post('mobile'));
        $business_id  = trim($this->request->post('business_id'));
        $agency_price = trim($this->request->post('agency_price'));
        if (!empty($agency_price) && !is_numeric($agency_price)) {
            return ['code' => '3002'];
        }
        if (checkMobile($mobile) === false) {
            return ['code' => '3001']; //手机号格式错误
        }
        $agency_price = floatval($agency_price);
        $result = $this->app->user->seetingUserEquities($conId, $mobile, $business_id, $agency_price);
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
    public function recordUserQualification() {
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
    public function getUserEquitises(){
        $conId        = trim($this->request->post('con_id'));
        $result = $this->app->user->getUserEquitises($conId);
        // $this->apiLog($apiName, [$conId, $nick_name, $user_type, $passwd, $mobile, $email], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 完善营业执照
     * @apiDescription   uploadBusinessLicense
     * @apiGroup         index_user
     * @apiName          getUserEquitises
     * @apiParam (入参) {String} con_id
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:账号不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/uploadBusinessLicense
     * @return array
     * @author rzc
     */
}
