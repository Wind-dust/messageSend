<?php

namespace app\common\action\admin;

use app\facade\DbAdministrator;
use app\facade\DbUser;
use think\Db;

class User extends CommonIndex {
    /**
     * 会员列表
     * @return array
     * @author rzc
     */
    public function getUsers($page, $pagenum, $mobile = '') {
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3002'];
        }
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $where = [];
        if (!empty($mobile)) {
            array_push($where, ['mobile', '=', $mobile]);
        }
        $limit  = $offset . ',' . $pagenum;
        $result = DbUser::getUserInfo($where, '*', false, 'id', $limit, 'desc');
        if (empty($result)) {
            return ['code' => '3000'];
        }
        foreach ($result as $key => $value) {
            $result[$key]['has_qualification'] = 0;
            if (DbAdministrator::getUserQualification(['uid' => $value['id']], 'uid', true)) {
                $result[$key]['has_qualification'] = 1;
            }
        }
        $totle = DbUser::getUserInfoCount($where);
        return ['code' => '200', 'totle' => $totle, 'result' => $result];
    }

    public function seetingUser($uid, $user_status, $reservation_service, $free_trial) {
        if ($user_status) {
            $data['user_status'] = $user_status;
        }
        if ($reservation_service) {
            $data['reservation_service'] = $reservation_service;
        }
        if ($reservation_service) {
            $data['free_trial'] = $free_trial;
        }

        Db::startTrans();
        try {
            DbUser::updateUser($data, $uid);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function seetingUserEquities($uid, $business_id, $agency_price = 0){
        $business = DbAdministrator::getBusiness(['id' => $business_id],'*',true);
        if (empty($business)) {
            return ['code' => '3001'];
        }
        $user = DbUser::getUser(['id' => $uid]);
        if ($user['pid'] > 0) {
            return ['code' => '3006'];
        }
        if (DbAdministrator::getUserEquities(['uid' => $uid, 'business_id' => $business_id],'id',true)) {
            return ['code' => '3005'];
        }
        $data = [];
        $data = [
            'business_id' => $business_id,
            'num_balance' => $business['donate_num'],
            'uid'         => $uid,
        ];
        if ($agency_price){
            if ($agency_price < $business['price']){
                return ['code' => '3004'];
            }
            $data['agency_price'] = $agency_price;
        }else {
            $data['agency_price'] = $business['price'];
        }
        Db::startTrans();
        try {
            DbAdministrator::addUserEquities($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            // exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getUserInfo($uid){
        $result = DbUser::getUserInfo(['id' => $uid], '*', true);
        if (empty($result)) {
            return ['code' => '3001'];
        }
        $user_equies = DbAdministrator::getUserEquities(['uid' => $uid],'id,business_id,num_balance,agency_price',false);
        if (!empty($user_equies)) {
            foreach ($user_equies as $key => $value) {
                $user_equies[$key]['business_name'] = DbAdministrator::getBusiness(['id' => $value['business_id'],'title',true])['title'];
            }
        }else{
            $user_equies = [];
        }

        return ['code' => '200', 'user_info' => $result,'user_equies' => $user_equies];
    }
}