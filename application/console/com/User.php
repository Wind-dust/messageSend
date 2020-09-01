<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use think\Db;
use cache\Phpredis;

class User extends Pzlife
{
    private $redis;

    /**
     * 数据库连接
     *
     */
    public function db_connect($databasename)
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

    public function bangzhixinReceive(){
        try {
            $redis = Phpredis::getConn();
            // $MinID = $redis->get('index:meassage:code:receipt:zhonglan:MinID');
                    $MinID = $MinID ? $MinID : 0;
                    $MinID = 0;
                    $receive = sendRequest('http://www.wemediacn.net/webservice/mmsservice.asmx/QueryMMSSeqReport','post',['TokenID' => '7100455520709585', 'MinID' => $MinID]);
                    $receive_data = json_decode(json_encode(simplexml_load_string($receive, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                    
                    if (!empty($receive_data['result'])) {
                        $MinID = $receive_data['@attributes']['nextID'];
                        // $redis->set('index:meassage:code:receipt:zhonglan:MinID',$MinID);
                        if ($receive_data['@attributes']['count'] > 1) {
                            $receipts = [];
                            $receipts = $receive_data['result'];
                            foreach ($receipts as $key => $value) {
                                $task_id = $redis->hGet('index:meassage:code:back_taskno:zhonglan', $value['MessageID']);
                                $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver'; //创蓝彩信回执通道
                                if ($value['statustext'] == 1000){
                                    $stat = 'DELIVRD';
                                }else {
                                    $stat = $value['statustext'];
                                }
                                $send_task_log = [
                                        'task_id'        => $task_id,
                                        'mobile'         => $value['mobile'],
                                        'status_message' => $stat,
                                        'send_time'      => strtotime($value['inserttime']),
                                    ];
                                    $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
                            }
                        }else{
                           $task_id = $redis->hGet('index:meassage:code:back_taskno:zhonglan', $receive_data['result']['MessageID']);
                           $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver'; //创蓝彩信回执通道
                           if ($receive_data['result']['statustext'] == 1000){
                               $stat = 'DELIVRD';
                           }else {
                               $stat = $receive_data['result']['statustext'];
                           }
                           $send_task_log = [
                                'task_id'        => $task_id,
                                'mobile'         => $receive_data['result']['mobile'],
                                'status_message' => $stat,
                                'send_time'      => strtotime($receive_data['result']['inserttime']),
                            ];
                            $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
                        }
                    }
                    die;
                    $MinID = $redis->get('index:meassage:code:receipt:zhonglan:upriver:MinID');
                    $MinID = $MinID ? $MinID : 0;
                    $MinID = 0;
                    $receive = sendRequest('http://www.wemediacn.net/webservice/smsservice.asmx/QuerySMSUP','post',['TokenID' => '7100455520709585', 'MinID' => $MinID, 'Count' => 0, 'externCode' => '']);
                    $receive_data = json_decode(json_encode(simplexml_load_string($receive, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                    $codelen = strlen('106900294555');
                    /* $upgoing = [];
                    $upgoing = [
                        'mobile' => $phone,
                        'message_info' => $msg,
                        'get_time' => $moTime,
                    ];
                   */
                    if (!empty($receive_data['result'])) {
                        $MinID = $receive_data['@attributes']['nextID'];
                        // $redis->set('index:meassage:code:receipt:zhonglan:upriver:MinID',$MinID);
                        foreach ($receive_data['result'] as $key => $value) {
                            $upgoing = [];
                            if (is_array($value)) {
                                $develop_code = mb_substr($value['DestNumber'],$codelen);
                                $mobile = $value['mobile'];
                                $message_info = $value['MsgFormat'];
                                $get_time = $value['ReceiveTime'];
                                $upgoing = [
                                    'mobile' => $mobile,
                                    'message_info' => $message_info,
                                    'get_time' => $get_time,
                                ];
                                $sql = "SELECT `uid`,`task_no` FROM yx_user_multimedia_message_log WHERE `mobile` = '".$mobile."' ";
                                if (!empty($develop_code)) {
                                    $sql.= " AND `develop_no` = '".$develop_code."'";
                                }
                                $sql.= ' ORDER BY `id` DESC LIMIT 1';
                                $task_log = Db::query($sql);
                                // 
                                if (!empty($task_log)) {
                                    $task_log = $task_log[0];
                                    // print_r($task_log);die;
                                    $redis->rPush('index:message:Mmsupriver:' . $task_log['uid'], json_encode($upgoing));
                                    $insert_data = [];
                                    $insert_data = [
                                        'uid' => $task_log['uid'],
                                        'task_no' => $task_log['task_no'],
                                        'mobile' => $mobile,
                                        'message_info' => $message_info,
                                        'create_time' => strtotime($get_time),
                                        'business_id' => 8,
                                    ]; 
                                    DB::table('yx_user_upriver')->insert($insert_data);
                                }
                                // print_r($codelen);
                            }else{
                                $develop_code = mb_substr($receive_data['result']['DestNumber'],$codelen);
                                $mobile = $receive_data['result']['mobile'];
                                $message_info = $receive_data['result']['MsgFormat'];
                                $get_time = $receive_data['result']['ReceiveTime'];
                                $upgoing = [
                                    'mobile' => $mobile,
                                    'message_info' => $message_info,
                                    'get_time' => $get_time,
                                ];
                                $sql = "SELECT `uid`,`task_no` FROM yx_user_multimedia_message_log WHERE `mobile` = '".$mobile."' ";
                                if (!empty($develop_code)) {
                                    $sql.= " AND `develop_no` = '".$develop_code."'";
                                }
                                $sql.= ' ORDER BY `id` DESC LIMIT 1';
                                $task_log = Db::query($sql);
                                // 
                                if (!empty($task_log)) {
                                    $task_log = $task_log[0];
                                    // print_r($task_log);die;
                                    $redis->rPush('index:message:Mmsupriver:' . $task_log['uid'], json_encode($upgoing));
                                    $insert_data = [];
                                    $insert_data = [
                                        'uid' => $task_log['uid'],
                                        'task_no' => $task_log['task_no'],
                                        'mobile' => $mobile,
                                        'message_info' => $message_info,
                                        'create_time' => strtotime($get_time),
                                        'business_id' => 8,
                                    ]; 
                                    DB::table('yx_user_upriver')->insert($insert_data);
                                }
                                break;
                            }
                            // print_r($develop_code);
                           
                        }
                    }
                   
            die;
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
       
        $XML = '<?xml version="1.0" encoding="utf-8"?>
        <string xmlns="http://mms.wemediacn.com/">OK:[202008278997526671004555]</string>';
$receive_data = json_decode(json_encode(simplexml_load_string($XML, 'SimpleXMLElement', LIBXML_NOCDATA)), true); 
$report_msg_id = $receive_data[0];
$report_msg_data = explode(':',$report_msg_id);
$report_task_id = $report_msg_data[1];
$report_task_id = trim($report_task_id,'[');
$report_task_id = trim($report_task_id,']');
print_r($report_task_id);die;
$XML  = '<?xml version="1.0" encoding="utf-8" ?>
<returnsms>
    <statusbox>
        <mobile>18723854437</mobile>
        <taskid>83525</taskid>
        <status>10</status>
        <receivetime>2020-05-01 17:01:57</receivetime>
        <errorcode>
            <![CDATA[Retrieved--1000]]>
        </errorcode>
        <extno>
            <![CDATA[]]>
        </extno>
    </statusbox>

    </returnsms>';
    $receive_data = json_decode(json_encode(simplexml_load_string($XML, 'SimpleXMLElement', LIBXML_NOCDATA)), true); 
//   print_r($XML);die;
        // $weidu = arrayLevel($XML);
        // echo $weidu;
      
        // echo count($XML['statusbox']);
        // die;
        $redis = Phpredis::getConn();
        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:' . 13; //彩信MsgId
    if (isset($receive_data['statusbox'])) {
        // print_r($receive_data['statusbox']);
        foreach ($receive_data['statusbox'] as $key => $value) {
           if (is_array($value)) {
               echo "多条回执";
           }else{
               echo "单条回执";
           }
        }

        foreach ($receive_data['statusbox'] as $key => $value) {
            // $receive_info = [];
            // $receive_info = explode(',', $value);
            // $task_id      = $receive_id[$value['taskid']];
            if (is_array($value)) {
                $task_id = $redis->hget('index:meassage:code:back_taskno:' . 13, trim($value['taskid']));
                // print_r($value);die;
                $task    = $this->getSendTask($task_id);
                if ($task == false) {
                    echo "error task_id" . "\n";
                }
                $stat          = $value['errorcode'];
                $send_task_log = [];
                if ($value['status'] == '10') {

                    $send_status = 3;
                    $stat        = 'DELIVRD';
                } else {
                    $send_status = 4;
                }
                $send_task_log = [
                    'task_no'        => $task['task_no'],
                    'uid'            => $task['uid'],
                    'mobile'         => $value['mobile'],
                    'status_message' => $stat,
                    'send_status'    => $send_status,
                    'send_time'      => strtotime($value['receivetime']),
                ];
                $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
                // Db::startTrans();
                // try {
                //     Db::table('yx_user_send_task_log')->insert($send_task_log);
                //     Db::commit();
                // } catch (\Exception $e) {
                //     Db::rollback();
                //     return ['code' => '3009']; //修改失败
                // }
                unset($send_status);
            }else{
                $task_id = $redis->hget('index:meassage:code:back_taskno:' . 13, trim($receive_data['statusbox']['taskid']));
                print_r($receive_data['statusbox']['taskid']);die;
                $task    = $this->getSendTask($task_id);
                if ($task == false) {
                    echo "error task_id" . "\n";
                    break;
                }
                $stat          = $receive_data['statusbox']['errorcode'];
                $send_task_log = [];
                if ($receive_data['statusbox']['status'] == '10') {

                    $send_status = 3;
                    $stat        = 'DELIVRD';
                } else {
                    $send_status = 4;
                }
                $send_task_log = [
                    'task_no'        => $task['task_no'],
                    'uid'            => $task['uid'],
                    'mobile'         => $receive_data['statusbox']['mobile'],
                    'status_message' => $stat,
                    'send_status'    => $send_status,
                    'send_time'      => strtotime($receive_data['statusbox']['receivetime']),
                ];
                $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
                break;
            }
           
        }
    }

    }

    public function getSendTask($id) {
        $task = Db::query("SELECT `task_no`,`uid` FROM yx_user_multimedia_message WHERE `id` =" . $id);
        if ($task) {
            return $task[0];
        }
        return false;
    }

}


