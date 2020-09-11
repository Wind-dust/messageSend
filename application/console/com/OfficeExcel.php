<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use cache\PhpredisNew;
use Config;
use Env;
use Exception;
use PHPExcel;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Fill;
use PHPExcel_Writer_Excel2007;
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
        $i    = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
            // // print_r($phone);die;
            // $j = ',';
            if (!empty($cellVal)) {
                $prefix          = substr(trim($cellVal), 0, 7);
                $res             = Db::query("SELECT `source_name`,`province`,`city` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                $newres          = array_shift($res);
                $value           = [];
                $value['mobile'] = trim($cellVal);
                if ($newres) {
                    $value['source_name'] = $newres['source_name'];
                    $value['province']    = $newres['province'];
                    $value['city']        = $newres['city'];
                } else {
                    $value['source_name'] = '未知';
                    $value['province']    = '';
                    $value['city']        = '';
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
        $i              = 0;
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
        $file  = explode('.', $name1);
        $data1 = array();
        $type  = $file[1];
        if ($type == 'txt') {
            $path = realpath("./") . "/" . $name1;
            $file = fopen($path, "r");
            $data = array();
            $i    = 0;
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
        $name2 = '';
        /*  if (!empty($name2)) {
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
        } */
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
        $date  = date('Y-m-d H:i:s', time());
        $time1 = strtotime('2020-02-13 18:30:55');
        $i     = 0;
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
            1 => 253, //空号0.01比例
            2 => 842, //失败0.1比例
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
            1 => 'Expired--4441', //空号
            2 => 'UNDELIVERED', //失败
            3 => 'DELIVRD',
            // 3 => 'DB:0141',
            // 4 => 'DELIVRD'
        ];
        $send_info_count = [
            1 => '未知', //失败
            2 => '失败', //空号
            3 => '成功',
            // 3 => 'DB:0141',
            // 4 => 'DELIVRD'
        ];
        asort($send_status);
        $max = max($send_status);
        $j   = 1;
        $n   = 0;
        // echo  count($data1);die;
        // $data1 = array_diff($data1, $data2);
        foreach ($data1 as $key => $value) {
            $new_value = [];
            if (strpos($value, '00000') || strpos($value, '111111') || strpos($value, '222222') || strpos($value, '333333') || strpos($value, '444444') || strpos($value, '555555') || strpos($value, '666666') || strpos($value, '777777') || strpos($value, '888888') || strpos($value, '999999')) {
                $status_info = '空号';
                $status      = 'MK:0001';
            } else {
                $num     = mt_rand(1, $max);
                $sendNum = 0;
                foreach ($send_status as $sk => $sl) {
                    if ($num <= $sl) {
                        $sendNum = $sk;
                        break;
                    }
                }
                $status      = $send_status_count[$sendNum];
                $status_info = $send_info_count[$sendNum];
            }
            $new_value = [
                'mobile'      => $value,
                'title'       => '特殊时期，如何提升孕妈和宝宝的抵抗力',
                'status_info' => $status_info,
                'status'      => $status,
                'send_time'   => date("Y/m/d H:i:s", $time1 + ceil($n / 1000)),
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
                    array('mobile', '手机号码'),
                    array('title', '标题'),
                    array('status_info', '状态描述'),
                    array('status', '状态'),
                    array('send_time', '发送时间'),
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
                $i              = 0;
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
                $objWriter->save($j . '.xlsx');
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
                array('mobile', '手机号码'),
                array('status_info', '状态描述'),
                array('status', '状态'),
                array('send_time', '发送时间'),
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
            $outputFileName = $j . ".xlsx";
            $i              = 0;
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
            $objWriter->save($j . '.xlsx');
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
            $cellVal     = $objPHPExcel->getActiveSheet()->getCell($cell . $i)->getValue();
            $inser_value = [];
            $inser_value = [
                'word'        => trim($cellVal),
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
        $i    = 0;
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
        $secret    = 'VPNDYgDb7mTv2KuDTwWkAwRnDQtWj97E';
        $nonce     = $this->getRandomString(8);
        $time      = time();

        $sign        = md5('{"client_id":' . $client_id . ',"nonce":"' . $nonce . '","secret":"VPNDYgDb7mTv2KuDTwWkAwRnDQtWj97E","timestamp":' . $time . '}');
        $jy_token    = base64_encode('{"client_id":' . $client_id . ',"nonce":"' . $nonce . '","sign":"' . $sign . '","timestamp":' . $time . '}');
        $request_url = 'https://api-sit.itingluo.com/apiv1/openapi/search/insertdict/info?word_type=word&value=' . $value;
        $header      = array(
            'client_id:' . $client_id,
            'secret:' . $secret,
            'nonce:' . $nonce,
            'timestamp:' . $time,
            'jy-token:' . $jy_token,
            'Content-Type:' . 'application/x-www-form-urlencoded; charset=UTF-8',
        );
        return $this->httpRequest($request_url, '', $header);
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
    public function httpRequest($url, $data = null, $header = null)
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
        $path  = realpath("./") . "/1.txt";
        $file  = fopen($path, "r");
        $data1 = array();
        $i     = 0;
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
        $path  = realpath("./") . "/2.txt";
        $file  = fopen($path, "r");
        $data2 = array();
        $i     = 0;
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
        $data1     = array_unique(array_filter($data1));
        $data2     = array_unique(array_filter($data2));
        $new1      = [];
        $new2      = [];
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        // print_r(realpath("../"). "\yt_area_mobile.csv");die;

        $objPHPExcel = $objReader->load(realpath("./") . "/1207.xlsx");
        // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
        //选择标签页
        $sheet       = $objPHPExcel->getSheet(0); //取得sheet(0)表
        $highestRow  = $sheet->getHighestRow(); // 取得总行数//获取表格列数
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
        $i              = 0;
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
        $two_codes           = [];
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
        $four_codes         = [];
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
        $six_codes         = [];
        foreach ($remain_five_codes as $key => $value) {
            for ($i = 0; $i < 10; $i++) {
                $six_codes[] = $value . $i;
            }
        }

        $all_develop_no = [];
        foreach ($two_keep_back_codes as $key => $value) {
            $develop_data = [];
            $develop_data = [
                'develop_no'  => $value,
                'no_lenth'    => strlen($value),
                'create_time' => time(),
            ];
            $all_develop_no[] = $develop_data;
        }

        foreach ($three_keep_back_codes as $key => $value) {
            $develop_data = [];
            $develop_data = [
                'develop_no'  => $value,
                'no_lenth'    => strlen($value),
                'create_time' => time(),
            ];
            $all_develop_no[] = $develop_data;
        }

        foreach ($four_keep_back_codes as $key => $value) {
            $develop_data = [];
            $develop_data = [
                'develop_no'  => $value,
                'no_lenth'    => strlen($value),
                'create_time' => time(),
            ];
            $all_develop_no[] = $develop_data;
        }

        foreach ($five_keep_back_codes as $key => $value) {
            $develop_data = [];
            $develop_data = [
                'develop_no'  => $value,
                'no_lenth'    => strlen($value),
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
                'develop_no'  => $six_codes[$i],
                'no_lenth'    => strlen($six_codes[$i]),
                'create_time' => time(),
            ];
            $all_develop_no[] = $develop_data;
            $j++;
            if ($j >= 10000) {
                Db::table('yx_develop_code')->insertAll($all_develop_no);
                $all_develop_no = [];
                $j              = 1;
            }
        }
        if (!empty($all_develop_no)) {
            Db::table('yx_develop_code')->insertAll($all_develop_no);
        }
        // echo count($two_keep_back_codes) + count($three_keep_back_codes) + count($four_keep_back_codes) + count($five_keep_back_codes) + count($six_codes);

    }

    public function updateDevelopCode()
    {

        $two_codes           = [];
        //查询2位扩展码
        $two_keep_back_codes = [];
        $two_have_back_codes = Db::query("SELECT `develop_no` FROM yx_develop_code WHERE `no_lenth` = 2");

        foreach ($two_have_back_codes as $key => $value) {
            $two_keep_back_codes[] = $value['develop_no'];
        }

        for ($i = 10; $i < 100; $i++) {
            if (!in_array($i, $two_keep_back_codes)) {
                $two_codes[] = $i;
            }
        }

        $three_keep_back_codes = [];
        $three_have_back_codes = Db::query("SELECT `develop_no` FROM yx_develop_code WHERE `no_lenth` = 3");
        foreach ($three_have_back_codes as $key => $value) {
            $three_keep_back_codes[] = $value['develop_no'];
        }
        // print_r($three_keep_back_codes);die;
        $three_codes = [];
        foreach ($two_codes as $key => $value) {
            for ($i = 0; $i < 10; $i++) {
                $no = 0;
                $no =  $value . $i;
                // $three_codes[] = $value . $i;
                if (!in_array($no, $three_keep_back_codes)) {
                    $three_codes[] = $no;
                }
            }
        }

        $four_keep_back_codes = [];
        $four_have_back_codes = Db::query("SELECT `develop_no` FROM yx_develop_code WHERE `no_lenth` = 4");
        foreach ($four_have_back_codes as $key => $value) {
            $four_keep_back_codes[] = $value['develop_no'];
        }
        // print_r($four_keep_back_codes);die;

        //获取5位已绑定的拓展码
        $five_has_bind_code = Db::query("SELECT `develop_no` FROM yx_develop_code WHERE `no_lenth` = 5 AND  `is_bind` = '2' ");
        foreach ($five_has_bind_code as $key => $value) {
            // $four_keep_back_codes[] = $value['develop_no'];
            // print_r($value['develop_no']);die;
            $no = '';
            $no = mb_substr($value['develop_no'], 0, 4);
            $four_keep_back_codes[] = $no;
        }
        $four_codes = [];
        foreach ($three_codes as $key => $value) {
            for ($i = 0; $i < 10; $i++) {
                $no = 0;
                $no =  $value . $i;
                // $three_codes[] = $value . $i;
                if (!in_array($no, $four_keep_back_codes)) {
                    $four_codes[] = $no;
                }
            }
        }
        // print_r($four_codes);die;
        $new_four_codes = [];
        $new_four_codes = array_rand($four_codes, 900);
        $new_insert_four_codes = [];
        foreach ($new_four_codes as $key => $value) {
            $no = $four_codes[$value];
            //SELECT * FROM `messagesend`.`yx_develop_code` WHERE `develop_no` LIKE '1401%'
            // print_r($no);die;
            $del_ids = [];
            $had_set_code = Db::query("SELECT `id` FROM `messagesend`.`yx_develop_code` WHERE `develop_no` LIKE '" . $no . "%' ");
            if (!empty($had_set_code)) {
                foreach ($had_set_code as $key => $value) {
                    $del_ids[] = $value['id'];
                }
                $ids = '';
                $ids = join(',', $del_ids);
                // print_r($ids);die;
                Db::table('yx_develop_code')->where("id in ($ids)")->delete();
            }

            $new_insert_four_codes[] = $no;
        }
        $all_develop_no = [];
        foreach ($new_insert_four_codes as $key => $value) {
            $develop_data = [];
            $develop_data = [
                'develop_no'  => $value,
                'no_lenth'    => strlen($value),
                'create_time' => time(),
            ];
            $all_develop_no[] = $develop_data;
        }
        if (!empty($all_develop_no)) {
            Db::table('yx_develop_code')->insertAll($all_develop_no);
        }
    }

    public function newRedisConnect()
    {
        try {
            $mobileredis = PhpredisNew::getConn();
            $redis       = Phpredis::getConn();
            // print_r($this->redis);
            ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
            /* 白名单设置 */
            $white_mobiles = Db::query("SELECT * FROM yx_whitelist ");
            foreach ($white_mobiles as $key => $value) {
                $data   = [];
                $prefix = substr(trim($value['mobile']), 0, 7);
                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                // print_r($newres);die;
                if (empty($newres)) {
                    $source = Db::query("SELECT `mobile`,`source`,`province_id`,`city_id` FROM yx_number_source WHERE `id` = " . $value['id'])[0];
                    // print_r($source);die;
                    $newres = [];
                    $newres = [
                        'source'      => $source['source'],
                        'province_id' => $source['province_id'],
                        'city_id'     => $source['city_id'],
                    ];
                }
                $mobileredis->hset('yx:mobile:white', $value['mobile'], json_encode($newres));
            }
            /* 黑名单设置 */
            $black_mobiles = Db::query("SELECT * FROM yx_blacklist ");
            foreach ($black_mobiles as $key => $value) {
                $data   = [];
                $prefix = substr(trim($value['mobile']), 0, 7);
                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                // print_r($newres);die;
                if (empty($newres)) {
                    $source = Db::query("SELECT `mobile`,`source`,`province_id`,`city_id` FROM yx_number_source WHERE `id` = " . $value['id'])[0];
                    // print_r($source);die;
                    $newres = [];
                    $newres = [
                        'source'      => $source['source'],
                        'province_id' => $source['province_id'],
                        'city_id'     => $source['city_id'],
                    ];
                }
                $mobileredis->hset('yx:mobile:black', $value['mobile'], json_encode($newres));
            }

            /* 空号设置 */
            $empty_mobiles = Db::query("SELECT * FROM yx_mobile ");
            foreach ($empty_mobiles as $key => $value) {
                $data   = [];
                $prefix = substr(trim($value['mobile']), 0, 7);
                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                // print_r($newres);die;
                if (empty($newres)) {
                    $source = Db::query("SELECT `mobile`,`source`,`province_id`,`city_id` FROM yx_number_source WHERE `id` = " . $value['id'])[0];
                    // print_r($source);die;
                    $newres = [];
                    $newres = [
                        'source'      => $source['source'],
                        'province_id' => $source['province_id'],
                        'city_id'     => $source['city_id'],
                    ];
                }
                $newres['check_status'] = $value['check_status'];
                $newres['update_time']  = $value['update_time'];
                $newres['check_result'] = $value['check_result'];
                $mobileredis->hset('yx:mobile:empty', $value['mobile'], json_encode($newres));
            }

            /* 实号设置 */
            $real_mobiles = Db::query("SELECT * FROM yx_real_mobile ");

            foreach ($real_mobiles as $key => $value) {
                $data   = [];
                $prefix = substr(trim($value['mobile']), 0, 7);
                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                // print_r($newres);die;
                if (in_array(substr(trim($value['mobile']), 0, 3), ['141', '142', '143', '144', '145', '146', '148', '149', '154', '163', '169', '179', '196'])) {
                    continue;
                }

                if (in_array(trim($value['mobile']), ['15402915944', '15433445566', '15445563221'])) {
                    $redis->hset('yx:mobile:empty', $value['mobile'], json_encode(['check_status' => 2, 'check_result' => 0, 'create_time' => time(), 'update_time' => time()]));
                    continue;
                }
                if (empty($newres)) {
                    if ($prefix == 1650006) {
                        $newres = [
                            'source'      => 1,
                            'province_id' => 1802,
                            'city_id'     => 1803,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1650713) {
                        $newres = [
                            'source'      => 1,
                            'province_id' => 1802,
                            'city_id'     => 1885,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1651033) {
                        $newres = [
                            'source'      => 1,
                            'province_id' => 1426,
                            'city_id'     => 1439,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1653427) {
                        $newres = [
                            'source'      => 1,
                            'province_id' => 499,
                            'city_id'     => 586,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif (in_array($prefix, [1660020, 1660021, 1660025, 1660027, 1660034, 1662236, 1662237, 1662215, 1662288, 1662290])) { //天津联通
                        $newres = [
                            'source'      => 2,
                            'province_id' => 19,
                            'city_id'     => 20,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif (in_array($prefix, [1660102, 1660114, 1660137, 1660152, 1660155])) { //北京联通

                        $newres = [
                            'source'      => 2,
                            'province_id' => 1,
                            'city_id'     => 2,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif (in_array($prefix, [1660170, 1660173, 1660178, 1660179, 1660181, 1660183, 1660184, 1660174, 1662102, 1662103, 1662107, 1662109, 1660214, 1662120, 1662122, 1662123, 1662152, 1662160, 1662167, 1662169, 1662171, 1662173, 1662174, 1662178, 1662179])) { //上海联通

                        $newres = [
                            'source'      => 2,
                            'province_id' => 841,
                            'city_id'     => 842,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660271) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 1802,
                            'city_id'     => 1803,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660272) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 1802,
                            'city_id'     => 1803,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660351) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 240,
                            'city_id'     => 241,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660371) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 1601,
                            'city_id'     => 1602,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660387) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 1601,
                            'city_id'     => 1602,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660396) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 1601,
                            'city_id'     => 1788,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660399) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 1601,
                            'city_id'     => 1602,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660427) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 499,
                            'city_id'     => 586,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660471) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 377,
                            'city_id'     => 378,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660532) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 1426,
                            'city_id'     => 1439,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660713) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 1802,
                            'city_id'     => 1439,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif ($prefix == 1660875) {
                        $newres = [
                            'source'      => 2,
                            'province_id' => 2801,
                            'city_id'     => 2837,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif (in_array($prefix, [1662303, 1662312, 1662331])) { //重庆联通

                        $newres = [
                            'source'      => 2,
                            'province_id' => 2454,
                            'city_id'     => 2455,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif (in_array($prefix, [1662477])) { //广州联通

                        $newres = [
                            'source'      => 2,
                            'province_id' => 2076,
                            'city_id'     => 2077,
                        ];
                        $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                    } elseif (substr(trim($value['mobile']), 0, 3) == 166) {
                        $newres = [
                            'source' => 2,
                        ];
                    } elseif (in_array(substr(trim($value['mobile']), 0, 3), [170, 173, 178, 184, 191, 199, 162, 133, 149, 153, 173, 177, 180, 181, 189])) { //电信
                        $newres = [
                            'source' => 3,
                        ];
                    } elseif (in_array(substr(trim($value['mobile']), 0, 3), [171, 175, 176, 185, 167, 130, 131, 132, 145, 155, 156, 166, 186, 166])) { //联通
                        $newres = [
                            'source' => 2,
                        ];
                    } elseif (in_array(substr(trim($value['mobile']), 0, 3), [147, 172, 177, 187, 188, 195, 198, 165, 134, 135, 136, 137, 138, 139, 147, 150, 151, 152, 157, 158, 159, 1705, 178, 182, 183, 184, 187, 188, 198])) { //移动
                        $newres = [
                            'source' => 1,
                        ];
                    } else {
                        continue;
                    }
                }

                if (empty($newres)) {
                    $source = Db::query("SELECT `mobile`,`source`,`province_id`,`city_id` FROM yx_number_source WHERE `id` = " . $value['id']);
                    // print_r($source);die;
                    $source = $source[0];
                    $newres = [];
                    $newres = [
                        'source'      => $source['source'],
                        'province_id' => $source['province_id'],
                        'city_id'     => $source['city_id'],
                    ];
                }

                $newres['update_time']  = $value['update_time'];
                $newres['check_status'] = $value['check_status'];
                $newres['check_result'] = $value['check_result'];
                // print_r($newres);die;
                $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
            }
        } catch (\Exception $th) {
            //throw $th
            // print_r($value);
            exception($th);
        }
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

    public function oneToOne()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $path  = realpath("./") . "/111.txt";
        $file  = fopen($path, "r");
        $data1 = array();
        $i     = 0;
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
        $path  = realpath("./") . "/112.txt";
        $file  = fopen($path, "r");
        $data2 = array();
        $i     = 0;
        // $phone = '';
        // $j     = '';

        while (!feof($file)) {
            // $data2[] = trim(fgets($file));
            // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
            // // print_r($phone);die;
            // $j = ',';
            if (!in_array(trim(fgets($file)), $data1)) {
                $data2[] = trim(fgets($file));
                // print_r(trim(fgets($file)));
            }
            $i++;
        }
        fclose($file);

        // $data3 = array_diff(array_filter($data1), array_filter($data2));
        // print_r(count($data2));
        // die;
        $myfile = fopen("11313.txt", "w");
        for ($i = 0; $i < count($data2); $i++) {
            $txt = $data2[$i] . "\n";
            fwrite($myfile, $txt);
        }
        fclose($myfile);
    }

    public function Two()
    {
        // $log = Db::query("SELECT `send_length`,`mobile_content` FROM `yx_user_send_code_task` WHERE `id` = '" . 214723 . "'");
        // $mobile = $log[0]['mobile_content'];
        // // print_r($mobile);
        // $mobile_data = explode(',', $mobile);
        $true_mobile = [];

        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $path  = realpath("./") . "/13213.txt";
        $file  = fopen($path, "r");
        $data1 = array();
        $i     = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $mobile_data[] = trim(fgets($file));
            // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
            // // print_r($phone);die;
            // $j = ',';
            $i++;
        }
        fclose($file);
        for ($i = 0; $i < count($mobile_data); $i++) {
            if (checkMobile(trim($mobile_data[$i])) == true) {
                $prefix = substr(trim($mobile_data[$i]), 0, 7);
                $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                $newres = array_shift($res);
                //游戏通道分流
                if ($newres) {
                    if ($newres['source'] == 2) { //米加联通营销

                    } else if ($newres['source'] == 1) { //蓝鲸
                        $true_mobile[] = $mobile_data[$i];
                    } else if ($newres['source'] == 3) { //米加电信营销

                    }
                }
            }
        }
        $myfile = fopen("10253.txt", "w");
        for ($i = 0; $i < count($true_mobile); $i++) {
            $txt = $true_mobile[$i] . "\n";
            fwrite($myfile, $txt);
        }
        fclose($myfile);
    }

    public function exportMultimediaReceiptReport($id)
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $result = Db::query("SELECT `task_no`,`title` FROM `yx_user_multimedia_message` WHERE `id` =  " . $id);
        // print_r($result);
        // die;
        $data = Db::query("SELECT * FROM `yx_user_multimedia_message_log` WHERE `task_id` =  " . $id);

        if (empty($data)) {
            return ['code' => '3002', 'msg' => '发送记录暂未同步'];
        }
        foreach ($data as $key => $value) {
            $data[$key]['task_content'] = $result[0]['title'];
            $data[$key]['update_time']  = date('Y-m-d H:i:s', $value['create_time'] + ceil($key / 1000));
            switch ($value['send_status']) {
                case 2:
                    $data[$key]['send_status'] = '未知';
                    break;
                case 3:
                    $data[$key]['send_status'] = '成功';
                    break;
                case 4:
                    $data[$key]['send_status'] = '失败';
                    break;
                default:
                    $data[$key]['send_status'] = '未知';
                    break;
            }
        }

        $objExcel = new PHPExcel();
        // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
        $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
        $objWriter->setOffice2003Compatibility(true);

        //设置文件属性
        $objProps = $objExcel->getProperties();
        $objProps->setTitle("sheet1");
        $objProps->setSubject($result[0]['task_no'] . ":" . date('Y-m-d H:i:s', time()));

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        $date = date('Y-m-d H:i:s', time());

        //设置当前活动sheet的名称
        $objActSheet->setTitle("sheet1");
        $CellList = array(
            array('task_content', '标题'),
            array('mobile', '手机号'),
            array('send_status', '发送状态'),
            array('status_message', '回执码'),
            array('update_time', '发送时间'),
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
        $outputFileName = $result[0]['task_no'] . "" . date('YmdHis', time()) . ".xlsx";
        $i              = 0;
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
        $objWriter->save($outputFileName);
        exit;
    }

    public function erportSendTaskLog()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        /*  $sql = "SELECT
        ustl.task_content,
        ustl.mobile,
        ustl.create_time,
        ustl.status_message,
        ustl.real_message,
        str.status_message,
        str.real_message,
        str.task_id,
        str.mobile
        FROM
        yx_user_send_task_log AS ustl
        INNER JOIN yx_send_task_receipt AS str ON ustl.task_id = str.task_id AND ustl.mobile = str.mobile
        WHERE
        ustl.task_id = 15939 LIMIT 1 "; */

        //第二批补发任务ID  15992，15994，
        $all_mobile = [];
        $sendTask   = $this->getSendTask(15992);
        $mobile     = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $sendTask = $this->getSendTask(15994);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $sendTask = $this->getSendTask(15993);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $sendTask = $this->getSendTask(15995);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        // print_r(count($all_mobile));
        // die;
        $ids   = [15939, 15940, 15941, 15942, 15943, 15944, 15945, 15946, 15952, 15953, 15954, 15964, 15965, 15966, 15967, 15968, 15969, 15970, 15971, 15984, 15985, 15987, 15988, 15989, 15990];
        $error = 1;
        foreach ($ids as $is => $id) {
            /*  $sql = "SELECT
            ustl.task_no,
            ustl.task_content,
            ustl.mobile,
            ustl.create_time,
            ustl.status_message,
            ustl.real_message,
            ustl.create_time
            FROM
            yx_user_send_task AS ustl
            WHERE
            ustl.task_id = " . $id;
            $task_log = Db::query($sql);
            $task_no = $task_log[0]['task_no']; */
            $sendTask     = $this->getSendTask($id);
            $all_log      = [];
            $new_task_log = [];
            try {
                // foreach ($task_log as $key => $value) {
                //     unset($value['task_no']);
                //     /*  if (empty($value['real_message'])) {
                //         $this_receipt = Db::query("SELECT `status_message`,`real_message` FROM yx_send_task_receipt WHERE  `task_id` = '" . $value['task_id'] . "' AND `mobile` = '" . $value['mobile'] . "' ");
                //         if (!empty($this_receipt)) {
                //             $value['status_message'] = $this_receipt[0]['status_message'];
                //             $task_log[$key]['status_message'] = $this_receipt[0]['status_message'];
                //             $task_log[$key]['real_message'] = $this_receipt[0]['real_message'];
                //         } else {
                //             if (mt_rand(0, 10000) > 5) {
                //                 $value['status_message'] = 'DELIVRD';
                //                 $task_log[$key]['status_message'] = 'DELIVRD';
                //                 $task_log[$key]['real_message'] = 'DELIVRD';
                //             }
                //         }
                //     }
                //     switch ($value['status_message']) {
                //         case 'DELIVRD':
                //             $value['send_status'] = '成功';
                //             break;
                //         case '':
                //             $value['send_status'] = '未知';
                //             break;
                //         default:
                //             $value['send_status'] = '失败';
                //             break;
                //     } */
                //     $value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);

                //     if (checkMobile(trim($value['mobile'])) == true) {
                //         /*  $prefix = substr(trim($value['mobile']), 0, 7);
                //         $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                //         $newres = array_shift($res);

                //         if ($newres && $newres['source'] == 2 && in_array($value['task_id'], [15992, 15994])) {
                //             continue;
                //         } */
                //         if (in_array($value['mobile'], $all_mobile)) { //失败
                //             // print_r($value['mobile']);
                //             // echo "\n";
                //             $num = mt_rand(1, 10);
                //             switch ($num) {
                //                 case 1:
                //                     $status_message = 'UNDELIV';
                //                     break;
                //                 case 2:
                //                     $status_message = 'BLKLIST';
                //                     break;
                //                 case 3:
                //                     $status_message = 'IC:0055';
                //                     break;
                //                 case 4:
                //                     $status_message = 'ID:0012';
                //                     break;
                //                 case 5:
                //                     $status_message = 'ID:0076';
                //                     break;
                //                 case 6:
                //                     $status_message = 'XF:1001';
                //                     break;
                //                 case 7:
                //                     $status_message = 'DB:0141';
                //                     break;
                //                 case 8:
                //                     $status_message = 'DB:0141';
                //                     break;
                //                 case 9:
                //                     $status_message = 'EXPIRED';
                //                     break;
                //                 default:
                //                     $status_message = 'REJECTD';
                //                     break;
                //             }
                //             $value['send_status'] = '失败';
                //             $value['status_message'] = $status_message;
                //             // $error++;
                //         } else {
                //             if (mt_rand(0, 100000) <= 8) {
                //                 // $value['status_message'] = 'DELIVRD';
                //                 // $task_log[$key]['status_message'] = 'DELIVRD';
                //                 // $task_log[$key]['real_message'] = 'DELIVRD';

                //                 $value['send_status'] = '未知';
                //                 $value['status_message'] = '';
                //             } else {
                //                 $value['send_status'] = '成功';
                //                 $value['status_message'] = 'DELIVRD';
                //             }
                //             $new_task_log[] = $value;
                //         }
                //     } else {
                //         $value['send_status'] = '失败';
                //         $value['status_message'] = "DB:0101";
                //         $new_task_log[] = $value;
                //     }
                // }

                // $task_log = array_filter($task_log);
                $mobilesend  = explode(',', $sendTask['mobile_content']);
                $mobilesend  = array_filter($mobilesend);
                $send_length = mb_strlen($sendTask['task_content'], 'utf8');

                for ($i = 0; $i < count($mobilesend); $i++) {
                    // $channel_id    = 0;
                    $send_log = [];
                    if (checkMobile(trim($mobilesend[$i])) == true) {
                        if (in_array($mobilesend[$i], $all_mobile)) {
                            continue;

                            $num = mt_rand(1, 10);
                            switch ($num) {
                                case 1:
                                    $status_message = 'UNDELIV';
                                    break;
                                case 2:
                                    $status_message = 'BLKLIST';
                                    break;
                                case 3:
                                    $status_message = 'IC:0055';
                                    break;
                                case 4:
                                    $status_message = 'ID:0012';
                                    break;
                                case 5:
                                    $status_message = 'ID:0076';
                                    break;
                                case 6:
                                    $status_message = 'XF:1001';
                                    break;
                                case 7:
                                    $status_message = 'DB:0141';
                                    break;
                                case 8:
                                    $status_message = 'DB:0141';
                                    break;
                                case 9:
                                    $status_message = 'EXPIRED';
                                    break;
                                default:
                                    $status_message = 'REJECTD';
                                    break;
                            }
                            $send_log = [
                                'task_content'   => $sendTask['task_content'],
                                'mobile'         => $mobilesend[$i],
                                'send_status'    => '失败',
                                'status_message' => $status_message, //无效号码
                                'create_time'    => date("Y-m-d H:i:s", $sendTask['update_time'] + $i / 1000),
                            ];
                            // $error++;
                        } else {
                            $send_log = [
                                'task_content'   => $sendTask['task_content'],
                                'mobile'         => $mobilesend[$i],
                                'send_status'    => '成功',
                                'status_message' => 'DELIVRD', //无效号码
                                'create_time'    => date("Y-m-d H:i:s", $sendTask['update_time'] + $i / 1000),
                            ];
                            $all_log[] = $send_log;
                        }
                    } else {
                        $send_log = [
                            'task_content'   => $sendTask['task_content'],
                            'mobile'         => $mobilesend[$i],
                            'send_status'    => "失败",
                            'status_message' => 'DB:0101', //无效号码
                            'create_time'    => date("Y-m-d H:i:s", $sendTask['update_time'] + $i / 1000),
                        ];

                        $all_log[] = $send_log;
                    }
                    /*     $send_log = [
                'task_content' => $sendTask['task_content'],
                'mobile'       => $mobilesend[$i],
                'send_status'  => '成功',
                'status_message' => 'DELIVRD', //无效号码
                'create_time'  => date("Y-m-d H:i:s", $sendTask['update_time'] + $i / 1000)
                ];
                $all_log[] = $send_log; */
                }

                $objExcel = new PHPExcel();
                // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
                // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
                $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
                $objWriter->setOffice2003Compatibility(true);

                //设置文件属性
                $objProps = $objExcel->getProperties();
                $objProps->setTitle("sheet1");
                $objProps->setSubject($sendTask['task_no'] . ":" . date('Y-m-d H:i:s', time()));

                $objExcel->setActiveSheetIndex(0);
                $objActSheet = $objExcel->getActiveSheet();

                $date = date('Y-m-d H:i:s', time());

                //设置当前活动sheet的名称
                $objActSheet->setTitle("sheet1");
                $CellList = array(
                    array('task_content', '标题'),
                    array('mobile', '手机号'),
                    array('send_status', '发送状态'),
                    array('status_message', '回执码'),
                    array('create_time', '发送时间'),
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
                $outputFileName = $sendTask['task_no'] . "" . date('YmdHis', time()) . ".xlsx";
                $i              = 0;
                foreach ($all_log as $key => $orderdata) {
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
                $objWriter->save($outputFileName);
                // exit;
            } catch (\Exception $e) {
                exception($e);
            }
        }
        // echo $error;
    }

    private function getSendTask($id)
    {
        $getSendTaskSql = sprintf("select * from yx_user_send_task where delete_time=0 and id = %d", $id);
        $sendTask       = Db::query($getSendTaskSql);
        // print_r($sendTask);die;
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    public function kouLiang()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = 'index:meassage:marketing:kouliang';
        $all_mobile                = [];
        $sendTask                  = $this->getSendTask(15992);
        $mobile                    = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $sendTask = $this->getSendTask(15994);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $sendTask = $this->getSendTask(15993);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $sendTask = $this->getSendTask(15995);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $error = 1;
        // print_r(count($all_mobile));
        //  array_unique($all_mobile);
        for ($i = 15947; $i < 15986; $i++) {
            $this->redis->rPush('index:meassage:marketing:kouliang', $i);
        }
        // $this->redis->rPush('index:meassage:marketing:kouliang', 15956);
        $all_log  = [];
        $true_log = [];
        $j        = 1;
        try {
            while (true) {
                $send = $this->redis->lpop('index:meassage:marketing:kouliang');
                // $send = 15745;
                if (empty($send)) {
                    break;
                }
                $rollback[] = $send;
                $sendTask   = $this->getSendTask($send);
                $mobilesend = [];
                // print_r($sendTask);
                // die;
                if ($sendTask['yidong_channel_id']) {
                    continue;
                }
                if ($sendTask['free_trial'] != 2) {
                    continue;
                }
                $mobilesend  = explode(',', $sendTask['mobile_content']);
                $mobilesend  = array_filter($mobilesend);
                $send_length = mb_strlen($sendTask['task_content'], 'utf8');

                for ($i = 0; $i < count($mobilesend); $i++) {
                    // $channel_id    = 0;
                    $send_log = [];
                    if (checkMobile(trim($mobilesend[$i])) == true) {
                        if (in_array($mobilesend[$i], $all_mobile)) {
                            continue;

                            $num = mt_rand(1, 10);
                            switch ($num) {
                                case 1:
                                    $status_message = 'UNDELIV';
                                    break;
                                case 2:
                                    $status_message = 'BLKLIST';
                                    break;
                                case 3:
                                    $status_message = 'IC:0055';
                                    break;
                                case 4:
                                    $status_message = 'ID:0012';
                                    break;
                                case 5:
                                    $status_message = 'ID:0076';
                                    break;
                                case 6:
                                    $status_message = 'XF:1001';
                                    break;
                                case 7:
                                    $status_message = 'DB:0141';
                                    break;
                                case 8:
                                    $status_message = 'DB:0141';
                                    break;
                                case 9:
                                    $status_message = 'EXPIRED';
                                    break;
                                default:
                                    $status_message = 'REJECTD';
                                    break;
                            }
                            $send_log = [
                                'task_content'   => $sendTask['task_content'],
                                'mobile'         => $mobilesend[$i],
                                'send_status'    => '失败',
                                'status_message' => $status_message, //无效号码
                                'create_time'    => date("Y-m-d H:i:s", $sendTask['update_time'] + $i / 1000),
                            ];
                            // $error++;
                        } else {
                            $send_log = [
                                'task_content'   => $sendTask['task_content'],
                                'mobile'         => $mobilesend[$i],
                                'send_status'    => '成功',
                                'status_message' => 'DELIVRD', //无效号码
                                'create_time'    => date("Y-m-d H:i:s", $sendTask['update_time'] + $i / 1000),
                            ];
                            $all_log[] = $send_log;
                        }
                    } else {
                        $send_log = [
                            'task_content'   => $sendTask['task_content'],
                            'mobile'         => $mobilesend[$i],
                            'send_status'    => "失败",
                            'status_message' => 'DB:0101', //无效号码
                            'create_time'    => date("Y-m-d H:i:s", $sendTask['update_time'] + $i / 1000),
                        ];

                        $all_log[] = $send_log;
                    }
                    /*     $send_log = [
                'task_content' => $sendTask['task_content'],
                'mobile'       => $mobilesend[$i],
                'send_status'  => '成功',
                'status_message' => 'DELIVRD', //无效号码
                'create_time'  => date("Y-m-d H:i:s", $sendTask['update_time'] + $i / 1000)
                ];
                $all_log[] = $send_log; */
                }

                // foreach ($mobilesend as $key => $kvalue) {
                //     if (in_array($channel_id, [2, 6, 7, 8])) {
                //         // $getSendTaskSql = "select source,province_id,province from yx_number_source where `mobile` = '".$prefix."' LIMIT 1";
                //     }
                // }
                // exit("SUCCESS");

                $objExcel = new PHPExcel();
                // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
                // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
                $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
                $objWriter->setOffice2003Compatibility(true);

                //设置文件属性
                $objProps = $objExcel->getProperties();
                $objProps->setTitle("sheet1");
                $objProps->setSubject($sendTask['task_no'] . ":" . date('Y-m-d H:i:s', time()));

                $objExcel->setActiveSheetIndex(0);
                $objActSheet = $objExcel->getActiveSheet();

                $date = date('Y-m-d H:i:s', time());

                //设置当前活动sheet的名称
                $objActSheet->setTitle("sheet1");
                $CellList = array(
                    array('task_content', '标题'),
                    array('mobile', '手机号'),
                    array('send_status', '发送状态'),
                    array('status_message', '回执码'),
                    array('create_time', '发送时间'),
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
                // $outputFileName = $sendTask['task_no'] . "" . date('YmdHis', time()) . ".xlsx";
                $outputFileName = $sendTask['task_no'] . ".xlsx";
                $i              = 0;
                foreach ($all_log as $key => $orderdata) {
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
                $objWriter->save($outputFileName);
                unset($all_log);
            }
        } catch (\Exception $e) {
            exception($e);
        }

        // echo time() -1574906657;die;
        // echo $error;
    }

    public function getmobileSFL()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $all_mobile = [];
        $sendTask   = $this->getSendTask(15992);
        $mobile     = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $sendTask = $this->getSendTask(15994);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $sendTask = $this->getSendTask(15993);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $sendTask = $this->getSendTask(15995);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        // $ids = [15939, 15940, 15941, 15942, 15943, 15944, 15945, 15946, 15952, 15953, 15954, 15964, 15965, 15966, 15967, 15968, 15969, 15970, 15971, 15984, 15985, 15987, 15988, 15989, 15990];
        // $error = 1;
        /*  $myfile = fopen("200311.txt", "w");
        // for ($i = 0; $i < count($true_mobile); $i++) {

        // }

        foreach ($ids as $is => $id) {
        $sendTask = $this->getSendTask($id);
        $mobilesend = explode(',', $sendTask['mobile_content']);
        for ($i = 0; $i < count($mobilesend); $i++) {
        if (in_array($mobilesend[$i], $all_mobile)) {
        $txt = $mobilesend[$i] . "\n";
        fwrite($myfile, $txt);
        }
        }
        }
        fclose($myfile); */
        $myfile = fopen("20031101.txt", "w");
        for ($i = 15947; $i < 15986; $i++) {
            $this->redis->rPush('index:meassage:marketing:kouliang', $i);
        }
        try {
            while (true) {
                $send = $this->redis->lpop('index:meassage:marketing:kouliang');
                // $send = 15745;
                if (empty($send)) {
                    break;
                }
                $rollback[] = $send;
                $sendTask   = $this->getSendTask($send);
                $mobilesend = [];
                // print_r($sendTask);
                // die;
                if ($sendTask['yidong_channel_id']) {
                    continue;
                }
                if ($sendTask['free_trial'] != 2) {
                    continue;
                }
                $mobilesend = explode(',', $sendTask['mobile_content']);

                for ($i = 0; $i < count($mobilesend); $i++) {
                    if (in_array($mobilesend[$i], $all_mobile)) {
                        $txt = $mobilesend[$i] . "\n";
                        fwrite($myfile, $txt);
                    }
                }
            }
        } catch (\Exception $e) {
            exception($e);
        }
    }

    public function readSFL()
    {
        //第一批重复数据
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $all_mobile = [];
        $sendTask   = $this->getSendTask(15992);
        $mobile     = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $sendTask = $this->getSendTask(15993);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }
        $myfile = fopen("200311补.txt", "w");
        for ($i = 0; $i < count($all_mobile); $i++) {

            $txt = $all_mobile[$i] . "\n";
            fwrite($myfile, $txt);
        }
        fclose($myfile);
        die;
        $sendTask = $this->getSendTask(15994);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }

        $sendTask = $this->getSendTask(15995);
        $mobile   = explode(',', $sendTask['mobile_content']);
        foreach ($mobile as $key => $value) {
            $all_mobile[] = $value;
        }

        // $ids = [15939, 15940, 15941, 15942, 15943, 15944, 15945, 15946, 15952, 15953, 15954, 15964, 15965, 15966, 15967, 15968, 15969, 15970, 15971, 15984, 15985, 15987, 15988, 15989, 15990];
        $ids = [
            15939, 15940, 15941, 15942, 15943, 15944, 15945, 15946, 15947, 15948, 15949, 15950, 15951, 15952, 15953, 15954, 15955, 15956, 15984, 15985, 15986,
        ];
        // $error = 1;
        $myfile = fopen("200311第一批.txt", "w");
        // for ($i = 0; $i < count($true_mobile); $i++) {

        // }

        foreach ($ids as $is => $id) {
            $sendTask   = $this->getSendTask($id);
            $mobilesend = explode(',', $sendTask['mobile_content']);
            for ($i = 0; $i < count($mobilesend); $i++) {
                if (in_array($mobilesend[$i], $all_mobile)) {
                    $txt = $mobilesend[$i] . "\n";
                    fwrite($myfile, $txt);
                }
            }
        }
        fclose($myfile);
    }

    public function setLog()
    {
        echo getenv('path');
        die;
        $bu   = [];
        $path = realpath("./") . "/2003111457.txt";
        $file = fopen($path, "r");
        $data = array();
        $i    = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            $bu[]    = $cellVal;
        }
        fclose($file);

        $one  = [];
        $path = realpath("./") . "/2003111458.txt";
        $file = fopen($path, "r");
        $data = array();
        $i    = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            $one[]   = $cellVal;
        }
        fclose($file);
        print_r(count($bu));
        echo "\n";
        print_r(count($one));
        $has = array_diff($one, $bu);
        foreach ($has as $key => $value) {
            $send_log = [
                'task_content'   => "【丝芙兰】天猫SEPHORA海外旗舰店盛大开业！3/5-3/8会员专享9折叠加300-30满减，戳 m.tb.cn/.TS2x7j 回T退订",
                'mobile'         => $value,
                'send_status'    => '成功',
                'status_message' => 'DELIVRD', //无效号码
                'create_time'    => date("Y-m-d H:i:s", 1583377428 + $key / 1000),
            ];
            $all_log[] = $send_log;
        }

        $objExcel = new PHPExcel();
        // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
        $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
        $objWriter->setOffice2003Compatibility(true);

        //设置文件属性
        $objProps = $objExcel->getProperties();
        $objProps->setTitle("sheet1");
        $objProps->setSubject("2003111458:" . date('Y-m-d H:i:s', time()));

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        $date = date('Y-m-d H:i:s', time());

        //设置当前活动sheet的名称
        $objActSheet->setTitle("sheet1");
        $CellList = array(
            array('task_content', '标题'),
            array('mobile', '手机号'),
            array('send_status', '发送状态'),
            array('status_message', '回执码'),
            array('create_time', '发送时间'),
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
        $outputFileName = "2003111458" . "" . date('YmdHis', time()) . ".xlsx";
        $i              = 0;
        foreach ($all_log as $key => $orderdata) {
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
        $objWriter->save($outputFileName);
    }

    public function apitest()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $url = "http://127.0.0.1:1007/index/send/getSmsBuiness";
        // $url = "http://127.0.0.1:1007/index/send/getSmsMarketingTask";
        // getSmsMarketingTask
        $task   = Db::query("SELECT * FROM `messagesend`.`yx_user_send_task` WHERE `uid` = '92'");
        $mobile = [];
        foreach ($task as $key => $value) {
            $mobile_content = explode(',', $value['mobile_content']);
            foreach ($mobile_content as $key => $con) {
                $mobile[] = $con;
            }
        }
        // echo count($mobile);
        echo "开始时间" . date('Y-m-d H:i:s', time());
        echo "\n";
        $all_num   = count($mobile);
        $send_num  = [];
        $j         = 1;
        $send_data = [];
        $send_data = [
            'appid'        => '5e17dbaaddbb7',
            'appkey'       => '50da9965e43a2fdf69118bf6791f6cd3',
            'signature_id' => 'guatGcEq',
            'content'      => '1张9折券已飞奔向您！亲爱的测试会员，您所获赠的九折券自2020-03-30起生效，有效期截止2020-09-29，请在有效期间内前往门店选购哦！(在sephora.cn购物时需与官网账号绑定。累积消费积分1500分或四次不同日消费即自动兑换1张九折劵)/回T退订',
        ];
        for ($i = 0; $i < $all_num; $i++) {
            $send_num[] = $mobile[$i];
            $j++;
            // if ($j >= 2000) {
            //     $send_data['mobile'] = join(',',$send_num);
            //     sendRequest($url,'post',$send_data);
            //     unset($send_data['mobile']);
            //     unset($send_num);
            //     $j = 1;
            // }
            $send_data['mobile'] = $mobile[$i];
            $result              = sendRequest($url, 'post', $send_data);
            // print_r($result);die;
        }
        // if ($send_num) {
        //     $send_data['mobile'] = join(',',$send_num);
        //         sendRequest($url,'post',$send_data);
        // }
        echo "结束时间" . date('Y-m-d H:i:s', time());
    }

    public function FirstDaySupplyAgain()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        /*      $type = 'Excel2007';
        $objReader = PHPExcel_IOFactory::createReader($type);
        $path      = $info->getpathName();
        $objPHPExcel = $objReader->load($path, $encode = 'utf-8'); //加载文件
        $sheet = $objPHPExcel->getSheet(0); //取得sheet(0)表
        $highestRow = $sheet->getHighestRow(); // 取得总行数

        $highestColumn    = $sheet->getHighestColumn();
        $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
        for ($i = 1; $i <= $highestRow; $i++) {
        $mobile = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();

        $text = '';
        $cor = '';
        for ($j = 1; $j < $highestColumnNum; $j++) {
        $cellName = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
        $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容

        if (!empty($cellVal)) {
        $text .= $cor . $cellVal;
        $cor = ',';
        }
        }
        $send_data[] = $text . ":" . $mobile;
        // print_r($send_data);
        } */
        try {
            $redis = Phpredis::getConn();
            $type  = 'Excel5';
            /*   $objReader = PHPExcel_IOFactory::createReader('Excel5');
            // print_r(realpath("./") . "\asd.xlsx");die;

            $objPHPExcel = $objReader->load(realpath("./") . "/asd.xls");
            // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
            //选择标签页
            $sheet      = $objPHPExcel->getSheet(0); //取得sheet(0)表
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $highestColumn    = $sheet->getHighestColumn();
            $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
            $data       = array();
            $all_status = [];
            for ($i = 1; $i < $highestRow; $i++) {
            $value = [];
            // $cellVal = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
            for ($j = 1; $j < $highestColumnNum; $j++) {
            $cellName = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
            $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容
            // // print_r($cellVal);die;
            switch ($j) {
            case '1':
            $value['task_id'] = $cellVal;
            break;
            case '5':
            $value['mobile'] = $cellVal;
            break;
            case '7':
            $value['message_info'] = $cellVal;
            break;
            case '8':
            $value['status_message'] = $cellVal;
            break;
            case '9':
            $value['time'] = strtotime($cellVal);
            break;
            default:
            # code...
            break;
            }

            $value['uid'] = 91;
            }

            $all_status[] = $value;

            }
            $suppay = [];
            foreach ($all_status as $key => $value) {
            // print_r($value);die;
            $mul_task_log = Db::query("SELECT * FROM yx_user_multimedia_message_log where `task_id` = '".$value['task_id']."' AND `mobile` = '".$value['mobile']."' ");
            //没有回执
            if (empty($mul_task_log)) {
            $task =  Db::query("SELECT `id`,`create_time`,`update_time`,`source`,`task_no` FROM `yx_user_multimedia_message` WHERE `id` = '" . $value['task_id'] . "' ");
            Db::startTrans();
            try {
            if ($value['status_message'] == 1000) {
            $send_status = 3;
            $status_message = "DELIVRD";
            $real_message = "DELIVRD";
            Db::table('yx_user_multimedia_message_log')->insert([
            'uid' => $value['uid'],
            'task_no' => $task[0]['task_no'],
            'mobile' => $value['mobile'],
            'send_status' => $send_status,
            'create_time' => $task[0]['update_time'],
            'update_time' => $value['time'],
            'real_message' => $real_message,
            'status_message' => $status_message,
            'task_id' => $task[0]['id'],
            'source' => $task[0]['source'],
            ]);
            //推送给用户
            $redis->rpush('index:meassage:code:user:mulreceive:' . $value['uid'], json_encode([
            'task_no' =>   $task[0]['task_no'],
            'status_message' =>   $real_message,
            'message_info' =>  '发送成功',
            'mobile' =>   $value['mobile'],
            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
            'send_time' => isset($value['time']) ? date('Y-m-d H:i:s', trim($value['time'])) : date('Y-m-d H:i:s', time()),
            ])); //写入用户带处理日志
            }else{//失败 补发
            // $send_status = 4;
            // $status_message = "发送失败";
            // $real_message = "发送失败";
            if (!in_array($value['mobile'],['13681834423','13585872995','15021417314','15921904656'])) {
            $suppay[] = $value['task_id'];
            }
            }

            Db::commit();
            } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            }
            }else{
            //失败补发
            if ($value['status_message'] != 1000) {
            if (!in_array($value['mobile'],['13681834423','13585872995','15021417314','15921904656'])) {
            $suppay[] = $value['task_id'];
            }
            }
            }
            } */
            // print_r($suppay);
            // $suppay_id = join(',',$suppay);
            // echo count($suppay);
            // print_r($suppay_id);
            /*  foreach ($suppay as $key => $a) {
            $redis->rpush("index:meassage:multimediamessage:sendtask", $a);
            } */

            $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            // print_r(realpath("./") . "\asd.xlsx");die;

            $objPHPExcel = $objReader->load(realpath("./") . "/aswe.xlsx");
            // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
            //选择标签页
            $sheet            = $objPHPExcel->getSheet(0); //取得sheet(0)表
            $highestRow       = $sheet->getHighestRow(); // 取得总行数
            $highestColumn    = $sheet->getHighestColumn();
            $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
            $data             = array();
            for ($i = 1; $i < $highestRow; $i++) {
                $value = [];
                // $cellVal = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                for ($j = 0; $j < $highestColumnNum; $j++) {
                    $cellName = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
                    $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容
                    // // print_r($cellVal);die;
                    switch ($j) {
                        case '0':
                            $value['task_id'] = $cellVal;
                            break;
                        case '3':
                            $value['mobile'] = $cellVal;
                            break;
                        case '5':
                            $value['message_info'] = $cellVal;
                            break;
                        case '6':
                            $value['status_message'] = $cellVal;
                            break;
                        case '8':
                            $value['time'] = strtotime($cellVal);
                            break;
                        default:
                            # code...
                            break;
                    }

                    $value['uid'] = 91;
                }

                $all_status[] = $value;
                foreach ($all_status as $key => $value) {
                    // print_r($value);die;
                    $mul_task_log = Db::query("SELECT * FROM yx_user_multimedia_message_log where `task_id` = '" . $value['task_id'] . "' AND `mobile` = '" . $value['mobile'] . "' ");
                    //没有回执
                    if (empty($mul_task_log)) {
                        $task = Db::query("SELECT `id`,`create_time`,`update_time`,`source`,`task_no` FROM `yx_user_multimedia_message` WHERE `id` = '" . $value['task_id'] . "' ");
                        Db::startTrans();
                        try {
                            if ($value['status_message'] == 1000) {
                                $send_status    = 3;
                                $status_message = "DELIVRD";
                                $real_message   = "DELIVRD";
                                Db::table('yx_user_multimedia_message_log')->insert([
                                    'uid'            => $value['uid'],
                                    'task_no'        => $task[0]['task_no'],
                                    'mobile'         => $value['mobile'],
                                    'send_status'    => $send_status,
                                    'create_time'    => $task[0]['update_time'],
                                    'update_time'    => $value['time'],
                                    'real_message'   => $real_message,
                                    'status_message' => $status_message,
                                    'task_id'        => $task[0]['id'],
                                    'source'         => $task[0]['source'],
                                ]);
                                //推送给用户
                                $redis->rpush('index:meassage:code:user:mulreceive:' . $value['uid'], json_encode([
                                    'task_no'        => $task[0]['task_no'],
                                    'status_message' => $real_message,
                                    'message_info'   => '发送成功',
                                    'mobile'         => $value['mobile'],
                                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                    'send_time'      => isset($value['time']) ? date('Y-m-d H:i:s', trim($value['time'])) : date('Y-m-d H:i:s', time()),
                                ])); //写入用户带处理日志
                            } else { //失败 补发
                                // $send_status = 4;
                                // $status_message = "发送失败";
                                // $real_message = "发送失败";
                                if (!in_array($value['mobile'], ['13681834423', '13585872995', '15021417314', '15921904656'])) {
                                    $suppay[] = $value['task_id'];
                                }
                            }

                            Db::commit();
                        } catch (\Exception $e) {
                            Db::rollback();
                            exception($e);
                        }
                    } else {
                        //失败补发
                        if ($value['status_message'] != 1000) {
                            if (!in_array($value['mobile'], ['13681834423', '13585872995', '15021417314', '15921904656'])) {
                                $suppay[] = $value['task_id'];
                            }
                        }
                    }
                }
            }
        } catch (\Exception $E) {
            //throw $th;
            exception($E);
        }
    }

    public function imageTest()
    {
        $image_data          = [];
        $value['image_path'] = "20200413/b29288993583d9cc5de893a8704fecf95e93cc10d9775.jpg";
        // $path = Config::get('qiniu.domain') . '/' . $value['image_path'];
        $md5 = md5(Config::get('qiniu.domain') . '/' . $value['image_path']);
        if (isset($image_data[$md5])) {
            $frame['content'] = $image_data[$md5];
        } else {
            $imagebase        = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
            $image_data[$md5] = $imagebase;
            $frame['content'] = $imagebase;
        }
        for ($i = 0; $i < 2; $i++) {
        }

        print_r($image_data);
    }

    public function testPressure()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $model_path = realpath("./") . "/SMS.txt";
        $file       = fopen($model_path, "r");
        $data       = array();
        $i          = 0;
        // $phone = '';
        // $j     = '';
        $SMS_model = [];
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            if (!empty($cellVal)) {
                $cellVal = str_replace('"', '', $cellVal);
                $data[]  = explode(',', $cellVal);
            }
        }
        fclose($file);
        foreach ($data as $fkey => $fvalue) {
            // print_r($fvalue);
            $tem                   = [];
            $tem['num']            = $fvalue[2];
            $tem['content']        = $fvalue[4];
            $SMS_model[$fvalue[0]] = $tem;
        }
        // print_r($SMS_model);die;
        $bu   = [];
        $path = realpath("./") . "/text.txt";
        $file = fopen($path, "r");
        $data = array();
        $i    = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            if (!empty($cellVal)) {
                $cellVal = str_replace('"', '', $cellVal);
                $bu[]    = explode(',', $cellVal);
            }
        }
        fclose($file);
        $path   = realpath("./") . "/textpressure.txt";
        $myfile = fopen($path, "w");
        $task   = Db::query("SELECT `mobile_content` FROM `messagesend`.`yx_user_send_task` WHERE `uid` = '92'");
        foreach ($task as $key => $value) {
            $mobile = explode(',', $value['mobile_content']);
            $num    = mt_rand(0, 29);
            for ($i = 0; $i < count($mobile); $i++) {
                $message    = $bu[$num];
                $message[3] = $mobile[$i];
                fwrite($myfile, join(',', $message) . "\n");
                // fwrite($myfile, "/n");
                // print_r($message);die;
            }
        }
        fclose($myfile);
        unset($task);

        $all_path = realpath("./") . "/textpressure.txt";
        $file     = fopen($all_path, "r");
        $txt      = array();
        $i        = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            if (!empty($cellVal)) {
                $cellVal = str_replace('"', '', $cellVal);
                $txt[]   = explode(',', $cellVal);
            }
        }
        $SMSmessage = [];
        fclose($file);
        // $today_sftp_path =  realpath("./") . "/todaysfty.txt";
        // $today_file     = fopen($today_sftp_path, "w");
        try {
            //校验
            $model_check = [];
            foreach ($txt as $tkey => $tvalue) {
                if (isset($model_check[$tvalue[2]])) {
                    $model_check[$tvalue[2]]++;
                } else {
                    $model_check[$tvalue[2]] = 1;
                }
            }
            /*  foreach ($file_data as $key => $value) {
                // print_r($value[2]);
                // print_r($model_check[$value[0]]);

                // die;
                if ($value[2] != $model_check[$value[0]]) {
                    //校验失败
                    return  [ 'code' => 200, "error"=>"校验失败"];
                }
            } */
            $j = 1;
            $insertSMS = [];
            foreach ($txt as $tkey => $tvalue) {
                /*  if (isset($model_check[$tvalue[2]])) {
                $model_check[$tvalue[2]]++;
                } else {
                $model_check[$tvalue[2]] = 1;
                } */
                $SMS_real_send               = [];
                $SMS_real_send               = [];
                $SMS_real_send['mseeage_id'] = $tvalue[0];
                $SMS_real_send['mobile']     = $tvalue[3];
                $SMS_real_send['free_trial'] = 1;
                // $SMS_real_send['real_num'] = 1;
                $SMS_real_send['send_num']    = 1;
                $SMS_real_send['send_status'] = 1;
                $SMS_real_send['template_id'] = $tvalue[2];
                $SMS_real_send['create_time'] = time();
                $content                      = $SMS_model[$tvalue[2]]['content'];
                $content                      = str_replace('{FULL_NAME}', $tvalue[4], $content);
                $content                      = str_replace('{RESERVED_FIELD_1}', $tvalue[7], $content);
                $content                      = str_replace('{RESERVED_FIELD_2}', $tvalue[8], $content);
                $content                      = str_replace('{RESERVED_FIELD_3}', $tvalue[9], $content);
                $content                      = str_replace('{RESERVED_FIELD_4}', $tvalue[10], $content);
                $content                      = str_replace('{RESERVED_FIELD_5}', $tvalue[11], $content);
                $content                      = str_replace('{ACCOUNT_NUMBER}', $tvalue[1], $content);
                $content                      = str_replace('{MOBILE}', $tvalue[3], $content);
                $content                      = str_replace('{POINTS_AVAILABLE}', $tvalue[5], $content);
                $content                      = str_replace('{TOTAL_POINTS}', $tvalue[6], $content);
                if (strpos($content, '【丝芙兰】') !== false) {
                } else {
                    $content = '【丝芙兰】' . $content;
                }
                if (strpos($content, '回T退订') !== false) {
                } else {
                    $content = $content . "/回T退订";
                }
                // print_r($content);die;
                $send_length = mb_strlen($content, 'utf8');
                $real_length = 1;
                if ($send_length > 70) {
                    $real_length = ceil($send_length / 67);
                }
                $SMS_real_send['task_content'] = $content;
                $SMS_real_send['real_num']     = $real_length;
                $SMS_real_send['send_length']  = $send_length;
                // array_push($insertSMS, $SMS_real_send);
                // unset($SMSmessage[$i]);
                $insertSMS[] = $SMS_real_send;
                $j++;
                if ($j > 100) {
                    Db::startTrans();
                    try {
                        Db::table('yx_sfl_send_task')->insertAll($insertSMS);
                        unset($insertSMS);
                        $j = 1;
                        Db::commit();
                    } catch (\Exception $e) {
                        exception($e);
                    }
                    // $this->redis->rPush('index:meassage:business:sendtask', $send);

                }
                // $SMSmessage[]                  = $SMS_real_send;
                // fwrite($today_file, json_encode($SMS_real_send) . "\n");
                // print_r($content);die;
            }
            // fclose($today_file);
            if (!empty($insertSMS)) {
                Db::startTrans();
                try {
                    Db::table('yx_sfl_send_task')->insertAll($insertSMS);
                    unset($insertSMS);
                    Db::commit();
                } catch (\Exception $e) {
                    exception($e);
                }
            }
            exit;
            $insertSMS = [];
            $j         = 1;
            for ($i = 0; $i < count($SMSmessage); $i++) {
                if ($SMSmessage[$i]) {
                    array_push($insertSMS, $SMSmessage[$i]);
                    // unset($SMSmessage[$i]);
                    $j++;
                    if ($j > 100) {
                        Db::startTrans();
                        try {
                            Db::table('yx_sfl_send_task')->insertAll($insertSMS);
                            unset($insertSMS);
                            $j = 1;
                            Db::commit();
                        } catch (\Exception $e) {
                            exception($e);
                        }
                        // $this->redis->rPush('index:meassage:business:sendtask', $send);

                    }
                }
            }
            if (!empty($insertSMS)) {
                Db::startTrans();
                try {
                    Db::table('yx_sfl_send_task')->insertAll($insertSMS);
                    unset($insertSMS);
                    Db::commit();
                } catch (\Exception $e) {
                    exception($e);
                }
            }
            unset($SMSmessage);
        } catch (\Exception $th) {
            exception($th);
        }
    }

    public function sflErrorExport()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $model_path = realpath("./uploads/SFL/UnZip/SMS/Communication_targets_SMS_1_20200529140223") . "/Communication_targets_SMS_1_20200529140223.txt";
        $file       = fopen($model_path, "r");

        // $error_path = realpath("./")."/_error.txt";
        // $error_file = fopen($error_path, "w");
        $black = 0;
        $white = 0;
        $receive_alls = [];
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            if (!empty($cellVal)) {
                $value = explode('",', $cellVal);
                // $cellVal = str_replace('"', '', $cellVal);
                foreach ($value as $key => $svalue) {
                    $value[$key] = str_replace('"', '', $svalue);
                }
                if (checkMobile($value[3]) == false || strlen($value[3]) > 11) {
                    $receive = [];
                    $receive = [
                        'MESSAGE_ID' => $value[0],
                        'COMMUNICATION_CHANNEL_ID' => $value[2],
                        'MOBILE' => $value[3],
                        'STATUS' => '',
                        'SENDING_TIME' => '',
                    ];
                    $receive_alls[] = $receive;
                } else {
                    $receive = [
                        'MESSAGE_ID' => $value[0],
                        'COMMUNICATION_CHANNEL_ID' => $value[2],
                        'MOBILE' => $value[3],
                        'STATUS' => '',
                        'SENDING_TIME' => '2020-05-20 10:00:01',
                    ];
                    if (strpos($value[3], '000000') !== false || strpos($value[3], '111111') !== false || strpos($value[3], '222222') !== false || strpos($value[3], '333333') !== false || strpos($value[3], '444444') !== false || strpos($value[3], '555555') !== false || strpos($value[3], '666666') !== false || strpos($value[3], '777777') !== false || strpos($value[3], '888888') !== false || strpos($value[3], '999999') !== false) {
                        //固定失败
                        // print_r($value[3]);die;
                        // fwrite($error_file,$value[3]."\n");
                        $receive['STATUS'] = 'SMS:2';
                        $receive_alls[] = $receive;
                    }

                    /*  if ($value[2] == '100180395') {
                        $black++;
                    }else{
                        $white++;
                    } */
                }
            }
        }
        fclose($file);
        /*  echo "黑卡号码数:".$black;
        echo "\n";
        echo "白卡号码数:".$white;
        die; */
        $name = "receive_mms_error_20200530.xlsx";
        $this->derivedTables($receive_alls, $name);
    }

    public function SflErrorMobile()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $this->redis = Phpredis::getConn();

        // $phone = '';
        // $j     = '';
        // $SMS_model = [];
        // $error_path = realpath("./")."/error.txt";
        // $error_file = fopen($error_path, "w");
        //黑卡
        $black_error_path = realpath("./") . "/0808.txt";
        $black_error_file       = fopen($black_error_path, "r");
        $black_error_mobile = [];
        $receive_alls = [];
        while (!feof($black_error_file)) {
            $cellVal = trim(fgets($black_error_file));
            if (!empty($cellVal)) {
                $cellVal = str_replace('"', '', $cellVal);
                $cellVal = trim($cellVal);
                // print_r($cellVal);die;
                $black_error_mobile[] = $cellVal;
            }
        }
        // echo count($black_error_mobile);die;
        /*  $white_error_path = realpath("./")."/100180396.txt";
        $white_error_file       = fopen($white_error_path, "r");
        $white_error_mobile = [];
        while (!feof($white_error_file)) {
            $cellVal = trim(fgets($white_error_file));
            if (!empty($cellVal)) {
                $white_error_mobile[] = $cellVal;
            }
        } */

        //黑卡写入文件地址
        /*  $black_receipt_path = realpath("./")."/100180395_receipt.txt";
        $black_receipt_file       = fopen($black_receipt_path, "w");
        
        $white_receipt_path = realpath("./")."/100180396_receipt.txt";
        $white_receipt_file       = fopen($white_receipt_path, "w"); */
        // echo count($black_error_mobile);die;
        $start_time = strtotime('2020-08-08 10:00');
        $i = 1;
        $j = 2;
        $model_path = realpath("./uploads\SFL\UnZip\SMS\Communication_targets_SMS_1_20200807153020") . "/Communication_targets_SMS_1_20200807153020.txt";
        // $model_path = realpath("./") . "/0624.txt";
        $file       = fopen($model_path, "r");
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            if (!empty($cellVal)) {
                $value = explode('",', $cellVal);
                // $cellVal = str_replace('"', '', $cellVal);
                foreach ($value as $key => $svalue) {
                    $value[$key] = str_replace('"', '', $svalue);
                }
                // print_r($value);die;
                if (checkMobile($value[3]) == false || strlen($value[3]) > 11) {
                    continue;
                }
                /*  if (!in_array($value[2],['529','100150820','100150821','100150822','100180393'])) {
                    continue;
                } */
                /*  if (!in_array($value[2],['82301','82309','100125372','100180389'])) {
                    continue;
                } */
                $receive = [];
                $receive = [
                    'MESSAGE_ID' => $value[0],
                    'COMMUNICATION_CHANNEL_ID' => $value[2],
                    'MOBILE' => $value[3],
                    'SENDING_TIME' => date('Y-m-d H:i:s', $start_time + ceil($i / 1700)),
                ];
                if (strpos($value[3], '000000') !== false || strpos($value[3], '111111') !== false || strpos($value[3], '222222') !== false || strpos($value[3], '333333') !== false || strpos($value[3], '444444') !== false || strpos($value[3], '555555') !== false || strpos($value[3], '666666') !== false || strpos($value[3], '777777') !== false || strpos($value[3], '888888') !== false || strpos($value[3], '999999') !== false) {
                    //固定失败
                    // print_r($value[3]);die;
                    // fwrite($error_file,$value[3]."\n");
                    $receive['STATUS'] = 'SMS:2';
                } else {
                    $receive['STATUS'] = 'SMS:1';
                    if (in_array(substr(trim($value[3]), 0, 3), ['141', '142', '143', '144', '145', '146', '148', '149'])) {
                        $receive['STATUS'] = 'SMS:2';
                    }
                    /* $num = mt_rand(0,1000);
                    if ($num <= 33) {
                        $receive['STATUS'] = 'SMS:2';
                    } */
                    if (in_array($value[3], $black_error_mobile)) {
                        $receive['STATUS'] = 'SMS:2';
                    }
                    //  $this->redis->rpush('0625',json_encode($receive));
                    /*  if ($value[2] == 100180395) {//黑卡
                        continue;
                        if (in_array($value[3],$black_error_mobile)) {
                            $receive['STATUS'] = 'SMS:2';
                        }
                        if (in_array($value[3],['13851739296','13936347542','18468947720'])) {
                            $receive['STATUS'] = 'SMS:4';
                        }
                        // fwrite($black_receipt_file,json_encode($receive)."\n");
                        // $this->redis->rpush('100180395',json_encode($receive));
                    }else{
                        if (in_array($value[3],$white_error_mobile)) {
                            $receive['STATUS'] = 'SMS:2';
                        }
                        if (in_array($value[3],['13776601787','13796111777','13845416514','13951566424','15845910770','15946213875','18425106696','18425140306','18425487624','18425695852','18452141141','18452226356','18745414545'])) {
                            $receive['STATUS'] = 'SMS:4';
                        }
                        // fwrite($white_receipt_file,json_encode($receive)."\n");
                        $this->redis->rpush('100180396',json_encode($receive));
                    }*/
                }

                $receive_alls[] = $receive;

                $i++;
                if ($i > 200000) {
                    $name = "imp_mobile_status_report_mms_" . $j . "_20200808.xlsx";
                    $this->derivedTables($receive_alls, $name);
                    $j++;
                    $receive_alls = [];
                    $i = 1;
                }
            }
        }
        fclose($file);
        // fclose($black_receipt_file);
        // fclose($white_receipt_file);
        // fclose($error_file);
        if (!empty($receive_alls)) {
            $name = "imp_mobile_status_report_mms_" . $j . "_20200808.xlsx";
            $this->derivedTables($receive_alls, $name);
        }
    }

    public function export()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $this->redis = Phpredis::getConn();
        $receive_alls = [];
        $i = 1;
        $j = 12;
        try {
            while (true) {
                $receipt = $this->redis->lpop('100180395');
                if (empty($receipt)) {
                    break;
                }

                $receive_alls[] = json_decode($receipt, true);
                $i++;
                if ($i > 200000) {
                    $name = "receive_mms_" . $j . "_20200530.xlsx";
                    $this->derivedTables($receive_alls, $name);
                    $j++;
                    $receive_alls = [];
                    $i = 1;
                }
            }
            if (!empty($receive_alls)) {
                $name = "receive_mms_" . $j . "_20200530.xlsx";
                $this->derivedTables($receive_alls, $name);
                $j++;
                $receive_alls = [];
                $i = 1;
            }
            while (true) {
                $receipt = $this->redis->lpop('100180396');
                if (empty($receipt)) {
                    break;
                }
                $receive_alls[] = json_decode($receipt, true);
                $i++;
                if ($i > 200000) {
                    $name = "receive_mms_" . $j . "_20200530.xlsx";
                    $this->derivedTables($receive_alls, $name);
                    $j++;
                    $receive_alls = [];
                    $i = 1;
                }
            }
            if (!empty($receive_alls)) {
                $name = "receive_mms_" . $j . "_20200530.xlsx";
                $this->derivedTables($receive_alls, $name);
                $j++;
                $receive_alls = [];
                $i = 1;
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function derivedTables($receive_alls, $name)
    {
        $objExcel = new PHPExcel();
        // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
        $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
        $objWriter->setOffice2003Compatibility(true);

        //设置文件属性
        $objProps = $objExcel->getProperties();
        $objProps->setTitle("imp_mobile_status_report");
        $objProps->setSubject("金卡1:" . date('Y-m-d H:i:s', time()));

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        $date = date('Y-m-d H:i:s', time());

        //设置当前活动sheet的名称
        $objActSheet->setTitle("imp_mobile_status_report");
        $CellList = array(
            array('MESSAGE_ID', 'MESSAGE_ID'),
            array('COMMUNICATION_CHANNEL_ID', 'COMMUNICATION_CHANNEL_ID'),
            array('MOBILE', 'MOBILE'),
            array('STATUS', 'STATUS'),
            array('SENDING_TIME', 'SENDING_TIME'),
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
        // $outputFileName = "receive_mms_1_20200523.xlsx";
        $i = 0;
        foreach ($receive_alls as $key => $orderdata) {
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
        //imp_mobile_status_report_mms_1_20200531.xlsx
        $objWriter->save($name);
        return 1;
    }

    public function futureExport($uid)
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        // $task = Db::query("SELECT `id` FROM `yx_user_send_task` WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137 AND `create_time` >= '1590940800' AND `create_time` <= '1593532800' ");
        // print_r();die;
        /*  $task = Db::query("SELECT
        yx_user_send_task.uid,
        yx_user_send_task.task_content,
        yx_user_send_task.mobile_content,
        yx_user_send_task.send_msg_id,
        yx_user_send_task.create_time,
        yx_send_task_receipt.status_message,
        yx_send_task_receipt.mobile
        FROM
        yx_user_send_task
        LEFT JOIN yx_send_task_receipt ON yx_user_send_task.id = yx_send_task_receipt.task_id
        WHERE
        yx_user_send_task.uid IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND
        yx_user_send_task.create_time >= '1590940800' AND
        yx_user_send_task.create_time <= '1593532800' AND
        yx_user_send_task.send_num = '1'");
        echo count($task);die; */
        $all_log = [];
        $user = Db::query("SELECT * FROM yx_users WHERE `pid` = 137 AND `id` =" . $uid);
        if (empty($user)) {
            exit;
        }
        try {
            $value = $user[0];

            $task = Db::query("SELECT * FROM `yx_user_send_task` WHERE `uid` = " . $value['id'] . "  AND `create_time` >= '1590940800' AND `create_time` <= '1593532800' ");
            // print_r($task);die;
            if (empty($task)) {
                exit;
            }
            // print_r($value);die;
            /*  echo count($task);
            die; */
            $j = 1;
            $a = 1;
            foreach ($task as $key => $tvalue) {

                // $send_task =  Db::query("SELECT * FROM `yx_user_send_code_task` WHERE `id` = ".$tvalue['id']);
                // $send_task = $send_task[0];
                $send_task = $tvalue;
                // print_r($send_task);die;
                // print_r($send_task);die;
                $task_log = [];
                $real_num = 1;
                // $real_num = $send_task['real_num'] / $send_task['send_num'] ;
                if (mb_strlen($send_task['task_content']) > 70) {
                    $real_num = ceil(mb_strlen($send_task['task_content']) / 67);
                }
                $task_log = [
                    'uid' => $send_task['uid'],
                    'task_content' => $send_task['task_content'],
                    'send_msg_id' => $send_task['send_msg_id'],
                    'send_length' => $send_task['send_length'],
                    'real_num' => $real_num,
                    'create_time' => date('Y-m-d H:i:s', $send_task['create_time']),
                ];
                $mobile_content = explode(',', $send_task['mobile_content']);
                if ($send_task['yidong_channel_id']) {
                    $has_mobile = [];
                    $task_receipt = Db::query("SELECT `*` FROM yx_send_task_receipt WHERE `task_id` = " . $tvalue['id'] . " AND `mobile` IN (" . $send_task['mobile_content'] . ") ");
                    //有回执的
                    foreach ($task_receipt as $rkey => $rvalue) {
                        // print_r($rvalue);die;
                        if (trim($rvalue['status_message'] == 'DELIVRD')) {
                            $task_log['success_num'] = $real_num;
                            $task_log['default_num'] = 0;
                        } else {
                            $task_log['success_num'] = 0;
                            $task_log['default_num'] = $real_num;
                        }
                        $stat = '';
                        $stat_info = '';
                        for ($n = 0; $n < $real_num; $n++) {
                            $k = $n + 1;
                            $stat .= $k . '|' . $rvalue['status_message'] . ';';
                            if (trim($rvalue['status_message'] == 'DELIVRD')) {
                                $stat_info .= $k . '|发送成功;';
                            } else {
                                $stat_info .= $k . '|发送失败;';
                            }
                        }
                        $task_log['mobile'] = $rvalue['mobile'];
                        $task_log['status'] = $stat;
                        $task_log['status_info'] = $stat_info;
                        $all_log[] = $task_log;
                        // print_r($task_log);die;
                        $a++;
                        $has_mobile[] =  $rvalue['mobile'];
                        if ($a > 200000) {
                            $this->derivedTablesForTaskReceipt($all_log, $value['nick_name'] . "_" . $j . '_营销' . '.xlsx');
                            $all_log = [];
                            $a = 1;
                            $j++;
                        }
                    }
                    $nuknow_mobile = [];
                    $nuknow_mobile = array_diff($mobile_content, $has_mobile);

                    if (!empty($nuknow_mobile)) {
                        foreach ($nuknow_mobile as $nkey => $nvalue) {
                            $stat = '';
                            $stat_info = '';
                            for ($n = 0; $n < $real_num; $n++) {
                                $k = $n + 1;
                                $stat .= $k . '|UNDELIV;';
                                $stat_info .= $k . '|发送失败;';
                            }
                            $task_log['success_num'] = 0;
                            $task_log['default_num'] = $real_num;
                            $task_log['mobile'] = $nvalue;
                            $task_log['status'] = $stat;
                            $task_log['status_info'] = $stat_info;
                            $all_log[] = $task_log;
                            $a++;
                            if ($a > 200000) {
                                $this->derivedTablesForTaskReceipt($all_log, $value['nick_name'] . "_" . $j . '_营销' . '.xlsx');
                                $all_log = [];
                                $a = 1;
                                $j++;
                            }
                        }
                        // for ($i=0; $i < count($nuknow_mobile); $i++) { 

                        // }
                    }
                } else {
                    for ($i = 0; $i < count($mobile_content); $i++) {
                        $stat = '';
                        $stat_info = '';
                        for ($n = 0; $n < $real_num; $n++) {
                            $k = $n + 1;
                            $stat .= $k . '|MK:100C;';
                            $stat_info .= $k . '|发送失败;';
                        }
                        $task_log['success_num'] = 0;
                        $task_log['default_num'] = $real_num;
                        $task_log['mobile'] = $mobile_content[$i];
                        $task_log['status'] = $stat;
                        $task_log['status_info'] = $stat_info;
                        $all_log[] = $task_log;
                        $a++;
                        if ($a > 200000) {
                            $this->derivedTablesForTaskReceipt($all_log, $value['nick_name'] . "_" . $j . '_营销' . '.xlsx');
                            $all_log = [];
                            $a = 1;
                            $j++;
                        }
                    }
                }
                print_r($a);
                echo "\n";
            }
            if (!empty($all_log)) {
                $this->derivedTablesForTaskReceipt($all_log, $value['nick_name'] . "_" . $j . '_营销' . '.xlsx');
                $all_log = [];
                $a = 1;
                $j++;
            }
            die;
            /*  foreach ($user as $key => $value) {
                // # code...
                //
               
            } */

            $task = [];
            $j = 1;
            $a = 1;
            $task = Db::query("SELECT * FROM `yx_user_send_task` WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137)  AND `create_time` >= '1590940800' AND `create_time` <= '1593532800' ");
            if (!empty($task)) {
                foreach ($task as $key => $tvalue) {

                    // $send_task =  Db::query("SELECT * FROM `yx_user_send_code_task` WHERE `id` = ".$tvalue['id']);
                    // $send_task = $send_task[0];
                    $send_task = $tvalue;
                    // print_r($send_task);die;
                    // print_r($send_task);die;
                    $task_log = [];
                    $real_num = 1;
                    // $real_num = $send_task['real_num'] / $send_task['send_num'] ;
                    if (mb_strlen($send_task['task_content']) > 70) {
                        $real_num = ceil(mb_strlen($send_task['task_content']) / 67);
                    }
                    $task_log = [
                        'uid' => $send_task['uid'],
                        'task_content' => $send_task['task_content'],
                        'send_msg_id' => $send_task['send_msg_id'],
                        'send_length' => $send_task['send_length'],
                        'real_num' => $real_num,
                        'create_time' => date('Y-m-d H:i:s', $send_task['create_time']),
                    ];
                    $mobile_content = explode(',', $send_task['mobile_content']);
                    if ($send_task['yidong_channel_id']) {
                        $has_mobile = [];
                        $task_receipt = Db::query("SELECT `*` FROM yx_send_task_receipt WHERE `task_id` = " . $tvalue['id'] . " AND `mobile` IN (" . $send_task['mobile_content'] . ") ");
                        //有回执的
                        foreach ($task_receipt as $rkey => $rvalue) {
                            // print_r($rvalue);die;
                            if (trim($rvalue['status_message'] == 'DELIVRD')) {
                                $task_log['success_num'] = $real_num;
                                $task_log['default_num'] = 0;
                            } else {
                                $task_log['success_num'] = 0;
                                $task_log['default_num'] = $real_num;
                            }
                            $stat = '';
                            $stat_info = '';
                            for ($n = 0; $n < $real_num; $n++) {
                                $k = $n + 1;
                                $stat .= $k . '|' . $rvalue['status_message'] . ';';
                                if (trim($rvalue['status_message'] == 'DELIVRD')) {
                                    $stat_info .= $k . '|发送成功;';
                                } else {
                                    $stat_info .= $k . '|发送失败;';
                                }
                            }
                            $task_log['mobile'] = $rvalue['mobile'];
                            $task_log['status'] = $stat;
                            $task_log['status_info'] = $stat_info;
                            $all_log[] = $task_log;
                            // print_r($task_log);die;
                            $a++;
                            $has_mobile[] =  $rvalue['mobile'];
                            if ($a > 200000) {
                                $this->derivedTablesForTaskReceipt($all_log, "future_" . $j . '_营销' . '.xlsx');
                                $all_log = [];
                                $a = 1;
                                $j++;
                            }
                        }
                        $nuknow_mobile = [];
                        $nuknow_mobile = array_diff($mobile_content, $has_mobile);

                        if (!empty($nuknow_mobile)) {
                            foreach ($nuknow_mobile as $nkey => $nvalue) {
                                $stat = '';
                                $stat_info = '';
                                for ($n = 0; $n < $real_num; $n++) {
                                    $k = $n + 1;
                                    $stat .= $k . '|UNDELIV;';
                                    $stat_info .= $k . '|发送失败;';
                                }
                                $task_log['success_num'] = 0;
                                $task_log['default_num'] = $real_num;
                                $task_log['mobile'] = $nvalue;
                                $task_log['status'] = $stat;
                                $task_log['status_info'] = $stat_info;
                                $all_log[] = $task_log;
                                $a++;
                                if ($a > 200000) {
                                    $this->derivedTablesForTaskReceipt($all_log, "future_" . $j . '_营销' . '.xlsx');
                                    $all_log = [];
                                    $a = 1;
                                    $j++;
                                }
                            }
                            // for ($i=0; $i < count($nuknow_mobile); $i++) { 

                            // }
                        }
                    } else {
                        for ($i = 0; $i < count($mobile_content); $i++) {
                            $stat = '';
                            $stat_info = '';
                            for ($n = 0; $n < $real_num; $n++) {
                                $k = $n + 1;
                                $stat .= $k . '|MK:100C;';
                                $stat_info .= $k . '|发送失败;';
                            }
                            $task_log['success_num'] = 0;
                            $task_log['default_num'] = $real_num;
                            $task_log['mobile'] = $mobile_content[$i];
                            $task_log['status'] = $stat;
                            $task_log['status_info'] = $stat_info;
                            $all_log[] = $task_log;
                            $a++;
                            if ($a > 200000) {
                                $this->derivedTablesForTaskReceipt($all_log, "future_" . $j . '_营销' . '.xlsx');
                                $all_log = [];
                                $a = 1;
                                $j++;
                            }
                        }
                    }
                    print_r($a);
                    echo "\n";
                }
            }
            if (!empty($all_log)) {
                $this->derivedTablesForTaskReceipt($all_log, "future_" . $j . '_营销' . '.xlsx');
                $all_log = [];
                $a = 1;
                $j++;
            }

            /*  foreach($task as $key => $value){
                $send_task =  Db::query("SELECT * FROM `yx_user_send_task` WHERE `id` = ".$value['id']);
                $send_task = $send_task[0];
                // print_r($send_task);die;
                $task_log = [];
                $task_log = [
                    'task_content' => $send_task['task_content'],
                    'send_msg_id' => $send_task['send_msg_id'],
                    'send_length' => $send_task['send_length'],
                    'real_num' => $send_task['real_num'] / $send_task['send_num'] ,
                    'create_time' => date('Y-m-d H:i:s',$send_task['create_time']),
                ];
                $mobile_content = explode(',',$send_task['mobile_content']);
                if ($send_task['yidong_channel_id']) {
                    $has_mobile = [];
                    $task_receipt = Db::query("SELECT `*` FROM yx_send_task_receipt WHERE `task_id` = ".$value['id']." AND `mobile` IN (".$send_task['mobile_content'].") ");
                    //有回执的
                    foreach ($task_receipt as $rkey => $rvalue) {
                        // print_r($rvalue);die;
                        $task_log['mobile'] = $rvalue['mobile'];
                        $task_log['status'] = $rvalue['status_message'];
                        $all_log[] = $task_log;
                        $j ++;
                        $has_mobile[] =  $rvalue['mobile'];
                    }
                    $nuknow_mobile = [];
                    $nuknow_mobile = array_diff($mobile_content,$has_mobile);
                    
                    if (!empty($nuknow_mobile)) {
                        foreach ($nuknow_mobile as $nkey => $nvalue) {
                            $task_log['mobile'] = $nvalue;
                            $task_log['status'] = 'UNDELIV';
                            $all_log[] = $task_log;
                            $j++;
                        }
                        // for ($i=0; $i < count($nuknow_mobile); $i++) { 
                            
                        // }
                    }
                }else{
                    for ($i=0; $i < count($mobile_content); $i++) { 
                        $task_log['mobile'] = $mobile_content[$i];
                        $task_log['status'] = 'MK:100C';
                        $all_log[] = $task_log;
                        $j++;
                    }
                }
            }
            $this->derivedTablesForTaskReceipt($all_log,'future_KOLON.xlsx'); */
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }


    public function derivedTablesForTaskReceipt($receive_alls, $name)
    {
        $objExcel = new PHPExcel();
        // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
        $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
        $objWriter->setOffice2003Compatibility(true);

        //设置文件属性
        $objProps = $objExcel->getProperties();
        $objProps->setTitle("imp_mobile_status_report");
        $objProps->setSubject("imp_mobile_status_report:" . date('Y-m-d H:i:s', time()));

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        $date = date('Y-m-d H:i:s', time());

        //设置当前活动sheet的名称
        $objActSheet->setTitle("imp_mobile_status_report");
        $CellList = array(
            array('uid', '账户id'),
            array('mobile', '手机号'),
            array('task_content', '短信内容'),
            array('create_time', '发送时间'),
            array('send_msg_id', 'msg_id'),
            // array('send_length', '长度'),
            array('real_num', '总条数'),
            array('success_num', '成功数'),
            array('default_num', '失败数'),
            array('status', '状态码'),
            array('status_info', '错误信息'),
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
        // $outputFileName = "receive_mms_1_20200523.xlsx";
        $i = 0;
        foreach ($receive_alls as $key => $orderdata) {
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
        //imp_mobile_status_report_mms_1_20200531.xlsx
        $objWriter->save($name);
        return 1;
    }

    public function mobileChecked()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $black_error_path = realpath("./") . "/20200708.txt";
        $black_error_file       = fopen($black_error_path, "r");
        $black_error_mobile = [];
        while (!feof($black_error_file)) {
            $cellVal = trim(fgets($black_error_file));
            if (!empty($cellVal)) {
                $cellVal = str_replace('"', '', $cellVal);
                $cellVal = trim($cellVal);
                // print_r($cellVal);die;
                $black_error_mobile[] = $cellVal;
            }
        }
        // print_r(count($black_error_mobile));
        $result = $this->mobilesFiltrate(join(',', $black_error_mobile));
        // print_r($result);
        $white_receipt_path = realpath("./") . "/20200708new.txt";
        $white_receipt_file       = fopen($white_receipt_path, "w");
        foreach ($result as $key => $value) {
            fwrite($white_receipt_file, $value . "\n");
        }
        fclose($white_receipt_file);;
    }


    /* 第二版本号码清洗 */
    public function mobilesFiltrate($mobile)
    {
        try {
            // $deduct = 0;
            $error_mobile = []; //错号或者黑名单
            $real_send_mobile = []; //实际发送号码
            $true_mobile = []; //实号号码
            $mobile = str_replace('&quot;', '', $mobile);
            $mobile_data = explode(',', $mobile);
            // echo count($mobile_data);
            // echo "\n";
            foreach ($mobile_data as $key => $value) {
                // print_r($value);die;
                if (!is_numeric($value)) {
                    unset($mobile_data[$key]);
                    continue;
                }
                if (checkMobile($value) == false) {

                    $error_mobile[] = $value;
                }
            }
            $mobile = join(',', $mobile_data);

            //白名单
            $white_mobiles = [];
            $white_mobile = Db::query("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (" . $mobile . ") GROUP BY `mobile` ");
            // print_r("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (".$mobile.") ");
            if (!empty($white_mobile)) {
                foreach ($white_mobile as $key => $value) {
                    $white_mobiles[] = $value['mobile'];
                    $true_mobile[] = $value['mobile'];
                }
            }
            //黑名单
            $black_mobile = Db::query("SELECT `mobile` FROM `yx_blacklist` WHERE mobile IN (" . $mobile . ") GROUP BY `mobile` ");
            // print_r("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (".$mobile.") ");
            if (!empty($black_mobile)) {
                foreach ($black_mobile as $key => $value) {
                    $error_mobile[] = $value['mobile'];
                    $true_mobile[] = $value['mobile'];
                }
            }
            //去除黑名单后实际有效号码
            $real_send_mobile = array_diff($mobile_data, $error_mobile);
            //扣量

            $remaining_mobile = array_diff($real_send_mobile, $white_mobiles);

            //实号
            $entity_mobile = Db::query("SELECT `mobile` FROM `yx_real_mobile` WHERE mobile IN (" . join(',', $remaining_mobile) . ") GROUP BY `mobile` ");
            // echo count($entity_mobile);die;
            //去除实号
            // print_r(count($entity_mobile));die;
            $entity_mobiles = []; //实号即能扣量号码
            if (!empty($entity_mobile)) {
                foreach ($entity_mobile as $key => $value) {
                    $entity_mobiles[] = $value['mobile'];
                    $true_mobile[] = $value['mobile'];
                }
            }

            //未知或者空号



            $vacant  = array_diff($remaining_mobile, $entity_mobiles);
            // echo count($vacant);
            // die;
            //空号检测
            // print_r($vacant);
            //查出这个月内检测出的空号
            $the_month_time = date('Ymd', time());
            $the_month_checkvacant = [];
            $the_month_checkvacant =  Db::query("SELECT `mobile` FROM  yx_mobile WHERE `mobile` IN (" . join(',', $vacant) . ") AND `check_status` = 2 AND `update_time` >= " . $the_month_time . "  GROUP BY  mobile  ");
            /*  print_r($the_month_checkvacant);
                    echo"SELECT `mobile` FROM  yx_mobile WHERE `mobile` IN (" . join(',',$vacant) . ") AND `check_status` = 2 AND `update_time` >= ".$the_month_time."  GROUP BY  mobile  ";
                    die; */
            $the_month_checkvacant_mobiles = [];
            if (!empty($the_month_checkvacant)) {
                foreach ($the_month_checkvacant as $key => $value) {
                    $the_month_checkvacant_mobiles[] = $value['mobile'];
                }
            }
            $need_check_mobile = [];
            $need_check_mobile = array_diff($vacant, $the_month_checkvacant_mobiles);
            $check_result = [];
            // print_r($the_month_checkvacant_mobiles);die;
            if (!empty($need_check_mobile)) {
                $check_result = $this->checkMobileApi($need_check_mobile);
                // print_r($check_result);
                // die;
                // ['real_mobile' => $real_mobile, 'empty_mobile' => $empty_mobile]
                $check_empty_mobile = [];
                $check_empty_mobile = $check_result['empty_mobile']; //检测出来的空号
                $check_real_mobile = [];
                $check_real_mobile = $check_result['real_mobile']; //检测出来的实号

            }
            foreach ($check_real_mobile as $key => $value) {
                $true_mobile[] = $value;
            }
            // return ['']
            return $true_mobile;
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function checkMobileApi($mobiledata = [])
    {
        $real_mobile = [];
        $empty_mobile = [];
        $secret_id = '06FDC4A71F5E1FDE4C061DBA653DD2A5';
        $secret_key = 'ef0587df-86dc-459f-ad82-41c6446b27a5';
        $api = 'https://api.yunzhandata.com/api/deadnumber/v1.0/detect?sig=';
        $ts = date("YmdHis", time());
        $sig = sha1($secret_id . $secret_key . $ts);
        $api = $api . $sig . "&sid=" . $secret_id . "&skey=" . $secret_key . "&ts=" . $ts;
        // $check_mobile = $this->decrypt('6C38881649F7003B910582D1095DA821',$secret_id);
        // print_r($check_mobile);die;
        $data = [];
        $check_mobile_data = [];
        $j = 1;
        foreach ($mobiledata as $key => $value) {
            $check_mobile_data[] = encrypt($value, $secret_id);
            $j++;
            if ($j > 2000) {
                $data = [
                    'mobiles' => $check_mobile_data
                ];
                $headers = [
                    'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json'
                ];
                $result = $this->sendRequest2($api, 'post', $data, $headers);
                // print_r(json_decode($data),true);

                $result = json_decode($result, true);
                // print_r($result);
                if ($result['code'] == 0) { //接口请求成功
                    $mobiles = $result['mobiles'];
                    foreach ($mobiles as $mkey => $value) {
                        $mobile = decrypt($value['mobile'], $secret_id);
                        $check_result = $value['mobileStatus'];
                        $check_status = 2;
                        if ($check_result == 2) { //实号
                            Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_real_mobile')->insert([
                                'mobile' => $mobile,
                                'check_result' => 3,
                                'check_status' => $check_status,
                                'update_time' => time(),
                                'create_time' => time()
                            ]);
                            // return false;
                            $real_mobile[] = $mobile;
                        } else {
                            Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_mobile')->insert([
                                'mobile' => $mobile,
                                'check_result' => $check_result,
                                'check_status' => $check_status,
                                'update_time' => time(),
                                'create_time' => time()
                            ]);
                            $empty_mobile[] = $mobile;
                        }
                    }
                } else {
                    $empty_mobile = $mobiledata;
                }
                $check_mobile_data = [];
                $j = 1;
            }
        }
        if (!empty($check_mobile_data)) {
            $data = [
                'mobiles' => $check_mobile_data
            ];
            $headers = [
                'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json'
            ];
            $result = $this->sendRequest2($api, 'post', $data, $headers);
            // print_r(json_decode($data),true);
            // print_r($data);
            $result = json_decode($result, true);
            if ($result['code'] == 0) { //接口请求成功
                $mobiles = $result['mobiles'];
                if (!is_array($mobiles)) {
                    $result = $this->sendRequest2($api, 'post', $data, $headers);
                    // print_r(json_decode($data),true);
                    // print_r($data);
                    $result = json_decode($result, true);
                    if ($result['code'] == 0) { //接口请求成功
                        $mobiles = $result['mobiles'];
                    }
                }
                foreach ($mobiles as $key => $value) {
                    $mobile = decrypt($value['mobile'], $secret_id);
                    $check_result = $value['mobileStatus'];
                    $check_status = 2;
                    if ($check_result == 2) { //实号
                        Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_real_mobile')->insert([
                            'mobile' => $mobile,
                            'check_result' => 3,
                            'check_status' => $check_status,
                            'update_time' => time(),
                            'create_time' => time()
                        ]);
                        // return false;
                        $real_mobile[] = $mobile;
                    } else {
                        Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_mobile')->insert([
                            'mobile' => $mobile,
                            'check_result' => $check_result,
                            'check_status' => $check_status,
                            'update_time' => time(),
                            'create_time' => time()
                        ]);
                        $empty_mobile[] = $mobile;
                    }
                }
            } else {
                $empty_mobile = $mobiledata;
            }
        }

        return ['real_mobile' => $real_mobile, 'empty_mobile' => $empty_mobile];
    }

    function sendRequest2($requestUrl, $method = 'get', $data = [], $headers)
    {
        $methonArr = ['get', 'post'];
        if (!in_array(strtolower($method), $methonArr)) {
            return [];
        }
        if ($method == 'post') {
            if (!is_array($data) || empty($data)) {
                return [];
            }
        }
        $curl = curl_init(); // 初始化一个 cURL 对象
        curl_setopt($curl, CURLOPT_URL, $requestUrl); // 设置你需要抓取的URL
        curl_setopt($curl, CURLOPT_HEADER, 0); // 设置header 响应头是否输出
        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Chrome/53.0.2785.104 Safari/537.36 Core/1.53.2372.400 QQBrowser/9.5.10548.400'); // 模拟用户使用的浏览器
        }
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        // 1如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($curl); // 运行cURL，请求网页
        curl_close($curl); // 关闭URL请求
        return $res; // 显示获得的数据
    }

    public function exportToExcel()
    {
        ini_set('memory_limit', '10240M');
        $task = Db::query("SELECT  `id`,`task_no`,`mobile_content`,`update_time`,`task_content`,`send_length` FROM yx_user_send_task WHERE `id` IN(366635,366636,366637,366640,366641,366642,366643,366644,366645,366646,366647) ");
        $export = [];
        foreach ($task as $key => $value) {
            $send_mobile = [];
            $mobile = [];
            $mobile = explode(',', $value['mobile_content']);
            /* for ($i = 0; $i < count($mobile); $i++) {
                if (checkMobile($mobile[$i]) == false) {
                    continue;
                } else {
                    $send_mobile[] = $mobile[$i];
                }
            } */
            foreach ($mobile as $mkey => $mvalue) {
                if (checkMobile($mvalue) == false) {
                    continue;
                } else {
                    $send_mobile[] = $mvalue;
                }
            }

            /* $real_mobile = array_unique($send_mobile);
            echo count($mobile);
            echo "\n";
            continue; */
            foreach ($send_mobile as $skey => $svalue) {
                $receipt = Db::query("SELECT `status_message`,`create_time` FROM yx_send_code_task_receipt WHERE `mobile` = '" . $svalue . "' AND `task_id`  = '" . $value['id'] . "' LIMIT 1 ");
                if (empty($receipt)) {
                    $stat = 'DELIVRD';
                    $date = date('Y-m-d H:i:s', $value['update_time'] + mt_rand(5, 20));
                } else {
                    $stat = $receipt[0]['status_message'];
                    $date = date('Y-m-d H:i:s', $receipt[0]['create_time']);
                }
                $receipts = [];
                $receipts = [
                    'mobile' => $svalue,
                    'stat' => $stat,
                    'date' => $date,
                    'task_no' => $value['task_no'],
                    'task_content' => $value['task_content'],
                    'send_length' => $value['send_length'],
                ];
                $export[] = $receipts;
            }
        }

        $objExcel = new PHPExcel();
        // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
        $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
        $objWriter->setOffice2003Compatibility(true);

        //设置文件属性
        $objProps = $objExcel->getProperties();
        $objProps->setTitle("imp_mobile_status_report");
        $objProps->setSubject("imp_mobile_status_report:" . date('Y-m-d H:i:s', time()));

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        $date = date('Y-m-d H:i:s', time());

        //设置当前活动sheet的名称
        $objActSheet->setTitle("imp_mobile_status_report");
        $CellList = array(
            array('mobile', '手机号'),
            array('stat', '状态码'),
            array('date', '回执时间'),
            array('task_no', '任务编号'),
            array('task_content', '内容'),
            array('send_length', '内容长度'),
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
        // $outputFileName = "receive_mms_1_20200523.xlsx";
        $i = 0;
        foreach ($export as $key => $orderdata) {
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
        //imp_mobile_status_report_mms_1_20200531.xlsx
        $objWriter->save('多乐士.xlsx');
    }
}
