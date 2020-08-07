<?php

namespace app\index\controller;

use app\index\MyController;
use PHPExcel_Cell;
use PHPExcel_IOFactory;

class Upload extends MyController
{

    protected $beforeActionList = [
        // 'isLogin',//所有方法的前置操作
        // 'isLogin' => ['except' => ''], //除去getFirstCate其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 上传单个本地表格文件
     * @apiDescription   uploadUserExcel
     * @apiGroup         index_upload
     * @apiName          uploadUserExcel
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {file} filename 表格名称 支持文件格式 txt,xlsx,csv,xls
     * @apiSuccess (返回) {String} code 200:成功  / 3001:上传文件不能为空 / 3002:上传失败 / 3003:上传号码为空
     * @apiSuccess (data) {Number} submit_num 上传数量
     * @apiSuccess (data) {Number} real_num 真实有效数量
     * @apiSuccess (data) {Number} mobile_num 移动手机号数量
     * @apiSuccess (data) {Number} unicom_num 联通手机号数量
     * @apiSuccess (data) {Number} telecom_num 电信手机号数量
     * @apiSuccess (data) {Number} virtual_num 虚拟运营商手机号数量
     * @apiSuccess (data) {Number} unknown_num 未知归属运营商手机号数量
     * @apiSuccess (data) {Number} mobile_phone 移动手机号码包
     * @apiSuccess (data) {Number} unicom_phone 联通手机号码包
     * @apiSuccess (data) {Number} telecom_phone 电信手机号码包
     * @apiSuccess (data) {Number} virtual_phone 虚拟运营商手机号码包
     * @apiSuccess (data) {Number} error_phone 错号包
     * @apiSuccess (data) {String} phone 真实手机号结果
     * @apiSampleRequest /index/upload/uploadUserExcel
     * @author rzc
     */
    public function uploadUserExcel()
    {
        // $apiName  = classBasename($this) . '/' . __function__;
        // $conId    = trim($this->request->post('con_id'));
        // echo phpinfo();die;
        $filename = $this->request->file('filename');
        // echo $filename->getError();die;
        if (empty($filename)) {
            return ['code' => '3001'];
        }
        //表格拓展类型  xlsx:vnd.openxmlformats-officedocument.spreadsheetml.sheet,xls:vnd.ms-excel,csv:csv
        $fileInfo = $filename->getInfo();
        $fileType = explode('/', $fileInfo['type']);
        if ($fileType[0] == 'text') {
            $info = $filename->move('../uploads/text');
            $type = $info->getExtension();
            $path      = $info->getpathName();
            if (empty($path)) {
                return ['code' => '3002'];
            }
            $file = fopen($path, "r");
            $data = array();
            $i = 0;
            $phone = '';
            $j     = '';
            while (!feof($file)) {
                // $phone_data[]= trim(fgets($file));
                $phone .= $j . trim(fgets($file)); //fgets()函数从文件指针中读取一行
                // print_r($phone);die;
                $j = ',';
                $i++;
            }
            fclose($file);
            // $result = $this->app->send->getMobilesDetail($phone_data);
            return ['code' => 200, 'phone' => $phone];
        }
        if (!in_array($fileType[1], ['vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'vnd.ms-excel', 'csv'])) {
            return ['code' => '3004']; //上传的不是表格
        }
        $info = $filename->move('../uploads/excel');
        // $phone_data = [];
        // print_r($info);die;
        if ($info) {
            $type = $info->getExtension();
            if ($type == 'csv') {
                $type      = 'CSV';
                $path      = $info->getpathName();
                $objReader = PHPExcel_IOFactory::createReader($type)
                    ->setDelimiter(',')
                    ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
                    ->setEnclosure('"')
                    ->setSheetIndex(0);
                // print_r(realpath("../"). "\yt_area_mobile.csv");die;

                $objPHPExcel = $objReader->load($path);
                // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
                //选择标签页
                $sheet            = $objPHPExcel->getSheet(0); //获取行数与列数,注意列数需要转换
                $highestRowNum    = $sheet->getHighestRow();
                $highestColumn    = $sheet->getHighestColumn();
                $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
                // $filed            = array();
                // for ($i = 0; $i < $highestColumnNum; $i++) {
                //     $cellName = PHPExcel_Cell::stringFromColumnIndex($i) . '1';
                //     $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容
                //     $filed[]  = $cellVal;
                // } 
                //开始取出数据并存入数组
                $phone = '';
                $j     = '';
                for ($i = 1; $i <= $highestRowNum; $i++) {
                    $row      = array();
                    $cellName = PHPExcel_Cell::stringFromColumnIndex(0) . $i;
                    $cellVal  = $sheet->getCell($cellName)->getValue();
                    // $phone_data[]= trim($cellVal);
                    // for ($j = 0; $j < $highestColumnNum; $j++) {

                    //     $row[$filed[$j]] = trim($cellVal);
                    // }
                    // $data[] = $row;
                    if (empty($cellVal)) {
                        $phone .= $j . trim($cellVal);
                        $j = ',';
                    }
                   
                }
            } else if ($type == 'xlsx') {
                $type = 'Excel2007';
                $objReader = PHPExcel_IOFactory::createReader($type);
                $path      = $info->getpathName();
                $objPHPExcel = $objReader->load($path, $encode = 'utf-8'); //加载文件
                $sheet = $objPHPExcel->getSheet(0); //取得sheet(0)表
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $phone = '';
                $j     = '';
                for ($i = 1; $i <= $highestRow; $i++) {
                    $cellVal = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                    // $phone_data[]= trim($cellVal);
                    if (!empty($cellVal)) {
                        $phone .= $j . trim($cellVal);
                        $j = ',';
                    }
                    
                }
               
            } elseif ($type == 'xls') {
                $type = 'Excel5';
                $objReader = PHPExcel_IOFactory::createReader($type);
                $path      = $info->getpathName();
                $objPHPExcel = $objReader->load($path, $encode = 'utf-8'); //加载文件
                $sheet = $objPHPExcel->getSheet(0); //取得sheet(0)表
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $phone = '';
                $j     = '';

                for ($i = 1; $i <= $highestRow; $i++) {
                    $cellVal = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                    // $phone_data[]= trim($cellVal);
                    // $j = ',';
                    if (empty($cellVal)) {
                        $phone .= $j . trim($cellVal);
                        $j = ',';
                    }
                }
            }
            if (empty($phone)) {
                return ['code' => '3003'];
            }
            // $result = $this->app->send->getMobilesDetail($phone_data);
            return ['code' => 200, 'phone' => $phone];
        } else {
            return ['code' => '3002'];
        }
    }

    /**
     * @api              {post} / 上传模板表格文件（非变量类型）
     * @apiDescription   uploadModelExcel
     * @apiGroup         index_upload
     * @apiName          uploadModelExcel
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {file} filename 表格名称 支持文件格式xlsx,csv,xls
     * @apiSuccess (返回) {String} code 200:成功  / 3001:上传文件不能为空 / 3002:上传失败 / 3003:上传号码为空
     * @apiSuccess (data) {String} send_data 真实手机号结果
     * @apiSampleRequest /index/upload/uploadModelExcel
     * @author rzc
     */
    public function uploadModelExcel()
    {
        $filename = $this->request->file('filename');
        // echo $filename->getError();die;
        if (empty($filename)) {
            return ['code' => '3001'];
        }
        //表格拓展类型  xlsx:vnd.openxmlformats-officedocument.spreadsheetml.sheet,xls:vnd.ms-excel,csv:csv
        $fileInfo = $filename->getInfo();
        $fileType = explode('/', $fileInfo['type']);
        $info = $filename->move('../uploads/excel');
        $send_data = [];
        // print_r($info);die;
        if ($info) {
            $type = $info->getExtension();
            if ($type == 'csv') {
                $type      = 'CSV';
                $path      = $info->getpathName();
                $objReader = PHPExcel_IOFactory::createReader($type)
                    ->setDelimiter(',')
                    ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
                    ->setEnclosure('"')
                    ->setSheetIndex(0);
                // print_r(realpath("../"). "\yt_area_mobile.csv");die;

                $objPHPExcel = $objReader->load($path);
                // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
                //选择标签页
                $sheet            = $objPHPExcel->getSheet(0); //获取行数与列数,注意列数需要转换
                $highestRowNum    = $sheet->getHighestRow();
                $highestColumn    = $sheet->getHighestColumn();
                $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
                for ($i = 1; $i <= $highestRowNum; $i++) {
                    $row      = array();
                    $cellName = PHPExcel_Cell::stringFromColumnIndex(0) . $i;
                    // $cellVal  = $sheet->getCell($cellName)->getValue();
                    $mobile   = $sheet->getCell($cellName)->getValue();
                    $cellName = PHPExcel_Cell::stringFromColumnIndex(1) . $i;
                    $connect = $sheet->getCell($cellName)->getValue();
                    // $send_data[] = urlencode($connect) . ":" . $mobile;
                    if (!empty($mobile) && !empty($connect)) {
                        $send_data[] = base64_encode($connect)  . ":" . $mobile;
                    }
                }
            } elseif ($type == 'xlsx') {
                $type = 'Excel2007';
                $objReader = PHPExcel_IOFactory::createReader($type);
                $path      = $info->getpathName();
                $objPHPExcel = $objReader->load($path, $encode = 'utf-8'); //加载文件
                $sheet = $objPHPExcel->getSheet(0); //取得sheet(0)表
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                for ($i = 1; $i <= $highestRow; $i++) {
                    $mobile = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                    $connect = $objPHPExcel->getActiveSheet()->getCell("B" . $i)->getValue();
                    // $send_data[] = urlencode($connect)  . ":" . $mobile;
                    if (!empty($mobile) && !empty($connect)) {
                        $send_data[] = base64_encode($connect)  . ":" . $mobile;
                    }
                   
                }
            } elseif ($type == 'xls') {
                $type = 'Excel5';
                $objReader = PHPExcel_IOFactory::createReader($type);
                $path      = $info->getpathName();
                $objPHPExcel = $objReader->load($path, $encode = 'utf-8'); //加载文件
                $sheet = $objPHPExcel->getSheet(0); //取得sheet(0)表
                $highestRow = $sheet->getHighestRow(); // 取得总行数

                for ($i = 1; $i <= $highestRow; $i++) {
                    $mobile = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                    $connect = $objPHPExcel->getActiveSheet()->getCell("B" . $i)->getValue();
                    // $send_data[] = urlencode($connect) . ":" . $mobile;
                    if (!empty($mobile) && !empty($connect)) {
                        $send_data[] = base64_encode($connect)  . ":" . $mobile;
                    }
                }
            }
            if (empty($send_data)) {
                return ['code' => '3003'];
            }
        }
        // $result = $this->app->send->getMobilesDetail($phone_data);
        return ['code' => 200, 'send_data' => join(';', $send_data)];
    }

    /**
     * @api              {post} / 上传模板表格文件（变量类型）
     * @apiDescription   uploadVarModelExcel
     * @apiGroup         index_upload
     * @apiName          uploadVarModelExcel
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {file} filename 表格名称 支持文件格式xlsx,csv,xls
     * @apiSuccess (返回) {String} code 200:成功  / 3001:上传文件不能为空 / 3002:上传失败 / 3003:上传号码为空
     * @apiSuccess (data) {String} send_data 内容结果
     * @apiSampleRequest /index/upload/uploadVarModelExcel
     * @author rzc
     */
    public function uploadVarModelExcel()
    {
        $filename = $this->request->file('filename');
        // echo $filename->getError();die;
        if (empty($filename)) {
            return ['code' => '3001'];
        }
        //表格拓展类型  xlsx:vnd.openxmlformats-officedocument.spreadsheetml.sheet,xls:vnd.ms-excel,csv:csv
        $fileInfo = $filename->getInfo();
        $fileType = explode('/', $fileInfo['type']);
        $info = $filename->move('../uploads/excel');
        $send_data = [];
        // print_r($info);die;
        if ($info) {
            $type = $info->getExtension();
            if ($type == 'csv') {
                $type      = 'CSV';
                $path      = $info->getpathName();
                $objReader = PHPExcel_IOFactory::createReader($type)
                    ->setDelimiter(',')
                    ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
                    ->setEnclosure('"')
                    ->setSheetIndex(0);
                // print_r(realpath("../"). "\yt_area_mobile.csv");die;

                $objPHPExcel = $objReader->load($path);
                // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
                //选择标签页
                $sheet            = $objPHPExcel->getSheet(0); //获取行数与列数,注意列数需要转换
                $highestRowNum    = $sheet->getHighestRow();
                $highestColumn    = $sheet->getHighestColumn();
                $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
                for ($i = 1; $i <= $highestRowNum; $i++) {
                    $mobile = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                    if (empty($mobile)) {
                        continue;
                    }
                    $text = '';
                    $cor = '';
                    for ($j = 1; $j < $highestColumnNum; $j++) {
                        $cellName = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
                        $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容
                        if (!empty($cellVal)) {
                            // $text .= $cor .urlencode($cellVal);
                            $text .= $cor .base64_encode($cellVal);
                            $cor = ',';
                        }
                    }
                    $send_data[] = $text . ":" . $mobile;
                    // $row      = array();
                    // $cellName = PHPExcel_Cell::stringFromColumnIndex(0) . $i;
                    // // $cellVal  = $sheet->getCell($cellName)->getValue();
                    // $mobile   = $sheet->getCell($cellName)->getValue();
                    // $cellName = PHPExcel_Cell::stringFromColumnIndex(1) . $i;
                    // $connect = $sheet->getCell($cellName)->getValue();
                    // $send_data[] = $connect . ":" . $mobile;
                }
            } elseif ($type == 'xlsx') {
                $type = 'Excel2007';
                $objReader = PHPExcel_IOFactory::createReader($type);
                $path      = $info->getpathName();
                $objPHPExcel = $objReader->load($path, $encode = 'utf-8'); //加载文件
                $sheet = $objPHPExcel->getSheet(0); //取得sheet(0)表
                $highestRow = $sheet->getHighestRow(); // 取得总行数

                $highestColumn    = $sheet->getHighestColumn();
                $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
                for ($i = 1; $i <= $highestRow; $i++) {
                    $mobile = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                    if (empty($mobile)) {
                        continue;
                    }
                    $text = '';
                    $cor = '';
                    for ($j = 1; $j < $highestColumnNum; $j++) {
                        $cellName = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
                        $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容

                        if (!empty($cellVal)) {
                            // $text .= $cor . urlencode($cellVal);
                            $text .= $cor . base64_encode($cellVal);
                            $cor = ',';
                        }
                    }
                    $send_data[] = $text . ":" . $mobile;
                    // print_r($send_data);
                }
                // die;
            } elseif ($type == 'xls') {
                $type = 'Excel5';
                $objReader = PHPExcel_IOFactory::createReader($type);
                $path      = $info->getpathName();
                $objPHPExcel = $objReader->load($path, $encode = 'utf-8'); //加载文件
                $sheet = $objPHPExcel->getSheet(0); //取得sheet(0)表
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $highestColumn    = $sheet->getHighestColumn();
                $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
                for ($i = 1; $i <= $highestRow; $i++) {
                    $mobile = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                    if (empty($mobile)) {
                        continue;
                    }
                    $text = '';
                    $cor = '';
                    for ($j = 1; $j < $highestColumnNum; $j++) {
                        $cellName = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
                        $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容
                        if (!empty($cellVal)) {
                            // $text .= $cor . urlencode($cellVal);
                            $text .= $cor . base64_encode($cellVal);
                            $cor = ',';
                        }
                    }
                    $send_data[] = $text . ":" . $mobile;
                }
            }
            if (empty($send_data)) {
                return ['code' => '3003'];
            }
        }
        $number = count($send_data);
        return ['code' => 200, 'number' => $number, 'send_data' => join(';', $send_data)];
    }

    /**
     * @api              {post} / 上传单个图片
     * @apiDescription   uploadFile
     * @apiGroup         index_upload
     * @apiName          uploadFilee
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {file} image 图片
     * @apiSuccess (返回) {String} code 200:成功  / 3000:appid或者appkey错误/ 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /index/upload/uploadfile
     * @author zyr
     */
    public function uploadFile()
    {
        $apiName = classBasename($this) . '/' . __function__;
        // $conId   = trim($this->request->post('con_id'));
        $appid = trim($this->request->post('appid')); //登录名
        $appkey = trim($this->request->post('appkey')); //登陆密码
        $image   = $this->request->file('image');
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($image)) {
            return ['code' => '3004'];
        }
        $fileInfo = $image->getInfo();
        $fileType = explode('/', $fileInfo['type']);
        if ($fileType[0] != 'image') {
            return ['3001']; //上传的不是图片
        }
        if ($fileInfo['size'] > 1024 * 90) {
            return [ 'code' => '3002']; //上传图片不能超过90KB
        }
        $result = $this->app->upload->uploadFile($appid, $appkey, $fileInfo);
        // $this->apiLog($apiName, [$conId, $image], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 上传音视频接口
     * @apiDescription   uploadVideo
     * @apiGroup         index_upload
     * @apiName          uploadVedio
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {file} video 视频文件
     * @apiSuccess (返回) {String} code 200:成功  / 3000:appid或者appkey错误/ 3001:上传的不是视频文件 / 3002:上传文件不能超过2M / 3003:上传失败 / 3004:上传文件不能为空
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /index/upload/uploadVideo
     * @author rzc
     */
    public function uploadVideo()
    {
        $apiName = classBasename($this) . '/' . __function__;
        // $conId   = trim($this->request->post('con_id'));
        $appid = trim($this->request->post('appid')); //登录名
        $appkey = trim($this->request->post('appkey')); //登陆密码
        $video   = $this->request->file('video');
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($video)) {
            return ['code' => '3004'];
        }
        $fileInfo = $video->getInfo();
        $fileType = explode('/', $fileInfo['type']);
        if ($fileType[0] != 'video' || $fileType[0] != 'audio') { //音频或者视频
            return ['3001']; //上传的不是视频文件
        }
        /*  if ($fileInfo['size'] > 1024 * 1024 * 2) {
            return ['3002']; //上传图片不能超过2M
        } */
        $result = $this->app->upload->uploadVideo($appid, $appkey, $fileInfo);
        // $this->apiLog($apiName, [$conId, $image], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 上传单个图片
     * @apiDescription   uploadFileTest
     * @apiGroup         index_upload
     * @apiName          uploadFileTest
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {file} image 图片
     * @apiSuccess (返回) {String} code 200:成功  / 3000:appid或者appkey错误/ 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /index/upload/uploadFileTest
     * @author zyr
     */
    public function uploadFileTest()
    {
        $apiName = classBasename($this) . '/' . __function__;
        // $conId   = trim($this->request->post('con_id'));
        $appid = trim($this->request->post('appid')); //登录名
        $appkey = trim($this->request->post('appkey')); //登陆密码
        $image   = $this->request->file('image');
        if (empty($appid)) {
            return ['code' => '3000'];
        }
        if (empty($appkey)) {
            return ['code' => '3000'];
        }
        if (empty($image)) {
            return ['code' => '3004'];
        }
        $fileInfo = $image->getInfo();
        $fileType = explode('/', $fileInfo['type']);
        if ($fileType[0] != 'image') {
            return ['3001']; //上传的不是图片
        }
        if ($fileInfo['size'] > 1024 * 1024 * 2) {
            return ['3002']; //上传图片不能超过2M
        }
        $result = $this->app->upload->uploadFile($appid, $appkey, $fileInfo);
        // $this->apiLog($apiName, [$conId, $image], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 上传彩信模板变量表格
     * @apiDescription   uploadModelMultimediaVar
     * @apiGroup         index_upload
     * @apiName          uploadModelMultimediaVar
     * @apiParam (入参) {String} appid appid
     * @apiParam (入参) {String} appkey appkey
     * @apiParam (入参) {file}  filename 文件 
     * @apiSuccess (返回) {String} code 200:成功  / 3000:appid或者appkey错误/ 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /index/upload/uploadModelMultimediaVar
     * @author zyr
     */
    public function uploadModelMultimediaVar(){
        $filename = $this->request->file('filename');
        // echo $filename->getError();die;
        if (empty($filename)) {
            return ['code' => '3001'];
        }
        //表格拓展类型  xlsx:vnd.openxmlformats-officedocument.spreadsheetml.sheet,xls:vnd.ms-excel,csv:csv
        $fileInfo = $filename->getInfo();
        $fileType = explode('/', $fileInfo['type']);
        $info = $filename->move('../uploads/excel');
        $send_data = [];
        // print_r($info);die;
        if ($info) {
            $type = $info->getExtension();
            if ($type == 'csv') {
                $type      = 'CSV';
                $path      = $info->getpathName();
                $objReader = PHPExcel_IOFactory::createReader($type)
                    ->setDelimiter(',')
                    ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
                    ->setEnclosure('"')
                    ->setSheetIndex(0);
                // print_r(realpath("../"). "\yt_area_mobile.csv");die;

                $objPHPExcel = $objReader->load($path);
                // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
                //选择标签页
                $sheet            = $objPHPExcel->getSheet(0); //获取行数与列数,注意列数需要转换
                $highestRowNum    = $sheet->getHighestRow();
                $highestColumn    = $sheet->getHighestColumn();
                $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
                for ($i = 1; $i <= $highestRowNum; $i++) {
                    
                    $mobile = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                    if (empty($mobile)) {
                        continue;
                    }
                    $var_data = [];
                    $var_data['mobile'] = $mobile;
                    $text = '';
                    $cor = '';
                    for ($j = 1; $j < $highestColumnNum; $j++) {
                        $cellName = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
                        $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容
                        if (!empty($cellVal)) {
                           /*  $text .= $cor .urlencode($cellVal);
                            $cor = ','; */
                            // $var_num = $j-1;
                            $var_data['{{var'.$j.'}}'] = $cellVal;
                        }
                    }
                    // $send_data[] = $text . ":" . $mobile;
                    $send_data[] = $var_data;
                    // $row      = array();
                    // $cellName = PHPExcel_Cell::stringFromColumnIndex(0) . $i;
                    // // $cellVal  = $sheet->getCell($cellName)->getValue();
                    // $mobile   = $sheet->getCell($cellName)->getValue();
                    // $cellName = PHPExcel_Cell::stringFromColumnIndex(1) . $i;
                    // $connect = $sheet->getCell($cellName)->getValue();
                    // $send_data[] = $connect . ":" . $mobile;
                }
            } elseif ($type == 'xlsx') {
                $type = 'Excel2007';
                $objReader = PHPExcel_IOFactory::createReader($type);
                $path      = $info->getpathName();
                $objPHPExcel = $objReader->load($path, $encode = 'utf-8'); //加载文件
                $sheet = $objPHPExcel->getSheet(0); //取得sheet(0)表
                $highestRow = $sheet->getHighestRow(); // 取得总行数

                $highestColumn    = $sheet->getHighestColumn();
                $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
                for ($i = 1; $i <= $highestRow; $i++) {
                    $mobile = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                    if (empty($mobile)) {
                        continue;
                    }
                    $var_data = [];
                    $var_data['mobile'] = $mobile;
                    $text = '';
                    $cor = '';
                    for ($j = 1; $j < $highestColumnNum; $j++) {
                        $cellName = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
                        $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容

                        if (!empty($cellVal)) {
                            // $var_num = $j-1;
                            $var_data['{{var'.$j.'}}'] = $cellVal;
                        }
                    }
                    // $send_data[] = $text . ":" . $mobile;
                    // print_r($send_data);
                    $send_data[] = $var_data;
                }
                // die;
            } elseif ($type == 'xls') {
                $type = 'Excel5';
                $objReader = PHPExcel_IOFactory::createReader($type);
                $path      = $info->getpathName();
                $objPHPExcel = $objReader->load($path, $encode = 'utf-8'); //加载文件
                $sheet = $objPHPExcel->getSheet(0); //取得sheet(0)表
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $highestColumn    = $sheet->getHighestColumn();
                $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn); //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
                for ($i = 1; $i <= $highestRow; $i++) {
                    $mobile = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
                    if (empty($mobile)) {
                        continue;
                    }
                    $text = '';
                    $cor = '';
                    $var_data = [];
                    $var_data['mobile'] = $mobile;
                    for ($j = 1; $j < $highestColumnNum; $j++) {
                        $cellName = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
                        $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容
                        if (!empty($cellVal)) {
                            // $text .= $cor . urlencode($cellVal);
                            // $cor = ',';
                            // $var_num = $j-1;
                            $var_data['{{var'.$j.'}}'] = $cellVal;
                        }
                    }
                    // $send_data[] = $text . ":" . $mobile;
                    $send_data[] = $var_data;
                }
            }
            if (empty($send_data)) {
                return ['code' => '3003'];
            }
        }
        $number = count($send_data);
        return ['code' => 200, 'number' => $number, 'send_data' => $send_data];
    }
}
