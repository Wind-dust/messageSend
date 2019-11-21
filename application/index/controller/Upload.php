<?php

namespace app\index\controller;

use app\index\MyController;
use PHPExcel_Cell;
use PHPExcel_IOFactory;

class Upload extends MyController {

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
     * @apiParam (入参) {String} filename 表格名称 支持文件格式 txt,xlsx,csv,xls
     * @apiSuccess (返回) {String} code 200:成功  / 3001:上传文件不能为空 / 3002:上传失败
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /index/upload/uploadUserExcel
     * @author zyr
     */
    public function uploadUserExcel() {
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
        if ($fileType[0] == 'text'){
            $info = $filename->move('../uploads/text');
            $type = $info->getExtension();
            $path      = $info->getpathName();
            if (empty($path)) {
                return ['code' => '3002'];
            }
            $file = fopen($path, "r");
            $data=array();
            $i=0;
            $phone = '';
            $j     = '';
            while(! feof($file))
            {
                $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
                // print_r($phone);die;
                $j = ',';
                $i++;
            }
            fclose($file);
            $data=array_filter($data);
            return ['code' => '200','phone' => $phone];
        }
        if (!in_array($fileType[1], ['vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'vnd.ms-excel', 'csv'])) {
            return ['3001']; //上传的不是表格
        }
        $info = $filename->move('../uploads/excel');

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
                $filed            = array();for ($i = 0; $i < $highestColumnNum; $i++) {
                    $cellName = PHPExcel_Cell::stringFromColumnIndex($i) . '1';
                    $cellVal  = $sheet->getCell($cellName)->getValue(); //取得列内容
                    $filed[]  = $cellVal;
                } //开始取出数据并存入数组
                $phone = '';
                $j     = '';
                for ($i = 1; $i <= $highestRowNum; $i++) {
                    $row      = array();
                    $cellName = PHPExcel_Cell::stringFromColumnIndex(0) . $i;
                    $cellVal  = $sheet->getCell($cellName)->getValue();
                    $phone .= $j . $cellVal;
                    $j = ',';
                    // for ($j = 0; $j < $highestColumnNum; $j++) {

                    //     $row[$filed[$j]] = trim($cellVal);
                    // }
                    // $data[] = $row;
                }
                return ['code' => '200', 'phone' => $phone];
            } else if ($type == 'xlsx') {
                $type = 'Excel2007';
                $objReader = PHPExcel_IOFactory::createReader($type);
                $path      = $info->getpathName();
                $objPHPExcel = $objReader->load($path,$encode='utf-8');//加载文件
                $sheet = $objPHPExcel->getSheet(0);//取得sheet(0)表
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $phone = '';
                $j     = '';
                for ($i=1; $i < $highestRow; $i++) { 
                    $cellVal = $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
                    $phone .= $j . $cellVal;
                    $j = ',';
                }
                return ['code' => '200', 'phone' => $phone];
            } elseif ($type == 'xls') {
                $type = 'Excel5';
                $objReader = PHPExcel_IOFactory::createReader($type);
                $path      = $info->getpathName();
                $objPHPExcel = $objReader->load($path,$encode='utf-8');//加载文件
                $sheet = $objPHPExcel->getSheet(0);//取得sheet(0)表
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $phone = '';
                $j     = '';
                for ($i=1; $i < $highestRow; $i++) { 
                    $cellVal = $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
                    $phone .= $j . $cellVal;
                    $j = ',';
                }
                return ['code' => '200', 'phone' => $phone];
            }

        } else {
            return ['code' => '3002'];
        }

    }

    /**
     * @api              {post} / 上传单个图片
     * @apiDescription   uploadFile
     * @apiGroup         index_upload
     * @apiName          uploadFilee
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {file} image 图片
     * @apiSuccess (返回) {String} code 200:成功  / 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /index/upload/uploadfile
     * @author zyr
     */
    public function uploadFile() {
        $apiName = classBasename($this) . '/' . __function__;
        $conId   = trim($this->request->post('con_id'));
        $image   = $this->request->file('image');
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
        $result = $this->app->upload->uploadFile($fileInfo);
        $this->apiLog($apiName, [$conId, $image], $result['code'], $conId);
        return $result;
    }
}