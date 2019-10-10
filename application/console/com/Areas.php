<?php

namespace app\console\com;

use app\console\Pzlife;
use think\Db;

class Areas extends Pzlife {
    private $year = '2017';

    /**
     * 添加省市区脚本
     */
    public function addAreas() {
        ini_set('memory_limit', '1024M');
        $all = $this->getAreas();
//        print_r($all);die;
        foreach ($all as $allKey => $allVal) {
            $level    = 1;
            $shengSql = "insert into pz_areas (`code`,`area_name`,`level`,`update_time`) values ('" . $allKey . "','" . $allVal['name'] . "','" . $level . "','" . time() . "')";
            Db::execute($shengSql);
            $shengId = Db::getLastInsID();
            foreach ($allVal['city'] as $cityKey => $cityVal) {
                $level   = 2;
                $citySql = "insert into pz_areas (`pid`,`code`,`area_name`,`level`,`update_time`) values ('" . $shengId . "','" . $cityKey . "','" . $cityVal['name'] . "','" . $level . "','" . time() . "')";
                Db::execute($citySql);
                $cityId = Db::getLastInsID();
                foreach ($cityVal['area'] as $arKey => $arVal) {
                    $level  = 3;
                    if ($arVal == '市辖区') {
                        continue;
                    }
                    $arSql = "insert into pz_areas (`pid`,`code`,`area_name`,`level`,`update_time`) values ('" . $cityId . "','" . $arKey . "','" . $arVal . "','" . $level . "','" . time() . "')";
                    Db::execute($arSql);
                }
            }
        }
        exit('成功');
    }


    /**
     * 爬取国家统计局的省市区划分
     * @return array
     */
    private function getAreas() {
        $result = [];
        $curl   = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/' . $this->year . '/index.html');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
        // 裁头
        $offset = mb_strpos($data, 'provincetr', 2000, 'GBK');
        $data   = mb_substr($data, $offset, NULL, 'GBK');
        // 裁尾
        $offset = mb_strpos($data, '</TABLE>', 200, 'GBK');
        $data   = mb_substr($data, 0, $offset, 'GBK');
        // preg_match_all('/\d{2}|[\x7f-\xff]+/', $data, $out);
        preg_match_all('/\d{2}/', $data, $outid);
        preg_match_all('/[\x7f-\xff]+/', $data, $outname);
        $out = array_combine($outid[0], $outname[0]);
        // 省份
        foreach ($out as $key => $val) {
            $result[$key] = ['name' => $val];
        }
        foreach ($out as $key => $val) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/' . $this->year . '/' . $key . '.html');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($curl);
            curl_close($curl);
            $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
            // 裁头
            $offset = mb_strpos($data, 'citytr', 2000, 'GBK');
            $data   = mb_substr($data, $offset, NULL, 'GBK');
            // 裁尾
            $offset = mb_strpos($data, '</TABLE>', 200, 'GBK');
            $data   = mb_substr($data, 0, $offset, 'GBK');
//            preg_match_all('/\d{12}|[\x7f-\xff]+/', $data, $city);
            preg_match_all('/\d{12}/', $data, $code2);
            preg_match_all('/[\x7f-\xff]+/', $data, $codename);
            $city = array_combine($code2[0], $codename[0]);

            foreach ($city as $ckey => $cval) {
                $result[$key]['city'][$ckey] = ['name' => $cval];
            }
            foreach ($city as $ckey => $cval) {
//                $result[$key]['city'][$ckey] = ['name' => $cval];
                $code = substr($ckey, 0, 4);
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/' . substr($code, 0, 2) . '/' . $code . '.html');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                $data = curl_exec($curl);
                curl_close($curl);
                $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
                // 裁头
                $offset = @mb_strpos($data, 'countytr', 2000, 'GBK');
                if (!$offset) {
//                    continue;
                    $offset = @mb_strpos($data, 'towntr', 2000, 'GBK');
                }
                $data = mb_substr($data, $offset, NULL, 'GBK');
                // 裁尾
                $offset = mb_strpos($data, '</TABLE>', 200, 'GBK');
                $data   = mb_substr($data, 0, $offset, 'GBK');
//                preg_match_all('/\d{12}|[\x7f-\xff]+/', $data, $out);
                preg_match_all('/\d{12}/', $data, $area);
                preg_match_all('/[\x7f-\xff]+/', $data, $areaname);
                $areares = array_combine($area[0], $areaname[0]);
//                $result[$key]['city'][$ckey]['area'][$area[0]] = $areaname[0];
                foreach ($areares as $akey => $aval) {
                    $result[$key]['city'][$ckey]['area'][$akey] = $aval;
                }
                unset($areares);
            }
            unset($city);
        }
        unset($out);
        return $result;

//四级行政区：乡村或街道
//        foreach ($code_list as $key => $code) {
//            $code = substr($code, 0, 6);
//            $curl = curl_init();
//            curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/' . substr($code, 0, 2) . '/' . substr($code, 2, 2) . '/' . $code . '.html');
//            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//            $data = curl_exec($curl);
//            curl_close($curl);
//            $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
//// 裁头
//            $offset = @mb_strpos($data, 'towntr', 2000, 'GBK');
//            if (!$offset) {
//                continue;
//            }
//            $data = mb_substr($data, $offset, NULL, 'GBK');
//// 裁尾
//            $offset = mb_strpos($data, '</TABLE>', 200, 'GBK');
//            $data   = mb_substr($data, 0, $offset, 'GBK');
//            preg_match_all('/\d{12}|[\x7f-\xff]+/', $data, $out);
//            $out  = $out[0];
//            $list = [];
//            for ($j = 0; $j < count($out); $j++) {
//                $list[] = [
//                    'code'        => $out[$j],
//                    'name'        => $out[++$j],
//                    'create_time' => $time,
//                    'update_time' => $time,
//                ];
//            }
//            Db::table('town')->insertAll($list);
//        }
    }
}