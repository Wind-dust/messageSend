<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use think\Db;

class OfficeExcel extends Pzlife {

    public function OfficeExcelRead() {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $objReader = PHPExcel_IOFactory::createReader('csv')
            ->setDelimiter(',')
            ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
            ->setEnclosure('"')
            ->setSheetIndex(0);
        // print_r(realpath("../"). "\yt_area_mobile.csv");die;
      
        $objPHPExcel = $objReader->load(realpath("./") . "\yt_area_mobile.csv");
        // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
        //选择标签页
        $sheet            = $objPHPExcel->getSheet(0); //获取行数与列数,注意列数需要转换
        $highestRowNum    = $sheet->getHighestRow();
        $highestColumn    = $sheet->getHighestColumn();
        $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
        $filed            = array();for ($i = 0; $i < $highestColumnNum; $i++) {
            $cellName = PHPExcel_Cell::stringFromColumnIndex($i) . '1';
            $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容
            $filed[]  = $cellVal;
        } //开始取出数据并存入数组
        $data = array();
        for ($i = 2; $i <= $highestRowNum; $i++) {
            //ignore row 1
            $row = array();
            for ($j = 0; $j < $highestColumnNum; $j++) {
                $cellName        = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
                $cellVal         = $sheet->getCell($cellName)->getValue();
                $row[$filed[$j]] = trim($cellVal);
            }
            if (Db::query("SELECT * FROM yx_number_source WHERE mobile = ".$row['mobile'])) 
            {
                continue;
            }
            $mobileowner = $this->getMobileOwner($row['mobile']);
            if ($mobileowner == false) {
                echo $row['mobile'].'is not found';die;
            }
            $mobile_new = [];
            $mobile_new = $row;
            $mobile_new['source'] = $mobileowner['source'];
            $mobile_new['source_name'] = $mobileowner['name'];
            if ($row['province'] != $row['city']) {
                $a = "省";
            }else{
                $a = "市";
            }
            
            $mobile_province = $this->getArea($row['province'],1);
            if ($mobile_province == false) {
                echo $row['province'].'is not found';die;
            }
            $mobile_new['province_id'] = $mobile_province['id'];
            $mobile_new['province'] = $mobile_province['area_name'];
            if ($row['city']== '克州') {
                $row['city'] = '克孜勒苏柯尔克孜自治州';
            }else if ($row['city']== '阿盟') {
                $row['city'] = '阿拉善盟';
            }else if ($row['city']== '巴彦淖尔盟') {
                $row['city'] = '巴彦淖尔市';
            }else if ($row['city']== '乌兰察布盟') {
                $row['city'] = '乌兰察布市';
            }else if ($row['city']== '江汉（天门/仙桃/潜江）区') {
                $row['city'] = '江汉（天门/仙桃/潜江）区';
            }
            if ($row['city'] != '江汉（天门/仙桃/潜江）区') {
                $mobile_city = $this->getCity($row['city']);
            }else {
                $mobile_city['id'] = 1917;
                $mobile_city['area_name'] = '江汉（天门/仙桃/潜江）区';
            }
           
            if ($mobile_city == false) {
                echo $row['city'].'is not found';die;
            }
            $mobile_new['city_id'] = $mobile_city['id'];
            $mobile_new['city'] = $mobile_city['area_name'];
            Db::table('yx_number_source')->insert($mobile_new);
            // print_r($mobile_new);die;
            // $data[] = $row;
        }

        // print_r($data);
        echo 'success';
//完成，可以存入数据库了
    }


    function getMobileOwner($mobile){
        $mobile = substr($mobile,0,3);
        $mobileowner = Db::query("SELECT * FROM yx_number_segment WHERE mobile =".$mobile);
        if ($mobileowner) {
            return $mobileowner[0];
        }else{
            return false;
        }
    }

    function getArea($name, $level) {
        $areaSql  = "select * from yx_areas where delete_time=0 and area_name LIKE '%" . $name . "%' and level =  " . $level;
        $areaInfo = Db::query($areaSql);
        if ($areaInfo) {
            return $areaInfo[0];
        }else{
            return false;
        }
        
    }

    function getCity($name) {
        $areaSql  = "select * from yx_areas where delete_time=0 and area_name LIKE '%" . $name . "%' and (level = 2 or level = 3 ) ORDER BY level ASC LIMIT 1";
        $areaInfo = Db::query($areaSql);
        if ($areaInfo) {
            return $areaInfo[0];
        }else{
            return false;
        }
        
    }

    public function setMobileOwner(){
        $data = [
            [
                'mobile' => 134,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 135,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 136,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 137,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 138,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 139,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 147,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 148,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 150,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 151,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 152,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 157,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 158,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 159,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 172,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 178,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 182,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 183,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 184,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 187,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 188,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 198,
                'source' => 1,
                'name' => "中国移动",
            ],
            [
                'mobile' => 130,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 131,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 132,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 145,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 146,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 155,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 156,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 166,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 167,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 171,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 175,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 176,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 185,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 186,
                'source' => 2,
                'name' => "中国联通",
            ],
            [
                'mobile' => 133,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 141,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 149,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 153,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 173,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 174,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 177,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 180,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 181,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 189,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 191,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 199,
                'source' => 3,
                'name' => "中国电信",
            ],
            [
                'mobile' => 165,
                'source' => 4,
                'name' => "虚拟运营商",
            ],
            [
                'mobile' => 170,
                'source' => 4,
                'name' => "虚拟运营商",
            ],
        ];
        Db::table('yx_number_segment')->insertAll($data);
    }

}
