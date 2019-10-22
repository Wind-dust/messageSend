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
            if (DbAdministrator::getUserQualification(['uid' => $value['id']], 'id', true)) {
                $result[$key]['has_qualification'] = 1;
            }
        }
        $totle = DbUser::getUserInfoCount($where);
        return ['code' => '200', 'totle' => $totle, 'result' => $result];
    }

    public function seetingUser($uid, $user_status, $reservation_service) {
        if ($user_status) {
            $data['user_status'] = $user_status;
        }
        if ($reservation_service) {
            $data['reservation_service'] = $reservation_service;
        }

        Db::startTrans();
        try {
            DbUser::updateUser($data, $uid);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
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
            DbUser::addUserEquities($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }
}