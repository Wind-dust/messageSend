<?php

namespace app\common\db\user;

use app\common\model\LogVercode;
use app\common\model\UserCon;
use app\common\model\Users;
use app\common\model\UserDevelopCode;
use app\common\model\StatisticsYear;
use app\common\model\StatisticsMonth;
use app\common\model\StatisticsDay;
use app\common\model\LogTrading;
use app\common\model\UserUpriver;
use app\common\model\SflReportLog;
use app\common\model\NotificationsSettings;
use think\Db;

class DbUser
{

    /**
     * 获取一个用户信息
     * @param $where
     * @return array
     */
    public function getUser($where)
    {
        $field = ['passwd', 'delete_time'];
        $user  = Users::where($where)->field($field, true)->findOrEmpty()->toArray();
        return $user;
    }

    public function getUserOne($where, $field)
    {
        $user = Users::where($where)->field($field)->findOrEmpty()->toArray();
        return $user;
    }

    public function getUserInfo($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = Users::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    public function getUserInfoCount($where)
    {
        return Users::where($where)->count();
    }

    /**
     * 获取con_id记录
     * @param $where
     * @param $field
     * @param bool $row
     * @return array
     * @author zyr
     */
    public function getUserCon($where, $field, $row = false)
    {
        $obj = UserCon::where($where)->field($field);
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->select()->toArray();
    }


    public function updateUserCon($data, $id)
    {
        $UserCon = new UserCon;
        return $UserCon->save($data, ['id' => $id]);
    }

    /**
     * 添加验证码日志
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addLogVercode($data)
    {
        $logVercode = new LogVercode();
        $logVercode->save($data);
        return $logVercode->id;
    }

    /**
     * 获取一条验证码日志
     * @param $where
     * @param $field
     * @return array
     * @author zyr
     */
    public function getOneLogVercode($where, $field)
    {
        return LogVercode::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * @param $obj
     * @param bool $row
     * @param string $orderBy
     * @param string $limit
     * @return mixed
     * @author zyr
     */
    private function getResult($obj, $row = false, $orderBy = '', $limit = '')
    {
        if (!empty($orderBy)) {
            $obj = $obj->order($orderBy);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    /**
     * 添加一天con_id记录
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addUserCon($data)
    {
        $userCon = new UserCon();
        $userCon->save($data);
        return $userCon->id;
    }

    public function addUser($data)
    {
        $user = new Users();
        $user->save($data);
        return $user->id;
    }

    /**
     * 更新用户
     * @param $data
     * @param $uid
     * @return bool
     * @author zyr
     */
    public function updateUser($data, $uid)
    {
        $user = new Users();
        return $user->save($data, ['id' => $uid]);
    }

    /**
     * 查找扩展码关联关系
     * @param $data
     * @param $uid
     * @return bool
     * @author rzc
     */
    public function getUserDevelopCode($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserDevelopCode::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * 修改扩展码关联关系
     * @param $data
     * @param $uid
     * @return bool
     * @author rzc
     */
    public function updateUserDevelopCode($data, $id)
    {
        $UserDevelopCode = new UserDevelopCode();
        return $UserDevelopCode->save($data, ['id' => $id]);
    }

    public function addUserDevelopCode($data)
    {
        $UserDevelopCode = new UserDevelopCode();
        return $UserDevelopCode->save($data);
    }

    public function delUserDevelopCode($id)
    {
        return UserDevelopCode::destroy($id);
    }

    public function getUserStatisticsYear($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = StatisticsYear::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserStatisticsYear($where)
    {
        return StatisticsYear::where($where)->count();
    }

    public function getUserStatisticsMonth($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = StatisticsMonth::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserStatisticsMonth($where)
    {
        return StatisticsMonth::where($where)->count();
    }


    public function getUserStatisticsDay($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = StatisticsDay::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserStatisticsDay($where)
    {
        return StatisticsDay::where($where)->count();
    }

    public function getLogTrading($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = LogTrading::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }


    public function addLogTrading($data)
    {
        $LogTrading = new LogTrading;
        $LogTrading->save($data);
        return $LogTrading->id;
    }

    public function editLogTrading($data, $id)
    {
        $LogTrading = new LogTrading;
        return $LogTrading->save($data, ['id' => $id]);
    }

    public function countLogTrading($where)
    {
        return LogTrading::where($where)->count();
    }

    public function getUserUpriver($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserUpriver::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserUpriver($where){
        return UserUpriver::where($where)->count();
    }

    public function saveSflReport($data){
         $res = Db::table('yx_sfl_report_log')->insert($data);
         return $res;
    }

    public function getSflReportLog(){
        $res = Db::table('yx_sfl_report_log')->select();
        return $res;
    }
    public function editSflReportLog($update){
        $res = Db::table('yx_sfl_report_log')->update($update);
        return $res;
    }

    public function getNotificationsSettings($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = NotificationsSettings::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function updateNotificationsSettings($data, $id)
    {
        $NotificationsSettings = new NotificationsSettings();
        return $NotificationsSettings->save($data, ['id' => $id]);
    }

    public function addNotificationsSettings($data)
    {
        $NotificationsSettings = new NotificationsSettings();
        return $NotificationsSettings->save($data);
    }

    public function countNotificationsSettings($where){
        $NotificationsSettings = new NotificationsSettings();
        return $NotificationsSettings->where()->count();
    }

    public function delcountNotificationsSettings($id)
    {
        return NotificationsSettings::destroy($id);
    }
}
