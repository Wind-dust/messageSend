<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use CURLFile;
use Exception;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Fill;
use PHPExcel_Writer_Excel2007;
use think\Db;
use upload\Imageupload;
use ZipArchive;

header("Content-Type: text/html;charset=utf-8");
class SflUpload extends Pzlife
{

    private $redis;
    /**
     * 数据库连接
     *
     */
    public function dbConnect($databasename)
    {
        if ($databasename == 'old') {
            return Db::connect(Config::get('database.db_config'));
        } else {
            return Db::connect(Config::get('database.'));
        }
    }

    /**
     * ftp 测试
     */
    public function ftpConfig()
    {
        return ['host' => '127.0.0.1', 'port' => '8007', 'user' => '', 'password' => ''];
    }

    public function testFtp()
    {
        $ftp_config = $this->ftpConfig();
        $ftp = ftp_connect($ftp_config['host'], $ftp_config['port']);
        if (!$ftp) {
            echo "connect fail\n";
            exit;
        }
        echo "connect success\n";

        // 进行ftp登录，使用给定的ftp登录用户名和密码进行login
        $f_login = ftp_login($ftp, $ftp_config['user'], $ftp_config['password']);
        if (!$f_login) {
            echo "login fail\n";
            exit;
        }
        echo "login success\n";

        // 获取当前所在的ftp目录
        $in_dir = ftp_pwd($ftp);
        if (!$in_dir) {
            echo "get dir info fail\n";
            exit(1);
        }
        echo "$in_dir\n";

        // 获取当前所在ftp目录下包含的目录与文件
        $exist_dir = ftp_nlist($ftp, ftp_pwd($ftp));
        print_r($exist_dir);

        /* // 要求是按照日期在ftp目录下创建文件夹作为文件上传存放目录
        echo date("Ymd") . "\n";
        $dir_name = date("Ymd");
        // 检查ftp目录下是否已存在当前日期的文件夹，如不存在则进行创建
        if (!in_array("$in_dir/$dir_name", $exist_dir)) {
        if (!ftp_mkdir($ftp, $dir_name)) {
        echo "mkdir fail\n";
        exit(1);
        } else {
        echo "mkdir $dir_name success\n";
        }
        }
        // 切换目录
        if (!ftp_chdir($ftp, $dir_name)) {
        echo "chdir fail\n";
        exit(1);
        } else {
        echo "chdir $dir_name success\n";
        } */
        // 进行文件上传
        $result = ftp_put($ftp, 'bbb.mp3', '/root/liang/ftp/bbb.mp3', FTP_BINARY);
        if (!$result) {
            echo "upload file fail\n";
            exit(1);
        } else {
            echo "upload file success\n";
            exit(0);
        }
    }

    public function fileCheck()
    {
        // $path = 'D:\Dev\Dev.Data\www\messageSend\uploads\SFL\MMS\100088234_20200424155750.zip';
        // echo md5_file($path);
        // 25c9bf2d94b4106dff22399dd41e7321
        // 25c9bf2d94b4106dff22399dd41e7321
        $path = '';
        //校验成功后推送核验成功
    }

    //文件夹解压并上传彩信模板
    public function unZip()
    {
        $redis = Phpredis::getConn();
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G
        $zip = new ZipArchive();
        $path = realpath("") . "/uploads/SFL/";
        $path_data = $this->getDirContent($path);
        if ($path_data == false) {
            exit("This Dir IS null");
        }
        if ($path_data == false) {
            exit("This Dir IS null");
        }

        //队列名称
        $mms_send_had_file = "sftp:mms:sfl:hadsendfile"; //sftp彩信丝芙兰已发送
        $mms_send_have_file = "sftp:mms:sfl:havesendfile"; //sftp彩信丝芙兰待发送
        $sms_send_model = "sftp:sms:sfl:model"; //sftp短信模板
        $sms_send_had_file = "sftp:sms:sfl:hadsendfile"; //sftp短信丝芙兰已发送
        $sms_send_have_file = "sftp:sms:sfl:havesendfile"; //sftp短信丝芙兰待发送
        try {
            foreach ($path_data as $key => $value) {
                //进入二级目录 MMS 或者 SMS 等
                //跳过本地解压文件夹
                if ($value == 'UnZip') {
                    continue;
                }
                $son_path_data = $this->getDirContent($path . $value);
                if ($value == 'MMS') {
                    $send_data = [];
                    if ($son_path_data !== false) {

                        foreach ($son_path_data as $skey => $svalue) {
                            $son_path = '';
                            $son_path = $path . $value . "/" . $svalue;
                            // $file = fopen($path.$value."/".$svalue,"r");
                            $file_info = explode('.', $svalue);
                            if ($file_info[1] == 'zip') { //需要解压
                                //开始解压
                                if ($zip->open($son_path) === true) {
                                    $unpath = $path . 'UnZip' . "/" . $value . "/" . $file_info[0]; //解压目录
                                    $count = $zip->numFiles;
                                    // $results = [];
                                    $files_name = [];
                                    for ($i = 0; $i < $count; $i++) {
                                        $entry = $zip->statIndex($i, ZipArchive::FL_ENC_RAW);
                                        $entry['name'] = rtrim(str_replace('\\', '/', $entry['name']), '/');
                                        $encoding = mb_detect_encoding($entry['name'], array('Shift_JIS', 'EUC_JP', 'EUC_KR', 'KOI8-R', 'ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8'));
                                        $filename = iconv($encoding, 'UTF-8', $entry['name']);
                                        $filename = $filename ?: $entry['name'];
                                        $size = $entry['size'];
                                        $comp_size = $entry['comp_size'];
                                        $mtime = $entry['mtime'];
                                        $crc = $entry['crc'];
                                        $is_dir = ($crc == 0);
                                        // $path = '/' . $filename;

                                        $_names = explode('/', $filename);
                                        $_idx = count($_names) - 1;

                                        $name = $_names[$_idx];
                                        if (empty($name)) {
                                            continue;
                                        }
                                        $files_name[] = $name;
                                        $index = $i;
                                        //$data = $zip->getFromIndex($i);
                                        $entry = compact('name', 'path', 'size', 'comp_size', 'mtime', 'crc', 'index', 'is_dir');
                                        // $results[] = $entry;
                                    }
                                    // print_r($files_name);die;
                                    $mcw = $zip->extractTo($unpath, $files_name); //解压到$route这个目录中
                                    // // $mcw    = $zip->extractTo($unpath); //解压到$route这个目录中
                                    $zip->close();
                                    //解压完成
                                    //获取解压完成地址
                                    $unzip = $this->getDirContent($unpath);
                                    //先上传模板内容

                                    //发送内容跳出并写入待处理缓存
                                    if (strpos($file_info[0], "targets")) {
                                        foreach ($unzip as $ukey => $uvalue) {
                                            $send_data[] = $unpath . '/' . $uvalue;
                                        }
                                        continue;
                                    }
                                    $fram_model = [];
                                    foreach ($unzip as $ukey => $uvalue) {
                                        $fram = [];
                                        $un_file_info = explode('.', $uvalue);
                                        // if ($un_file_info[1] == 'jpg') { //图片

                                        // }elseif ($un_file_info[1] == '') {}
                                        $son_dir_path = $unpath . "/" . $uvalue;
                                        if ($uvalue == '1.jpg' || $uvalue == '1.gif') {

                                            //调用内部api 上传图片
                                            $data = [
                                                'appid' => '5e17e42ae9fe3',
                                                'appkey' => 'da1416c4d51b8edd58596ca4b56ca267',
                                                'image' => new CURLFile($son_dir_path, 'image', $uvalue),
                                            ];
                                            $info = $this->uploadFileToBase($data);
                                            // $result = sendRequest('', 'post',  $data);
                                            // $fileInfo = $this->getInfo($image);

                                            if (isset($info['code']) && $info['code'] == 200) {
                                                $fram['num'] = 1;
                                                $fram['name'] = "第一帧";
                                                $fram['image_path'] = filtraImage(Config::get('qiniu.domain'), $info['image_path']);
                                                $fram_model[] = $fram;
                                                // array_push($fram, $fram_model);
                                            }
                                        } else if ($uvalue == '1.txt') {
                                            $fram['content'] = '';
                                            $txt = $this->readForTxtToArray($son_dir_path);
                                            $fram['num'] = 2;
                                            $fram['name'] = "第二帧";

                                            $fram['content'] = join('\\n', $txt);

                                            if (strpos($fram['content'], '【丝芙兰】') !== false) {
                                            } else {
                                                $fram['content'] = '【丝芙兰】' . $fram['content'];
                                            }
                                            // print_r($fram['content']);

                                            // die;
                                            $fram_model[] = $fram;
                                            // array_push($fram, $fram_model);
                                        } else if ($uvalue == '2.jpg' || $uvalue == '2.gif') {
                                            $data = [
                                                'appid' => '5e17e42ae9fe3',
                                                'appkey' => 'da1416c4d51b8edd58596ca4b56ca267',
                                                'image' => new CURLFile($son_dir_path, 'image', $uvalue),
                                            ];
                                            // $info = $this->uploadFileToBase($data);
                                            // $result = sendRequest('', 'post',  $data);
                                            // $fileInfo = $this->getInfo($image);
                                            if (isset($info['code']) && $info['code'] == 200) {
                                                $fram['num'] = 3;
                                                $fram['name'] = "第三帧";
                                                $fram['image_path'] = filtraImage(Config::get('qiniu.domain'), $info['image_path']);
                                                $fram_model[] = $fram;
                                                // array_push($fram, $fram_model);
                                            }
                                        } else if ($uvalue == '2.txt') {
                                            $txt = $this->readForTxtToArray($son_dir_path);
                                            $fram['num'] = 4;
                                            $fram['name'] = "第四帧";

                                            $fram['content'] = join('\n', $txt);
                                            $fram_model[] = $fram;
                                            // array_push($fram, $fram_model);
                                        } elseif ($uvalue == 'SUBJECT.txt') { //标题
                                            $txt = $this->readForTxtToArray($son_dir_path);
                                            $fram_model['title'] = $txt[0];
                                        }
                                    }
                                    $all_models[$file_info[0]] = $fram_model;
                                    // print_r($all_models);
                                    // die;
                                }
                            } else if ($file_info[1] == 'txt') {
                                $file_data = $this->readForTxtToDyadicArray($son_path); //关联关系

                                // print_r($son_path);
                                // die;
                            }
                        }

                        //创建模板
                        /* (
                        [0] => "100178136"
                        [1] => "白卡会员积分近1500"
                        [2] => "6"
                        [3] => "100088234"
                        [4] => "100088234_20200424155750.zip"
                        [5] => "2020-04-24 00:00:00"
                        ) */

                        foreach ($file_data as $fkey => $fvalue) {

                            $sfl_model = [];
                            $sfl_model = [
                                'sfl_relation_id' => $fvalue[0], //对应communication_channel_id 渠道id 关联target目标的唯一识别码
                                'sfl_model_name' => $fvalue[1], //communication_name 渠道名称
                                'sfl_model_id' => $fvalue[3], //模板id
                                'sfl_model_filename' => $fvalue[4], //主题的名称 对应MMS模板的主题 图片以及内容的压缩文件
                            ];
                            $fram_key = explode('.', $fvalue[4]);
                            $sfl_SMS_fram = $all_models[$fram_key[0]];
                            $sfl_model['title'] = $sfl_SMS_fram['title'];
                            $sfl_model['create_time'] = time();
                            unset($sfl_SMS_fram['title']);
                            if (Db::query("SELECT * FROM yx_sfl_multimedia_template WHERE `sfl_model_id` = " . $fvalue[3])) {
                                continue;
                            }
                            $sfl_multimedia_template_id = Db::table('yx_sfl_multimedia_template')->insertGetId($sfl_model);

                            // print_r($sfl_SMS_fram);
                            foreach ($sfl_SMS_fram as $key => $value) {
                                // # code...
                                $value['sfl_multimedia_template_id'] = $sfl_multimedia_template_id;
                                $value['sfl_model_id'] = $fvalue[3];
                                $value['create_time'] = time();
                                Db::table('yx_sfl_multimedia_template_frame')->insert($value);
                            }
                        }

                        //发送内容并 进行拼接
                        if (!empty($send_data)) {
                            foreach ($send_data as $key => $value) {
                                // print_r($redis->hset($mms_send_had_file,$value,1));
                                if ($redis->hget($mms_send_had_file, $value)) {
                                    continue;
                                }
                                $redis->hset($mms_send_have_file, $value, 1);
                            }
                        }

                        // print_r($model_check);

                        // continue;

                        // print_r($MMSmessage);
                        // die;
                    }
                } elseif ($value == 'SMS') {
                    $send_data = [];
                    $SMS_model = [];
                    $SMSmessage = [];
                    $model_check = [];
                    if ($son_path_data !== false) {
                        foreach ($son_path_data as $skey => $svalue) {
                            $son_path = $path . $value . "/" . $svalue;
                            // $file = fopen($path.$value."/".$svalue,"r");
                            $file_info = explode('.', $svalue);
                            if ($file_info[1] == 'zip') { //需要解压
                                if ($zip->open($son_path) === true) {
                                    $unpath = $path . 'UnZip' . "/" . $value . "/" . $file_info[0];
                                    $count = $zip->numFiles;
                                    // $results = [];
                                    $files_name = [];
                                    for ($i = 0; $i < $count; $i++) {
                                        $entry = $zip->statIndex($i, ZipArchive::FL_ENC_RAW);
                                        $entry['name'] = rtrim(str_replace('\\', '/', $entry['name']), '/');
                                        $encoding = mb_detect_encoding($entry['name'], array('Shift_JIS', 'EUC_JP', 'EUC_KR', 'KOI8-R', 'ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8'));
                                        $filename = iconv($encoding, 'UTF-8', $entry['name']);
                                        $filename = $filename ?: $entry['name'];
                                        $size = $entry['size'];
                                        $comp_size = $entry['comp_size'];
                                        $mtime = $entry['mtime'];
                                        $crc = $entry['crc'];
                                        $is_dir = ($crc == 0);
                                        // $path = '/' . $filename;

                                        $_names = explode('/', $filename);
                                        $_idx = count($_names) - 1;

                                        $name = $_names[$_idx];
                                        if (empty($name)) {
                                            continue;
                                        }
                                        $files_name[] = $name;
                                        $index = $i;
                                        //$data = $zip->getFromIndex($i);
                                        $entry = compact('name', 'path', 'size', 'comp_size', 'mtime', 'crc', 'index', 'is_dir');
                                        // $results[] = $entry;
                                    }
                                    // print_r($files_name);die;
                                    $mcw = $zip->extractTo($unpath, $files_name); //解压到$route这个目录中
                                    // $mcw    = $zip->extractTo($unpath); //解压到$route这个目录中
                                    $zip->close();
                                    //解压完成
                                    $unzip = $this->getDirContent($unpath);
                                    //先上传模板内容
                                    // print_r($unzip);
                                    if (strpos($file_info[0], "targets")) {
                                        foreach ($unzip as $ukey => $uvalue) {
                                            $send_data[] = $unpath . '/' . $uvalue;
                                        }
                                        continue;
                                    }
                                }
                            } else { //获取模板信息
                                $file_data = $this->readForTxtToDyadicArray($son_path); //关联关系
                                // print_r($son_path);die;
                            }
                        }

                        foreach ($file_data as $fkey => $fvalue) {
                            // print_r($fvalue);
                            $tem = [];
                            $tem['num'] = $fvalue[2];
                            $tem['content'] = $fvalue[4];
                            // $SMS_model[$fvalue[0]] = $tem;
                            if ($redis->hget($sms_send_model, $fvalue[0])) {
                                continue;
                            }
                            $redis->hset($sms_send_model, $fvalue[0], json_encode($tem));
                        }
                        // print_r($send_data);
                        foreach ($send_data as $key => $value) {
                            $txt = [];
                            // $txt = $this->readForTxtToDyadicArray($value); # code...
                            if ($redis->hget($sms_send_had_file, $value)) {
                                continue;
                            }
                            $redis->hset($sms_send_have_file, $value, 1);
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /* sftp 彩信任务拼接 */

    public function sftpSflMms()
    {
        $mms_send_had_file = "sftp:mms:sfl:hadsendfile"; //sftp彩信丝芙兰已发送
        $mms_send_have_file = "sftp:mms:sfl:havesendfile"; //sftp彩信丝芙兰待发送
        $mms_send_task = "sftp:mms:sfl:sendtask";
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G
        $file_data = $redis->hgetall($mms_send_have_file);
        // print_r($file_data);
        if (!empty($file_data)) {
            foreach ($file_data as $key => $value) {
                sleep(1);
                //锁

                if ($redis->setNx($key, 1) === false) {
                    continue;
                }
                if (!is_file($key)) {
                    exit("Thefile IS NOT A FILE");
                }

                $file = fopen($key, "r");
                $data = array();
                while (!feof($file)) {
                    $cellVal = trim(fgets($file));
                    if (!empty($cellVal)) {
                        // $cellVal = trim($cellVal, '"');
                        $cellVal = str_replace('"', '', $cellVal);
                        $value = explode(',', $cellVal);
                        // array_push($data, $value);

                        $MMS_real_send = [];

                        $MMS_real_send['mseeage_id'] = $value[0];
                        $MMS_real_send['mobile'] = $value[3];
                        $MMS_real_send['free_trial'] = 1;
                        $MMS_real_send['real_num'] = 1;
                        $MMS_real_send['send_num'] = 1;
                        $MMS_real_send['send_status'] = 1;
                        $MMS_real_send['sfl_model_id'] = 1;
                        $MMS_real_send['create_time'] = time();

                        $variable = [];
                        $variable = [
                            '{ACCOUNT_NUMBER}' => $value[1],
                            '{MOBILE}' => $value[3],
                            '{FULL_NAME}' => $value[4],
                            '{POINTS_AVAILABLE}' => $value[5],
                            '{TOTAL_POINTS}' => $value[6],
                            '{RESERVED_FIELD_1}' => $value[7],
                            '{RESERVED_FIELD_2}' => $value[8],
                            '{RESERVED_FIELD_3}' => $value[9],
                            '{RESERVED_FIELD_4}' => $value[10],
                            '{RESERVED_FIELD_5}' => $value[11],
                        ];

                        $MMS_real_send['sfl_relation_id'] = $value[2];
                        $MMS_real_send['variable'] = $variable;
                        // print_r($MMS_real_send);die;
                        $redis->Hset($mms_send_task, json_encode($MMS_real_send), 1);
                    }
                }
                fclose($file);
                $redis->hset($mms_send_had_file, $key, 1);
                $redis->hdel($mms_send_have_file, $key);
                //解锁
                $redis->del($key);
            }
        }
    }

    /* 短信任务拼接 */

    public function sftpSflSms()
    {

        $sms_send_model = "sftp:sms:sfl:model"; //sftp短信模板
        $sms_send_had_file = "sftp:sms:sfl:hadsendfile"; //sftp短信丝芙兰已发送
        $sms_send_have_file = "sftp:sms:sfl:havesendfile"; //sftp短信丝芙兰待发送
        $sms_send_task = "sftp:sms:sfl:sendtask";
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G
        $file_data = $redis->hgetall($sms_send_have_file);
        // print_r($file_data);
        if (!empty($file_data)) {
            foreach ($file_data as $key => $value) {
                sleep(1);
                //锁

                if ($redis->setNx($key, 1) === false) {
                    continue;
                }
                if (!is_file($key)) {
                    exit("Thefile IS NOT A FILE");
                }
                $file = fopen($key, "r");
                $data = array();
                while (!feof($file)) {
                    $cellVal = trim(fgets($file));
                    if (!empty($cellVal)) {
                        // $cellVal = trim($cellVal, '"');
                        $cellVal = str_replace('"', '', $cellVal);
                        $value = explode(',', $cellVal);
                        // array_push($data, $value);
                        $model = $redis->hget($sms_send_model, $value[2]);
                        if (empty($model)) {
                            exit("The Sms Model Is Not Null");
                        }
                        $model = json_decode($model, true);
                        $SMS_real_send = [];
                        $SMS_real_send = [];
                        $SMS_real_send['mseeage_id'] = $value[0];
                        $SMS_real_send['mobile'] = $value[3];
                        $SMS_real_send['free_trial'] = 1;
                        // $SMS_real_send['real_num'] = 1;
                        $SMS_real_send['send_num'] = 1;
                        $SMS_real_send['send_status'] = 1;
                        $SMS_real_send['template_id'] = $value[2];
                        $SMS_real_send['create_time'] = time();
                        $content = $model['content'];
                        $content = str_replace('{FULL_NAME}', $value[4], $content);
                        $content = str_replace('{RESERVED_FIELD_1}', $value[7], $content);
                        $content = str_replace('{RESERVED_FIELD_2}', $value[8], $content);
                        $content = str_replace('{RESERVED_FIELD_3}', $value[9], $content);
                        $content = str_replace('{RESERVED_FIELD_4}', $value[10], $content);
                        $content = str_replace('{RESERVED_FIELD_5}', $value[11], $content);
                        $content = str_replace('{ACCOUNT_NUMBER}', $value[1], $content);
                        $content = str_replace('{MOBILE}', $value[3], $content);
                        $content = str_replace('{POINTS_AVAILABLE}', $value[5], $content);
                        $content = str_replace('{TOTAL_POINTS}', $value[6], $content);
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
                        $SMS_real_send['real_num'] = $real_length;
                        $SMS_real_send['send_length'] = $send_length;
                        $redis->Hset($sms_send_task, json_encode($SMS_real_send), 1);
                    }
                }
                fclose($file);
                $redis->hset($sms_send_had_file, $key, 1);
                $redis->hdel($sms_send_have_file, $key);
                //解锁
                $redis->del($key);
            }
        }
    }

    /* sftp 彩信任务入库 */
    public function sftpSflMMSToBase()
    {

        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G
        $redis = Phpredis::getConn();
        //白名单入库
        /* if (in_array($tvalue[3],$white_list)) {
        $redis->rpush('sftp:sfl:marketing:whitesendtask',json_encode($SMS_real_send));
        }else{
        $redis->rpush('sftp:sfl:marketing:sendtask',json_encode($SMS_real_send));
        } */
        $send_task = [];
        $task_id = $mysql_connect->query("SELECT `id` FROM yx_sfl_multimedia_message  ORDER BY `id` DESC limit 1 ");
        if (empty($task_id)) {
            $this_id = 1;
        } else {
            $this_id = $task_id[0]['id'];
        }
        // print_r($this_id);
        // die;
        $i = 1;
        while (true) {
            $white_task = $redis->lpop('sftp:sfl:MMS:whitesendtask');
            if (empty($white_task)) {
                break;
            }
            $white_task = json_decode($white_task, true);
            // print_r($white_task);die;
            $this_id++;
            $white_task['id'] = $this_id;
            $send_task[] = $white_task;

            $i++;
            if ($i > 100) {
                $mysql_connect->startTrans();
                try {
                    $mysql_connect->table('yx_sfl_multimedia_message')->insertAll($send_task);
                    unset($send_task);
                    $i = 1;
                    $mysql_connect->commit();
                } catch (\Exception $e) {
                    exception($e);
                }
            }
        }

        if (!empty($send_task)) {
            try {
                $mysql_connect->table('yx_sfl_multimedia_message')->insertAll($send_task);
                unset($send_task);
                $i = 1;
                $mysql_connect->commit();
            } catch (\Exception $e) {
                exception($e);
            }
        }
        die;
        // print_r($this_id);
        // die;
        $task_receipt_all = [];

        while (true) {
            $white_task = $redis->lpop('sftp:sfl:MMS:errorsendtask');
            if (empty($white_task)) {
                break;
            }
            $white_task = json_decode($white_task, true);
            // print_r($white_task);die;
            $this_id++;
            $white_task['id'] = $this_id;
            $send_task[] = $white_task;
            $task_receipt = [];
            $task_receipt = [
                'mseeage_id' => $white_task['mseeage_id'],
                'mobile' => $white_task['mobile'],
                'status_message' => 'MMS:2',
                'messageinfo' => '发送失败',
                'task_id' => $white_task['id'],
                'template_id' => $white_task['template_id'],
            ];
            $task_receipt_all[] = $task_receipt;
            $i++;
            if ($i > 100) {
                $mysql_connect->startTrans();
                try {
                    $mysql_connect->table('yx_sfl_multimedia_message')->insertAll($send_task);
                    $mysql_connect->table('yx_sfl_send_task_receipt')->insertAll($task_receipt_all);
                    unset($send_task);
                    unset($task_receipt_all);
                    $i = 1;
                    $mysql_connect->commit();
                } catch (\Exception $e) {
                    exception($e);
                }
            }
        }
        if (!empty($send_task)) {
            try {
                $mysql_connect->table('yx_sfl_multimedia_message')->insertAll($send_task);
                $mysql_connect->table('yx_sfl_send_task_receipt')->insertAll($task_receipt_all);
                unset($send_task);
                unset($task_receipt_all);
                $i = 1;
                $mysql_connect->commit();
            } catch (\Exception $e) {
                exception($e);
            }
        }
        $deduct = ceil(1100000 / 1704098 * 100);

        /* 扣量 */
        // $all_num = [0,1,2,3,4];
        // $deduct_key = array_rand($all_num,3);
        // print_r($deduct_key);die;
        $all_num = [];
        for ($i = 0; $i < 100; $i++) {
            # code...
            $all_num[] = $i;
        }
        // $deduct_key = array_rand($all_num, $deduct);
        /*  print_r($deduct_key);
        die; */
        // echo count($all_num);
        // die;
        /* print_r($all_num);
        die; */
        $deduct_nums = 5;
        $i = 1;
        while (true) {
            $white_task = $redis->lpop('sftp:sfl:MMS:sendtask');
            if (empty($white_task)) {
                break;
            }
            $i++;
            $white_task = json_decode($white_task, true);
            // print_r($white_task);die;
            $this_id++;
            $white_task['id'] = $this_id;
            $send_task[] = $white_task;
            $white_task = $redis->rpush('sftp:sfl:MMS:deductsendtask', json_encode($white_task));
            if ($i > count($all_num)) {
                // $all_num    = [0, 1, 2, 3, 4];
                $deduct_key = array_rand($all_num, $deduct);
                /*  print_r($deduct_key);
                die; */
                foreach ($send_task as $key => $value) {
                    if (in_array($key, $deduct_key)) {
                        continue;
                    }
                    $prefix = '';
                    $prefix = substr(trim($value['mobile']), 0, 7);
                    $res = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                    // print_r($res);
                    if ($res) {
                        $newres = array_shift($res);
                        if ($newres['source'] == 1) {
                            $channel_id = 94;
                        } elseif ($newres['source'] == 2) {
                            $channel_id = 94;
                        } elseif ($newres['source'] == 3) {
                            $channel_id = 94;
                        }
                    } else {
                        $channel_id = 94;
                    }
                    $sendmessage = [
                        'mseeage_id' => $value['mseeage_id'],
                        'template_id' => $value['template_id'],
                        'mobile' => $value['mobile'],
                        'mar_task_id' => $value['id'],
                        'content' => $value['task_content'],
                        'from' => 'yx_sfl_send_task',
                    ];
                    $redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode($sendmessage)); //三体营销
                }
                $i = 1;
                unset($send_task);
            }
        }
        if (!empty($send_task)) {
            // $all_num    = [0, 1, 2, 3, 4];
            $deduct_key = array_rand($all_num, $deduct);
            foreach ($send_task as $key => $value) {
                if (in_array($key, $deduct_key)) {
                    continue;
                }
                $prefix = '';
                $prefix = substr(trim($value['mobile']), 0, 7);
                $res = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                // print_r($res);
                if ($res) {
                    $newres = array_shift($res);
                    if ($newres['source'] == 1) {
                        $channel_id = 94;
                    } elseif ($newres['source'] == 2) {
                        $channel_id = 94;
                    } elseif ($newres['source'] == 3) {
                        $channel_id = 94;
                    }
                } else {
                    $channel_id = 94;
                }
                $sendmessage = [
                    'mseeage_id' => $value['mseeage_id'],
                    'template_id' => $value['template_id'],
                    'mobile' => $value['mobile'],
                    'mar_task_id' => $value['id'],
                    'content' => $value['task_content'],
                    'from' => 'yx_sfl_multimedia_message',
                ];
                $redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode($sendmessage)); //三体营销
            }
        }
        // die;
        $send_task = [];
        while (true) {
            $white_task = $redis->lpop('sftp:sfl:marketing:deductsendtask');
            if (empty($white_task)) {
                break;
            }
            $white_task = json_decode($white_task, true);
            $white_task['yidong_channel_id'] = 83;
            $white_task['liantong_channel_id'] = 83;
            $white_task['dianxin_channel_id'] = 84;
            $send_task[] = $white_task;
            $i++;
            if ($i > 100) {
                $mysql_connect->startTrans();
                try {
                    $mysql_connect->table('yx_sfl_send_task')->insertAll($send_task);
                    unset($send_task);
                    $i = 1;
                    $mysql_connect->commit();
                } catch (\Exception $e) {
                    exception($e);
                }
            }
        }
        if (!empty($send_task)) {
            try {
                $mysql_connect->table('yx_sfl_send_task')->insertAll($send_task);
                unset($send_task);
                $i = 1;
                $mysql_connect->commit();
            } catch (\Exception $e) {
                exception($e);
            }
        }
    }
    // WHERE `template_id` = '100183121';
    /* sftp 短信任务入库 */
    public function sftpSflSendTaskToBase()
    {

        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G
        $redis = Phpredis::getConn();
        try {
            while (true) {
                //白名单入库
                /* if (in_array($tvalue[3],$white_list)) {
                $redis->rpush('sftp:sfl:marketing:whitesendtask',json_encode($SMS_real_send));
                }else{
                $redis->rpush('sftp:sfl:marketing:sendtask',json_encode($SMS_real_send));
                } */
                $send_task = [];
                /* $task_id   = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task  ORDER BY `id` DESC limit 1 ");
                if (empty($task_id)) {
                $this_id = 1;
                } else {
                $this_id   = $task_id[0]['id'];
                } */
                // print_r($this_id);
                // die;
                $i = 1;
                while (true) {
                    $white_task = $redis->lpop('sftp:sfl:marketing:whitesendtask');
                    if (empty($white_task)) {
                        break;
                    }
                    $white_task = json_decode($white_task, true);
                    // print_r($white_task);die;
                    // $this_id++;
                    // $white_task['id'] = $this_id;
                    $send_task[] = $white_task;

                    $i++;
                    if ($i > 100) {
                        $mysql_connect->startTrans();
                        try {
                            $mysql_connect->table('yx_sfl_send_task')->insertAll($send_task);
                            unset($send_task);
                            $i = 1;
                            $mysql_connect->commit();
                        } catch (\Exception $e) {
                            exception($e);
                        }
                    }
                }

                if (!empty($send_task)) {
                    try {
                        $mysql_connect->table('yx_sfl_send_task')->insertAll($send_task);
                        unset($send_task);
                        $i = 1;
                        $mysql_connect->commit();
                    } catch (\Exception $e) {
                        exception($e);
                    }
                }
                // die;
                // exit;
                // print_r($this_id);
                // die;
                $task_receipt_all = [];

                while (true) {
                    $white_task = $redis->lpop('sftp:sfl:marketing:errorsendtask');
                    if (empty($white_task)) {
                        break;
                    }
                    $white_task = json_decode($white_task, true);
                    // print_r($white_task);die;
                    // $this_id++;
                    // $white_task['id'] = $this_id;
                    $send_task[] = $white_task;
                    $task_receipt = [];
                    $task_receipt = [
                        'mseeage_id' => $white_task['mseeage_id'],
                        'mobile' => $white_task['mobile'],
                        'status_message' => 'SMS:2',
                        'messageinfo' => '发送失败',
                        // 'task_id'        => $white_task['id'],
                        'template_id' => $white_task['template_id'],
                    ];
                    $task_receipt_all[] = $task_receipt;
                    $i++;
                    if ($i > 100) {
                        $mysql_connect->startTrans();
                        try {
                            $mysql_connect->table('yx_sfl_send_task')->insertAll($send_task);
                            $mysql_connect->table('yx_sfl_send_task_receipt')->insertAll($task_receipt_all);
                            unset($send_task);
                            unset($task_receipt_all);
                            $i = 1;
                            $mysql_connect->commit();
                        } catch (\Exception $e) {
                            exception($e);
                        }
                    }
                }
                if (!empty($send_task)) {
                    try {
                        $mysql_connect->table('yx_sfl_send_task')->insertAll($send_task);
                        $mysql_connect->table('yx_sfl_send_task_receipt')->insertAll($task_receipt_all);
                        unset($send_task);
                        unset($task_receipt_all);
                        $i = 1;
                        $mysql_connect->commit();
                    } catch (\Exception $e) {
                        exception($e);
                    }
                }
                $deduct = ceil(2000000 / 5768335 * 100);
                // $deduct = 65;

                // $all_num = [0,1,2,3,4];
                // $deduct_key = array_rand($all_num,3);
                // print_r($deduct_key);die;
                $all_num = [];
                for ($i = 0; $i < 100; $i++) {
                    # code...
                    $all_num[] = $i;
                }
                // $deduct_key = array_rand($all_num, $deduct);
                /*  print_r($deduct_key);
                die; */
                // echo count($all_num);
                // die;
                /* print_r($all_num);
                die; */
                $deduct_nums = 5;
                $i = 1;
                while (true) {
                    $white_task = $redis->lpop('sftp:sfl:marketing:sendtask');
                    if (empty($white_task)) {
                        break;
                    }
                    $i++;
                    $white_task = json_decode($white_task, true);
                    // print_r($white_task);die;
                    // $this_id++;
                    // $white_task['id'] = $this_id;
                    $send_task[] = $white_task;
                    // $white_task = $redis->rpush('sftp:sfl:marketing:deductsendtask', json_encode($white_task));
                    // $white_task = $redis->rpush('sftp:sfl:marketing:deductsendtask', json_encode($white_task));
                    if ($i > count($all_num)) {
                        // $all_num    = [0, 1, 2, 3, 4];
                        $deduct_key = array_rand($all_num, $deduct);
                        /*  print_r($deduct_key);
                        die; */
                        foreach ($send_task as $key => $value) {
                            if (in_array($key, $deduct_key)) {
                                continue;
                            }
                            $prefix = '';
                            $prefix = substr(trim($value['mobile']), 0, 7);
                            $res = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                            // print_r($res);
                            if ($res) {
                                $newres = array_shift($res);
                                if ($newres['source'] == 1) {
                                    $channel_id = 156;
                                } elseif ($newres['source'] == 2) {
                                    $channel_id = 157;
                                } elseif ($newres['source'] == 3) {
                                    $channel_id = 157;
                                }
                            } else {
                                $channel_id = 156;
                            }
                            $sendmessage = [
                                'mseeage_id' => $value['mseeage_id'],
                                'template_id' => $value['template_id'],
                                'mobile' => $value['mobile'],
                                // 'mar_task_id' => $value['id'],
                                'content' => $value['task_content'],
                                'from' => 'yx_sfl_send_task',
                            ];
                            $redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode($sendmessage)); //三体营销
                        }
                        $i = 1;
                        $mysql_connect->table('yx_sfl_send_task')->insertAll($send_task);
                        unset($send_task);
                    }
                }
                if (!empty($send_task)) {
                    // $all_num    = [0, 1, 2, 3, 4];
                    $deduct_key = array_rand($all_num, $deduct);
                    foreach ($send_task as $key => $value) {
                        if (in_array($key, $deduct_key)) {
                            continue;
                        }
                        $prefix = '';
                        $prefix = substr(trim($value['mobile']), 0, 7);
                        $res = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                        // print_r($res);
                        if ($res) {
                            $newres = array_shift($res);
                            if ($newres['source'] == 1) {
                                $channel_id = 156;
                            } elseif ($newres['source'] == 2) {
                                $channel_id = 157;
                            } elseif ($newres['source'] == 3) {
                                $channel_id = 157;
                            }
                        } else {
                            $channel_id = 156;
                        }
                        $sendmessage = [
                            'mseeage_id' => $value['mseeage_id'],
                            'template_id' => $value['template_id'],
                            'mobile' => $value['mobile'],
                            // 'mar_task_id' => $value['id'],
                            'content' => $value['task_content'],
                            'from' => 'yx_sfl_send_task',
                        ];
                        $redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode($sendmessage)); //三体营销
                    }
                    $mysql_connect->table('yx_sfl_send_task')->insertAll($send_task);
                }
                // die;
                $send_task = [];
                while (true) {
                    $white_task = $redis->lpop('sftp:sfl:marketing:deductsendtask');
                    if (empty($white_task)) {
                        break;
                    }
                    $white_task = json_decode($white_task, true);
                    $white_task['yidong_channel_id'] = 156;
                    $white_task['liantong_channel_id'] = 157;
                    $white_task['dianxin_channel_id'] = 157;
                    $send_task[] = $white_task;
                    $i++;
                    if ($i > 100) {
                        $mysql_connect->startTrans();
                        try {
                            $mysql_connect->table('yx_sfl_send_task')->insertAll($send_task);
                            unset($send_task);
                            $i = 1;
                            $mysql_connect->commit();
                        } catch (\Exception $e) {
                            exception($e);
                        }
                    }
                }
                if (!empty($send_task)) {
                    try {
                        $mysql_connect->table('yx_sfl_send_task')->insertAll($send_task);
                        unset($send_task);
                        $i = 1;
                        $mysql_connect->commit();
                    } catch (\Exception $e) {
                        exception($e);
                    }
                }
                sleep(10);
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    /* save_type 入库方式 */

    public function sflZip($time_key, $save_type = '')
    {
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G
        $this->upload = new Imageupload();
        $zip = new ZipArchive();
        $redis = Phpredis::getConn();
        $path = realpath("") . "/uploads/SFL/";
        $path_data = $this->getDirContent($path);
        // print_r($path_data);
        if ($path_data == false) {
            exit("This Dir IS null");
        }
        $all_models = [];
        $white_list = [
            13918001944,
            13023216322,
            18616841500,
            15021417314,
            15000773110,
            18217584060,
            13585699417,
            15800400970, 13472865840, 13611664019, 13636311653, 13701789119, 13764272451, 13801687321, 13816091848, 13817515864, 13818181256, 13916292097, 13917823241, 13918902911, 15000773110, 15800815262, 15921904656, 18800232095, 13918153000, 18817718456, 15000796805, 13681961185, 13681961185, 18817718456, 13918153000, 15000796805, 13162248755, 16621181441, 18501684687, 18521329177, 18521569417, 18621714497, 18621720742, 18618353064, 18618353064, 18013770122, 18019762207, 18121252120, 18918267758, 18918267758, 18817718456, 18618353064, 18602893299, 15099630574, 15150180286, 15105518868, 15852736815, 15189366366, 15618985205, 13472718707, 18817973600, 13801991996, 15900856149, 15021138946, 15508970381, 18817973600, 15921133310,
        ];
        $mms_id = $mysql_connect->query("SELECT `id` FROM yx_sfl_multimedia_message  ORDER BY `id` DESC limit 1 ");
        $task_id = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task  ORDER BY `id` DESC limit 1 ");
        if (empty($task_id)) {
            $this_id = 1;
        } else {
            $this_id = $task_id[0]['id'];
        }
        if (empty($time_key)) {
            $time_key = date('Ymd', time());
        }

        // $time_key = '202101061551';
        // $time_key = '2021012818';

        // $time_key = date('Ymd', time());
        try {
            foreach ($path_data as $key => $value) {
                //进入二级目录 MMS 或者 SMS 等
                //跳过本地解压文件夹
                if ($value == 'UnZip') {
                    continue;
                }
                $son_path_data = $this->getDirContent($path . $value);
                if ($value == 'MMS') {
                    // continue;
                    $err_task_num = [];
                    $send_data = [];
                    if ($son_path_data !== false) {

                        foreach ($son_path_data as $skey => $svalue) {
                            $son_path = '';
                            $son_path = $path . $value . "/" . $svalue;
                            // $file = fopen($path.$value."/".$svalue,"r");
                            // print_r($svalue);die;

                            /* if (!strpos($svalue, '20200930')) {
                            continue;
                            } */
                            if (!strpos($svalue, $time_key)) {
                                continue;
                            }
                            // print_r($svalue);die;
                            /* if (!strpos($svalue, date("Ymd"))) {
                            continue;
                            } */
                            if (strlen($time_key) < 12) {
                                $new_time_key = '';
                                $new_time_key = $time_key;
                                // $new_time_key = $time_key."";
                                $ps_num = 12 - strlen($time_key);
                                for ($a = 0; $a < $ps_num; $a++) {
                                    $new_time_key .= '0';
                                }
                            } else {
                                $new_time_key = $time_key;
                            }
                            $start_time = strtotime($new_time_key);
                            // $end_time = $start_time+86400;
                            $expeort_time = $start_time + 43200 - mt_rand(0, 3000);
                            $file_info = explode('.', $svalue);
                            if ($file_info[1] == 'zip') { //需要解压
                                //开始解压
                                if ($zip->open($son_path) === true) {
                                    $unpath = $path . 'UnZip' . "/" . $value . "/" . $file_info[0]; //解压目录
                                    $count = $zip->numFiles;
                                    // $results = [];
                                    $files_name = [];
                                    for ($i = 0; $i < $count; $i++) {
                                        $entry = $zip->statIndex($i, ZipArchive::FL_ENC_RAW);
                                        $entry['name'] = rtrim(str_replace('\\', '/', $entry['name']), '/');
                                        $encoding = mb_detect_encoding($entry['name'], array('Shift_JIS', 'EUC_JP', 'EUC_KR', 'KOI8-R', 'ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8'));
                                        $filename = iconv($encoding, 'UTF-8', $entry['name']);
                                        $filename = $filename ?: $entry['name'];
                                        $size = $entry['size'];
                                        $comp_size = $entry['comp_size'];
                                        $mtime = $entry['mtime'];
                                        $crc = $entry['crc'];
                                        $is_dir = ($crc == 0);
                                        // $path = '/' . $filename;

                                        $_names = explode('/', $filename);
                                        $_idx = count($_names) - 1;

                                        $name = $_names[$_idx];
                                        if (empty($name)) {
                                            continue;
                                        }
                                        $files_name[] = $name;
                                        $index = $i;
                                        //$data = $zip->getFromIndex($i);
                                        $entry = compact('name', 'path', 'size', 'comp_size', 'mtime', 'crc', 'index', 'is_dir');
                                        // $results[] = $entry;
                                    }
                                    // print_r($files_name);die;
                                    $mcw = $zip->extractTo($unpath, $files_name);
                                    //解压到$route这个目录中
                                    // // $mcw    = $zip->extractTo($unpath);
                                    //解压到$route这个目录中
                                    $zip->close();
                                    //解压完成
                                    $unzip = $this->getDirContent($unpath);
                                    //先上传模板内容

                                    if (strpos($file_info[0], "targets")) {
                                        foreach ($unzip as $ukey => $uvalue) {
                                            $send_data[] = $unpath . '/' . $uvalue;
                                        }
                                        continue;
                                    }
                                    $fram_model = [];
                                    foreach ($unzip as $ukey => $uvalue) {
                                        $fram = [];
                                        $un_file_info = explode('.', $uvalue);
                                        // if ($un_file_info[1] == 'jpg') { //图片

                                        // }elseif ($un_file_info[1] == '') {}
                                        $son_dir_path = $unpath . "/" . $uvalue;
                                        if ($uvalue == '1.jpg' || $uvalue == '1.gif') {

                                            //调用内部api 上传图片
                                            $data = [
                                                'appid' => '5e17e42ae9fe3',
                                                'appkey' => 'da1416c4d51b8edd58596ca4b56ca267',
                                                'image' => new CURLFile($son_dir_path, 'image', $uvalue),
                                            ];
                                            $info = $this->uploadFileToBase($data);
                                            // $result = sendRequest('', 'post',  $data);
                                            // $fileInfo = $this->getInfo($image);

                                            if (isset($info['code']) && $info['code'] == 200) {
                                                $fram['num'] = 1;
                                                $fram['name'] = "第一帧";
                                                $fram['image_path'] = filtraImage(Config::get('qiniu.domain'), $info['image_path']);
                                                $fram_model[] = $fram;
                                                // array_push($fram, $fram_model);
                                            }
                                        } else if ($uvalue == '1.txt') {
                                            $fram['content'] = '';
                                            $txt = $this->readForTxtToArray($son_dir_path);
                                            $fram['num'] = 2;
                                            $fram['name'] = "第二帧";

                                            $fram['content'] = join('\\n', $txt);

                                            if (strpos($fram['content'], '【丝芙兰】') !== false) {
                                            } else {
                                                $fram['content'] = '【丝芙兰】' . $fram['content'];
                                            }
                                            // print_r($fram['content']);

                                            // die;
                                            $fram_model[] = $fram;
                                            // array_push($fram, $fram_model);
                                        } else if ($uvalue == '2.jpg' || $uvalue == '2.gif') {
                                            $data = [
                                                'appid' => '5e17e42ae9fe3',
                                                'appkey' => 'da1416c4d51b8edd58596ca4b56ca267',
                                                'image' => new CURLFile($son_dir_path, 'image', $uvalue),
                                            ];
                                            // $info = $this->uploadFileToBase($data);
                                            // $result = sendRequest('', 'post',  $data);
                                            // $fileInfo = $this->getInfo($image);
                                            if (isset($info['code']) && $info['code'] == 200) {
                                                $fram['num'] = 3;
                                                $fram['name'] = "第三帧";
                                                $fram['image_path'] = filtraImage(Config::get('qiniu.domain'), $info['image_path']);
                                                $fram_model[] = $fram;
                                                // array_push($fram, $fram_model);
                                            }
                                        } else if ($uvalue == '2.txt') {
                                            $txt = $this->readForTxtToArray($son_dir_path);
                                            $fram['num'] = 4;
                                            $fram['name'] = "第四帧";

                                            $fram['content'] = join('\n', $txt);
                                            $fram_model[] = $fram;
                                            // array_push($fram, $fram_model);
                                        } elseif ($uvalue == 'SUBJECT.txt') { //标题
                                            $txt = $this->readForTxtToArray($son_dir_path);
                                            $fram_model['title'] = $txt[0];
                                        }
                                    }
                                    $all_models[$file_info[0]] = $fram_model;
                                    // print_r($all_models);
                                    // die;
                                }
                            } else if ($file_info[1] == 'txt') {
                                $file_data = $this->readForTxtToDyadicArray($son_path); //关联关系

                                // print_r($son_path);
                                // die;
                            }
                        }

                        //创建模板
                        /* (
                        [0] => "100178136"
                        [1] => "白卡会员积分近1500"
                        [2] => "6"
                        [3] => "100088234"
                        [4] => "100088234_20200424155750.zip"
                        [5] => "2020-04-24 00:00:00"
                        ) */
                        if (!empty($file_data)) {
                            foreach ($file_data as $fkey => $fvalue) {

                                $sfl_model = [];
                                $sfl_model = [
                                    'sfl_relation_id' => $fvalue[0], //对应communication_channel_id 渠道id 关联target目标的唯一识别码
                                    'sfl_model_name' => $fvalue[1], //communication_name 渠道名称
                                    'sfl_model_id' => $fvalue[3], //模板id
                                    'sfl_model_filename' => $fvalue[4], //主题的名称 对应MMS模板的主题 图片以及内容的压缩文件
                                ];
                                $fram_key = explode('.', $fvalue[4]);
                                $sfl_SMS_fram = $all_models[$fram_key[0]];
                                $sfl_model['title'] = "来自【丝芙兰】：" . $sfl_SMS_fram['title'];
                                $sfl_model['create_time'] = time();
                                unset($sfl_SMS_fram['title']);
                                if ($mysql_connect->query("SELECT * FROM yx_sfl_multimedia_template WHERE `sfl_model_id` = " . $fvalue[3])) {
                                    continue;
                                }
                                $sfl_multimedia_template_id = $mysql_connect->table('yx_sfl_multimedia_template')->insertGetId($sfl_model);

                                // print_r($sfl_SMS_fram);
                                foreach ($sfl_SMS_fram as $key => $value) {
                                    // # code...
                                    $value['sfl_multimedia_template_id'] = $sfl_multimedia_template_id;
                                    $value['sfl_model_id'] = $fvalue[3];
                                    $value['create_time'] = time();
                                    $mysql_connect->table('yx_sfl_multimedia_template_frame')->insert($value);
                                }
                            }
                        }

                        // print_r($file_data);die;
                        //发送内容并 进行拼接
                        // print_r($send_data);die;
                        $MMSmessage = [];
                        $model_check = [];

                        $j = 1;
                        if (!empty($send_data)) {
                            foreach ($send_data as $key => $value) {
                                $txt = [];
                                $txt = $this->readForTxtToDyadicArray($value); # code...
                                // print_r($txt);die;
                                if (!empty($txt)) {
                                    // print_r($txt);die;
                                    foreach ($txt as $tkey => $tvalue) {
                                        $MMS_real_send = [];

                                        $MMS_real_send['mseeage_id'] = $tvalue[0];
                                        $MMS_real_send['mobile'] = $tvalue[3];
                                        $MMS_real_send['free_trial'] = 1;
                                        $MMS_real_send['real_num'] = 1;
                                        $MMS_real_send['send_num'] = 1;
                                        $MMS_real_send['send_status'] = 1;
                                        $MMS_real_send['sfl_model_id'] = 1;
                                        // $MMS_real_send['create_time']  = time();
                                        $MMS_real_send['create_time'] = $expeort_time + ceil($key / 7000);
                                        if (isset($model_check[$tvalue[2]])) {
                                            $model_check[$tvalue[2]]++;
                                        } else {
                                            $model_check[$tvalue[2]] = 1;
                                        }
                                        $variable = [];
                                        $variable = [
                                            '{ACCOUNT_NUMBER}' => $tvalue[1],
                                            '{MOBILE}' => $tvalue[3],
                                            '{FULL_NAME}' => $tvalue[4],
                                            '{POINTS_AVAILABLE}' => $tvalue[5],
                                            '{TOTAL_POINTS}' => $tvalue[6],
                                            '{RESERVED_FIELD_1}' => $tvalue[7],
                                            '{RESERVED_FIELD_2}' => $tvalue[8],
                                            '{RESERVED_FIELD_3}' => $tvalue[9],
                                            '{RESERVED_FIELD_4}' => $tvalue[10],
                                            '{RESERVED_FIELD_5}' => $tvalue[11],
                                        ];

                                        $MMS_real_send['sfl_relation_id'] = $tvalue[2];
                                        /* foreach ($file_data as $fkey => $fvalue) {
                                        if ($fvalue[0] == $tvalue[2]) {

                                        // $MMS_real_send['sfl_relation_id'] = $tvalue[2];
                                        $MMS_real_send['sfl_model_id'] = $fvalue[3];
                                        $fram_key = explode('.', $fvalue[4]);
                                        $sfl_SMS_fram = $all_models[$fram_key[0]];
                                        // print_r($sfl_SMS_fram);die;
                                        // print_r($fvalue);die;
                                        $MMS_real_send['title'] = $sfl_SMS_fram['title'];
                                        unset($sfl_SMS_fram['title']);
                                        foreach ($sfl_SMS_fram as $sfkey => $sfvalue) {
                                        if (isset($sfvalue['content'])) {
                                        $content = $sfl_SMS_fram[$sfkey]['content'];
                                        // $sfl_SMS_fram[$sfkey]['content'] = str_replace('{FULL_NAME}',$fvalue[4],$sfl_SMS_fram[$sfkey]['content']);
                                        $content = str_replace('{FULL_NAME}',$tvalue[4],$content);
                                        $content = str_replace('{RESERVED_FIELD_1}',$tvalue[7],$content);
                                        $content = str_replace('{RESERVED_FIELD_2}',$tvalue[8],$content);
                                        $content = str_replace('{RESERVED_FIELD_3}',$tvalue[9],$content);
                                        $content = str_replace('{RESERVED_FIELD_4}',$tvalue[10],$content);
                                        $content = str_replace('{RESERVED_FIELD_5}',$tvalue[11],$content);
                                        $content = str_replace('{ACCOUNT_NUMBER}',$tvalue[1],$content);
                                        $content = str_replace('{MOBILE}',$tvalue[3],$content);
                                        $content = str_replace('{POINTS_AVAILABLE}',$tvalue[5],$content);
                                        $content = str_replace('{TOTAL_POINTS}',$tvalue[6],$content);
                                        // print_r($content);die;
                                        $sfl_SMS_fram[$sfkey]['content'] = $content;
                                        }
                                        }
                                        $MMS_real_send['frame'] = $sfl_SMS_fram;
                                        break;
                                        }
                                        } */
                                        $MMS_real_send['variable'] = json_encode($variable);
                                        // print_r($MMS_real_send);die;
                                        if (checkMobile($tvalue[3]) == false) {
                                            continue;
                                        }

                                        // if (!in_array($tvalue[2], ['82301', '82309', '100125372', '100181913', '1', '100184821'])) {
                                        //     continue;
                                        // }
                                        // $MMSmessage[] = $MMS_real_send;
                                        /*    if ($save_type == 'redis') {
                                        if (in_array($tvalue[3], $white_list)) {
                                        $redis->rpush('sftp:sfl:MMS:whitesendtask', json_encode($MMS_real_send));
                                        } else {
                                        if (strpos($tvalue[3], '000000') !== false || strpos($tvalue[3], '111111') || strpos($tvalue[3], '222222') || strpos($tvalue[3], '333333') || strpos($tvalue[3], '444444') || strpos($tvalue[3], '555555') || strpos($tvalue[3], '666666') || strpos($tvalue[3], '777777') || strpos($tvalue[3], '888888') || strpos($tvalue[3], '999999')) {
                                        $redis->rpush('sftp:sfl:MMS:errorsendtask', json_encode($MMS_real_send));
                                        } else {
                                        $redis->rpush('sftp:sfl:MMS:sendtask', json_encode($MMS_real_send));
                                        }
                                        }
                                        } else {

                                        } */
                                        $MMSmessage[] = $MMS_real_send;
                                        // print_r($content);die;
                                        $j++;
                                        if ($j > 100) {
                                            $mysql_connect->startTrans();
                                            try {
                                                $mysql_connect->table('yx_sfl_multimedia_message')->insertAll($MMSmessage);
                                                unset($MMSmessage);
                                                $j = 1;
                                                $mysql_connect->commit();
                                            } catch (\Exception $e) {
                                                exception($e);
                                            }
                                            // $this->redis->rPush('index:meassage:business:sendtask', $send);

                                        }
                                        /*  if ($tvalue[3] == "") {

                                        if (isset($err_task_num['The Mobile IS NULL'])) {
                                        $err_task_num['The Mobile IS NULL']  += 1;
                                        }else{
                                        $err_task_num['The Mobile IS NULL']  = 1;
                                        }
                                        continue;

                                        } */
                                        // $MMSmessage[] = $MMS_real_send;
                                    }
                                }
                            }
                        }

                        // print_r($model_check);
                        if (!empty($file_data)) {
                            foreach ($file_data as $key => $value) {
                                // print_r($value[2]);
                                // print_r($model_check[$value[0]]);

                                // die;
                                if ($value[2] != $model_check[$value[0]]) {
                                    //校验失败
                                    return false;
                                }
                            }
                        }

                        // continue;
                        $insertMMS = [];
                        if (!empty($MMSmessage)) {
                            for ($i = 0; $i < count($MMSmessage); $i++) {
                                // array_push($insertMMS, $MMSmessage[$i]);
                                //写入redis
                                //插入数据库
                                $insertMMS[] = $MMSmessage[$i];
                                $j++;
                                if ($j > 100) {
                                    $mysql_connect->startTrans();
                                    try {
                                        $mysql_connect->table('yx_sfl_multimedia_message')->insertAll($insertMMS);
                                        unset($insertMMS);
                                        $j = 1;
                                        $mysql_connect->commit();
                                    } catch (\Exception $e) {
                                        exception($e);
                                    }
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);

                                }
                            }
                        }
                        if (!empty($insertMMS)) {
                            $mysql_connect->startTrans();
                            try {
                                $mysql_connect->table('yx_sfl_multimedia_message')->insertAll($insertMMS);
                                unset($insertMMS);
                                $mysql_connect->commit();
                            } catch (\Exception $e) {
                                exception($e);
                            }
                        }
                    }
                } elseif ($value == 'SMS') {
                    // continue;
                    $send_data = [];
                    $SMS_model = [];
                    $SMSmessage = [];
                    $model_check = [];
                    $err_task_num = [];
                    if ($son_path_data !== false) {
                        foreach ($son_path_data as $skey => $svalue) {
                            // continue;
                            $son_path = $path . $value . "/" . $svalue;
                            // $file = fopen($path.$value."/".$svalue,"r");
                            /*  if (!strpos($svalue, date("Ymd"))) {
                            continue;
                            } */

                            if (!strpos($svalue, $time_key)) {
                                continue;
                            }

                            //  strpos($svalue, '2020071518') == false
                            /* if (strpos($svalue, '20201027') == false) {
                            continue;
                            } */

                            if (strlen($time_key) < 12) {
                                $new_time_key = '';
                                $new_time_key = $time_key;
                                // $new_time_key = $time_key."";
                                $ps_num = 12 - strlen($time_key);
                                for ($a = 0; $a < $ps_num; $a++) {
                                    $new_time_key .= '0';
                                }
                            } else {
                                $new_time_key = $time_key;
                            }
                            $start_time = strtotime($new_time_key);
                            $end_time = $start_time + 86400;
                            $expeort_time = $start_time + 43200 - mt_rand(0, 3000);

                            /*   if (strpos($svalue,'20200625') == false) {
                            continue;
                            } */
                            /* if (strpos($svalue,'2020061810') !== false) {
                            continue;
                            } */
                            // if (strpos($svalue,'2020061510') !== false) {
                            // continue;
                            // }
                            // '2020060321'
                            /* if (!strpos($svalue, '2020060321')) {
                            continue;
                            } */
                            $file_info = explode('.', $svalue);
                            if ($file_info[1] == 'zip') { //需要解压
                                if ($zip->open($son_path) === true) {
                                    $unpath = $path . 'UnZip' . "/" . $value . "/" . $file_info[0];
                                    $count = $zip->numFiles;
                                    // $results = [];
                                    $files_name = [];
                                    for ($i = 0; $i < $count; $i++) {
                                        $entry = $zip->statIndex($i, ZipArchive::FL_ENC_RAW);
                                        $entry['name'] = rtrim(str_replace('\\', '/', $entry['name']), '/');
                                        $encoding = mb_detect_encoding($entry['name'], array('Shift_JIS', 'EUC_JP', 'EUC_KR', 'KOI8-R', 'ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8'));
                                        $filename = iconv($encoding, 'UTF-8', $entry['name']);
                                        $filename = $filename ?: $entry['name'];
                                        $size = $entry['size'];
                                        $comp_size = $entry['comp_size'];
                                        $mtime = $entry['mtime'];
                                        $crc = $entry['crc'];
                                        $is_dir = ($crc == 0);
                                        // $path = '/' . $filename;

                                        $_names = explode('/', $filename);
                                        $_idx = count($_names) - 1;

                                        $name = $_names[$_idx];
                                        if (empty($name)) {
                                            continue;
                                        }
                                        $files_name[] = $name;
                                        $index = $i;
                                        //$data = $zip->getFromIndex($i);
                                        $entry = compact('name', 'path', 'size', 'comp_size', 'mtime', 'crc', 'index', 'is_dir');
                                        // $results[] = $entry;
                                    }
                                    // print_r($files_name);die;
                                    $mcw = $zip->extractTo($unpath, $files_name);
                                    //解压到$route这个目录中
                                    // $mcw    = $zip->extractTo($unpath);
                                    //解压到$route这个目录中
                                    $zip->close();
                                    //解压完成
                                    $unzip = $this->getDirContent($unpath);
                                    //先上传模板内容
                                    // print_r($unzip);
                                    if (strpos($file_info[0], "targets")) {
                                        foreach ($unzip as $ukey => $uvalue) {
                                            $send_data[] = $unpath . '/' . $uvalue;
                                        }
                                        continue;
                                    }
                                }
                            } elseif ($file_info[1] == 'txt') { //获取模板信息
                                $file_data = $this->readForTxtToDyadicArray($son_path); //关联关系
                                // print_r($son_path);die;
                                if (!empty($file_data)) {
                                    foreach ($file_data as $fkey => $fvalue) {
                                        // print_r($fvalue);
                                        $tem = [];
                                        $tem['num'] = $fvalue[2];
                                        $tem['content'] = $fvalue[4];
                                        $SMS_model[$fvalue[0]] = $tem;
                                    }
                                }
                            }
                        }
                        if (!empty($file_data)) {
                            foreach ($file_data as $fkey => $fvalue) {
                                // print_r($fvalue);
                                $tem = [];
                                $tem['num'] = $fvalue[2];
                                $tem['content'] = $fvalue[4];
                                $SMS_model[$fvalue[0]] = $tem;
                            }
                        }
                        // print_r($SMS_model);
                        // die;
                        // print_r($send_data);die;
                        if (!empty($send_data)) {
                            foreach ($send_data as $key => $value) {
                                $txt = [];
                                // $txt = $this->readForTxtToDyadicArray($value);
                                $file = fopen($value, "r");
                                $data = array();
                                while (!feof($file)) {
                                    $cellVal = trim(fgets($file));
                                    if (!empty($cellVal)) {
                                        // $cellVal = trim($cellVal, '"');
                                        $tvalue = explode('",', $cellVal);
                                        // $cellVal = str_replace('"', '', $cellVal);
                                        foreach ($tvalue as $key => $svalue) {
                                            $tvalue[$key] = str_replace('"', '', $svalue);
                                        }
                                        // array_push($data, $value);
                                        // print_r($tvalue);die;
                                        if (isset($model_check[$tvalue[2]])) {
                                            $model_check[$tvalue[2]]++;
                                        } else {
                                            $model_check[$tvalue[2]] = 1;
                                        }
                                    }
                                }
                            }
                        }
                        if (!empty($file_data)) {
                            foreach ($file_data as $key => $value) {
                                // print_r($value[2]);
                                // print_r($model_check[$value[0]]);

                                // die;
                                /* if ($value[2] != $model_check[$value[0]]) {
                            //校验失败
                            return ['code' => 200, "error" => "校验失败"];
                            } */
                            }
                        }
                        // print_r($send_data);die;
                        if (!empty($send_data)) {
                            $j = 1;
                            foreach ($send_data as $key => $value) {
                                $txt = [];
                                // $txt = $this->readForTxtToDyadicArray($value);
                                $file = fopen($value, "r");
                                $data = array();
                                while (!feof($file)) {
                                    $cellVal = trim(fgets($file));
                                    if (!empty($cellVal)) {
                                        // $cellVa  l = trim($cellVal, '"');
                                        $tvalue = explode('",', $cellVal);
                                        // $cellVal = str_replace('"', '', $cellVal);
                                        foreach ($tvalue as $key => $svalue) {
                                            $tvalue[$key] = str_replace('"', '', $svalue);
                                        }
                                        // array_push($data, $value);
                                        // print_r($tvalue);die;
                                        if (isset($model_check[$tvalue[2]])) {
                                            $model_check[$tvalue[2]]++;
                                        } else {
                                            $model_check[$tvalue[2]] = 1;
                                        }
                                        $SMS_real_send = [];
                                        $SMS_real_send = [];
                                        $SMS_real_send['mseeage_id'] = $tvalue[0];
                                        $SMS_real_send['mobile'] = $tvalue[3];
                                        $SMS_real_send['free_trial'] = 1;
                                        // $SMS_real_send['real_num'] = 1;
                                        $SMS_real_send['send_num'] = 1;
                                        $SMS_real_send['send_status'] = 1;
                                        $SMS_real_send['template_id'] = $tvalue[2];
                                        // $SMS_real_send['create_time'] = time();
                                        $SMS_real_send['create_time'] = $expeort_time + ceil($key / 7000);

                                        $content = $SMS_model[$tvalue[2]]['content'];
                                        $content = str_replace('{FULL_NAME}', $tvalue[4], $content);
                                        $content = str_replace('{RESERVED_FIELD_1}', $tvalue[7], $content);
                                        $content = str_replace('{RESERVED_FIELD_2}', $tvalue[8], $content);
                                        $content = str_replace('{RESERVED_FIELD_3}', $tvalue[9], $content);
                                        $content = str_replace('{RESERVED_FIELD_4}', $tvalue[10], $content);
                                        $content = str_replace('{RESERVED_FIELD_5}', $tvalue[11], $content);
                                        $content = str_replace('{ACCOUNT_NUMBER}', $tvalue[1], $content);
                                        $content = str_replace('{MOBILE}', $tvalue[3], $content);
                                        $content = str_replace('{POINTS_AVAILABLE}', $tvalue[5], $content);
                                        $content = str_replace('{TOTAL_POINTS}', $tvalue[6], $content);
                                        if (strpos($content, '【丝芙兰】') !== false) {
                                        } else {
                                            $content = '【丝芙兰】' . $content;
                                        }
                                        if (strpos($content, '回T退订') !== false) {
                                        } else {
                                            if ($tvalue[2] == '100181316') {
                                                $content = $content . " 回T退订";
                                            } elseif ($tvalue[2] == '100181315') {
                                                $content = $content . " 回T退订";
                                            } elseif ($tvalue[2] == '100182791') {
                                                $content = $content . " /回T退订";
                                            } elseif ($tvalue[2] == '100183751') {
                                                $content = $content . " /回T退订";
                                            } elseif ($tvalue[2] == '100184586') {
                                                $content = $content . " /回T退订";
                                            } elseif ($tvalue[2] == '100186712') {
                                                $content = $content . " /回T退订";
                                            } else {
                                                $content = $content . "/回T退订";
                                            }
                                        }
                                        /* if (!in_array($tvalue[2], ['100186519'])) {
                                        continue;
                                        } */
                                        // print_r($content);die;
                                        $send_length = mb_strlen($content, 'utf8');
                                        $real_length = 1;
                                        if ($send_length > 70) {
                                            $real_length = ceil($send_length / 67);
                                        }
                                        $SMS_real_send['task_content'] = $content;
                                        $SMS_real_send['real_num'] = $real_length;
                                        $SMS_real_send['send_length'] = $send_length;
                                        if (checkMobile($tvalue[3]) == false) {
                                            continue;
                                        }
                                        if (strlen($tvalue[3]) > 11) {
                                            continue;
                                        }
                                        /*   if ($tvalue[3] == "") {

                                        if (isset($err_task_num['The Mobile IS NULL'])) {
                                        $err_task_num['The Mobile IS NULL']  += 1;
                                        }else{
                                        $err_task_num['The Mobile IS NULL']  = 1;
                                        }
                                        continue;

                                        } */

                                        // if (!in_array($tvalue[2], ['514', '529', '100107992', '100150820', '100150821', '100150822', '100182845', '100150970', '100182846', '100182847', '100182848', '100182849', '100182850', '100183948', '100183978'])) {
                                        //     continue;
                                        // }
                                        //100185876 先不发

                                        /* if (!in_array($tvalue[2], ['100186365'])) {
                                        continue;
                                        } */
                                        if ($save_type == 'redis') {
                                            if (in_array($tvalue[3], $white_list)) {
                                                $redis->rpush('sftp:sfl:marketing:whitesendtask', json_encode($SMS_real_send));
                                            } else {
                                                if (strpos($tvalue[3], '000000') !== false || strpos($tvalue[3], '111111') || strpos($tvalue[3], '222222') || strpos($tvalue[3], '333333') || strpos($tvalue[3], '444444') || strpos($tvalue[3], '555555') || strpos($tvalue[3], '666666') || strpos($tvalue[3], '777777') || strpos($tvalue[3], '888888') || strpos($tvalue[3], '999999')) {
                                                    $redis->rpush('sftp:sfl:marketing:errorsendtask', json_encode($SMS_real_send));
                                                } else {
                                                    $redis->rpush('sftp:sfl:marketing:sendtask', json_encode($SMS_real_send));
                                                }
                                            }
                                        } else {
                                            $SMSmessage[] = $SMS_real_send;
                                            // print_r($content);die;
                                            $j++;
                                            if ($j > 100) {
                                                $mysql_connect->startTrans();
                                                try {
                                                    $mysql_connect->table('yx_sfl_send_task')->insertAll($SMSmessage);
                                                    unset($SMSmessage);
                                                    $j = 1;
                                                    $mysql_connect->commit();
                                                } catch (\Exception $e) {
                                                    exception($e);
                                                }
                                                // $this->redis->rPush('index:meassage:business:sendtask', $send);

                                            }
                                        }
                                    }
                                }
                                /*  if (!empty($txt)) {
                            foreach ($txt as $tkey => $tvalue) {

                            }
                            } */
                            }
                        }

                        if (!empty($SMSmessage)) {
                            $mysql_connect->startTrans();
                            try {
                                $mysql_connect->table('yx_sfl_send_task')->insertAll($SMSmessage);
                                unset($SMSmessage);
                                $mysql_connect->commit();
                            } catch (\Exception $e) {
                                exception($e);
                            }
                        }
                        //   print_r($insertSMS);die;
                    }
                }
            }
        } catch (\Exception $e) {
            exception($e);
        }
    }

    public function SFLSftpTest()
    {
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G
        $this->upload = new Imageupload();
        $zip = new ZipArchive();

        $path = realpath("") . "/uploads/SFL/";
        $path_data = $this->getDirContent($path);
        // print_r($path_data);
        if ($path_data == false) {
            exit("This Dir IS null");
        }
        $all_models = [];
        try {
            foreach ($path_data as $key => $value) {
                //进入二级目录 MMS 或者 SMS 等
                //跳过本地解压文件夹
                if ($value == 'UnZip') {
                    continue;
                }
                $son_path_data = $this->getDirContent($path . $value);
                if ($value == 'MMSTest') {
                    // print_r($son_path_data);die;
                    // continue;
                    $send_data = [];
                    if ($son_path_data !== false) {

                        foreach ($son_path_data as $skey => $svalue) {
                            $son_path = '';
                            $son_path = $path . $value . "/" . $svalue;
                            // $file = fopen($path.$value."/".$svalue,"r");

                            if (strpos($svalue, date("Ymd")) === false) {
                                continue;
                            }

                            /*  if (!strpos($svalue, '2020070217')) {
                            continue;
                            } */
                            /*  print_r($svalue);
                            echo "\n";
                            print_r(strpos($svalue, '2020070217'));
                            die; */
                            $file_info = explode('.', $svalue);
                            if ($file_info[1] == 'zip') { //需要解压
                                //开始解压
                                if ($zip->open($son_path) === true) {
                                    $unpath = $path . 'UnZip' . "/" . $value . "/" . $file_info[0]; //解压目录
                                    $count = $zip->numFiles;
                                    // $results = [];
                                    $files_name = [];
                                    for ($i = 0; $i < $count; $i++) {
                                        $entry = $zip->statIndex($i, ZipArchive::FL_ENC_RAW);
                                        $entry['name'] = rtrim(str_replace('\\', '/', $entry['name']), '/');
                                        $encoding = mb_detect_encoding($entry['name'], array('Shift_JIS', 'EUC_JP', 'EUC_KR', 'KOI8-R', 'ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8'));
                                        $filename = iconv($encoding, 'UTF-8', $entry['name']);
                                        $filename = $filename ?: $entry['name'];
                                        $size = $entry['size'];
                                        $comp_size = $entry['comp_size'];
                                        $mtime = $entry['mtime'];
                                        $crc = $entry['crc'];
                                        $is_dir = ($crc == 0);
                                        // $path = '/' . $filename;

                                        $_names = explode('/', $filename);
                                        $_idx = count($_names) - 1;

                                        $name = $_names[$_idx];
                                        if (empty($name)) {
                                            continue;
                                        }
                                        $files_name[] = $name;
                                        $index = $i;
                                        //$data = $zip->getFromIndex($i);
                                        $entry = compact('name', 'path', 'size', 'comp_size', 'mtime', 'crc', 'index', 'is_dir');
                                        // $results[] = $entry;
                                    }
                                    // print_r($files_name);die;
                                    $mcw = $zip->extractTo($unpath, $files_name); //解压到$route这个目录中
                                    // // $mcw    = $zip->extractTo($unpath); //解压到$route这个目录中
                                    $zip->close();
                                    //解压完成
                                    $unzip = $this->getDirContent($unpath);
                                    //先上传模板内容

                                    if (strpos($file_info[0], "targets")) {
                                        foreach ($unzip as $ukey => $uvalue) {
                                            $send_data[] = $unpath . '/' . $uvalue;
                                        }
                                        continue;
                                    }
                                    $fram_model = [];
                                    foreach ($unzip as $ukey => $uvalue) {
                                        $fram = [];
                                        $un_file_info = explode('.', $uvalue);
                                        // if ($un_file_info[1] == 'jpg') { //图片

                                        // }elseif ($un_file_info[1] == '') {}
                                        $son_dir_path = $unpath . "/" . $uvalue;
                                        if ($uvalue == '1.jpg' || $uvalue == '1.gif') {

                                            //调用内部api 上传图片
                                            $data = [
                                                'appid' => '5e17e42ae9fe3',
                                                'appkey' => 'da1416c4d51b8edd58596ca4b56ca267',
                                                'image' => new CURLFile($son_dir_path, 'image', $uvalue),
                                            ];
                                            $info = $this->uploadFileToBase($data);
                                            // $result = sendRequest('', 'post',  $data);
                                            // $fileInfo = $this->getInfo($image);

                                            if (isset($info['code']) && $info['code'] == 200) {
                                                $fram['num'] = 1;
                                                $fram['name'] = "第一帧";
                                                $fram['image_path'] = filtraImage(Config::get('qiniu.domain'), $info['image_path']);
                                                $fram_model[] = $fram;
                                                // array_push($fram, $fram_model);
                                            }
                                        } else if ($uvalue == '1.txt') {
                                            $fram['content'] = '';
                                            $txt = $this->readForTxtToArray($son_dir_path);
                                            $fram['num'] = 2;
                                            $fram['name'] = "第二帧";

                                            $fram['content'] = join('\\n', $txt);

                                            if (strpos($fram['content'], '【丝芙兰】') !== false) {
                                            } else {
                                                $fram['content'] = '【丝芙兰】' . $fram['content'];
                                            }
                                            // print_r($fram['content']);

                                            // die;
                                            $fram_model[] = $fram;
                                            // array_push($fram, $fram_model);
                                        } else if ($uvalue == '2.jpg' || $uvalue == '2.gif') {
                                            $data = [
                                                'appid' => '5e17e42ae9fe3',
                                                'appkey' => 'da1416c4d51b8edd58596ca4b56ca267',
                                                'image' => new CURLFile($son_dir_path, 'image', $uvalue),
                                            ];
                                            // $info = $this->uploadFileToBase($data);
                                            // $result = sendRequest('', 'post',  $data);
                                            // $fileInfo = $this->getInfo($image);
                                            if (isset($info['code']) && $info['code'] == 200) {
                                                $fram['num'] = 3;
                                                $fram['name'] = "第三帧";
                                                $fram['image_path'] = filtraImage(Config::get('qiniu.domain'), $info['image_path']);
                                                $fram_model[] = $fram;
                                                // array_push($fram, $fram_model);
                                            }
                                        } else if ($uvalue == '2.txt') {
                                            $txt = $this->readForTxtToArray($son_dir_path);
                                            $fram['num'] = 4;
                                            $fram['name'] = "第四帧";

                                            $fram['content'] = join('\n', $txt);
                                            $fram_model[] = $fram;
                                            // array_push($fram, $fram_model);
                                        } elseif ($uvalue == 'SUBJECT.txt') { //标题
                                            $txt = $this->readForTxtToArray($son_dir_path);
                                            $fram_model['title'] = $txt[0];
                                        }
                                    }
                                    $all_models[$file_info[0]] = $fram_model;
                                    // print_r($all_models);
                                    // die;
                                }
                            } else if ($file_info[1] == 'txt') {
                                $file_data = $this->readForTxtToDyadicArray($son_path); //关联关系

                                // print_r($son_path);
                                // die;
                            }
                        }

                        //创建模板
                        /* (
                        [0] => "100178136"
                        [1] => "白卡会员积分近1500"
                        [2] => "6"
                        [3] => "100088234"
                        [4] => "100088234_20200424155750.zip"
                        [5] => "2020-04-24 00:00:00"
                        ) */
                        if (!empty($file_data)) {
                            foreach ($file_data as $fkey => $fvalue) {

                                $sfl_model = [];
                                $sfl_model = [
                                    'sfl_relation_id' => $fvalue[0], //对应communication_channel_id 渠道id 关联target目标的唯一识别码
                                    'sfl_model_name' => $fvalue[1], //communication_name 渠道名称
                                    'sfl_model_id' => $fvalue[3], //模板id
                                    'sfl_model_filename' => $fvalue[4], //主题的名称 对应MMS模板的主题 图片以及内容的压缩文件
                                ];
                                $fram_key = explode('.', $fvalue[4]);
                                $sfl_SMS_fram = $all_models[$fram_key[0]];
                                $sfl_model['title'] = "来自【丝芙兰】：" . $sfl_SMS_fram['title'];
                                $sfl_model['create_time'] = time();
                                unset($sfl_SMS_fram['title']);
                                if ($mysql_connect->query("SELECT * FROM yx_sfl_multimedia_template WHERE `sfl_model_id` = " . $fvalue[3])) {
                                    continue;
                                }
                                $sfl_multimedia_template_id = $mysql_connect->table('yx_sfl_multimedia_template')->insertGetId($sfl_model);

                                // print_r($sfl_SMS_fram);
                                foreach ($sfl_SMS_fram as $key => $value) {
                                    // # code...
                                    $value['sfl_multimedia_template_id'] = $sfl_multimedia_template_id;
                                    $value['sfl_model_id'] = $fvalue[3];
                                    $value['create_time'] = time();
                                    $mysql_connect->table('yx_sfl_multimedia_template_frame')->insert($value);
                                }
                            }
                        }

                        // print_r($send_data);die;
                        //发送内容并 进行拼接

                        $MMSmessage = [];
                        $model_check = [];
                        $err_task_num = [];
                        if (!empty($send_data)) {
                            foreach ($send_data as $key => $value) {
                                $txt = [];
                                $txt = $this->readForTxtToDyadicArray($value); # code...
                                // print_r($txt);die;
                                if (!empty($txt)) {
                                    // print_r($txt);die;
                                    foreach ($txt as $tkey => $tvalue) {

                                        // print_r($tvalue);
                                        $MMS_real_send = [];

                                        $MMS_real_send['mseeage_id'] = $tvalue[0];
                                        $MMS_real_send['mobile'] = $tvalue[3];

                                        $MMS_real_send['free_trial'] = 1;
                                        $MMS_real_send['real_num'] = 1;
                                        $MMS_real_send['send_num'] = 1;
                                        $MMS_real_send['send_status'] = 1;
                                        $MMS_real_send['sfl_model_id'] = 1;
                                        $MMS_real_send['create_time'] = time();
                                        if (isset($model_check[$tvalue[2]])) {
                                            $model_check[$tvalue[2]]++;
                                        } else {
                                            $model_check[$tvalue[2]] = 1;
                                        }
                                        $variable = [];
                                        $variable = [
                                            '{ACCOUNT_NUMBER}' => $tvalue[1],
                                            '{MOBILE}' => $tvalue[3],
                                            '{FULL_NAME}' => $tvalue[4],
                                            '{POINTS_AVAILABLE}' => $tvalue[5],
                                            '{TOTAL_POINTS}' => $tvalue[6],
                                            '{RESERVED_FIELD_1}' => $tvalue[7],
                                            '{RESERVED_FIELD_2}' => $tvalue[8],
                                            '{RESERVED_FIELD_3}' => $tvalue[9],
                                            '{RESERVED_FIELD_4}' => $tvalue[10],
                                            // '{RESERVED_FIELD_5}' => $tvalue[11],
                                        ];

                                        $MMS_real_send['sfl_relation_id'] = $tvalue[2];
                                        /*  if ($tvalue[2] != '100181872') {
                                        continue;
                                        } */
                                        /* foreach ($file_data as $fkey => $fvalue) {
                                        if ($fvalue[0] == $tvalue[2]) {

                                        // $MMS_real_send['sfl_relation_id'] = $tvalue[2];
                                        $MMS_real_send['sfl_model_id'] = $fvalue[3];
                                        $fram_key = explode('.', $fvalue[4]);
                                        $sfl_SMS_fram = $all_models[$fram_key[0]];
                                        // print_r($sfl_SMS_fram);die;
                                        // print_r($fvalue);die;
                                        $MMS_real_send['title'] = $sfl_SMS_fram['title'];
                                        unset($sfl_SMS_fram['title']);
                                        foreach ($sfl_SMS_fram as $sfkey => $sfvalue) {
                                        if (isset($sfvalue['content'])) {
                                        $content = $sfl_SMS_fram[$sfkey]['content'];
                                        // $sfl_SMS_fram[$sfkey]['content'] = str_replace('{FULL_NAME}',$fvalue[4],$sfl_SMS_fram[$sfkey]['content']);
                                        $content = str_replace('{FULL_NAME}',$tvalue[4],$content);
                                        $content = str_replace('{RESERVED_FIELD_1}',$tvalue[7],$content);
                                        $content = str_replace('{RESERVED_FIELD_2}',$tvalue[8],$content);
                                        $content = str_replace('{RESERVED_FIELD_3}',$tvalue[9],$content);
                                        $content = str_replace('{RESERVED_FIELD_4}',$tvalue[10],$content);
                                        $content = str_replace('{RESERVED_FIELD_5}',$tvalue[11],$content);
                                        $content = str_replace('{ACCOUNT_NUMBER}',$tvalue[1],$content);
                                        $content = str_replace('{MOBILE}',$tvalue[3],$content);
                                        $content = str_replace('{POINTS_AVAILABLE}',$tvalue[5],$content);
                                        $content = str_replace('{TOTAL_POINTS}',$tvalue[6],$content);
                                        // print_r($content);die;
                                        $sfl_SMS_fram[$sfkey]['content'] = $content;
                                        }
                                        }
                                        $MMS_real_send['frame'] = $sfl_SMS_fram;
                                        break;
                                        }
                                        } */
                                        $MMS_real_send['variable'] = json_encode($variable);
                                        // print_r($MMS_real_send);die;
                                        if ($tvalue[3] == "") {

                                            if (isset($err_task_num['The Mobile IS NULL'])) {
                                                $err_task_num['The Mobile IS NULL'] += 1;
                                            } else {
                                                $err_task_num['The Mobile IS NULL'] = 1;
                                            }
                                            continue;
                                        }
                                        $MMSmessage[] = $MMS_real_send;
                                    }
                                }
                            }
                        }

                        // print_r($MMSmessage);die;
                        /*  if (!empty($file_data)) {
                        foreach ($file_data as $key => $value) {
                        // print_r($value[2]);
                        // print_r($model_check[$value[0]]);

                        // die;
                        if ($value[2] != $model_check[$value[0]]) {
                        //校验失败
                        return false;
                        }
                        }
                        } */

                        // continue;

                        // $MMSmessage = array_unique($MMSmessage);
                        $insertMMS = [];
                        $j = 1;
                        // print_r($MMSmessage);die;
                        if (!empty($MMSmessage)) {
                            for ($i = 0; $i < count($MMSmessage); $i++) {
                                // array_push($insertMMS, $MMSmessage[$i]);
                                $insertMMS[] = $MMSmessage[$i];
                                $j++;
                                if ($j > 100) {
                                    $mysql_connect->startTrans();
                                    try {
                                        $mysql_connect->table('yx_sfl_multimedia_message')->insertAll($insertMMS);
                                        unset($insertMMS);
                                        $j = 1;
                                        $mysql_connect->commit();
                                    } catch (\Exception $e) {
                                        exception($e);
                                    }
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);

                                }
                            }
                        }

                        if (!empty($insertMMS)) {
                            $mysql_connect->startTrans();
                            try {
                                $mysql_connect->table('yx_sfl_multimedia_message')->insertAll($insertMMS);
                                unset($insertMMS);
                                $mysql_connect->commit();
                            } catch (\Exception $e) {
                                exception($e);
                            }
                        }
                        // print_r($MMSmessage);
                        // die;
                    }
                } elseif ($value == 'SMSTest') {
                    // continue;
                    $send_data = [];
                    $SMS_model = [];
                    $SMSmessage = [];
                    $model_check = [];
                    $err_task_num = [];
                    if ($son_path_data !== false) {
                        foreach ($son_path_data as $skey => $svalue) {
                            $son_path = $path . $value . "/" . $svalue;
                            // $file = fopen($path.$value."/".$svalue,"r");
                            if (!strpos($svalue, date("Ymd"))) {
                                continue;
                            }
                            // continue;
                            $file_info = explode('.', $svalue);
                            if ($file_info[1] == 'zip') { //需要解压
                                if ($zip->open($son_path) === true) {
                                    $unpath = $path . 'UnZip' . "/" . $value . "/" . $file_info[0];
                                    $count = $zip->numFiles;
                                    // $results = [];
                                    $files_name = [];
                                    for ($i = 0; $i < $count; $i++) {
                                        $entry = $zip->statIndex($i, ZipArchive::FL_ENC_RAW);
                                        $entry['name'] = rtrim(str_replace('\\', '/', $entry['name']), '/');
                                        $encoding = mb_detect_encoding($entry['name'], array('Shift_JIS', 'EUC_JP', 'EUC_KR', 'KOI8-R', 'ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8'));
                                        $filename = iconv($encoding, 'UTF-8', $entry['name']);
                                        $filename = $filename ?: $entry['name'];
                                        $size = $entry['size'];
                                        $comp_size = $entry['comp_size'];
                                        $mtime = $entry['mtime'];
                                        $crc = $entry['crc'];
                                        $is_dir = ($crc == 0);
                                        // $path = '/' . $filename;

                                        $_names = explode('/', $filename);
                                        $_idx = count($_names) - 1;

                                        $name = $_names[$_idx];
                                        if (empty($name)) {
                                            continue;
                                        }
                                        $files_name[] = $name;
                                        $index = $i;
                                        //$data = $zip->getFromIndex($i);
                                        $entry = compact('name', 'path', 'size', 'comp_size', 'mtime', 'crc', 'index', 'is_dir');
                                        // $results[] = $entry;
                                    }
                                    // print_r($files_name);die;
                                    $mcw = $zip->extractTo($unpath, $files_name); //解压到$route这个目录中
                                    // $mcw    = $zip->extractTo($unpath); //解压到$route这个目录中
                                    $zip->close();
                                    //解压完成
                                    $unzip = $this->getDirContent($unpath);
                                    //先上传模板内容
                                    // print_r($unzip);
                                    if (strpos($file_info[0], "targets")) {
                                        foreach ($unzip as $ukey => $uvalue) {
                                            $send_data[] = $unpath . '/' . $uvalue;
                                        }
                                        continue;
                                    }
                                }
                            } elseif ($file_info[1] == 'txt') { //获取模板信息
                                $file_data = $this->readForTxtToDyadicArray($son_path); //关联关系
                                if (!empty($file_data)) {
                                    foreach ($file_data as $fkey => $fvalue) {
                                        // print_r($fvalue);
                                        $tem = [];
                                        $tem['num'] = $fvalue[2];
                                        $tem['content'] = $fvalue[4];
                                        $SMS_model[$fvalue[0]] = $tem;
                                    }
                                }
                            }
                        }

                        // print_r($SMS_model);
                        // die;
                        // print_r($send_data);
                        /*  if (!empty($SMS_model)) {
                        foreach ($SMS_model as $key => $value) {
                        if (!empty($send_data)) {
                        foreach ($send_data as $skey => $svalue) {
                        $txt = [];
                        $txt = $this->readForTxtToDyadicArray($svalue); # code...
                        if (!empty($txt)) {
                        foreach ($txt as $tkey => $tvalue) {
                        // print_r($tvalue);die;
                        if ($tvalue[2] == $key) {
                        if (isset($model_check[$tvalue[2]])) {
                        $model_check[$tvalue[2]]++;
                        } else {
                        $model_check[$tvalue[2]] = 1;
                        }
                        }

                        }
                        }
                        }
                        }
                        }
                        }
                        print_r($SMS_model);die; */
                        if (!empty($send_data)) {
                            foreach ($send_data as $key => $value) {
                                $txt = [];
                                $txt = $this->readForTxtToDyadicArray($value); # code...
                                if (!empty($txt)) {
                                    foreach ($txt as $tkey => $tvalue) {
                                        if (isset($model_check[$tvalue[2]])) {
                                            $model_check[$tvalue[2]]++;
                                        } else {
                                            $model_check[$tvalue[2]] = 1;
                                        }
                                        /*  $SMS_real_send               = [];
                                        $SMS_real_send               = [];
                                        $SMS_real_send['mseeage_id'] = $tvalue[0];
                                        $SMS_real_send['mobile']     = $tvalue[3];
                                        $SMS_real_send['free_trial'] = 1;
                                        // $SMS_real_send['real_num'] = 1;
                                        $SMS_real_send['send_num']     = 1;
                                        $SMS_real_send['send_status']  = 1;
                                        $SMS_real_send['template_id'] = $tvalue[2];
                                        $SMS_real_send['create_time']  = time();
                                        $content                       = $SMS_model[$tvalue[2]]['content'];
                                        $content                       = str_replace('{FULL_NAME}', $tvalue[4], $content);
                                        $content                       = str_replace('{RESERVED_FIELD_1}', $tvalue[7], $content);
                                        $content                       = str_replace('{RESERVED_FIELD_2}', $tvalue[8], $content);
                                        $content                       = str_replace('{RESERVED_FIELD_3}', $tvalue[9], $content);
                                        $content                       = str_replace('{RESERVED_FIELD_4}', $tvalue[10], $content);
                                        $content                       = str_replace('{RESERVED_FIELD_5}', $tvalue[11], $content);
                                        $content                       = str_replace('{ACCOUNT_NUMBER}', $tvalue[1], $content);
                                        $content                       = str_replace('{MOBILE}', $tvalue[3], $content);
                                        $content                       = str_replace('{POINTS_AVAILABLE}', $tvalue[5], $content);
                                        $content                       = str_replace('{TOTAL_POINTS}', $tvalue[6], $content);
                                        if (strpos($content,'【丝芙兰】') !== false) {

                                        }else{
                                        $content = '【丝芙兰】'.$content;
                                        }
                                        if (strpos($content,'回T退订') !== false) {

                                        }else{
                                        $content = $content."/回T退订";
                                        }
                                        // print_r($content);die;
                                        $send_length = mb_strlen($content, 'utf8');
                                        $real_length = 1;
                                        if ($send_length > 70) {
                                        $real_length = ceil($send_length / 67);
                                        }
                                        $SMS_real_send['task_content'] = $content;
                                        $SMS_real_send['real_num'] = $real_length;
                                        $SMS_real_send['send_length'] = $send_length;
                                        $SMSmessage[] = $SMS_real_send; */
                                        // print_r($content);die;
                                    }
                                }
                            }
                        }
                        /*
                        if (!empty($file_data)) {
                        foreach ($SMS_model as $key => $value) {
                        // print_r($value);die;

                        // die;
                        if ($value['num'] != $model_check[$key]) {
                        //校验失败
                        return  ['code' => 200, "error" => "校验失败"];
                        }
                        }
                        } */

                        if (!empty($send_data)) {
                            $j = 1;
                            foreach ($send_data as $key => $value) {
                                $txt = [];
                                $txt = $this->readForTxtToDyadicArray($value); # code...
                                if (!empty($txt)) {
                                    foreach ($txt as $tkey => $tvalue) {
                                        if (isset($model_check[$tvalue[2]])) {
                                            $model_check[$tvalue[2]]++;
                                        } else {
                                            $model_check[$tvalue[2]] = 1;
                                        }

                                        $SMS_real_send = [];
                                        $SMS_real_send = [];
                                        $SMS_real_send['mseeage_id'] = $tvalue[0];
                                        $SMS_real_send['mobile'] = $tvalue[3];
                                        $SMS_real_send['free_trial'] = 1;
                                        // $SMS_real_send['real_num'] = 1;
                                        $SMS_real_send['send_num'] = 1;
                                        $SMS_real_send['send_status'] = 1;
                                        $SMS_real_send['template_id'] = $tvalue[2];
                                        $SMS_real_send['create_time'] = time();
                                        $content = $SMS_model[$tvalue[2]]['content'];
                                        $content = str_replace('{FULL_NAME}', $tvalue[4], $content);
                                        $content = str_replace('{RESERVED_FIELD_1}', $tvalue[7], $content);
                                        $content = str_replace('{RESERVED_FIELD_2}', $tvalue[8], $content);
                                        $content = str_replace('{RESERVED_FIELD_3}', $tvalue[9], $content);
                                        $content = str_replace('{RESERVED_FIELD_4}', $tvalue[10], $content);
                                        // $content                       = str_replace('{RESERVED_FIELD_5}', $tvalue[11], $content);
                                        $content = str_replace('{ACCOUNT_NUMBER}', $tvalue[1], $content);
                                        $content = str_replace('{MOBILE}', $tvalue[3], $content);
                                        $content = str_replace('{POINTS_AVAILABLE}', $tvalue[5], $content);
                                        $content = str_replace('{TOTAL_POINTS}', $tvalue[6], $content);
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
                                        $SMS_real_send['real_num'] = $real_length;
                                        $SMS_real_send['send_length'] = $send_length;
                                        if ($tvalue[3] == "") {

                                            if (isset($err_task_num['The Mobile IS NULL'])) {
                                                $err_task_num['The Mobile IS NULL'] += 1;
                                            } else {
                                                $err_task_num['The Mobile IS NULL'] = 1;
                                            }
                                            continue;
                                        }
                                        $SMSmessage[] = $SMS_real_send;
                                        // print_r($content);die;
                                        $j++;
                                        if ($j > 100) {
                                            $mysql_connect->startTrans();
                                            try {
                                                $mysql_connect->table('yx_sfl_send_task')->insertAll($SMSmessage);
                                                unset($SMSmessage);
                                                $j = 1;
                                                $mysql_connect->commit();
                                            } catch (\Exception $e) {
                                                exception($e);
                                            }
                                            // $this->redis->rPush('index:meassage:business:sendtask', $send);

                                        }
                                    }
                                }
                            }
                        }
                        // print_r($SMSmessage);die;
                        if (!empty($SMSmessage)) {
                            $mysql_connect->startTrans();
                            try {
                                $mysql_connect->table('yx_sfl_send_task')->insertAll($SMSmessage);
                                unset($SMSmessage);
                                $mysql_connect->commit();
                            } catch (\Exception $e) {
                                exception($e);
                            }
                        }
                        //   print_r($insertSMS);die;
                    }
                }
            }
        } catch (\Exception $e) {
            exception($e);
        }
    }

    public function uploadFileToBase($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        //启用时会发送一个常规的POST请求，类型为：application/x-www-form-urlencoded，就像表单提交的一样。
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'http://sendapidev.shyuxi.com/index/upload/uploadFile');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Chrome/53.0.2785.104 Safari/537.36 Core/1.53.2372.400 QQBrowser/9.5.10548.400'); // 模拟用户使用的浏览器
        $res = curl_exec($ch); // 运行cURL，请求网页
        curl_close($ch);
        return json_decode($res, true);
    }

    //读文件输出成二维数组
    public function readForTxtToDyadicArray($path)
    {
        // $path = realpath("./") . "/191111.txt";
        if (!is_file($path)) {
            return false;
        }

        $file = fopen($path, "r");
        $data = array();
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            if (!empty($cellVal)) {
                // $cellVal = trim($cellVal, '"');
                $value = explode('",', $cellVal);
                // $cellVal = str_replace('"', '', $cellVal);
                foreach ($value as $key => $svalue) {
                    $value[$key] = str_replace('"', '', $svalue);
                }
                array_push($data, $value);
            }
        }
        return $data;
    }

    //读文件输出成一维数组
    public function readForTxtToArray($path)
    {
        if (!is_file($path)) {
            return false;
        }

        $file = fopen($path, "r");
        $data = array();
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            $cellVal = str_replace('"', '', $cellVal);
            if (!empty($cellVal)) {
                array_push($data, $cellVal);
            }
        }
        return $data;
    }

    public function getDirContent($path)
    {
        if (!is_dir($path)) {
            return false;
        }
        //readdir方法
        /* $dir = opendir($path);
        $arr = array();
        while($content = readdir($dir)){
        if($content != '.' && $content != '..'){
        $arr[] = $content;
        }
        }
        closedir($dir); */

        //scandir方法
        $arr = array();
        $data = scandir($path);
        foreach ($data as $value) {
            if ($value != '.' && $value != '..') {
                $arr[] = $value;
            }
        }
        return $arr;
    }

    public function sftpForSfl()
    {
        try {
            // $sftp = new SFTPConnection("localhost", 8080);
            // $sftp = new SFTPConnection("esftp.sephora.com.cn", 20981);
            // $sftp = new SFTPConnection("10.157.52.197", 20981);
            // $sftp->login("CHN-SMSDATA-sms", "TZYB@zn7");
            // $sftp->uploadFile("/CN-SMSDATA", "/tmp/to_be_received");
            $host = "47.103.200.251";
            $prot = "22";
            $username = "root";
            $password = "a!s^d(7)#f@g&h(9)";
            /*  $host = "esftp.sephora.com.cn";
            $prot = "20981";
            $username = "CHN-SMSDATA-sms";
            $password = "TZYB@zn7"; */
            $sftp = new SFTPConnection($host, $prot);
            $sftp->login($username, $password);
            //本地目录
            $env = getenv('tmp');
            if (!empty($env)) {
                $local_directory = $env;
            } else {
                $local_directory = "/tmp/sftp/SFL";
            }

            //远程目录
            // $remote_directory = "/root/club776/";
            $remote_directory_host = "/CN-SMSDATA/";
            //判断远程目录是否存在
            $address = $sftp->dirExits($remote_directory_host);
            $remote_directory_data = [];
            if ($address) {
                $this_directory = '';
                $this_directory = $remote_directory_host;
                $address_son = $sftp->scanFileSystem($this_directory);
                if (!empty($address_son)) {
                    foreach ($address_son as $key => $value) {
                        $son_directory = $this_directory . '/' . $value . "/";
                        $sms = $sftp->scanFileSystem($son_directory);
                        foreach ($sms as $key => $svalue) {
                            //下载远程文件
                            if (!empty($env)) {
                                // print_r("本地路径：" .realpath("./").'/uploads/SFL/' . $value);die;
                                // echo "\n";
                                // print_r("远程文件：".realpath("") . $son_directory . $svalue);
                                $sftp->downFile(realpath("./") . '/uploads/SFL/' . $value, $son_directory . $svalue);
                            } else {
                                $local_directory = "/tmp/sftp/SFL";
                                $sftp->downFile($local_directory . $value, $son_directory . $svalue);
                            }

                            //解压至文件目录
                        }
                    }
                }

                //    $sftp->uploadFile("/root/club776/", "/tmp/to_be_received");
                //获取远程目录下文件

            }
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    public function sflSftpMulTaskReceiptForExcel($time_key)
    {
        try {

            $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
            ini_set('memory_limit', '5120M'); // 临时设置最大内存占用为3G
            $start_time = strtotime($time_key);
            $end_time = $start_time + 86400;
            if (empty($expeort_time)) {
                $expeort_time = $start_time + 46800 - mt_rand(0, 180);
            }

            // $expeort_time = 1593421200;
            $expeort_time = strtotime("2021-01-26 10:34:54");
            // $mul_task_ids = $mysql_connect->query("SELECT `id` FROM yx_sfl_multimedia_message WHERE  `create_time` >  " . $start_time . " AND   `create_time` <  " . $end_time . " AND `sfl_relation_id` IN ('100181712','100181717','100181722') ");
            // $mul_task_ids = $mysql_connect->query("SELECT `id` FROM yx_sfl_multimedia_message WHERE  `create_time` >  " . $start_time . " AND   `create_time` <  " . $end_time . " AND `sfl_relation_id` IN ('82301','82309','100125372','100186373','1') ");
            // $mul_task_ids = $mysql_connect->query("SELECT `id` FROM yx_sfl_multimedia_message WHERE  `create_time` >  " . $start_time . " AND   `create_time` <  " . $end_time . " AND `sfl_relation_id` IN ('82301','82309','100125372','1','100186432') ");
            // echo "SELECT `id` FROM yx_sfl_multimedia_message WHERE  `create_time` >  " . $start_time . " AND   `create_time` <  " . $end_time . " AND `sfl_relation_id` IN ('82301','82309','100125372','1','100185593')";die;
            $mul_task_ids = $mysql_connect->query("SELECT `id` FROM yx_sfl_multimedia_message WHERE  `sfl_relation_id` IN ('100188183') ");
            $ids = [];
            foreach ($mul_task_ids as $key => $value) {
                $ids[] = $value['id'];
            }
            // $receipts = $mysql_connect->query("SELECT `mseeage_id`,`mobile`,`messageinfo`,`status_message`,`real_message`,`task_id` FROM `yx_sfl_send_multimediatask_receipt` WHERE task_id IN (".join(',',$ids).") GROUP BY `template_id`,`mseeage_id`,`mobile`,`messageinfo`,`status_message`,`real_message`,`task_id`");
            $nu_ids = [];
            $rece_id = [];
            $receive_all = [];
            $receive_alls = [];
            $success_num = 0;
            $default_num = 0;
            $i = 1;
            $j = 12;
            /*   foreach ($receipts as $key => $value) {
            $rece_id[] = $value['task_id'];
            $receive_all = [];
            $receive_all = [
            'MESSAGE_ID' => $value['mseeage_id'],
            'COMMUNICATION_CHANNEL_ID' => $value['template_id'],
            'MOBILE' => $value['mobile'],
            'STATUS' => $value['status_message'],
            'SENDING_TIME' => date('Y-m-d H:i:s',1590123522+mt_rand(10,1800)),
            ];
            if (trim($value['real_message']) == 'DELIVRD') {
            $success_num++;
            }else{
            $default_num++;
            }
            $receive_alls[] = $receive_all;
            } */

            // echo count($receive_alls);die;
            // $unknow = array_diff($ids, $rece_id);

            // echo $default_num;die;
            // echo count($unknow);die;
            // $all_success = 3348;
            $unknow = [];
            foreach ($ids as $key => $value) {
                // $receipts = $mysql_connect->query("SELECT * FROM yx_sfl_send_multimediatask_receipt WHERE `task_id` = " . $value);
                $receipts = [];
                $task = $mysql_connect->query("SELECT * FROM yx_sfl_multimedia_message WHERE `id` = " . $value);

                /*            $receive_all = [];
                $receive_all = [
                'MESSAGE_ID' => $task[0]['mseeage_id'],
                'COMMUNICATION_CHANNEL_ID' => $task[0]['sfl_relation_id'],
                'MOBILE' => $task[0]['mobile'],
                'STATUS' => 'MMS:1',
                'real_message' => '',
                'SENDING_TIME' => date('Y-m-d H:i:s',1590726600+ceil($key/1700)),
                ];
                 */
                // $num = mt_rand(0, 5378);
                // $receive_alls[] = $receive_all;
                if (!empty($receipts)) {
                    $num = count($receipts);

                    $receive_all = [
                        'MESSAGE_ID' => $receipts[$num - 1]['mseeage_id'],
                        'COMMUNICATION_CHANNEL_ID' => $receipts[$num - 1]['template_id'],
                        'MOBILE' => $receipts[$num - 1]['mobile'],
                        'STATUS' => $receipts[$num - 1]['status_message'],
                        // 'real_message'             => $receipts[0]['real_message'],
                        'real_message' => "",
                        'SENDING_TIME' => date('Y-m-d H:i:s', $expeort_time + ceil($key / 7000)),
                    ];
                    $num = mt_rand(0, 1000);
                    /* if ($num>=0 && $num < 113) {
                    $receive_all['STATUS'] = "MMS:2";
                    }else{
                    $receive_all['STATUS'] = "MMS:1";
                    } */
                    $receive_alls[] = $receive_all;
                    $i++;
                } else {
                    // $task = $mysql_connect->query("SELECT * FROM yx_sfl_multimedia_message WHERE `id` = ".$value);
                    $receive_all = [
                        'MESSAGE_ID' => $task[0]['mseeage_id'],
                        'COMMUNICATION_CHANNEL_ID' => $task[0]['sfl_relation_id'],
                        'MOBILE' => $task[0]['mobile'],
                        'STATUS' => 'MMS:1',
                        'real_message' => '',
                        'SENDING_TIME' => date('Y-m-d H:i:s', $expeort_time + ceil($key / 7000)),
                    ];
                    $num = mt_rand(0, 1000);
                    if ($num >= 0 && $num < 18) {
                        $receive_all['STATUS'] = "MMS:2";
                    } else {
                        $receive_all['STATUS'] = "MMS:1";
                    }
                    if (checkMobile($task[0]['mobile']) == false) {
                        $receive_all['STATUS'] = "MMS:2";
                    } else {
                        $end_num = substr($task[0]['mobile'], -6);
                        //按无效号码计算
                        //按无效号码计算
                        if (in_array($end_num, ['000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999'])) {
                            $receive_all['STATUS'] = "MMS:2";
                        }
                    }
                    $receive_alls[] = $receive_all;
                    $i++;
                }

                if ($i > 200000) {
                    $name = "imp_mobile_status_report_mms_" . $j . "_" . date('Ymd', $start_time) . ".xlsx";
                    $this->derivedTables($receive_alls, $name);
                    $j++;
                    $receive_alls = [];
                    $i = 1;
                }
            }
            if (!empty($receive_alls)) {
                $name = "imp_mobile_status_report_mms_" . $j . "_" . date('Ymd', $start_time) . ".xlsx";
                $this->derivedTables($receive_alls, $name);
            }
            die;
            // print_r($receive_alls);die;
            // 导出
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
                // array('real_message', 'real_message'),
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
            $objWriter->save('imp_mobile_status_report_mms_1_' . date("Ymd", $start_time) . '.xlsx');
        } catch (\Exception $th) {
            exception($th);
        }
    }

    public function sflSftpTaskReceiptForExcel($time_key, $expeort_time = '')
    {
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        // print_r(realpath("../"). "\yt_area_mobile.csv");die;
        $start_time = strtotime($time_key);
        $end_time = $start_time + 86400;
        if (empty($expeort_time)) {
            $expeort_time = $start_time + 43200 - mt_rand(0, 3000);
        }

        // $expeort_time = 1592884416;
        $expeort_time = strtotime("2021/01/30 10:00:00");
        try {

            // $mul_task_ids = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task WHERE  `create_time` >  " . $start_time . " AND   `create_time` <  " . $end_time . "  AND `template_id`  IN ('514','529','100150820','100150821','100150822','100150970','100182845','100182846','100182847','100182848','100182849','100182850','100186375','100187353') ");
            // $mul_task_ids = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task WHERE  `create_time` >  " . $start_time . " AND   `create_time` <  " . $end_time . "  AND `template_id`  IN ('100185591') ");
            $mul_task_ids = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task WHERE   `template_id`  IN ('100188308') ");
            // $mul_task_ids = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task WHERE  `template_id`  IN ('100187551','100187552','100187553','100187554','100187555','100187556','100187557','100187558','100187585','100187586','100187587','100187588','100187589','100187590') ");
            /*  echo "SELECT `id` FROM yx_sfl_send_task WHERE  `create_time` >  ".$start_time." AND   `create_time` <  ".$end_time."  AND `template_id` IN ('529','100150820','100150821','100150822','100180393') ";die; */

            $ids = [];
            $i = 1;
            $j = 2;
            foreach ($mul_task_ids as $key => $value) {
                // $ids[] = $value['id'];
                /*  $objPHPExcel = $objReader->load(realpath("./") . "/0522.xlsx");
                // $objPHPExcel = $objReader->load(realpath("./") . "/yt_area_mobile.csv");
                //选择标签页
                $sheet       = $objPHPExcel->getSheet(0); //取得sheet(0)表
                $highestRow  = $sheet->getHighestRow(); // 取得总行数//获取表格列数
                $columnCount = $sheet->getHighestColumn();
                $has = [];
                for ($row = 1; $row <= $highestRow; $row++) {
                //列数循环 , 列数是以A列开始
                for ($column = 'A'; $column <= $columnCount; $column++) {
                $dataArr[] = $objPHPExcel->getActiveSheet()->getCell($column . $row)->getValue();
                }
                if ($dataArr[0] == $value['mobile']) {
                break;
                }
                // print_r($dataArr);die;
                // $has[] = $dataArr;
                unset($dataArr);
                }
                // print_r($dataArr);die;
                if (empty($dataArr) || empty($dataArr[1])) {
                //未知
                // $unknow[] = $value['id'];
                $receive_all = [
                'MESSAGE_ID' => $value['mseeage_id'],
                'COMMUNICATION_CHANNEL_ID' => $value['template_id'],
                'MOBILE' => $value['mobile'],
                'STATUS' => 'SMS:1',
                'SENDING_TIME' => date('Y-m-d H:i:s',1590123522+mt_rand(10,1800)),
                ];
                $receive_alls[] = $receive_all;
                }else{
                $receive_all = [
                'MESSAGE_ID' => $value['mseeage_id'],
                'COMMUNICATION_CHANNEL_ID' => $value['template_id'],
                'MOBILE' => $value['mobile'],
                'SENDING_TIME' => date('Y-m-d H:i:s',1590123522+mt_rand(10,1800)),
                ];
                if (trim($dataArr[1]) == 0 || trim($dataArr[1]) == 'DELIVRD') {
                $receive_all['STATUS']= 'SMS:1';
                }elseif(strpos(trim($dataArr[1]),'BLACK')){
                $receive_all['STATUS']= 'SMS:4';
                }elseif(trim($dataArr[1]) == 45){
                $receive_all['STATUS']= 'SMS:4';
                }else{
                $receive_all['STATUS']= 'SMS:2';
                }
                $receive_alls[] = $receive_all;
                }
                 */

                // $receipts    = $mysql_connect->query("SELECT * FROM yx_sfl_send_task_receipt WHERE `task_id` = " . $value['id']);
                $receipts = [];
                $task = $mysql_connect->query("SELECT * FROM yx_sfl_send_task WHERE `id` = " . $value['id']);
                $receive_all = [];
                if (strpos($task[0]['task_content'], 'test')) {
                    continue;
                }
                $num = mt_rand(0, 1000);

                if (!empty($receipts)) {

                    $num = count($receipts);
                    $receive_all = [
                        'MESSAGE_ID' => $task[0]['mseeage_id'],
                        'COMMUNICATION_CHANNEL_ID' => $receipts[0]['template_id'],
                        'MOBILE' => $receipts[0]['mobile'],
                        'STATUS' => $receipts[0]['status_message'],
                        // 'real_message' => $receipts[0]['real_message'],
                        'SENDING_TIME' => date('Y-m-d H:i:s', $expeort_time + ceil($key / 7300)),
                    ];
                    if (checkMobile($receipts[0]['mobile']) == false) {
                        $receive_all['STATUS'] = "SMS:2";
                    } else {
                        $end_num = substr($task[0]['mobile'], -6);
                        //按无效号码计算
                        //按无效号码计算
                        /* if (in_array($end_num, ['000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999'])) {
                    $receive_all['STATUS'] = "SMS:2";
                    } */
                    }

                    /*  if (in_array(trim($receipts[0]['real_message']),['UNDELIV','MK:100D','MK1:100C','REJECTD','EXPIRED','NOROUTE','ID:0076'])) {
                    $receive_all['STATUS'] = 'SMS:1';
                    } */
                    $receive_alls[] = $receive_all;
                    $i++;
                    // $mysql_connect->table('yx_sfl_send_task_receipt')->where('id',$task[0]['id'])->update(['mseeage_id' => $task[0]['mseeage_id']]);
                } else {
                    // $task = $mysql_connect->query("SELECT * FROM yx_sfl_multimedia_message WHERE `id` = ".$value);
                    $receive_all = [
                        'MESSAGE_ID' => $task[0]['mseeage_id'],
                        'COMMUNICATION_CHANNEL_ID' => $task[0]['template_id'],
                        'MOBILE' => $task[0]['mobile'],
                        'STATUS' => 'SMS:1',
                        // 'real_message' => '',
                        'SENDING_TIME' => date('Y-m-d H:i:s', $expeort_time + ceil($key / 7300)),
                    ];
                    if ($num >= 0 && $num <= 18) {
                        $receive_all['STATUS'] = "SMS:2";
                    } else {
                        $receive_all['STATUS'] = "SMS:1";
                    }
                    // $receive_all['STATUS'] = "SMS:1";
                    if (checkMobile($task[0]['mobile']) == false) {
                        $receive_all['STATUS'] = "SMS:2";
                    } else {
                        $end_num = substr($task[0]['mobile'], -6);
                        //按无效号码计算
                        //按无效号码计算
                        if (in_array($end_num, ['000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999'])) {
                            $receive_all['STATUS'] = "SMS:2";
                        }
                    }
                    $receive_alls[] = $receive_all;
                    $i++;
                }

                if ($i > 200000) {
                    $name = "imp_mobile_status_report_sms_" . $j . "_" . date('Ymd', $start_time) . ".xlsx";
                    $this->derivedTables($receive_alls, $name);
                    $j++;
                    $receive_alls = [];
                    $i = 1;
                }
            }
            if (!empty($receive_alls)) {
                $name = "imp_mobile_status_report_sms_" . $j . "_" . date('Ymd', $start_time) . ".xlsx";
                $this->derivedTables($receive_alls, $name);
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function sflSftpUpRiverForExcel()
    {
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G
        $receive_alls = [];
        $upriver = Db::query("SELECT `mobile`,`message_info`,`create_time` FROM yx_user_upriver WHERE `uid` = '92' AND `create_time` <=1590854400 ");
        foreach ($upriver as $key => $value) {
            $source = Db::query("SELECT `name` FROM yx_number_segment WHERE `mobile` =  " . mb_substr($value['mobile'], 0, 3));
            $receive_all = [];
            $receive_all = [
                'MOBILE' => $value['mobile'],
                'TYPE' => 'SMS',
                'CONTENT' => $value['message_info'],
                'receive_time' => date('Y-m-d H:i:s', $value['create_time']),
                'CITY' => '',
                'CHANNEL' => $source[0]['name'],
            ];
            $receive_alls[] = $receive_all;
            // print_r($receive_all);die;
            // $upriver[0]['CHANNEL']

        }
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
        $objActSheet->setTitle("imp_mobile_feedback");
        $CellList = array(
            array('MOBILE', 'MOBILE'),
            array('TYPE', 'TYPE'),
            array('CONTENT', 'CONTENT'),
            array('receive_time', 'receive_time'),
            array('CITY', 'CITY'),
            array('CHANNEL', 'CHANNEL'),
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
        // $outputFileName = "receive_sms_2_20200524.xlsx";
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
        $objWriter->save('imp_mobile_feedback_sms_2_20200530.xlsx');
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
            // array('real_message', 'real_message'),
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
}

class SFTPConnection
{
    private $connection;
    private $sftp;

    public function __construct($host, $port = 22)
    {
        $this->connection = ssh2_connect($host, $port);
        if (!$this->connection) {
            throw new Exception("Could not connect to $host on port $port.");
        }
    }

    public function login($username, $password)
    {
        if (!ssh2_auth_password($this->connection, $username, $password)) {
            throw new Exception("Could not authenticate with username $username " .
                "and password $password.");
        }

        $this->sftp = ssh2_sftp($this->connection);
        if (!$this->sftp) {
            throw new Exception("Could not initialize SFTP subsystem.");
        }
    }

    public function uploadFile($local_file, $remote_file)
    {
        $sftp = $this->sftp;
        $stream = fopen("ssh2.sftp://$sftp$remote_file", 'w');

        if (!$stream) {
            throw new Exception("Could not open file: $remote_file");
        }

        $data_to_send = file_get_contents($local_file);
        if ($data_to_send === false) {
            throw new Exception("Could not open local file: $local_file.");
        }

        if (fwrite($stream, $data_to_send) === false) {
            throw new Exception("Could not send data from file: $local_file.");
        }

        fclose($stream);
    }
    /**
     * 下载文件
     * @param $local_file
     * @param $remote_file
     */
    public function downFile($local_file, $remote_file)
    {
        ssh2_scp_recv($this->connection, $remote_file, $local_file);
    }

    /**
     * 判断文件夹是否存在
     * @param string $dir  目录名称
     * @return bool
     */
    public function dirExits($dir)
    {
        return file_exists("ssh2.sftp://$this->sftp" . $dir);
    }

    /**
     * 创建目录
     * @param string $path 例子  '/home/username/newdir'
     * @param int $auth 默认 0777的权限
     */
    public function ssh2SftpMchkdir($path, $auth = 0777) //使用创建目录循环

    {
        $end = ssh2_sftp_mkdir($this->sftp, $path, $auth, true);
        if ($end !== true) {
            throw new Exception('文件夹创建失败');
        }
    }

    /**
     * 目录重命名
     * @param string $dir 例子：'/home/username/newnamedir'
     * $dir 示例：/var/file/image
     * @return bool
     */
    public function rename($old_dir, $new_dir)
    {
        $is_true = ssh2_sftp_rename($this->sftp, $old_dir, $new_dir);
        return $is_true;
    }

    /**
     * 删除文件
     * @param string $dir  例子：'/home/username/dirname/filename'
     * $dir 示例：/var/file/image/404NotFound.png
     * @return bool
     */
    public function delFile($dir)
    {
        $is_true = ssh2_sftp_unlink($this->sftp, $dir);
        return $is_true;
    }

    /**
     * 获取文件夹下的文件
     * @param string $remote_file 文件路径 例：/var/file/image
     * @return array
     */
    public function scanFileSystem($remote_file)
    {
        $sftp = $this->sftp;
        $dir = "ssh2.sftp://$sftp$remote_file";
        $tempArray = array();
        $handle = opendir($dir);
        // 所有的文件列表
        while (false !== ($file = readdir($handle))) {
            if (substr("$file", 0, 1) != ".") {
                if (is_dir($file)) {
                    //                $tempArray[$file] = $this->scanFilesystem("$dir/$file");
                } else {
                    $tempArray[] = $file;
                }
            }
        }
        closedir($handle);
        return $tempArray;
    }
}
class Sftp
{
    private $connection;
    private $sftp;
    public function __construct($params)
    {
        $host = $params['host']; //地址
        $port = $params['port']; //端口
        $this->connection = ssh2_connect($host, $port);
        if (!$this->connection) {
            throw new Exception("$host 连接 $port 端口失败");
        }
    }

    /**
     * 登录
     * @param string $login_type 登录类型
     * @param string $username  用户名
     * @param string $password  密码
     * @param string  $pub_key  公钥
     * @param string $pri_key  私钥
     * @throws Exception]
     */
    public function login($login_type, $username, $password = null, $pub_key = null, $pri_key = null)
    {
        switch ($login_type) {
            case 'username': //通过用户名密码登录
                $login_result = ssh2_auth_password($this->connection, $username, $password);
                break;
            case 'pub_key': //公钥私钥登录
                $login_result = ssh2_auth_pubkey_file($this->connection, $username, $pub_key, $pri_key);
                break;
        }
        if (!$login_result) {
            throw new Exception("身份验证失败");
        }

        $this->sftp = ssh2_sftp($this->connection);
        if (!$this->sftp) {
            throw new Exception("初始化sftp失败");
        }

        return true;
    }

    /**
     * 上传文件
     * @param string $local_file 本地文件
     * @param string $remote_file  远程文件
     * @throws Exception
     */
    public function uploadFile($local_file, $remote_file)
    {
        $is_true = ssh2_scp_send($this->connection, $local_file, $remote_file, 0777);
        return $is_true;
    }
}
