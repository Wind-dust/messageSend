<?php

namespace app\supadmin\controller;

use app\supadmin\SupAdminController;

class User extends SupAdminController {
    protected $beforeActionList = [
//        'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
//        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 后台登录
     * @apiDescription   sup_login
     * @apiGroup         supadmin_user
     * @apiName          sup_login
     * @apiParam (入参) {String} mobile 手机号
     * @apiParam (入参) {String} passwd 密码
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号密码不能为空 / 3002:用户不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSampleRequest /supadmin/user/login
     * @return array
     * @author zyr
     */
    public function login() {
        $apiName = classBasename($this) . '/' . __function__;
        $mobile  = trim($this->request->post('mobile'));
        $passwd  = trim($this->request->post('passwd'));
        if (empty($mobile) || empty($passwd)) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->login($mobile, $passwd);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 获取管理员信息
     * @apiDescription   getSupUser
     * @apiGroup         supadmin_user
     * @apiName          getSupUser
     * @apiParam (入参) {String} sup_con_id
     * @apiSuccess (返回) {String} code 200:成功  / 5000:请重新登录 2.5001:账号已停用
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSuccess (返回) {String} admin_name 管理员名
     * @apiSuccess (返回) {data} stype 用户类型 1.后台管理员 2.超级管理员
     * @apiSampleRequest /supadmin/user/getsupuser
     * @return array
     * @author zyr
     */
    public function getSupUser() {
        $apiName  = classBasename($this) . '/' . __function__;
        $supConId = trim($this->request->post('sup_con_id'));
        $result   = $this->app->user->getSupUser($supConId);
//        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加推广
     * @apiDescription   addPromote
     * @apiGroup         supadmin_user
     * @apiName          addPromote
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} big_image 大图
     * @apiParam (入参) {String} share_title 微信转发分享标题
     * @apiParam (入参) {String} share_image 微信转发分享图片
     * @apiParam (入参) {String} share_count 需要分享次数
     * @apiParam (入参) {String} bg_image 分享成功页面图片
     * @apiSuccess (返回) {String} code 200:成功 / 3001:title不能为空 / 3002:share_title不能为空 / 3003:big_image未上传 / 3004:share_image未上传 / 3005:bg_image未上传 / 3006:big_image图片没有上传过 / 3007:share_image图片没有上传过 / 3008:bg_image图片没有上传过 / 3009:share_count有误 / 3010:添加失败
     * @apiSampleRequest /supadmin/user/addpromote
     * @return array
     * @author zyr
     */
    public function addPromote() {
        $apiName    = classBasename($this) . '/' . __function__;
        $supConId   = trim($this->request->post('sup_con_id'));
        $title      = trim($this->request->post('title'));
        $bigImage   = trim($this->request->post('big_image'));
        $shareTitle = trim($this->request->post('share_title'));
        $shareImage = trim($this->request->post('share_image'));
        $shareCount = trim($this->request->post('share_count'));
        $bgImage    = trim($this->request->post('bg_image'));
        if (empty($title)) {
            return ['code' => '3001'];//title不能为空
        }
        if (empty($shareTitle)) {
            return ['code' => '3002'];//share_title不能为空
        }
        // if (empty($bigImage)) {
        //     return ['code' => '3003'];//big_image未上传
        // }
        if (empty($shareImage)) {
            return ['code' => '3004'];//share_image未上传
        }
        // if (empty($bgImage)) {
        //     return ['code' => '3005'];//bg_image未上传
        // }
        // if (!is_numeric($shareCount) || $shareCount < 0) {
        //     return ['code' => '3009'];//share_count有误
        // }
        $shareCount = 0;
        // $shareCount = intval($shareCount);
        $result     = $this->app->user->addPromote($title, $bigImage, $shareTitle, $shareImage, $shareCount, $bgImage, $supConId);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 编辑推广
     * @apiDescription   editPromote
     * @apiGroup         supadmin_user
     * @apiName          editPromote
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {Int} id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} big_image 大图
     * @apiParam (入参) {String} share_title 微信转发分享标题
     * @apiParam (入参) {String} share_image 微信转发分享图片
     * @apiParam (入参) {Int} share_count 需要分享次数
     * @apiParam (入参) {String} bg_image 分享成功页面图片
     * @apiSuccess (返回) {String} code 200:成功 /3000:推广活动不存在 / 3001:title不能为空 / 3002:share_title不能为空 / 3006:big_image图片没有上传过 / 3007:share_image图片没有上传过 / 3008:bg_image图片没有上传过 / 3009:share_count有误 / 3010:修改失败
     * @apiSampleRequest /supadmin/user/editpromote
     * @return array
     * @author zyr
     */
    public function editPromote() {
        $apiName    = classBasename($this) . '/' . __function__;
        $supConId   = trim($this->request->post('sup_con_id'));
        $id         = trim($this->request->post('id'));
        $title      = trim($this->request->post('title'));
        $bigImage   = trim($this->request->post('big_image'));
        $shareTitle = trim($this->request->post('share_title'));
        $shareImage = trim($this->request->post('share_image'));
        $shareCount = trim($this->request->post('share_count'));
        $bgImage    = trim($this->request->post('bg_image'));
        if (empty($title)) {
            return ['code' => '3001'];//title不能为空
        }
        if (empty($shareTitle)) {
            return ['code' => '3002'];//share_title不能为空
        }
        // if (!is_numeric($shareCount) || $shareCount < 0) {
        //     return ['code' => '3009'];//share_count有误
        // }
        $shareCount = 0;
        $shareCount = intval($shareCount);
        $result     = $this->app->user->editPromote($id, $title, $bigImage, $shareTitle, $shareImage, $shareCount, $bgImage);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 推广活动列表
     * @apiDescription   getPromoteList
     * @apiGroup         supadmin_user
     * @apiName          getPromoteList
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {Int} page 页数
     * @apiParam (入参) {Int} [page_num] 每页条数(默认10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:列表为空 / 3001:page错误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {Int} id
     * @apiSuccess (data) {String} title 标题
     * @apiSuccess (data) {String} big_image 大图
     * @apiSuccess (data) {String} share_title 微信转发分享标题
     * @apiSuccess (data) {String} share_image 微信转发分享图片
     * @apiSuccess (data) {Int} share_count 需要分享次数
     * @apiSuccess (data) {String} bg_image 分享成功页面图片
     * @apiSampleRequest /supadmin/user/getpromoteList
     * @return array
     * @author zyr
     */
    public function getPromoteList() {
        $apiName  = classBasename($this) . '/' . __function__;
        $supConId = trim($this->request->post('sup_con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('page_num'));
        if (!is_numeric($page) || $page < 1) {
            return ['code' => '3001'];//page错误
        }
        if (!is_numeric($pageNum) || $pageNum < 1) {
            $pageNum = 10;
        }
        $page    = intval($page);
        $pageNum = intval($pageNum);
        $result  = $this->app->user->getPromoteList($page, $pageNum, $supConId);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 推广活动详情
     * @apiDescription   getPromoteInfo
     * @apiGroup         supadmin_user
     * @apiName          getPromoteInfo
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {Int} id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:列表为空 / 3001:id错误 / 3002:详情id不存在
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {Int} id
     * @apiSuccess (data) {String} title 标题
     * @apiSuccess (data) {String} big_image 大图
     * @apiSuccess (data) {String} share_title 微信转发分享标题
     * @apiSuccess (data) {String} share_image 微信转发分享图片
     * @apiSuccess (data) {Int} share_count 需要分享次数
     * @apiSuccess (data) {String} bg_image 分享成功页面图片
     * @apiSampleRequest /supadmin/user/getpromoteinfo
     * @return array
     * @author zyr
     */
    public function getPromoteInfo() {
        $apiName  = classBasename($this) . '/' . __function__;
        $supConId = trim($this->request->post('sup_con_id'));
        $id       = trim($this->request->post('id'));
        if (!is_numeric($id) || $id < 1) {
            return ['code' => '3001'];//id错误
        }
        $id     = intval($id);
        $result = $this->app->user->getPromoteInfo($id, $supConId);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 修改密码
     * @apiDescription   resetPassword
     * @apiGroup         supadmin_user
     * @apiName          resetPassword
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {String} passwd 用户密码
     * @apiParam (入参) {String} new_passwd1 新密码
     * @apiParam (入参) {String} new_passwd2 确认密码
     * @apiSuccess (返回) {String} code 200:成功 / 3001:密码错误 / 3002:密码必须为6-16个任意字符 / 3003:老密码不能为空 / 3004:密码确认有误  / 3005:修改密码失败
     * @apiSampleRequest /supadmin/user/resetpassword
     * @return array
     * @author zyr
     */
    public function resetPassword() {
        $apiName    = classBasename($this) . '/' . __function__;
        $supConId   = trim($this->request->post('sup_con_id'));
        $passwd     = trim($this->request->post('passwd'));
        $newPasswd1 = trim($this->request->post('new_passwd1'));
        $newPasswd2 = trim($this->request->post('new_passwd2'));
        if ($newPasswd1 !== $newPasswd2) {
            return ['code' => '3004']; //密码确认有误
        }
        if (checkCmsPassword($newPasswd1) === false) {
            return ['code' => '3002']; //密码必须为6-16个任意字符
        }
        if (empty($passwd)) {
            return ['code' => '3003']; //老密码不能为空
        }
        $result = $this->app->user->resetPassword($supConId, $passwd, $newPasswd1);
//        $this->apiLog($apiName, [$cmsConId, $passwd, $newPasswd1], $result['code'], $cmsConId);
        return $result;
    }
}
