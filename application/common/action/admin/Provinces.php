<?php

namespace app\common\action\admin;

use app\facade\DbGoods;
use app\facade\DbProvinces;
use third\PHPTree;

class Provinces extends CommonIndex {

    /**
     * 省市列表
     * @return array
     * @author zyr
     */
    public function getProvinceCity() {
        $field  = 'id,area_name,pid';
        $where  = [
            'level' => [1, 2],
        ];
        $result = DbProvinces::getAreaInfo($field, $where);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $phptree = new PHPTree($result);
        $phptree->setParam('pk', 'id');
        $result = $phptree->listTree();
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 通过省id获取省下面的所有市
     * @param int $pid 上级id
     * @param int $level 层级 1.省 2.市 3.区
     * @return array
     * @author zyr
     */
    public function getArea($pid, $level) {
        $field    = 'id,area_name,pid';
        $where    = [
            'id'    => $pid,
            'level' => $level - 1,
        ];
        $province = DbProvinces::getAreaInfo($field, $where);
        if (empty($province)) {//判断省市是否存在
            return ['code' => '3001'];
        }
        $where2 = [
            'pid'   => $pid,
            'level' => $level,
        ];
        $result = DbProvinces::getAreaInfo($field, $where2);
        if (empty($result)) {//获取下级列表
            return ['code' => '3000'];
        }
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 获取运费模版的剩余可选省市列表
     * @param $freightId
     * @param $freightDetailId
     * @return array
     */
    public function getProvinceCityByFreight(int $freightId, int $freightDetailId) {
        $detailList           = DbGoods::getSupplierFreightDetail(['freight_id' => $freightId], 'id');
        $idList               = array_column($detailList, 'id');//运费价格详情id
        $areaWhere            = [['freight_detail_id', 'in', $idList],];
        $selectCity           = DbGoods::getSupplierFreightArea($areaWhere, 'city_id');//快递模版的所有价格详情选中的市
        $cityIdList           = array_column($selectCity, 'city_id');//已选择价格模版的city
        $selectDetailCity     = DbGoods::getSupplierFreightArea(['freight_detail_id' => $freightDetailId], 'city_id');//快递模版的所有价格详情选中的市
        $selectDetailCityList = array_column($selectDetailCity, 'city_id');
        $allCity              = DbProvinces::getAreaInfo('id,pid,area_name', ['level' => 2]);
        $allCityId            = array_column($allCity, 'id');//所有市id
        $cityId               = array_diff($allCityId, $cityIdList);//未选的市id(剩下可选的city_id)
//        $provinceId = array_unique(array_column($allCity, 'pid'));
        $city       = DbProvinces::getAreaInfo('id,pid,area_name', [['id', 'in', $cityId]]);//剩下可选的city_id
        $cityDetail = DbProvinces::getAreaInfo('id,pid,area_name', [['id', 'in', $selectDetailCityList]]);//所有价格详情选中的市
        $cityList   = array_merge($city, $cityDetail);
        $allList    = [];
        foreach ($cityList as $val) {
            if (in_array($val['id'], $cityId)) {
                $val['status'] = 1;//可选的
            } else {
                $val['status'] = 2;//已选的
            }
            array_push($allList, $val);
        }
        $provinceId = array_unique(array_column($allList, 'pid'));
        $province   = DbProvinces::getAreaInfo('id,pid,area_name', [['id', 'in', $provinceId]]);
        $result     = array_merge($province, $allList);
        $phptree    = new PHPTree($result);
        $phptree->setParam('pk', 'id');
        $result = $phptree->listTree();
        return ['code' => '200', 'data' => $result];
    }
}