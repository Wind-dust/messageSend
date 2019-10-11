<?php

namespace app\common\action\admin;

use app\common\action\notify\Note;
use app\facade\DbAdmin;
use app\facade\DbAdministrator;
use app\facade\DbUser;
use cache\Phpredis;
use Config;
use Env;
use think\Db;
use third\PHPTree;

class Administrator extends CommonIndex {
    private $cmsCipherUserKey = 'adminpass'; //用户密码加密key

    private function redisInit() {
        $this->redis = Phpredis::getConn();
//        $this->connect = Db::connect(Config::get('database.db_config'));
    }

    /**
     * @param $page
     * @param $pageNum
     * @return array
     * @author rzc
     */
    public function getBusiness($page, $pageNum, $id = 0, $getall) {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getBusiness(['id' => $id], '*', true);
        } else {
            if ($getall == 1) {
                $result = DbAdministrator::getBusiness([], '*', false);
            } else {
                $result = DbAdministrator::getBusiness([], '*', false, '', $offset . ',' . $pageNum);
            }
        }
        return ['code' => '200', 'Business' => $result];
    }

    public function addBusiness($title, $price, $donate_num = 0) {
        $data = [];
        $data = [
            'title'      => $title,
            'price'      => $price,
            'donate_num' => $donate_num,
        ];

        Db::startTrans();
        try {

            $bId = DbAdministrator::addBusiness($data); //添加后的商品id
            if ($bId === false) {
                Db::rollback();
                return ['code' => '3009']; //添加失败
            }
            Db::commit();
            return ['code' => '200', 'goods_id' => $bId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    public function updateBusiness($id, $title, $price, $donate_num = 0) {
        $Business = DbBusiness::getBusiness(['id' => $id], 'id', true);
        if (empty($Business)) {
            return ['code' => '3001'];
        }
        if (!empty($title)) {
            $data['title'] = $title;
        }
        if (!empty($price)) {
            $data['price'] = $price;
        }
        if (!empty($donate_num)) {
            $data['donate_num'] = $donate_num;
        }
        Db::startTrans();
        try {
            $updateRes = DbBusiness::editBusiness($data, $id);
            if ($updateRes) {
                Db::commit();
                return ['code' => '200'];
            }
            Db::rollback();
            return ['code' => '3009']; //修改失败
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getUserQualificationRecord($page, $pageNum, $id) {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getUserQualificationRecord(['id' => $id], '*', true);
        } else {
            $result = DbAdministrator::getUserQualificationRecord([], '*', false, '', $offset . ',' . $pageNum);
        }
        return ['code' => '200', 'Business' => $result];
    }

    public function auditUserQualificationRecord($id, $status) {
        $record = DbAdministrator::getUserQualificationRecord(['id' => $id], '*', true);
        if (empty($record)) {
            return ['code' => '3001'];
        }
        if ($record['status'] > 2) {
            return ['code' => '3003'];
        }

        Db::startTrans();
        try {
            $updateRes = DbAdministrator::editUserQualificationRecord(['status' => $status], $id);
            if ($updateRes) {
                if ($status == 3) {
                    unset($record['id']);
                    unset($record['status']);
                    DbAdministrator::addUserQualification($record);
                    //开通账户使用权限
                    DbUser::updateUser(['user_status' => 2], $record['uid']);
                }
                Db::commit();
                return ['code' => '200'];
            }
            Db::rollback();
            return ['code' => '3009']; //修改失败
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getUserEquities($mobile, $business_id) {
        $user = DbUser::getUserInfo(['mobile' => $mobile], 'id', true);
        if (empty($user)) {
            return ['code' => '3002'];
        }
        $business = DbAdministrator::getBusiness(['id' => $business_id], '*', true);
        if (empty($business)) {
            return ['code' => '3001'];
        }
        $result = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => $business_id], '*', true);
        return ['code' => '200', 'userequities' => $result];
    }

    public function rechargeApplication($cmsConId, $mobile, $business_id, $num) {
        $adminId = $this->getUidByConId($cmsConId);
        // $adminInfo     = DbAdmin::getAdminInfo(['id' => $adminId], 'id,passwd,status', true);
        $user = DbUser::getUserInfo(['mobile' => $mobile], 'id', true);
        if (empty($user)) {
            return ['code' => '3003'];
        }
        $business = DbAdministrator::getBusiness(['id' => $business_id], '*', true);
        if (empty($business)) {
            return ['code' => '3002'];
        }
        if (!DbAdministrator::getUserEquities(['business_id' => $business_id, 'uid' => $user['id']])){
            return ['code' => '3004'];
        }
        $data = [];
        $data = [
            'initiate_admin_id' => $adminId,
            'business_id'       => $business_id,
            'uid'               => $user['id'],
            'mobile'            => $mobile,
            'credit'            => $num,
            'status'            => 1,
        ];

        Db::startTrans();
        try {
            $updateRes = DbAdministrator::AdminRemittance($data);
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getRechargeApplication($page, $pageNum, $id = 0, $getall) {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getAdminRemittance(['id' => $id], '*', true);
        } else {
            if ($getall == 1) {
                $result = DbAdministrator::getAdminRemittance([], '*', false);
            } else {
                $result = DbAdministrator::getAdminRemittance([], '*', false, '', $offset . ',' . $pageNum);
            }
        }
        return ['code' => '200', 'data' => $result];
    }

    public function aduitRechargeApplication($status, $message, $id){
        $adminRemittance =  DbAdministrator::getAdminRemittance(['id' => $id], '*', true);
        if (empty($adminRemittance)) {
            return ['code' => '3001'];
        }
        if ($adminRemittance['status'] > 1){
            return ['code' => '3003'];
        }
        $userEquities = DbAdministrator::getUserEquities(['uid' => $adminRemittance['uid'],'business_id' => $adminRemittance['business_id']],'id',true);
        Db::startTrans();
        try {
            $updateRes = DbAdministrator::editAdminRemittance(['status' => $status, 'message' => $message],$id);
            if ($status == 2) {
                $expenseLog = [];
                $expenseLog = [
                    'uid' => $adminRemittance['uid'],
                    'business_id' => $adminRemittance['business_id'],
                    'money' => $adminRemittance['credit'],
                    'befor_money' => $userEquities['num_balance'],
                    'after_money' => bcadd($userEquities['num_balance'],$adminRemittance['credit']),
                    'change_type' => 3,
                ];
                DbAdministrator::addServiceConsumptionLog($expenseLog);
                DbAdministrator::modifyBalance($userEquities['id'],$adminRemittance['credit'],'inc');
            }
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }
}