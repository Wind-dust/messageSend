<?php

namespace app\common\action\admin;

use app\facade\DbAdministrator;
use app\facade\DbSendMessage;
use app\facade\DbUser;
use PHPExcel;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Fill;
use PHPExcel_Writer_Excel2007;
use think\Db;
use Config;

class Message extends CommonIndex {
    /**
     * @param $page
     * @param $pageNum
     * @return array
     * @author rzc
     */
    public function getMultimediaMessageTask($page, $pageNum, $id = 0, $title = '', $free_trial = 0, $send_status = 0) {
        $offset = ($page - 1) * $pageNum;
        $time = strtotime('-4 days',time());
        // echo $time;die;
        $where = [];
        array_push($where,['create_time','>=',$time]);
        $offset = ($page - 1) * $pageNum;
        if ($free_trial) {
            array_push($where,['free_trial','=',$free_trial]);
        }
        if ($send_status) {
            array_push($where,['send_status','=',$send_status]);
        }
        $where  = [];
        if (!empty($id)) {
            $result            = DbSendMessage::getUserMultimediaMessage(['id' => $id], '*', true);
            $result['content'] = DbSendMessage::getUserMultimediaMessageFrame(['multimedia_message_id' => $id], '*', false, ['num' => 'asc']);
        } else {
            if (empty($title)) {
                array_push($where, ['title', 'like', '%' . $title . '%']);
            }
            $result = DbSendMessage::getUserMultimediaMessage($where, '*', false, '', $offset . ',' . $pageNum);
            foreach ($result as $key => $value) {
                $result[$key]['content'] = DbSendMessage::getUserMultimediaMessageFrame(['multimedia_message_id' => $value['id']], '*', false, ['num' => 'asc']);
            }
        }
        $total = DbSendMessage::countUserMultimediaMessage($where);
        if ($id) {
            $total = 1;
        }

        return ['code' => '200', 'data' => $result, 'total' => $total];
    }

    public function auditMultimediaMessageTask($effective_id = [], $free_trial) {
        // print_r($effective_id);die;
        $userchannel = DbSendMessage::getUserMultimediaMessage([['id', 'in', join(',', $effective_id)]], 'id,task_no,send_msg_id,uid,mobile_content,real_num,free_trial,template_id,submit_content', false);

        if (empty($userchannel)) {
            return ['code' => '3001'];
        }
        $real_effective_id = [];
        $INTERCEPT_num = [];
        // $receipt = $redis->rPush('index:meassage:code:user:receive:168','{"task_no":"bus20063022452104364246","status_message":"NOROUTE","message_info":"\u53d1\u9001\u6210\u529f","mobile":"15103230163","msg_id":"70000500020200630224527169053","send_time":"2020-06-30 22:45:28","smsCount":1,"smsIndex":1}');
        // print_r($userchannel);die;
        $uids = [];
        foreach ($userchannel as $key => $value) {
            if ($value['free_trial'] > 1) {
                continue;
            }
            $real_effective_id[] = $value['id'];
            if ($free_trial == 3) {
                // $INTERCEPT[] = $value['id'];
                // $res = $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' =>$usertask['id'],'deduct' => $user['multimedia_deduct']]));
                // $mobile = explode()
                
                if (!empty($value['submit_content'])) {
                    $submit_content = json_decode($value['submit_content'],true);
                    if (!empty($submit_content)) {
                        foreach ($submit_content as $skey => $svalue) {
                            // # code...
                            $res = $this->redis->rpush("index:meassage:code:user:mulreceive:".$value['uid'], json_encode(['task_no' =>$value['task_no'],'msg_id' => $value['send_msg_id'],"status_message"=>"INTERCEPT","message_info" => "驳回","send_time" => date("Y-m-d H:i:s",time()),'mobile'=> $svalue['mobile']]));
                        }
                    }else{
                        $res = $this->redis->rpush("index:meassage:code:user:mulreceive:".$value['uid'], json_encode(['task_no' =>$value['task_no'],'msg_id' => $value['send_msg_id'],"status_message"=>"INTERCEPT","message_info" => "驳回","send_time" => date("Y-m-d H:i:s",time())]));
                    }
                }else{
                    $mobiles = explode(',',$value['mobile_content']);
                    foreach ($mobiles as $mkey => $mvalue) {
                        $res = $this->redis->rpush("index:meassage:code:user:mulreceive:".$value['uid'], json_encode(['task_no' =>$value['task_no'],'msg_id' => $value['send_msg_id'],"status_message"=>"INTERCEPT","message_info" => "驳回","send_time" => date("Y-m-d H:i:s",time()),'mobile'=> $mvalue]));
                    }
                }
                if (isset($INTERCEPT_num[$value['uid']])) {
                    $INTERCEPT_num[$value['uid']] += $value['real_num'];
                }else{
                     $INTERCEPT_num[$value['uid']] = $value['real_num'];
                     $uids[] = $value['uid'];
                }
                
            }
        }
        if (empty($real_effective_id)) {
            return ['code' => '3002', 'msg' => '没有需要审核的任务'];
        }
        $where_equitise = [
            ['uid', 'IN', join(',', $uids)], ['business_id', '=', 8]
        ];
        $user_equities = DbAdministrator::getUserEquities($where_equitise, 'id,uid,num_balance', false);
        /* print_r($user_equities);die;
        foreach ($INTERCEPT_num as $rkey => $rvalue) {
            
        } */
        Db::startTrans();
        try {
            foreach ($real_effective_id as $real => $efid) {
                DbSendMessage::editUserMultimediaMessage(['free_trial' => $free_trial], $efid);
            }
            if ($free_trial == 3) {
                foreach ($user_equities as $key => $value) {
                    DbAdministrator::modifyBalance($value['id'], $INTERCEPT_num[$value['uid']], 'inc');
                }
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributionMultimediaChannel($effective_id = [], $yidong_channel_id, $liantong_channel_id, $dianxin_channel_id, $business_id) {
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $yidong_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $liantong_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3011'];
        }
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $dianxin_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3012'];
        }
        $usertask = DbSendMessage::getUserMultimediaMessage([['id', 'in', join(',', $effective_id)]], 'id,uid,mobile_content,submit_content,free_trial,send_num,yidong_channel_id,liantong_channel_id,dianxin_channel_id', false);
        if (empty($usertask)) {
            return ['code' => '3001'];
        }
        $num               = 0;
        $uids              = [];
        $real_effective_id = [];
        $real_usertask     = [];
        foreach ($usertask as $key => $value) {
            if (empty($uids)) {
                $uids[] = $value['uid'];
            } elseif (!in_array($value['uid'], $uids)) {
                $uids[] = $value['uid'];
            }
            // print_r($value);
            if ($value['free_trial'] == 2 && !$value['yidong_channel_id']) {
                $real_length     = 1;
                $real_usertask[] = $value;
                $mobilesend      = explode(',', $value['mobile_content']);
                // $send_length     = mb_strlen($value['task_content'], 'utf8');
                // if ($send_length > 70) {
                //     $real_length = ceil($send_length / 67);
                // }
                $num += ($real_length * $value['send_num']);
                // foreach ($mobilesend as $key => $kvalue) {

                // }
            }
        }
        // die;
        // print_r($uids);die;
        if (count($uids) > 1) {
            return ['code' => '3008', 'msg' => '一批只能同时分配一个用户的营销任务'];
        }
        if (empty($real_usertask)) {
            return ['code' => '3010', 'msg' => '待分配的批量任务未空（提交了一批未审核的批量任务）'];
        }
        $userEquities = DbAdministrator::getUserEquities(['uid' => $uids[0], 'business_id' => $business_id], 'id,agency_price,num_balance', true);
        if (empty($userEquities)) {
            return ['code' => '3005'];
        }

        $user = DbUser::getUserInfo(['id' => $uids[0]], 'id,reservation_service,user_status,multimedia_deduct', true);
        if ($user['user_status'] != 2) {
            return ['code' => '3006'];
        }
        // print_r($num);die;
        /* if ($num > $userEquities['num_balance'] && $user['reservation_service'] != 2) {
        return ['code' => '3007'];
        } */
        $free_trial = 2;
        if ($userEquities['agency_price'] < $channel['channel_price']) {
            $free_trial = 4;
        }
        Db::startTrans();
        try {

            // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
            foreach ($real_usertask as $key => $value) {
                DbSendMessage::editUserMultimediaMessage(['free_trial' => $free_trial, 'yidong_channel_id' => $yidong_channel_id, 'liantong_channel_id' => $liantong_channel_id, 'dianxin_channel_id' => $dianxin_channel_id, 'send_status' => 2], $value['id']);
            }
            if ($free_trial == 2) {
                foreach ($real_usertask as $real => $usertask) {
                    $res = $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' =>$usertask['id'],'deduct' => $user['multimedia_deduct']]));
                }
            }

            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function exportReceiptReport($id, $business_id) {
        if ($business_id == 5) { //营销
            $result = DbAdministrator::getUserSendTask(['id' => $id], 'log_path,update_time,task_no', true);
        } elseif ($business_id == 6) { // 行业
            $result = DbAdministrator::getUserSendCodeTask(['id' => $id], 'log_path,update_time,task_no', true);
        } elseif ($business_id == 9) { //游戏
            // $result = DbAdministrator::getUserSendTask(['id' => $id], 'log_path', true);
        }
        if (!empty($result['log_path'])) {
            $task_log = [];
            if (file_exists($result['log_path'])) {
                $file = fopen($result['log_path'], "r");
            } else {
                if ($business_id == 6) {
                    $file = fopen(str_replace('marketing', 'business', $result['log_path']), "r");
                }
            }

            $data = array();
            $i    = 0;
            // $phone = '';
            // $j     = '';
            while (!feof($file)) {
                $cellVal = trim(fgets($file));
                $log     = json_decode($cellVal, true);
                if (isset($log['mobile'])) {
                    if (!isset($log['status_message'])) {
                        $log['status_message'] = '';
                    }
                    if (!isset($log['send_time'])) {
                        $log['send_time'] = '';
                    }
                    $data[] = $log;
                }
                $i++;
            }
            fclose($file);
        } else {
            $data = DbAdministrator::getUserSendCodeTaskLog(['task_no' => $result['task_no']], '*', false);
        }
        $objExcel = new PHPExcel();
        // $objWriter  = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
        // $sheets=$objWriter->getActiveSheet()->setTitle('金卡1.');//设置表格名称
        $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
        $objWriter->setOffice2003Compatibility(true);

        //设置文件属性
        $objProps = $objExcel->getProperties();
        $objProps->setTitle("sheet1");
        $objProps->setSubject($result['task_no'] . ":" . date('Y-m-d H:i:s', time()));

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        $date = date('Y-m-d H:i:s', time());

        //设置当前活动sheet的名称
        $objActSheet->setTitle("金卡1");
        $CellList = array(
            array('task_no', '任务编号'),
            array('uid', '用户id'),
            array('title', '标题'),
            array('content', '内容'),
            array('mobile', '手机号'),
            array('send_status', '发送状态'),
            array('create_time', '创建时间'),
            array('status_message', '回执码'),
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
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $outputFileName . '"');
        header("Content-Transfer-Encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $objWriter->save('php://output');
        exit;
    }

    public function exportMultimediaReceiptReport($id) {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        $result = DbSendMessage::getUserMultimediaMessage(['id' => $id], '*', true);
        $data   = DbSendMessage::getUserMultimediaMessageLog(['task_id' => $id], '*', false);
        if (empty($data)) {
            return ['code' => '3002', 'msg' => '发送记录暂未同步'];
        }
        foreach ($data as $key => $value) {
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
        $objProps->setSubject($result['task_no'] . ":" . date('Y-m-d H:i:s', time()));

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
        $outputFileName = $result['task_no'] . ":" . date('Y-m-d H:i:s', time()) . ".xlsx";
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
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $outputFileName . '"');
        header("Content-Transfer-Encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $objWriter->save('php://output');
        exit;
    }

    public function getUserModel($page, $pageNum) {
        $offset = $pageNum * ($page - 1);
        $result = DbSendMessage::getUserModel([], '*', false, '', $offset . ',' . $pageNum);
        $totle  = DbSendMessage::countUserModel([]);
        return ['code' => '200', 'totle' => $totle, 'result' => $result];
    }

    public function auditUserModel($id, $status) {
        $result = DbSendMessage::getUserModel(['id' => $id], '*', true);
        if (empty($result)) {
            return ['code' => '3001'];
        }
        if ($result['status'] != 1) {
            return ['code' => '3003'];
        }
        Db::startTrans();
        try {
            DbSendMessage::editUserModel(['status' => $status], $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getUserSignature($page, $pageNum) {
        $offset = $pageNum * ($page - 1);
        $result = DbSendMessage::getUserSignature([], '*', false, '', $offset . ',' . $pageNum);
        $totle  = DbSendMessage::countUserSignature([]);
        return ['code' => '200', 'total' => $totle, 'result' => $result];
    }

    public function auditUserSignature($id, $audit_status) {
        $result = DbSendMessage::getUserSignature(['id' => $id], '*', true);
        if (empty($result)) {
            return ['code' => '3001'];
        }
        if ($result['audit_status'] != 1) {
            return ['code' => '3003'];
        }
        Db::startTrans();
        try {
            if ($audit_status == 2) {
                $status = 2;
            } else {
                $status = 1;
            }
            DbSendMessage::editUserSignature(['audit_status' => $audit_status, 'status' => $status], $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getDevelopCode($page, $pageNum, $no_lenth = 0, $develop_no = '', $is_bind = 0) {
        $where = [];
        if (!empty($no_lenth)) {
            array_push($where, [['no_lenth', '=', $no_lenth]]);
        }
        if (!empty($develop_no)) {
            array_push($where, [['develop_no', 'like', '%' . $develop_no . '%']]);
        }
        if (!empty($is_bind)) {
            array_push($where, [['is_bind', '=', $is_bind]]);
        }
        $offset = $pageNum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '200', 'total' => 0, 'develop' => []];
        }
        $result = DbSendMessage::getDevelopCode($where, '*', false, '', $offset . ',' . $pageNum);
        $total  = DbSendMessage::countDevelopCode($where);
        return ['code' => '200', 'total' => $total, 'develop' => $result];
    }

    public function getOneRandomDevelopCode($no_lenth) {
        $result = DbSendMessage::getRandomDevelopCode(['no_lenth' => $no_lenth, 'is_bind' => 1], 'develop_no', true);
        if (empty($result)) {
            return ['code' => '3002'];
        }
        return ['code' => '200', 'develop_no' => $result['develop_no']];
    }

    public function verifyDevelopCode($develop_no) {
        $result = DbSendMessage::getDevelopCode(['develop_no' => $develop_no, 'is_bind' => 1], 'develop_no', true);
        if (!empty($result)) {
            return ['code' => '200'];
        }
        return ['code' => '3002'];
    }

    public function userBindDevelopCode($develop_no, $nick_name, $business_id, $source) {
        $user = DbUser::getUserInfo(['nick_name' => $nick_name], 'id,reservation_service,user_status', true);
        if (empty($user) || $user['user_status'] != 2) {
            return ['code' => '3003'];
        }
        $result = DbSendMessage::getDevelopCode(['develop_no' => $develop_no], 'id,develop_no,is_bind', true);
        if (empty($result)) {
            return ['code' => '3004'];
        }
        if ($result['is_bind'] == 1) { //未绑定
            Db::startTrans();
            try {
                $data = [];
                $data = [
                    'develop_no'  => $develop_no,
                    'uid'         => $user['id'],
                    'business_id' => $business_id,
                    'source'      => $source,
                ];
                Dbuser::addUserDevelopCode($data);
                DbSendMessage::updateDevelopCode(['is_bind' => 2], $result['id']);
                Db::commit();
                return ['code' => '200'];
            } catch (\Exception $e) {
                exception($e);
                Db::rollback();
                return ['code' => '3009']; //修改失败
            }
        } else { //已绑定
            $has_bind = Dbuser::getUserDevelopCode(['develop_no' => $develop_no], 'uid,business_id,source', false);
            if (empty($has_bind)) {
                return ['code' => '3007'];
            }
            foreach ($has_bind as $key => $value) {
                if ($value['uid'] != $user['id']) {
                    return ['code' => '3005'];
                }
                if ($value['business_id'] == $business_id && $value['source'] == $source) {
                    return ['code' => '3006'];
                }
            }
            Db::startTrans();
            try {
                $data = [];
                $data = [
                    'develop_no'  => $develop_no,
                    'uid'         => $user['id'],
                    'business_id' => $business_id,
                    'source'      => $source,
                ];
                Dbuser::addUserDevelopCode($data);
                Db::commit();
                return ['code' => '200'];
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => '3009']; //修改失败
            }
        }
    }

    public function getuserBindDevelopCode($develop_no) {
        $has_bind = Dbuser::getUserDevelopCode(['develop_no' => $develop_no], 'id,uid,business_id,source', false);
        if (empty($has_bind)) {
            return ['code' => '3000'];
        }
        foreach ($has_bind as $key => $value) {
            $has_bind[$key]['nick_name'] = DbUser::getUserInfo(['id' => $value['uid']], 'nick_name', true)['nick_name'];
        }
        return ['code' => '200', 'data' => $has_bind];
    }

    public function deluserBindDevelopCode($id) {
        $has_bind = Dbuser::getUserDevelopCode(['id' => $id], 'uid,business_id,source,develop_no', true);
        if (empty($has_bind)) {
            return ['code' => '3000'];
        }
        Db::startTrans();
        try {

            Dbuser::delUserDevelopCode($id);
            if (!Dbuser::getUserDevelopCode(['develop_no' => $has_bind['develop_no']], 'uid,business_id,source,develop_no', false)) {
                $result = DbSendMessage::getDevelopCode(['develop_no' => $has_bind['develop_no']], 'id,develop_no,is_bind', true);
                DbSendMessage::updateDevelopCode(['is_bind' => 1], $result['id']);
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            // exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getUserMultimediaTemplate($page, $pageNum) {
        $offset = $pageNum * ($page - 1);
        $result = DbSendMessage::getUserMultimediaTemplate([], '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
        foreach ($result as $key => $value) {
            $result[$key]['multimedia_frame'] = DbSendMessage::getUserMultimediaTemplateFrame(['multimedia_template_id' => $value['id']], '*', false, ['num' => 'asc']);
        }
        $totle = DbSendMessage::countUserMultimediaTemplate([]);
        return ['code' => '200', 'totle' => $totle, 'result' => $result];
    }

    public function auditUserMultimediaTemplatel($id, $status) {
        $result = DbSendMessage::getUserMultimediaTemplate(['id' => $id], '*', true);
        if (empty($result)) {
            return ['code' => '3001'];
        }
        if ($result['status'] != 1) {
            return ['code' => '3003'];
        }
        Db::startTrans();
        try {
            DbSendMessage::editUserMultimediaTemplate(['status' => $status], $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getSflSendTask($page, $pageNum, $id = 0, $template_id = '', $task_content = '', $mseeage_id = '', $mobile = '', $start_time = 0, $end_time = 0) {
        $offset = ($page - 1) * $pageNum;
        if ($offset < 0) {
            return ['code' => 200, 'total' => '0', 'data' => []];
        }
        if (!empty($id)) {
            $result = DbSendMessage::getSflSendTask(['id' => $id], '*', true);
            return ['code' => 200, 'total' => '1', 'data' => $result];
        } else {
            $where = [];
            if (!empty($template_id)) {
                array_push($where, ['template_id', '=', $template_id]);
            }

            if (!empty($task_content)) {
                array_push($where, ['task_content', 'LIKE', '%' . $task_content . '%']);
            }

            if (!empty($mseeage_id)) {
                array_push($where, ['mseeage_id', '=', $mseeage_id]);
            }

            if (!empty($mobile)) {
                array_push($where, ['mobile', '=', $mobile]);
            }

            if (!empty($start_time)) {
                array_push($where, ['create_time', '>=', $start_time]);
            }

            if (!empty($end_time)) {
                array_push($where, ['create_time', '<=', $end_time]);
            }

            $result = DbSendMessage::getSflSendTask($where, '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
            $total  = DbSendMessage::countSflSendTask($where);
            return ['code' => 200, 'total' => $total, 'data' => $result];
        }
    }

    public function auditSflSendTask($template_id, $free_trial, $start_time, $end_time) {
        $where = [];
        $where = [
            ['template_id', '=', $template_id],
            ['free_trial', '=', 1],
            ['create_time', '>=', $start_time],
            ['create_time', '<=', $end_time],
        ];
        $task = DbSendMessage::getSflSendTask($where, 'id', false);
        //  echo Db::getlastsql();die;
        // print_r($task);die;
        $updateAll = [];
        $ids       = [];
        foreach ($task as $key => $value) {
            $update = [];
            $update = [
                'id'         => $value['id'],
                'free_trial' => $free_trial,
            ];

            $updateAll[] = $update;
            $ids[]       = $value['id'];
        }

        Db::startTrans();
        try {
            $res = DbSendMessage::saveAllSflSendTask($updateAll);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }

    }

    public function distributionSflSendTaskChannel($template_id, $yidong_channel_id, $liantong_channel_id, $dianxin_channel_id, $start_time, $end_time) {
        $where = [];
        $where = [
            ['template_id', '=', $template_id],
            ['free_trial', '=', 2],
            ['create_time', '>=', $start_time],
            ['create_time', '<=', $end_time],
            ['yidong_channel_id', '=', 0],
            ['liantong_channel_id', '=', 0],
            ['dianxin_channel_id', '=', 0],
        ];
        $task      = DbSendMessage::getSflSendTask($where, 'id', false);
        $updateAll = [];
        $ids       = [];
        foreach ($task as $key => $value) {
            $update = [];
            $update = [
                'id'                  => $value['id'],
                'yidong_channel_id'   => $yidong_channel_id,
                'liantong_channel_id' => $liantong_channel_id,
                'dianxin_channel_id'  => $dianxin_channel_id,
            ];

            $updateAll[] = $update;
            $ids[]       = $value['id'];
        }
        Db::startTrans();
        try {
            $res = DbSendMessage::saveAllSflSendTask($updateAll);
            Db::commit();
            foreach ($ids as $key => $value) {
                $res = $this->redis->rpush("index:meassage:sflmessage:sendtask", $value);
            }
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }
    }

    public function auditOneSflSendTask($id, $free_trial) {
        $task = DbSendMessage::getSflSendTask(['id' => $id, 'free_trial' => 1], 'id', true);
        if (empty($task)) {
            return ['code' => '3001'];
        }
        // echo Db::getlastsql();die;
        Db::startTrans();
        try {
            DbSendMessage::editSflSendTask(['free_trial' => $free_trial], $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributionOneSflSendTaskChannel($id, $yidong_channel_id, $liantong_channel_id, $dianxin_channel_id) {
        $task = DbSendMessage::getSflSendTask(['id' => $id, 'free_trial' => 2, 'yidong_channel_id' => 0, 'liantong_channel_id' => 0, 'dianxin_channel_id' => 0], 'id', true);
        if (empty($task)) {
            return ['code' => '3001'];
        }

        Db::startTrans();
        try {
            DbSendMessage::editSflSendTask(['yidong_channel_id' => $yidong_channel_id, 'liantong_channel_id' => $liantong_channel_id, 'dianxin_channel_id' => $dianxin_channel_id], $id);
            Db::commit();
            $res = $this->redis->rpush("index:meassage:sflmessage:sendtask", $id);
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }
    }

    public function getSflSendMulTask($page, $pageNum, $id = 0, $sfl_relation_id = '', $mseeage_id = '', $mobile = '', $start_time = 0, $end_time = 0) {
        $offset = ($page - 1) * $pageNum;
        if ($offset < 0) {
            return ['code' => 200, 'total' => '0', 'data' => []];
        }
        if (!empty($id)) {
            $result   = DbSendMessage::getSflMultimediaMessage(['id' => $id], '*', true);
            // echo Db::getLastSQL();die;
            $mul      = DbSendMessage::getSflMultimediaTemplate(['sfl_relation_id' => $result['sfl_relation_id']], '*', true);
            $fram     = DbSendMessage::getSflMultimediaTemplateFrame(['sfl_multimedia_template_id' => $mul['id'], 'sfl_model_id' => $mul['sfl_model_id']], '*', false);
            $variable = json_decode($result['variable'], true);
            foreach ($fram as $key => $value) {
                if (!empty($value['content'])) {
                    foreach ($variable as $vkey => $val) {
                        $fram[$key]['content'] = str_replace($vkey, $val, $fram[$key]['content']);
                    }
                }
            }

            return ['code' => 200, 'total' => '1', 'data' => $result, 'mul' => $mul, 'fram' => $fram];
        } else {
            $where = [];
            if (!empty($sfl_relation_id)) {
                array_push($where, ['sfl_relation_id', '=', $sfl_relation_id]);
            }

            if (!empty($mseeage_id)) {
                array_push($where, ['mseeage_id', '=', $mseeage_id]);
            }

            if (!empty($mobile)) {
                array_push($where, ['mobile', '=', $mobile]);
            }

            if (!empty($start_time)) {
                array_push($where, ['create_time', '>=', $start_time]);
            }

            if (!empty($end_time)) {
                array_push($where, ['create_time', '<=', $end_time]);
            }
            $result = DbSendMessage::getSflMultimediaMessage($where, '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
            $total  = DbSendMessage::countSflMultimediaMessage($where);
            return ['code' => 200, 'total' => $total, 'data' => $result];
        }
    }

    public function auditSflMulSendTask($sfl_relation_id, $free_trial, $start_time, $end_time) {
        $where = [];
        $where = [
            ['sfl_relation_id', '=', $sfl_relation_id],
            ['free_trial', '=', 1],
            ['create_time', '>=', $start_time],
            ['create_time', '<=', $end_time],
        ];
        $task = DbSendMessage::getSflMultimediaMessage($where, 'id', false);
        //  echo Db::getlastsql();die;
        // print_r($task);die;
        $updateAll = [];
        $ids       = [];
        foreach ($task as $key => $value) {
            $update = [];
            $update = [
                'id'         => $value['id'],
                'free_trial' => $free_trial,
            ];

            $updateAll[] = $update;
            $ids[]       = $value['id'];
        }

        Db::startTrans();
        try {
            $res = DbSendMessage::saveSflMultimediaMessage($updateAll);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributionSflMulSendTaskChannel($sfl_relation_id, $yidong_channel_id, $liantong_channel_id, $dianxin_channel_id, $start_time, $end_time) {
        $where = [];
        $where = [
            ['sfl_relation_id', '=', $sfl_relation_id],
            ['free_trial', '=', 2],
            ['create_time', '>=', $start_time],
            ['create_time', '<=', $end_time],
            ['yidong_channel_id', '=', 0],
            ['liantong_channel_id', '=', 0],
            ['dianxin_channel_id', '=', 0],
        ];
        $task      = DbSendMessage::getSflMultimediaMessage($where, 'id', false);
        $updateAll = [];
        $ids       = [];
        foreach ($task as $key => $value) {
            $update = [];
            $update = [
                'id'                  => $value['id'],
                'yidong_channel_id'   => $yidong_channel_id,
                'liantong_channel_id' => $liantong_channel_id,
                'dianxin_channel_id'  => $dianxin_channel_id,
            ];

            $updateAll[] = $update;
            $ids[]       = $value['id'];
        }
        Db::startTrans();
        try {
            $res = DbSendMessage::saveSflMultimediaMessage($updateAll);
            Db::commit();
            foreach ($ids as $key => $value) {
                $res = $this->redis->rpush("index:meassage:sflmulmessage:sendtask", $value);
            }
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }
    }

    public function auditOneSflMulSendTask($id, $free_trial){
        $task = DbSendMessage::getSflMultimediaMessage(['id' => $id, 'free_trial' => 1], 'id', true);
        if (empty($task)) {
            return ['code' => '3001'];
        }
        // echo Db::getlastsql();die;
        Db::startTrans();
        try {
            DbSendMessage::editSflMultimediaMessage(['free_trial' => $free_trial], $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributionOneSflMulSendTaskChannel($id, $yidong_channel_id, $liantong_channel_id, $dianxin_channel_id) {
        $task = DbSendMessage::getSflMultimediaMessage(['id' => $id, 'free_trial' => 2, 'yidong_channel_id' => 0, 'liantong_channel_id' => 0, 'dianxin_channel_id' => 0], 'id', true);
        if (empty($task)) {
            return ['code' => '3001'];
        }
        Db::startTrans();
        try {
            DbSendMessage::editSflMultimediaMessage(['yidong_channel_id' => $yidong_channel_id, 'liantong_channel_id' => $liantong_channel_id, 'dianxin_channel_id' => $dianxin_channel_id], $id);
            Db::commit();
            $res = $this->redis->rpush("index:meassage:sflmulmessage:sendtask", $id);
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }
    }

    public function numberDetection(){
        $secret_id = '06FDC4A71F5E1FDE4C061DBA653DD2A5';
        $secret_key = 'ef0587df-86dc-459f-ad82-41c6446b27a5';
        $api = 'https://api.yunzhandata.com/api/deadnumber/v1.0/detect?sig=';
        $ts =date("YmdHis",time());
        $sig = sha1($secret_id . $secret_key . $ts);
        // echo $sig;
        $mobile = '15201926171';
        // return $this->encrypt($mobile, $secret_id);
        $en_mobile = $this->encrypt($mobile, $secret_id);
        // echo $en_mobile;
        $api = $api.$sig."&sid=" .$secret_id."&skey=" .$secret_key."&ts=".$ts;

        $data = [];
        $data = [
            // 'sig' => $sig,
            // 'sid' => $secret_id,
            // 'skey' => $secret_key,
            // 'ts' => $ts,
            'mobiles' => [
                $en_mobile
            ]
        ];
        $headers = [
            'Authorization:'.base64_encode($secret_id.':'.$ts),'Content-Type:application/json'
        ];
        // echo base64_decode('MDZGREM0QTcxRjVFMUZERTRDMDYxREJBNjUzREQyQTU6MTU5MTAwNzE5Ng==');
      
        $data = $this->sendRequest2($api,'post',$data,$headers);
        // print_r(json_decode($data),true);die;
        // print_r($data);die;
        $result = json_decode($data,true);
        return $result;
    }

    function sendRequest2($requestUrl, $method = 'get', $data = [],$headers)
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

       /**
    *
    * @param string $string 需要加密的字符串
    * @param string $key 密钥
    * @return string
    */
    public static function encrypt($string, $key)
    {
        // 对接java，服务商做的AES加密通过SHA1PRNG算法（只要password一样，每次生成的数组都是一样的），Java的加密源码翻译php如下：
        $key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);

        // openssl_encrypt 加密不同Mcrypt，对秘钥长度要求，超出16加密结果不变
        $data = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

        $data = strtoupper(bin2hex($data));
        // print_r($data);
        return $data;
    }

    public function hasNumber(){
        
    }

    public function getUserSupMessageTemplate($page, $pageNum){
        $offset = $pageNum * ($page - 1);
        $result = DbSendMessage::getUserSupMessageTemplate([], '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
        foreach ($result as $key => $value) {
            // $result[$key]['multimedia_frame'] = DbSendMessage::getUserSupMessageTemplateFrame(['multimedia_template_id' => $value['id']], '*', false, ['num' => 'asc']);
           $multimedia_frame = DbSendMessage::getUserSupMessageTemplateFrame(['multimedia_template_id' => $value['id']], '*', false, ['num' => 'asc']);
           foreach ($multimedia_frame as $mkey => $mvalue) {
                if ($mvalue['type'] == 2) {
                    $multimedia_frame[$mkey]['content'] = Config::get('qiniu.domain') . '/' . $mvalue['content'];
                }
                if ($mvalue['type'] == 3) {
                    $multimedia_frame[$mkey]['content'] = Config::get('qiniu.videodomain') . '/' .  $mvalue['content'];
                }
                if ($mvalue['type'] == 4) {
                    $multimedia_frame[$mkey]['content'] = Config::get('qiniu.videodomain') . '/' .  $mvalue['content'];
                }
           }
           $result[$key]['multimedia_frame'] = $multimedia_frame;
        }
        $totle = DbSendMessage::countUserSupMessageTemplate([]);
        return ['code' => '200', 'totle' => $totle, 'result' => $result];
    }



    public function auditUserSupMessageTemplate($id, $status) {
        $result = DbSendMessage::getUserSupMessageTemplate(['id' => $id], '*', true);
        if (empty($result)) {
            return ['code' => '3001'];
        }
        if ($result['status'] != 1) {
            return ['code' => '3003'];
        }
        Db::startTrans();
        try {
            DbSendMessage::editUserSupMessageTemplate(['status' => $status], $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getSupMessageTask($page, $pageNum, $id = 0, $title = '', $free_trial = 0, $send_status = 0) {
        $offset = ($page - 1) * $pageNum;
        $time = strtotime('-4 days',time());
        // echo $time;die;
        $where = [];
        array_push($where,['create_time','>=',$time]);
        $offset = ($page - 1) * $pageNum;
        if ($free_trial) {
            array_push($where,['free_trial','=',$free_trial]);
        }
        if ($send_status) {
            array_push($where,['send_status','=',$send_status]);
        }
        $where  = [];
        if (!empty($id)) {
            $result            = DbSendMessage::getUserSupMessage(['id' => $id], '*', true);
            $result['content'] = DbSendMessage::getUserSupMessageFrame(['multimedia_message_id' => $id], '*', false, ['num' => 'asc']);
        } else {
            /* if (!empty($title)) {
                array_push($where, ['title', 'like', '%' . $title . '%']);
            } */
            $result = DbSendMessage::getUserSupMessage($where, '*', false, '', $offset . ',' . $pageNum);
            foreach ($result as $key => $value) {
                $result[$key]['content'] = DbSendMessage::getUserSupMessageFrame(['multimedia_message_id' => $value['id']], '*', false, ['num' => 'asc']);
            }
        }
        $total = DbSendMessage::countUserSupMessageFrame($where);
        if ($id) {
            $total = 1;
        }

        return ['code' => '200', 'data' => $result, 'total' => $total];
    }

    public function auditSupMessageTask($effective_id = [], $free_trial) {
        // print_r($effective_id);die;
        $userchannel = DbSendMessage::getUserSupMessage([['id', 'in', join(',', $effective_id)]], 'id,task_no,send_msg_id,uid,mobile_content,real_num,free_trial,template_id,submit_content', false);

        if (empty($userchannel)) {
            return ['code' => '3001'];
        }
        $real_effective_id = [];
        $INTERCEPT_num = [];
        // $receipt = $redis->rPush('index:meassage:code:user:receive:168','{"task_no":"bus20063022452104364246","status_message":"NOROUTE","message_info":"\u53d1\u9001\u6210\u529f","mobile":"15103230163","msg_id":"70000500020200630224527169053","send_time":"2020-06-30 22:45:28","smsCount":1,"smsIndex":1}');
        // print_r($userchannel);die;
        $uids = [];
        foreach ($userchannel as $key => $value) {
            if ($value['free_trial'] > 1) {
                continue;
            }
            $real_effective_id[] = $value['id'];
            if ($free_trial == 3) {
                // $INTERCEPT[] = $value['id'];
                // $res = $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' =>$usertask['id'],'deduct' => $user['multimedia_deduct']]));
                // $mobile = explode()
                
                if (!empty($value['submit_content'])) {
                    $submit_content = json_decode($value['submit_content'],true);
                    if (!empty($submit_content)) {
                        foreach ($submit_content as $skey => $svalue) {
                            // # code...
                            $res = $this->redis->rpush("index:meassage:code:user:supreceive:".$value['uid'], json_encode(['task_no' =>$value['task_no'],'msg_id' => $value['send_msg_id'],"status_message"=>"INTERCEPT","message_info" => "驳回","send_time" => date("Y-m-d H:i:s",time()),'mobile'=> $svalue['mobile']]));
                        }
                    }else{
                        $res = $this->redis->rpush("index:meassage:code:user:supreceive:".$value['uid'], json_encode(['task_no' =>$value['task_no'],'msg_id' => $value['send_msg_id'],"status_message"=>"INTERCEPT","message_info" => "驳回","send_time" => date("Y-m-d H:i:s",time())]));
                    }
                }else{
                    $mobiles = explode(',',$value['mobile_content']);
                    foreach ($mobiles as $mkey => $mvalue) {
                        $res = $this->redis->rpush("index:meassage:code:user:supreceive:".$value['uid'], json_encode(['task_no' =>$value['task_no'],'msg_id' => $value['send_msg_id'],"status_message"=>"INTERCEPT","message_info" => "驳回","send_time" => date("Y-m-d H:i:s",time()),'mobile'=> $mvalue]));
                    }
                }
                if (isset($INTERCEPT_num[$value['uid']])) {
                    $INTERCEPT_num[$value['uid']] += $value['real_num'];
                }else{
                     $INTERCEPT_num[$value['uid']] = $value['real_num'];
                     $uids[] = $value['uid'];
                }
                
            }
        }
        if (empty($real_effective_id)) {
            return ['code' => '3002', 'msg' => '没有需要审核的任务'];
        }
        $where_equitise = [
            ['uid', 'IN', join(',', $uids)], ['business_id', '=', 11]
        ];
        $user_equities = DbAdministrator::getUserEquities($where_equitise, 'id,uid,num_balance', false);
        /* print_r($user_equities);die;
        foreach ($INTERCEPT_num as $rkey => $rvalue) {
            
        } */
        Db::startTrans();
        try {
            foreach ($real_effective_id as $real => $efid) {
                DbSendMessage::editUserSupMessage(['free_trial' => $free_trial], $efid);
            }
            if ($free_trial == 3) {
                foreach ($user_equities as $key => $value) {
                    DbAdministrator::modifyBalance($value['id'], $INTERCEPT_num[$value['uid']], 'inc');
                }
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributionSupMessageChannel($effective_id = [], $yidong_channel_id, $liantong_channel_id, $dianxin_channel_id, $business_id) {
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $yidong_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $liantong_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3011'];
        }
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $dianxin_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3012'];
        }
        $usertask = DbSendMessage::getUserSupMessage([['id', 'in', join(',', $effective_id)]], 'id,uid,mobile_content,submit_content,free_trial,send_num,yidong_channel_id,liantong_channel_id,dianxin_channel_id', false);
        if (empty($usertask)) {
            return ['code' => '3001'];
        }
        $num               = 0;
        $uids              = [];
        $real_effective_id = [];
        $real_usertask     = [];
        foreach ($usertask as $key => $value) {
            if (empty($uids)) {
                $uids[] = $value['uid'];
            } elseif (!in_array($value['uid'], $uids)) {
                $uids[] = $value['uid'];
            }
            // print_r($value);
            if ($value['free_trial'] == 2 && !$value['yidong_channel_id']) {
                $real_length     = 1;
                $real_usertask[] = $value;
                $mobilesend      = explode(',', $value['mobile_content']);
                // $send_length     = mb_strlen($value['task_content'], 'utf8');
                // if ($send_length > 70) {
                //     $real_length = ceil($send_length / 67);
                // }
                $num += ($real_length * $value['send_num']);
                // foreach ($mobilesend as $key => $kvalue) {

                // }
            }
        }
        // die;
        // print_r($uids);die;
        if (count($uids) > 1) {
            return ['code' => '3008', 'msg' => '一批只能同时分配一个用户的营销任务'];
        }
        if (empty($real_usertask)) {
            return ['code' => '3010', 'msg' => '待分配的批量任务未空（提交了一批未审核的批量任务）'];
        }
        $userEquities = DbAdministrator::getUserEquities(['uid' => $uids[0], 'business_id' => $business_id], 'id,agency_price,num_balance', true);
        if (empty($userEquities)) {
            return ['code' => '3005'];
        }

        $user = DbUser::getUserInfo(['id' => $uids[0]], 'id,reservation_service,user_status,supmessage_deduct', true);
        if ($user['user_status'] != 2) {
            return ['code' => '3006'];
        }
        // print_r($num);die;
        /* if ($num > $userEquities['num_balance'] && $user['reservation_service'] != 2) {
        return ['code' => '3007'];
        } */
        $free_trial = 2;
        if ($userEquities['agency_price'] < $channel['channel_price']) {
            $free_trial = 4;
        }
        Db::startTrans();
        try {

            // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
            foreach ($real_usertask as $key => $value) {
                DbSendMessage::editUserMultimediaMessage(['free_trial' => $free_trial, 'yidong_channel_id' => $yidong_channel_id, 'liantong_channel_id' => $liantong_channel_id, 'dianxin_channel_id' => $dianxin_channel_id, 'send_status' => 2], $value['id']);
            }
            if ($free_trial == 2) {
                foreach ($real_usertask as $real => $usertask) {
                    $res = $this->redis->rpush("index:meassage:supmessage:sendtask", json_encode(['id' =>$usertask['id'],'deduct' => $user['supmessage_deduct']]));
                }
            }

            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }
}
