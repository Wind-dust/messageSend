<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Admin extends AdminController {
    protected $beforeActionList = [
//        'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'login'], //除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 后台登录
     * @apiDescription   login
     * @apiGroup         admin_admin
     * @apiName          login
     * @apiParam (入参) {String} admin_name
     * @apiParam (入参) {String} passwd 密码
     * @apiSuccess (返回) {String} code 200:成功 / 3001:账号密码不能为空 / 3002:用户不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSampleRequest /admin/admin/login
     * @return array
     * @author zyr
     */
    public function login() {
        $apiName  = classBasename($this) . '/' . __function__;
        $adminName = trim($this->request->post('admin_name'));
        $passwd    = trim($this->request->post('passwd'));
        if (empty($adminName) || empty($passwd)) {
            return ['code' => '3001'];
        }
        $result = $this->app->admin->login($adminName, $passwd);
        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 获取后台管理员信息
     * @apiDescription   getAdminUsers
     * @apiGroup         admin_admin
     * @apiName          getAdminUsers
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功  / 5000:请重新登录 2.5001:账号已停用
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSuccess (返回) {String} admin_name 管理员名
     * @apiSuccess (返回) {data} stype 用户类型 1.后台管理员 2.超级管理员
     * @apiSampleRequest /admin/admin/getAdminUsers
     * @return array
     * @author rzc
     */
    public function getAdminUsers() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result   = $this->app->admin->getAdminUsers();
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取登录用户信息
     * @apiDescription   getAdminInfo
     * @apiGroup         admin_admin
     * @apiName          getAdminInfo
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功  / 5000:请重新登录 2.5001:账号已停用
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSuccess (返回) {String} admin_name 管理员名
     * @apiSuccess (返回) {Array} group 所属权限组列表
     * @apiSuccess (返回) {Int} stype 用户类型 1.后台管理员 2.超级管理员
     * @apiSampleRequest /admin/admin/getadmininfo
     * @return array
     * @author zyr
     */
    public function getAdminInfo() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result   = $this->app->admin->getAdminInfo($cmsConId);
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加后台管理员
     * @apiDescription   addAdmin
     * @apiGroup         admin_admin
     * @apiName          addAdmin
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} admin_name 添加的用户名
     * @apiParam (入参) {String} [passwd] 默认为:123456
     * @apiParam (入参) {Int} [stype] 添加的管理员类型 1.管理员 2超级管理员  默认为:1
     * @apiSuccess (返回) {String} code 200:成功 / 3001:账号不能为空 / 3002:密码必须为6-16个任意字符 / 3003:只有root账户可以添加超级管理员 / 3004:该账号已存在 / 3006:添加失败
     * @apiSampleRequest /admin/admin/addadmin
     * @return array
     * @author zyr
     */
    public function addAdmin() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $adminName = trim($this->request->post('admin_name'));
        $passwd    = trim($this->request->post('passwd'));
        $stype     = trim($this->request->post('stype'));
        $stypeArr  = [1, 2];
        if (empty($adminName)) {
            return ['code' => '3001'];
        }
        if (!in_array($stype, $stypeArr)) {
            $stype = 1;
        }
        $passwd = $passwd ?: '123456';
        if (checkCmsPassword($passwd) === false) {
            return ['code' => '3002']; //密码必须为6-16个任意字符
        }
        $result = $this->app->admin->addAdmin($cmsConId, $adminName, $passwd, $stype);
        $this->apiLog($apiName, [$cmsConId, $adminName, $passwd, $stype], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改密码
     * @apiDescription   midifyPasswd
     * @apiGroup         admin_admin
     * @apiName          midifyPasswd
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} passwd 用户密码
     * @apiParam (入参) {String} new_passwd1 新密码
     * @apiParam (入参) {String} new_passwd2 确认密码
     * @apiSuccess (返回) {String} code 200:成功 / 3001:密码错误 / 3002:密码必须为6-16个任意字符 / 3003:老密码不能为空 / 3004:密码确认有误  / 3005:修改密码失败
     * @apiSampleRequest /admin/admin/midifypasswd
     * @return array
     * @author zyr
     */
    public function midifyPasswd() {
        $apiName    = classBasename($this) . '/' . __function__;
        $cmsConId   = trim($this->request->post('cms_con_id'));
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
        $result = $this->app->admin->midifyPasswd($cmsConId, $passwd, $newPasswd1);
        $this->apiLog($apiName, [$cmsConId, $passwd, $newPasswd1], $result['code'], $cmsConId);
        return $result;
    }
   
    /**
     * @api              {post} / cms左侧菜单
     * @apiDescription   cmsMenu
     * @apiGroup         admin_admin
     * @apiName          cmsMenu
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /admin/admin/cmsmenu
     * @author zyr
     */
    public function cmsMenu() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result   = $this->app->admin->cmsMenu($cmsConId);
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms菜单详情
     * @apiDescription   cmsMenuOne
     * @apiGroup         admin_admin
     * @apiName          cmsMenuOne
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id 菜单id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.菜单id有误
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /admin/admin/cmsmenuone
     * @author zyr
     */
    public function cmsMenuOne() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        if (!is_numeric($id) || $id < 0) {
            return ['code' => '3001'];//菜单id有误
        }
        $result = $this->app->admin->cmsMenuOne($cmsConId, $id);
        $this->apiLog($apiName, [$cmsConId, $id], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改保存cms菜单
     * @apiDescription   editMenu
     * @apiGroup         admin_admin
     * @apiName          editMenu
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id 菜单id
     * @apiParam (入参) {String} name 菜单名称
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.菜单id有误 / 3002:菜单id不存在 / 3003:修改失败
     * @apiSampleRequest /admin/admin/editmenu
     * @author zyr
     */
    public function editMenu() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id   = trim($this->request->post('id'));
        $name = trim($this->request->post('name'));
        if (!is_numeric($id) || $id < 0) {
            return ['code' => '3001'];//菜单id有误
        }
        $result = $this->app->admin->editMenu($cmsConId, $id, $name);
        $this->apiLog($apiName, [$cmsConId, $id, $name], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加权限分组
     * @apiDescription   addPermissionsGroup
     * @apiGroup         admin_admin
     * @apiName          addPermissionsGroup
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} group_name 分组名称
     * @apiParam (入参) {String} content 详细描述
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组名称错误 /3005:添加失败
     * @apiSampleRequest /admin/admin/addpermissionsgroup
     * @author zyr
     */
    public function addPermissionsGroup() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $groupName = trim(($this->request->post('group_name')));
        $content   = trim(($this->request->post('content')));
        if (empty($groupName)) {
            return ['code' => '3001'];
        }
        $result = $this->app->admin->addPermissionsGroup($cmsConId, $groupName, $content);
        $this->apiLog($apiName, [$cmsConId, $groupName, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改权限分组
     * @apiDescription   editPermissionsGroup
     * @apiGroup         admin_admin
     * @apiName          editPermissionsGroup
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 权限分组ID
     * @apiParam (入参) {String} group_name 分组名称
     * @apiParam (入参) {String} content 详细描述
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组名称错误 / 3003:修改的用户不存在 / 3004:分组id错误 /3005:修改失败
     * @apiSampleRequest /admin/admin/editpermissionsgroup
     * @author zyr
     */
    public function editPermissionsGroup() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $groupId   = trim($this->request->post('group_id'));
        $groupName = trim(($this->request->post('group_name')));
        $content   = trim(($this->request->post('content')));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3004'];
        }
        if (empty($groupName)) {
            return ['code' => '3001'];
        }
        $groupId = intval($groupId);
        $result  = $this->app->admin->editPermissionsGroup($cmsConId, $groupId, $groupName, $content);
        $this->apiLog($apiName, [$cmsConId, $groupId, $groupName, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加管理员到权限组
     * @apiDescription   addAdminPermissions
     * @apiGroup         admin_admin
     * @apiName          addAdminPermissions
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 分组id
     * @apiParam (入参) {Int} add_admin_id 添加管理员id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组id错误 / 3003:权限分组不存在 /3004:添加用户不存在 / 3005:管理员id有误 / / 3006:该成员已存在 / 3007:添加失败
     * @apiSampleRequest /admin/admin/addadminpermissions
     * @author zyr
     */
    public function addAdminPermissions() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $groupId    = trim(($this->request->post('group_id')));
        $addAdminId = trim(($this->request->post('add_admin_id')));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
        if (!is_numeric($addAdminId) || $addAdminId < 2) {
            return ['code' => '3005'];
        }
        $groupId    = intval($groupId);
        $addAdminId = intval($addAdminId);
        $result     = $this->app->admin->addAdminPermissions($cmsConId, $groupId, $addAdminId);
        $this->apiLog($apiName, [$cmsConId, $groupId, $addAdminId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加接口权限列表
     * @apiDescription   addPermissionsApi
     * @apiGroup         admin_admin
     * @apiName          addPermissionsApi
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} menu_id 菜单id
     * @apiParam (入参) {String} api_name 接口url
     * @apiParam (入参) {Int} stype 接口curd权限 1.增 2.删 3.改
     * @apiParam (入参) {String} cn_name 权限名称
     * @apiParam (入参) {String} content 权限的详细描述
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:菜单id有误 / 3002:接口url不能为空 / 3003:接口权操作类型 /3004:权限名称不能为空 / 3005:接口已存在 / 3006:菜单不存在 / 3007:添加失败
     * @apiSampleRequest /admin/admin/addpermissionsapi
     * @author zyr
     */
    public function addPermissionsApi() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
//        if ($this->checkPermissions($cmsConId, $apiName) === false) { //该接口只有root可以使用,开发特殊接口
//            return ['code' => '3100'];
//        }
        $menuId   = trim($this->request->post('menu_id'));
        $apiUrl   = trim($this->request->post('api_name'));
        $stype    = trim($this->request->post('stype'));
        $cnName   = trim($this->request->post('cn_name'));
        $content  = trim($this->request->post('content'));
        $stypeArr = [1, 2, 3];
        if (!is_numeric($menuId) || $menuId < 1) {
            return ['code' => '3001'];//菜单id有误
        }
        $menuId = intval($menuId);
        if (empty($apiUrl)) {
            return ['code' => '3002'];//接口url不能为空
        }
        if (!in_array($stype, $stypeArr)) {
            return ['code' => '3003'];//接口权操作类型
        }
        if (empty($cnName)) {
            return ['code' => '3004'];//权限名称不能为空
        }
        $content = $content ?? '';
        $result  = $this->app->admin->addPermissionsApi($cmsConId, $menuId, $apiUrl, $stype, $cnName, $content);
        $this->apiLog($apiName, [$cmsConId, $menuId, $apiUrl, $stype, $cnName, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改接口权限名称和详情
     * @apiDescription   editPermissionsApi
     * @apiGroup         admin_admin
     * @apiName          editPermissionsApi
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id
     * @apiParam (入参) {String} cn_name 权限名称
     * @apiParam (入参) {String} content 权限的详细描述
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:接口id有误 /3004:权限名称不能为空 / 3005:接口不存在 / 3007:修改失败
     * @apiSampleRequest /admin/admin/editpermissionsapi
     * @author zyr
     */
    public function editPermissionsApi() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id      = trim($this->request->post('id'));
        $cnName  = trim($this->request->post('cn_name'));
        $content = trim($this->request->post('content'));
        if (!is_numeric($id) || $id < 1) {
            return ['code' => '3001'];//接口id有误
        }
        $id = intval($id);
        if (empty($cnName)) {
            return ['code' => '3004'];//权限名称不能为空
        }
        $content = $content ?? '';
        $result  = $this->app->admin->editPermissionsApi($cmsConId, $id, $cnName, $content);
        $this->apiLog($apiName, [$cmsConId, $id, $cnName, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 为权限组添加菜单接口
     * @apiDescription   addPermissionsGroupPower
     * @apiGroup         admin_admin
     * @apiName          addPermissionsGroupPower
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 分组id
     * @apiParam (入参) {String} permissions 权限分组:{"1":{"2":1,"3":0},"2":{"4":1,"5":0}}
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组id错误 / 3003:权限分组不存在 / 3004:权限分组不能为空 / 3005:permissions数据有误 / 3006:菜单不存在 / 3007:更改失败
     * @apiSampleRequest /admin/admin/addpermissionsgrouppower
     * @author zyr
     */
    public function addPermissionsGroupPower() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $groupId     = trim($this->request->post('group_id'));
        $permissions = trim($this->request->post('permissions'));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
//        $permissions = json_encode($arr);
        if (empty($permissions)) {
            return ['code' => '3004'];
        }
        $groupId = intval($groupId);
        $result  = $this->app->admin->addPermissionsGroupPower($cmsConId, $groupId, $permissions);
        $this->apiLog($apiName, [$cmsConId, $groupId, $permissions], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 删除权限组的成员
     * @apiDescription   delAdminPermissions
     * @apiGroup         admin_admin
     * @apiName          delAdminPermissions
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 分组id
     * @apiParam (入参) {Int} del_admin_id 要删除的admin_id
     * @apiSuccess (返回) {String} code 200:成功  / 3001:分组id错误 / 3003:权限分组不存在 /3004:删除用户不存在 / 3005:管理员id有误 /3006:删除的管理员不存在 / 3007:删除失败
     * @apiSampleRequest /admin/admin/deladminpermissions
     * @author zyr
     */
    public function delAdminPermissions() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $groupId    = trim($this->request->post('group_id'));
        $delAdminId = trim(($this->request->post('del_admin_id')));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
        if (!is_numeric($delAdminId) || $delAdminId < 2) {
            return ['code' => '3005'];
        }
        $groupId    = intval($groupId);
        $delAdminId = intval($delAdminId);
        $result     = $this->app->admin->delAdminPermissions($cmsConId, $groupId, $delAdminId);
        $this->apiLog($apiName, [$cmsConId, $groupId, $delAdminId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取权限组下的管理员
     * @apiDescription   getPermissionsGroupAdmin
     * @apiGroup         admin_admin
     * @apiName          getPermissionsGroupAdmin
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 分组id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组id错误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (返回) {String} admin_name 名字
     * @apiSampleRequest /admin/admin/getpermissionsgroupadmin
     * @author zyr
     */
    public function getPermissionsGroupAdmin() {
        $apiName = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $groupId  = trim($this->request->post('group_id'));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
        $groupId = intval($groupId);
        $result  = $this->app->admin->getPermissionsGroupAdmin($cmsConId, $groupId);
        $this->apiLog($apiName, [$cmsConId, $groupId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取用户或所有的权限组列表
     * @apiDescription   getAdminGroup
     * @apiGroup         admin_admin
     * @apiName          getAdminGroup
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} get_admin_id 管理员id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:管理员id有误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (返回) {String} group_name 组名
     * @apiSuccess (返回) {String} content 描述
     * @apiSampleRequest /admin/admin/getadmingroup
     * @author zyr
     */
    public function getAdminGroup() {
        $apiName = classBasename($this) . '/' . __function__;
        $cmsConId   = trim($this->request->post('cms_con_id'));
        $getAdminId = trim($this->request->post('get_admin_id'));
        if (!is_numeric($getAdminId) || $getAdminId < 2) {
            $getAdminId = 0;
        }
        $getAdminId = intval($getAdminId);
        $result     = $this->app->admin->getAdminGroup($cmsConId, $getAdminId);
        $this->apiLog($apiName, [$cmsConId, $getAdminId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取权限组信息
     * @apiDescription   getGroupInfo
     * @apiGroup         admin_admin
     * @apiName          getGroupInfo
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 管理员id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组id错误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (返回) {String} group_name 组名
     * @apiSuccess (返回) {String} content 描述
     * @apiSampleRequest /admin/admin/getgroupinfo
     * @author zyr
     */
    public function getGroupInfo() {
        $apiName = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $groupId  = trim($this->request->post('group_id'));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
        $groupId = intval($groupId);
        $result  = $this->app->admin->getGroupInfo($cmsConId, $groupId);
        $this->apiLog($apiName, [$cmsConId, $groupId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取权限列表
     * @apiDescription   getPermissionsList
     * @apiGroup         admin_admin
     * @apiName          getPermissionsList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:分组id错误
     * @apiSampleRequest /admin/admin/getpermissionslist
     * @author zyr
     */
    public function getPermissionsList() {
        $apiName = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $groupId  = trim($this->request->post('group_id'));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
        $groupId = intval($groupId);
        $result  = $this->app->admin->getPermissionsList($cmsConId, $groupId);
        $this->apiLog($apiName, [$cmsConId, $groupId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取接口权限列表
     * @apiDescription   getPermissionsApi
     * @apiGroup         admin_admin
     * @apiName          getPermissionsApi
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (返回) {String} group_name 组名
     * @apiSuccess (返回) {Int} menu_id 所属菜单
     * @apiSuccess (返回) {String} stype 权限类型 1.增 2.删 3.改
     * @apiSuccess (返回) {String} cn_name 名称
     * @apiSuccess (返回) {String} content 描述
     * @apiSampleRequest /admin/admin/getpermissionsapi
     * @author zyr
     */
    public function getPermissionsApi() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result   = $this->app->admin->getPermissionsApi($cmsConId);
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取接口权限详情
     * @apiDescription   getPermissionsApiOne
     * @apiGroup         admin_admin
     * @apiName          getPermissionsApiOne
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:接口id有误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (返回) {String} group_name 组名
     * @apiSuccess (返回) {Int} menu_id 所属菜单
     * @apiSuccess (返回) {String} stype 权限类型 1.增 2.删 3.改
     * @apiSuccess (返回) {String} cn_name 名称
     * @apiSuccess (返回) {String} content 描述
     * @apiSampleRequest /admin/admin/getpermissionsapione
     * @author zyr
     */
    public function getPermissionsApiOne() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        if (!is_numeric($id) || $id < 0) {
            return ['code' => '3001'];//接口id有误
        }
        $id     = intval($id);
        $result = $this->app->admin->getPermissionsApiOne($cmsConId, $id);
        $this->apiLog($apiName, [$cmsConId, $id], $result['code'], $cmsConId);
        return $result;
    }

}