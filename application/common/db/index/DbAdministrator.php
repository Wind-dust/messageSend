<?php

namespace app\common\db\index;

use app\common\model\AdminRemittance;
use app\common\model\Business;
use app\common\model\UserEquities;
use app\common\model\UserQualification;
use app\common\model\UserQualificationRecord;
use app\common\model\ExpenseLog;
use app\common\model\ServiceConsumptionLog;
use think\Db;

class DbAdministrator {
    /**
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return array
     * @author rzc
     */
    public function getBusiness($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = Business::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function addBusiness($data) {
        $Business = new Business;
        $Business->save($data);
        return $Business->id;
    }

    public function editBusiness($data, $id) {
        $Business = new Business;
        return $Business->save($data, ['id' => $id]);
    }

    private function getResult($obj, $row = false, $orderBy = '', $limit = '') {
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

    public function getUserQualificationRecord($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = UserQualificationRecord::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function addUserQualificationRecord($data) {
        $UserQualificationRecord = new UserQualificationRecord;
        $UserQualificationRecord->save($data);
        return $UserQualificationRecord->id;
    }

    public function editUserQualificationRecord($data, $id) {
        $UserQualificationRecord = new UserQualificationRecord;
        return $UserQualificationRecord->save($data, ['id' => $id]);
    }

    public function getUserQualification($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = UserQualification::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function addUserQualification($data) {
        $UserQualification = new UserQualification;
        $UserQualification->save($data);
        return $UserQualification->id;
    }

    public function editUserQualification($data, $id) {
        $UserQualification = new UserQualification;
        return $UserQualification->save($data, ['id' => $id]);
    }

    public function getUserEquities($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = UserEquities::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function addUserEquities($data) {
        $UserEquities = new UserEquities;
        $UserEquities->save($data);
        return $UserEquities->id;
    }

    public function editUserEquities($data, $id) {
        $UserEquities = new UserEquities;
        return $UserEquities->save($data, ['id' => $id]);
    }

    public function getAdminRemittance($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = AdminRemittance::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function addAdminRemittance($data) {
        $AdminRemittance = new AdminRemittance;
        $AdminRemittance->save($data);
        return $AdminRemittance->id;
    }

    public function editAdminRemittance($data, $id) {
        $AdminRemittance = new AdminRemittance;
        return $AdminRemittance->save($data, ['id' => $id]);
    }

    /**
     * 改短信余额
     * @param $uid
     * @param $balance
     * @param string $modify 增加/减少 inc/dec
     * @author zyr
     */
    public function modifyBalance($id, $balance, $modify = 'dec') {
        $UserEquities          = UserEquities::get($id);
        $UserEquities->num_balance = [$modify, $balance];
        $UserEquities->save();
    }

    public function getExpenseLog($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = ExpenseLog::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function addExpenseLog($data) {
        $ExpenseLog = new ExpenseLog;
        $ExpenseLog->save($data);
        return $ExpenseLog->id;
    }

    public function editExpenseLog($data, $id) {
        $ExpenseLog = new ExpenseLog;
        return $ExpenseLog->save($data, ['id' => $id]);
    }

    public function getServiceConsumptionLog($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = ServiceConsumptionLog::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function addServiceConsumptionLog($data) {
        $ServiceConsumptionLog = new ServiceConsumptionLog;
        $ServiceConsumptionLog->save($data);
        return $ServiceConsumptionLog->id;
    }

    public function editServiceConsumptionLog($data, $id) {
        $ServiceConsumptionLog = new ServiceConsumptionLog;
        return $ServiceConsumptionLog->save($data, ['id' => $id]);
    }
}