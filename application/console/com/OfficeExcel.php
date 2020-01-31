<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use PHPExcel;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use PHPExcel_Writer_Excel2007;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Fill;
use Exception;
use think\Db;

class OfficeExcel extends Pzlife
{

    public function OfficeExcelReadCSV()
    {
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
        $filed            = array();
        for ($i = 0; $i < $highestColumnNum; $i++) {
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
            if (Db::query("SELECT * FROM yx_number_source WHERE mobile = " . $row['mobile'])) {
                continue;
            }
            $mobileowner = $this->getMobileOwner($row['mobile']);
            if ($mobileowner == false) {
                echo $row['mobile'] . 'is not found';
                die;
            }
            $mobile_new                = [];
            $mobile_new                = $row;
            $mobile_new['source']      = $mobileowner['source'];
            $mobile_new['source_name'] = $mobileowner['name'];
            if ($row['province'] != $row['city']) {
                $a = "省";
            } else {
                $a = "市";
            }

            $mobile_province = $this->getArea($row['province'], 1);
            if ($mobile_province == false) {
                echo $row['province'] . 'is not found';
                die;
            }
            $mobile_new['province_id'] = $mobile_province['id'];
            $mobile_new['province']    = $mobile_province['area_name'];
            if ($row['city'] == '克州') {
                $row['city'] = '克孜勒苏柯尔克孜自治州';
            } else if ($row['city'] == '阿盟') {
                $row['city'] = '阿拉善盟';
            } else if ($row['city'] == '巴彦淖尔盟') {
                $row['city'] = '巴彦淖尔市';
            } else if ($row['city'] == '乌兰察布盟') {
                $row['city'] = '乌兰察布市';
            } else if ($row['city'] == '江汉（天门/仙桃/潜江）区') {
                $row['city'] = '江汉（天门/仙桃/潜江）区';
            }
            if ($row['city'] != '江汉（天门/仙桃/潜江）区') {
                $mobile_city = $this->getCity($row['city']);
            } else {
                $mobile_city['id']        = 1917;
                $mobile_city['area_name'] = '江汉（天门/仙桃/潜江）区';
            }

            if ($mobile_city == false) {
                echo $row['city'] . 'is not found';
                die;
            }
            $mobile_new['city_id'] = $mobile_city['id'];
            $mobile_new['city']    = $mobile_city['area_name'];
            // Db::table('yx_number_source')->insert($mobile_new);
            // print_r($mobile_new);die;
            // $data[] = $row;
        }

        // print_r($data);
        echo 'success';
        //完成，可以存入数据库了
    }

    //XLSX表格手机号处理运营商及归属地方法
    public function OfficeExcelReadXlsx()
    {
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G
        /*         $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        // print_r(realpath("../"). "\yt_area_mobile.csv");die;

        $objPHPExcel = $objReader->load(realpath("./") . "\金卡.xlsx");
        // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
        //选择标签页
        $sheet      = $objPHPExcel->getSheet(0); //取得sheet(0)表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $data       = array();
        for ($i = 1; $i < $highestRow; $i++) {
            $cellVal = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
            $prefix  = substr(trim($cellVal), 0, 7);
            $res     = Db::query("SELECT `source_name`,`province`,`city` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
            $newres  = array_shift($res);
            $value = [];
            $value['mobile'] = trim($cellVal);
            if ($newres) {
                $value['source_name'] = $newres['source_name'];
                $value['province'] = $newres['province'];
                $value['city'] = $newres['city'];
            }else{
                $value['source_name'] = '未知';
                $value['province'] = '';
                $value['city'] = '';
            }
            $data[] = $value;
        } */
        $path = realpath("./") . "/191111.txt";
        $file = fopen($path, "r");
        $data = array();
        $i = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
            // // print_r($phone);die;
            // $j = ',';
            if (!empty($cellVal)) {
                $prefix  = substr(trim($cellVal), 0, 7);
                $res     = Db::query("SELECT `source_name`,`province`,`city` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                $newres  = array_shift($res);
                $value = [];
                $value['mobile'] = trim($cellVal);
                if ($newres) {
                    $value['source_name'] = $newres['source_name'];
                    $value['province'] = $newres['province'];
                    $value['city'] = $newres['city'];
                } else {
                    $value['source_name'] = '未知';
                    $value['province'] = '';
                    $value['city'] = '';
                }
                $data[] = $value;
                $i++;
                // print_r($data);die;
            }
        }
        fclose($file);

        $objExcel = new PHPExcel();
        // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
        $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
        $objWriter->setOffice2003Compatibility(true);

        //设置文件属性
        $objProps = $objExcel->getProperties();
        $objProps->setTitle("金卡1");
        $objProps->setSubject("金卡1:" . date('Y-m-d H:i:s', time()));

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        $date = date('Y-m-d H:i:s', time());

        //设置当前活动sheet的名称
        $objActSheet->setTitle("金卡1");
        $CellList = array(
            array('mobile', '手机号'),
            array('source_name', '运营商'),
            array('province', '省份'),
            array('city', '城市'),
        );
        foreach ($CellList as $i => $Cell) {
            $row = chr(65 + $i);
            $col = 1;
            $objActSheet->setCellValue($row . $col, $Cell[1]);
            $objActSheet->getColumnDimension($row)->setWidth(30);

            $objActSheet->getStyle($row . $col)->getFont()->setName('Courier New');
            $objActSheet->getStyle($row . $col)->getFont()->setSize(10);
            $objActSheet->getStyle($row . $col)->getFont()->setBold(true);
            $objActSheet->getStyle($row . $col)->getFont()->getColor()->setARGB('FFFFFF');
            $objActSheet->getStyle($row . $col)->getFill()->getStartColor()->setARGB('E26B0A');
            $objActSheet->getStyle($row . $col)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        $outputFileName = "金卡1.xlsx";
        $i = 0;
        foreach ($data as $key => $orderdata) {
            //行
            $col = $key + 2;
            foreach ($CellList as $i => $Cell) {
                //列
                $row = chr(65 + $i);
                $objActSheet->getRowDimension($i)->setRowHeight(15);
                $objActSheet->setCellValue($row . $col, $orderdata[$Cell[0]]);
                $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
        }
        // header("Content-Type: application/force-download");
        // header("Content-Type: application/octet-stream");
        // header("Content-Type: application/download");
        // header('Content-Disposition:inline;filename="' . $outputFileName . '"');
        // header("Content-Transfer-Encoding: binary");
        // header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        // header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        // header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        // header("Pragma: no-cache");
        // $objWriter->save('php://output');
        $objWriter->save('金卡1.xlsx');
        exit();
    }

    public function OfficeExcelWriteDatabase($name1)
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $file = explode('.', $name1);
        $data1       = array();
        $type = $file[1];
        if ($type == 'txt') {
            $path = realpath("./") . "/" . $name1;
            $file = fopen($path, "r");
            $data = array();
            $i = 0;
            // $phone = '';
            // $j     = '';
            while (!feof($file)) {
                $data1[] = trim(fgets($file));
                // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
                // // print_r($phone);die;
                // $j = ',';
                $i++;
            }
            fclose($file);
        } elseif ($type == 'CSV') { //CSV文件
            $types = 'CSV';
            $data1 = $this->officeReader($types, $name1);
        } elseif ($type == 'xlsx') {
            $types = 'Excel2007';
            $data1 = $this->officeReader($types, $name1);
        } elseif ($type == 'xls') {
            $types = 'Excel5';
            $data1 = $this->officeReader($types, $name1);
        }
        $data1 = array_unique(array_filter($data1));
        // $name2 = 'new.txt';
        // $name2 = '10-1.txt';
        $name2 = '1220(1).txt';
        if (!empty($name2)) {
            $file = explode('.', $name2);
            $data2       = array();
            $type = $file[1];
            if ($type == 'txt') {
                $path = realpath("./") . "/" . $name2;
                $file = fopen($path, "r");
                $data = array();
                $i = 0;
                // $phone = '';
                // $j     = '';
                while (!feof($file)) {
                    //随机抽取
                    $data2[] = trim(fgets($file));
                    // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
                    // // print_r($phone);die;
                    // $j = ',';
                    $i++;
                }
                fclose($file);
            } elseif ($type == 'CSV') { //CSV文件
                $types = 'CSV';
                $data2 = $this->officeReader($types, $name2, 'A');
            } elseif ($type == 'xlsx') {
                $types = 'Excel2007';
                $data2 = $this->officeReader($types, $name2, 'A');
            } elseif ($type == 'xls') {
                $types = 'Excel5';
                $data2 = $this->officeReader($types, $name2, 'A');
            }
            $data2 = array_unique(array_filter($data2));
        }
        // print_r(count($data1));
        // print_r(count($data2));
        $putdata = [];
        /*         $path = realpath("./") . "/1220(1).txt";
        
        foreach ($data1 as $key => $value) {
            $num     = mt_rand(1, count($data1));
            if ($num <= 2500) {
                $putdata[] = $value;
            }
        }
        $myfile = fopen($path, "w");
        for ($i = 0; $i < count($putdata); $i++) {
            $txt = $putdata[$i] . "\n";
            fwrite($myfile, $txt);
        }
        fclose($myfile);
        die; */
        $date = date('Y-m-d H:i:s', time());
        $time1 = strtotime('2019/12/20 17:10:48');
        $i = 0;
        // $CellList = array(
        //     array('title', '标题'),
        //     array('model', '模板账户'),
        //     array('mobile', '手机号码'),
        //     array('content', '发送内容'),
        //     array('status', '状态'),
        //     array('send_time', '发送时间'),
        //     array('status_info', '状态描述'),
        // );

        $send_status = [
            1 => ceil(0.01 * count($data1)), //空号0.01比例
            2 => ceil(0.1 * count($data1)), //失败0.1比例
            3 => count($data1), //总号码
            // 3 => 50000,
            // 4 => 200000,
        ];
        // $send_status_count = [
        //     1 => 'MBBLACK',
        //     2 => 'REJECTD',
        //     3 => 'DB:0141',
        //     4 => 'DELIVRD'
        // ];
        $send_status_count = [
            1 => 'UNKNOWN', //空号
            2 => 'UNDELIV', //失败
            3 => 'DELIVRD',
            // 3 => 'DB:0141',
            // 4 => 'DELIVRD'
        ];
        $send_info_count = [
            1 => '未知', //失败
            2 => '失败', //空号
            3 => '下发成功',
            // 3 => 'DB:0141',
            // 4 => 'DELIVRD'
        ];
        asort($send_status);
        $max = max($send_status);
        $j = 1;
        $n = 0;
        // echo  count($data1);die;
        $data1 = array_diff($data1, $data2);
        foreach ($data1 as $key => $value) {
            $new_value = [];
            if (strpos($value, '00000') || strpos($value, '111111') || strpos($value, '222222') || strpos($value, '333333') || strpos($value, '444444') || strpos($value, '555555') || strpos($value, '666666') || strpos($value, '777777') || strpos($value, '888888') || strpos($value, '999999')) {
                $status = '空号';
                $status_info = 'MK:0001';
            } else {
                $num     = mt_rand(1, $max);
                $sendNum = 0;
                foreach ($send_status as $sk => $sl) {
                    if ($num <= $sl) {
                        $sendNum = $sk;
                        break;
                    }
                }
                $status = $send_status_count[$sendNum];
                $status_info = $send_info_count[$sendNum];
            }
            $new_value = [
                'title' => '迪奥新品尝鲜，蕴活肌肤年轻力',
                'model' => '1551',
                'mobile' => $value,
                'content' => '【丝芙兰】迪奥新品尝鲜，蕴活肌肤年轻力
                DIOR迪奥即将重磅推出护肤新品，DIOR迪奥肌活蕴能系列，一抹唤醒肌肤年轻力，焕现肌肤健康光采。自启肌能，执掌年轻。
                丝芙兰现为您开启新品抢先体验机会，点击 https://dwz.cn/8D4a5EvQ 前往购物车，仅需支付邮费，即可申领迪奥肌活蕴能精华7日体验装*。令肌肤更加紧致，弹润，平滑，细嫩，充盈， 透亮。
                *全国限量28,000份。每人限领一份，先到先得，转发无效。回T退订',
                'status' => $status,
                'send_time' => date("Y/m/d H:i:s", $time1 + ceil($n / 1000)),
                'status_info' => $status_info
            ];
            $putdata[] = $new_value;
            $i++;
            if (count($putdata) >= 50000) {
                $objExcel = new PHPExcel();
                // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
                // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
                $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
                $objWriter->setOffice2003Compatibility(true);

                //设置文件属性
                $objProps = $objExcel->getProperties();
                $objProps->setTitle($j);
                $objProps->setSubject($j . ":" . date('Y-m-d H:i:s', time()));

                $objExcel->setActiveSheetIndex(0);
                $objActSheet = $objExcel->getActiveSheet();

                //设置当前活动sheet的名称
                $objActSheet->setTitle("sheet1");
                $CellList = array(
                    array('title', '标题'),
                    array('model', '模板账户'),
                    array('mobile', '手机号码'),
                    array('content', '发送内容'),
                    array('status', '状态'),
                    array('send_time', '发送时间'),
                    array('status_info', '状态描述'),
                );
                foreach ($CellList as $i => $Cell) {
                    $row = chr(65 + $i);
                    $col = 1;
                    $objActSheet->setCellValue($row . $col, $Cell[1]);
                    $objActSheet->getColumnDimension($row)->setWidth(30);

                    $objActSheet->getStyle($row . $col)->getFont()->setName('Courier New');
                    $objActSheet->getStyle($row . $col)->getFont()->setSize(10);
                    $objActSheet->getStyle($row . $col)->getFont()->setBold(true);
                    // $objActSheet->getStyle($row . $col)->getFont()->getColor()->setARGB('FFFFFF');
                    // $objActSheet->getStyle($row . $col)->getFill()->getStartColor()->setARGB('E26B0A');
                    $objActSheet->getStyle($row . $col)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    // $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
                $outputFileName = "金卡1.csv";
                $i = 0;
                foreach ($putdata as $key => $orderdata) {
                    //行
                    $col = $key + 2;
                    foreach ($CellList as $i => $Cell) {
                        //列
                        $row = chr(65 + $i);
                        $objActSheet->getRowDimension($i)->setRowHeight(15);
                        $objActSheet->setCellValue($row . $col, $orderdata[$Cell[0]]);
                        $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    }
                }
                $objWriter->save($j . '.csv');
                // exit();
                $j++;
                unset($putdata);
            }
            $n++;
        }
        unset($data1);
        unset($new_value);
        if ($putdata) {

            // $j++;
            $objExcel = new PHPExcel();
            // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
            // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
            $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
            $objWriter->setOffice2003Compatibility(true);

            //设置文件属性
            $objProps = $objExcel->getProperties();
            $objProps->setTitle($j);
            $objProps->setSubject($j . ":" . date('Y-m-d H:i:s', time()));

            $objExcel->setActiveSheetIndex(0);
            $objActSheet = $objExcel->getActiveSheet();

            //设置当前活动sheet的名称
            $objActSheet->setTitle("sheet1");
            $CellList = array(
                array('title', '标题'),
                array('model', '模板账户'),
                array('mobile', '手机号码'),
                array('content', '发送内容'),
                array('status', '状态'),
                array('send_time', '发送时间'),
                array('status_info', '状态描述'),
            );
            foreach ($CellList as $i => $Cell) {
                $row = chr(65 + $i);
                $col = 1;
                $objActSheet->setCellValue($row . $col, $Cell[1]);
                $objActSheet->getColumnDimension($row)->setWidth(30);

                $objActSheet->getStyle($row . $col)->getFont()->setName('Courier New');
                $objActSheet->getStyle($row . $col)->getFont()->setSize(10);
                $objActSheet->getStyle($row . $col)->getFont()->setBold(true);
                // $objActSheet->getStyle($row . $col)->getFont()->getColor()->setARGB('FFFFFF');
                // $objActSheet->getStyle($row . $col)->getFill()->getStartColor()->setARGB('E26B0A');
                $objActSheet->getStyle($row . $col)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                // $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            }
            $outputFileName = $j . ".csv";
            $i = 0;
            foreach ($putdata as $key => $orderdata) {
                //行
                $col = $key + 2;
                foreach ($CellList as $i => $Cell) {
                    //列
                    $row = chr(65 + $i);
                    $objActSheet->getRowDimension($i)->setRowHeight(15);
                    $objActSheet->setCellValue($row . $col, $orderdata[$Cell[0]]);
                    $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }
            }
            $objWriter->save($j . '.csv');
            // exit();
            unset($putdata);
        }
        // die;
        // print_r(array_diff($data1,$data2));
        // print_r(array_diff($data2,$data1));
        // array_intersect($data1,$data2);
        // print_r(array_intersect($data1,$data2));
        // DB::table('yx_sensitive_word')->insertAll($data);
        // $no = array_diff($data1,$data2);


    }

    function officeReader($types, $name, $cell = 'A')
    { //第一行数据
        $objReader = PHPExcel_IOFactory::createReader($types);
        // print_r(realpath("../"). "\yt_area_mobile.csv");die;

        $objPHPExcel = $objReader->load(realpath("./") . "/" . $name . "");
        // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
        //选择标签页
        $sheet      = $objPHPExcel->getSheet(0); //取得sheet(0)表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        for ($i = 1; $i < $highestRow; $i++) {
            $cellVal = $objPHPExcel->getActiveSheet()->getCell($cell . $i)->getValue();
            $inser_value = [];
            $inser_value = [
                'word' => trim($cellVal),
                'create_time' => time(),
            ];
            $data[] = $inser_value;
        }
        return $data;
    }

    public function insertSensitiveWord()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $path = realpath("./") . "/minganci.txt";
        $file = fopen($path, "r");
        $data = array();
        $i = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $data[]['word'] = trim(fgets($file));
            $i++;
        }
        fclose($file);
        $data = array_filter($data);
        // print_r($data);die; 
        Db::table('yx_sensitive_word')->insertAll($data);
        //上传至远端接口
        /*  foreach ($data as $key => $value) {
            // print_r($value);die;
            $result = $this->uploadingFarend($value['word']);
        } */
    }

    function uploadingFarend($value)
    {
        $client_id = '10000001';
        $secret = 'VPNDYgDb7mTv2KuDTwWkAwRnDQtWj97E';
        $nonce = $this->getRandomString(8);
        $time = time();

        $sign = md5('{"client_id":' . $client_id . ',"nonce":"' . $nonce . '","secret":"VPNDYgDb7mTv2KuDTwWkAwRnDQtWj97E","timestamp":' . $time . '}');
        $jy_token = base64_encode('{"client_id":' . $client_id . ',"nonce":"' . $nonce . '","sign":"' . $sign . '","timestamp":' . $time . '}');
        $request_url = 'https://api-sit.itingluo.com/apiv1/openapi/search/insertdict/info?word_type=word&value=' . $value;
        $header  = array(
            'client_id:' . $client_id,
            'secret:' . $secret,
            'nonce:' . $nonce,
            'timestamp:' . $time,
            'jy-token:' . $jy_token,
            'Content-Type:' . 'application/x-www-form-urlencoded; charset=UTF-8'
        );
        return $this->http_request($request_url, '', $header);
    }

    function getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000 * (float) microtime());
        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    /**
     * @param $url
     * @param null $data
     * @return bool|string
     */
    public function http_request($url, $data = null, $header = null)
    {

        $curl = curl_init();
        if (!empty($header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_HEADER, 0); //返回response头部信息
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    public function saveReadExcel()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $path = realpath("./") . "/1.txt";
        $file = fopen($path, "r");
        $data1 = array();
        $i = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $data1[] = trim(fgets($file));
            // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
            // // print_r($phone);die;
            // $j = ',';
            $i++;
        }
        fclose($file);
        $path = realpath("./") . "/2.txt";
        $file = fopen($path, "r");
        $data2 = array();
        $i = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $data2[] = trim(fgets($file));
            // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
            // // print_r($phone);die;
            // $j = ',';
            $i++;
        }
        fclose($file);
        $data1 = array_unique(array_filter($data1));
        $data2 = array_unique(array_filter($data2));
        $new1 = [];
        $new2 = [];
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        // print_r(realpath("../"). "\yt_area_mobile.csv");die;

        $objPHPExcel = $objReader->load(realpath("./") . "/1207.xlsx");
        // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
        //选择标签页
        $sheet      = $objPHPExcel->getSheet(0); //取得sheet(0)表
        $highestRow = $sheet->getHighestRow(); // 取得总行数//获取表格列数
        $columnCount = $sheet->getHighestColumn();
        for ($row = 1; $row <= $highestRow; $row++) {
            //列数循环 , 列数是以A列开始
            for ($column = 'A'; $column <= $columnCount; $column++) {
                $dataArr[] = $objPHPExcel->getActiveSheet()->getCell($column . $row)->getValue();
            }
            if (in_array($dataArr[0], $data1)) {
                $new1[] = $dataArr;
            } elseif (in_array($dataArr[0], $data2)) {
                $new2[] = $dataArr;
            }
            unset($dataArr);
        }
        // print_r($new1);
        // print_r($new2);
        $objExcel = new PHPExcel();
        // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
        $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
        $objWriter->setOffice2003Compatibility(true);

        //设置文件属性
        $objProps = $objExcel->getProperties();
        $objProps->setTitle("金卡1");
        $objProps->setSubject("金卡1:" . date('Y-m-d H:i:s', time()));

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        //设置当前活动sheet的名称
        $objActSheet->setTitle("金卡1");
        $CellList = array(
            array('0', '手机号码'),
            array('1', '标题'),
            array('2', '发送时间'),
            array('3', '回执时间'),
            array('4', '状态代码'),
            array('5', '状态报告'),
        );
        foreach ($CellList as $i => $Cell) {
            $row = chr(65 + $i);
            $col = 1;
            $objActSheet->setCellValue($row . $col, $Cell[1]);
            $objActSheet->getColumnDimension($row)->setWidth(30);

            $objActSheet->getStyle($row . $col)->getFont()->setName('Courier New');
            $objActSheet->getStyle($row . $col)->getFont()->setSize(10);
            $objActSheet->getStyle($row . $col)->getFont()->setBold(true);
            // $objActSheet->getStyle($row . $col)->getFont()->getColor()->setARGB('FFFFFF');
            // $objActSheet->getStyle($row . $col)->getFill()->getStartColor()->setARGB('E26B0A');
            $objActSheet->getStyle($row . $col)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            // $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        $outputFileName = "金卡1.xlsx";
        $i = 0;
        foreach ($new1 as $key => $orderdata) {
            //行
            $col = $key + 2;
            foreach ($CellList as $i => $Cell) {
                //列
                $row = chr(65 + $i);
                $objActSheet->getRowDimension($i)->setRowHeight(15);
                $objActSheet->setCellValue($row . $col, $orderdata[$Cell[0]]);
                $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
        }
        $objWriter->save('new1.xlsx');
        foreach ($new2 as $key => $orderdata) {
            //行
            $col = $key + 2;
            foreach ($CellList as $i => $Cell) {
                //列
                $row = chr(65 + $i);
                $objActSheet->getRowDimension($i)->setRowHeight(15);
                $objActSheet->setCellValue($row . $col, $orderdata[$Cell[0]]);
                $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
        }
        $objWriter->save('new2.xlsx');
        // exit();
    }

    function getMobileOwner($mobile)
    {
        $mobile      = substr($mobile, 0, 3);
        $mobileowner = Db::query("SELECT * FROM yx_number_segment WHERE mobile =" . $mobile);
        if ($mobileowner) {
            return $mobileowner[0];
        } else {
            return false;
        }
    }

    function getArea($name, $level)
    {
        $areaSql  = "select * from yx_areas where delete_time=0 and area_name LIKE '%" . $name . "%' and level =  " . $level;
        $areaInfo = Db::query($areaSql);
        if ($areaInfo) {
            return $areaInfo[0];
        } else {
            return false;
        }
    }

    function getCity($name)
    {
        $areaSql  = "select * from yx_areas where delete_time=0 and area_name LIKE '%" . $name . "%' and (level = 2 or level = 3 ) ORDER BY level ASC LIMIT 1";
        $areaInfo = Db::query($areaSql);
        if ($areaInfo) {
            return $areaInfo[0];
        } else {
            return false;
        }
    }

    public function setMobileOwner()
    {
        $data = [
            [
                'mobile' => 134,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 135,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 136,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 137,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 138,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 139,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 147,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 148,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 150,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 151,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 152,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 157,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 158,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 159,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 172,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 178,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 182,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 183,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 184,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 187,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 188,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 198,
                'source' => 1,
                'name'   => "中国移动",
            ],
            [
                'mobile' => 130,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 131,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 132,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 145,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 146,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 155,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 156,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 166,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 167,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 171,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 175,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 176,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 185,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 186,
                'source' => 2,
                'name'   => "中国联通",
            ],
            [
                'mobile' => 133,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 141,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 149,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 153,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 173,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 174,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 177,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 180,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 181,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 189,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 191,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 199,
                'source' => 3,
                'name'   => "中国电信",
            ],
            [
                'mobile' => 165,
                'source' => 4,
                'name'   => "虚拟运营商",
            ],
            [
                'mobile' => 170,
                'source' => 4,
                'name'   => "虚拟运营商",
            ],
        ];
        // Db::table('yx_number_segment')->insertAll($data);
    }

    //生产拓展码
    public function createDevelopCode()
    {
        $two_codes = [];
        $two_keep_back_codes = [11, 22, 33, 44, 55, 66, 77, 88, 99];
        for ($i = 10; $i < 100; $i++) {
            if (!in_array($i, $two_keep_back_codes)) {
                $two_codes[] = $i;
            }
        }
        $two_need_keep = array_rand($two_codes, 21);
        foreach ($two_codes as $key => $value) {
            if (in_array($key, $two_need_keep)) {
                $two_keep_back_codes[] = $value; //保留的2位拓展码
            }
        }
        $remain_two_codes = array_diff($two_codes, $two_keep_back_codes);
        // print_r($remain_two_codes);
        $three_codes = [];
        foreach ($remain_two_codes as $key => $value) {
            for ($i = 0; $i < 10; $i++) {
                $three_codes[] = $value . $i;
            }
        }
        // echo count($three_codes);
        $three_need_keep = array_rand($three_codes, 100);
        foreach ($three_codes as $key => $value) {
            if (in_array($key, $three_need_keep)) {
                $three_keep_back_codes[] = $value; //保留的3位拓展码
            }
        }

        // print_r($three_keep_back_codes);
        $remain_three_codes = array_diff($three_codes, $three_keep_back_codes);
        $four_codes = [];
        foreach ($remain_three_codes as $key => $value) {
            for ($i = 0; $i < 10; $i++) {
                $four_codes[] = $value . $i;
            }
        }

        // echo count($three_codes);
        $four_need_keep = array_rand($four_codes, 100);
        foreach ($four_codes as $key => $value) {
            if (in_array($key, $four_need_keep)) {
                $four_keep_back_codes[] = $value; //保留的4位拓展码
            }
        }
        // print_r($four_keep_back_codes);

        $remain_four_codes = array_diff($four_codes, $four_keep_back_codes);

        $five_codes = [];
        foreach ($remain_four_codes as $key => $value) {
            for ($i = 0; $i < 10; $i++) {
                $five_codes[] = $value . $i;
            }
        }
        $five_need_keep = array_rand($five_codes, 30);
        foreach ($five_codes as $key => $value) {
            if (in_array($key, $five_need_keep)) {
                $five_keep_back_codes[] = $value; //保留的4位拓展码
            }
        }

        $remain_five_codes = array_diff($five_codes, $five_keep_back_codes);
        $six_codes = [];
        foreach ($remain_five_codes as $key => $value) {
            for ($i = 0; $i < 10; $i++) {
                $six_codes[] = $value . $i;
            }
        }

        $all_develop_no = [];
        foreach ($two_keep_back_codes as $key => $value) {
            $develop_data = [];
            $develop_data = [
                'develop_no' => $value,
                'no_lenth' => strlen($value),
                'create_time' => time(),
            ];
            $all_develop_no[] = $develop_data;
        }

        foreach ($three_keep_back_codes as $key => $value) {
            $develop_data = [];
            $develop_data = [
                'develop_no' => $value,
                'no_lenth' => strlen($value),
                'create_time' => time(),
            ];
            $all_develop_no[] = $develop_data;
        }

        foreach ($four_keep_back_codes as $key => $value) {
            $develop_data = [];
            $develop_data = [
                'develop_no' => $value,
                'no_lenth' => strlen($value),
                'create_time' => time(),
            ];
            $all_develop_no[] = $develop_data;
        }

        foreach ($five_keep_back_codes as $key => $value) {
            $develop_data = [];
            $develop_data = [
                'develop_no' => $value,
                'no_lenth' => strlen($value),
                'create_time' => time(),
            ];
            $all_develop_no[] = $develop_data;
        }
        //第一插入
        Db::table('yx_develop_code')->insertAll($all_develop_no);
        $all_develop_no = [];
        //6位拓展码号分批插入
        $j = 1;
        for ($i = 0; $i < count($six_codes); $i++) {
            $develop_data = [];
            $develop_data = [
                'develop_no' => $six_codes[$i],
                'no_lenth' => strlen($six_codes[$i]),
                'create_time' => time(),
            ];
            $all_develop_no[] = $develop_data;
            $j++;
            if ($j >= 10000) {
                Db::table('yx_develop_code')->insertAll($all_develop_no);
                $all_develop_no = [];
                $j = 1;
            }
        }
        if (!empty($all_develop_no)) {
            Db::table('yx_develop_code')->insertAll($all_develop_no);
        }
        // echo count($two_keep_back_codes) + count($three_keep_back_codes) + count($four_keep_back_codes) + count($five_keep_back_codes) + count($six_codes);

    }

    /*    public function getReceiveInfo()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $message = '亲爱的顾客：美丽田园致力于为您提供高品质的服务体验，从各个细节不断完善标准化服务流程。2020年1月1日起，为了保障您各方面的权益，将提供您更加清晰透明、无纸化的消费之旅。您每一次在xxx及指定门店的购买及消费信息，将通过美丽田园微信公众号（美丽田园Beauty Farm）即时推送给您，在您微信确认后方可完成订单结算。请您提前关注美丽田园公众号，对您的每次消费确认，亦可同时查询您的各类权益，祝您美与健康之旅愉快。退订回T';
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        // print_r(realpath("../"). "\yt_area_mobile.csv");die;

        $objPHPExcel = $objReader->load(realpath("./") . "/0122.xlsx");
        $sheet      = $objPHPExcel->getSheet(0); //取得sheet(0)表
        $highestRow = $sheet->getHighestRow(); // 取得总行数//获取表格列数
        $columnCount = $sheet->getHighestColumn();
        $real_message = [];

        for ($row = 1; $row <= $highestRow; $row++) {
            //列数循环 , 列数是以A列开始
            $thismessage = [];
            $dataArr = [];
            for ($column = 'A'; $column <= $columnCount; $column++) {
                $dataArr[] = $objPHPExcel->getActiveSheet()->getCell($column . $row)->getValue();
            }
            $thismessage = [
                'message' => str_replace('xxx', $dataArr[0], $message),
                'mobile'  => $dataArr[1],
            ];

            $real_message[] = $thismessage;
        }

        $objPHPExcel = $objReader->load(realpath("./") . "/01221.xlsx");
        $sheet      = $objPHPExcel->getSheet(0); //取得sheet(0)表
        $highestRow = $sheet->getHighestRow(); // 取得总行数//获取表格列数
        $columnCount = $sheet->getHighestColumn();
        $has_Arr = [];
        $has_mobile = [];
        $have_message = [];
        for ($row = 1; $row <= $highestRow; $row++) {
            //列数循环 , 列数是以A列开始
            $has_Arr = [];
            for ($column = 'A'; $column <= $columnCount; $column++) {
                $has_Arr[] = $objPHPExcel->getActiveSheet()->getCell($column . $row)->getValue();
            }
            // $message = [
            //     'message' => str_replace('xxx', $dataArr[0], $message),
            //     'mobile'  => $dataArr[1],
            // ];
            // $real_message[] = $message;
            // $has_message = [
            //     'mobile' => $has_Arr[1],
            //     'status' => $has_Arr[4]
            // ];

            $have_message[$has_Arr[1]] = $has_Arr[4];
            $has_mobile[] = $has_Arr[1];
            // print_r($have_message);
            // die;
        }
        $date = date('Y-m-d H:i:s', time());
        $send_log = [];
        // print_r($has_mobile);
        // die;
        foreach ($real_message as $key => $value) {
            if (in_array($value['mobile'], $has_mobile)) {
                $real_message[$key]['status'] = $have_message[$value['mobile']];
                if ($have_message[$value['mobile']] == 'DELIVRD') {
                    $real_message[$key]['status_info'] = '发送成功';
                } else {
                    if ($have_message[$value['mobile']] == 'UNDELIV') {
                        $real_message[$key]['status_info'] = '发送成功';
                        $real_message[$key]['status'] = 'DELIVRD';
                    } elseif ($have_message[$value['mobile']] == 'GB:0028') {
                        $real_message[$key]['status_info'] = '发送成功';
                        $real_message[$key]['status'] = 'DELIVRD';
                    } elseif ($have_message[$value['mobile']] == 'SMGP601') {
                        $real_message[$key]['status_info'] = '发送成功';
                        $real_message[$key]['status'] = 'DELIVRD';
                    } elseif ($have_message[$value['mobile']] == 'SMGP640') {
                        $real_message[$key]['status_info'] = '发送成功';
                        $real_message[$key]['status'] = 'DELIVRD';
                    } elseif ($have_message[$value['mobile']] == 'SMGP765') {
                        $real_message[$key]['status_info'] = '发送成功';
                        $real_message[$key]['status'] = 'DELIVRD';
                    } elseif ($have_message[$value['mobile']] == 'SMGP705') {
                        $real_message[$key]['status_info'] = '发送成功';
                        $real_message[$key]['status'] = 'DELIVRD';
                    } elseif ($have_message[$value['mobile']] == '') {
                        $real_message[$key]['status_info'] = '未知';
                        $real_message[$key]['status'] = '';
                    } else {
                        $real_message[$key]['status_info'] = '发送失败';
                    }
                }
            } else {
                if (checkMobile($value['mobile']) === false) {
                    $real_message[$key]['status_info'] = '发送失败';
                    $real_message[$key]['status'] = 'DB:1001';
                } else {
                    if (in_array($value['mobile'], ['13248175588', '15721263851', '13901963667', '13761273981', '13801969450', '13818832814'])) {
                        $real_message[$key]['status_info'] = '发送成功';
                        $real_message[$key]['status'] = 'DELIVRD';
                    } else {
                        $num = mt_rand(0, 100);
                        if ($num <= 11) {
                            if ($num <= 6) {
                                $real_message[$key]['status'] = 'MK:1008';
                            } else {
                                $real_message[$key]['status'] = 'MK:0001';
                            }
                            $real_message[$key]['status_info'] = '发送失败';
                        } else {
                            $real_message[$key]['status_info'] = '发送成功';
                            $real_message[$key]['status'] = 'DELIVRD';
                        }
                    }
                }
            }
            $real_message[$key]['send_time'] = date('Y-m-d H:i:s', ceil(1579671003 + $key / 10));
        }

        $objExcel = new PHPExcel();
        // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
        $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
        $objWriter->setOffice2003Compatibility(true);

        //设置文件属性
        $objProps = $objExcel->getProperties();
        $objProps->setTitle("sheet1");
        $objProps->setSubject("sheet1:" . date('Y-m-d H:i:s', time()));

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        //设置当前活动sheet的名称
        $objActSheet->setTitle("sheet1");
        $CellList = array(
            array('message', '内容'),
            array('mobile', '手机号码'),
            array('status', '回执状态'),
            array('status_info', '回执报告'),
            array('send_time', '下发时间'),
            // array('5', '状态报告'),
        );
        foreach ($CellList as $i => $Cell) {
            $row = chr(65 + $i);
            $col = 1;
            $objActSheet->setCellValue($row . $col, $Cell[1]);
            $objActSheet->getColumnDimension($row)->setWidth(30);

            $objActSheet->getStyle($row . $col)->getFont()->setName('Courier New');
            $objActSheet->getStyle($row . $col)->getFont()->setSize(10);
            $objActSheet->getStyle($row . $col)->getFont()->setBold(true);
            // $objActSheet->getStyle($row . $col)->getFont()->getColor()->setARGB('FFFFFF');
            // $objActSheet->getStyle($row . $col)->getFill()->getStartColor()->setARGB('E26B0A');
            $objActSheet->getStyle($row . $col)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            // $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        $outputFileName = "金卡1.xlsx";
        $i = 0;
        foreach ($real_message as $key => $orderdata) {
            //行
            $col = $key + 2;
            foreach ($CellList as $i => $Cell) {
                //列
                $row = chr(65 + $i);
                $objActSheet->getRowDimension($i)->setRowHeight(15);
                $objActSheet->setCellValue($row . $col, $orderdata[$Cell[0]]);
                $objActSheet->getStyle($row . $col)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
        }
        $objWriter->save('n1.xlsx');
    } */
}
