<?php

namespace app\common\action\admin;

use app\facade\DbAdministrator;
use app\facade\DbSendMessage;
use app\facade\DbUser;
use PHPExcel;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use PHPExcel_Writer_Excel2007;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Fill;
use think\Db;

class Message extends CommonIndex
{
    /**
     * @param $page
     * @param $pageNum
     * @return array
     * @author rzc
     */
    public function  getMultimediaMessageTask($page, $pageNum, $id = 0, $title = '')
    {
        $offset = ($page - 1) * $pageNum;
        $where = [];
        if (!empty($id)) {
            $result = DbSendMessage::getUserMultimediaMessage(['id' => $id], '*', true);
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

        return ['code' => '200', 'data' => $result];
    }

    public function auditMultimediaMessageTask($effective_id = [], $free_trial)
    {
        // print_r($effective_id);die;
        $userchannel = DbSendMessage::getUserMultimediaMessage([['id', 'in', join(',', $effective_id)]], 'id,mobile_content,free_trial', false);

        if (empty($userchannel)) {
            return ['code' => '3001'];
        }
        $real_effective_id = [];
        // print_r($userchannel);die;
        foreach ($userchannel as $key => $value) {
            if ($value['free_trial'] > 1) {
                continue;
            }
            $real_effective_id[] = $value['id'];
        }

        Db::startTrans();
        try {
            foreach ($real_effective_id as $real => $efid) {
                DbSendMessage::editUserMultimediaMessage(['free_trial' => $free_trial], $efid);
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributionChannel($effective_id = [], $channel_id, $business_id)
    {
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $channel_id, 'business_id' => $business_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        $usertask = DbSendMessage::getUserMultimediaMessage([['id', 'in', join(',', $effective_id)]], 'id,uid,mobile_content,task_content,free_trial,send_num,channel_id', false);
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
            if ($value['free_trial'] == 2 && !$value['channel_id']) {
                $real_length = 1;
                $real_usertask[] = $value;
                $mobilesend       = explode(',', $value['mobile_content']);
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

        $user = DbUser::getUserInfo(['id' => $uids[0]], 'id,reservation_service,user_status', true);
        if ($user['user_status'] != 2) {
            return ['code' => '3006'];
        }
        // print_r($num);die;
        if ($num > $userEquities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3007'];
        }
        $free_trial = 2;
        if ($userEquities['agency_price'] < $channel['channel_price']) {
            $free_trial = 4;
        }
        Db::startTrans();
        try {

            // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
            foreach ($real_usertask as $key => $value) {
                DbSendMessage::editUserMultimediaMessage(['free_trial' => $free_trial, 'channel_id' => $channel_id], $value['id']);
            }
            if ($free_trial == 2) {
                foreach ($real_usertask as $real => $usertask) {
                    $res = $this->redis->rpush("index:meassage:multimediamessage:sendtask", $usertask['id']);
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

    public function exportReceiptReport($id, $business_id)
    {
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
            $i = 0;
            // $phone = '';
            // $j     = '';
            while (!feof($file)) {
                $cellVal = trim(fgets($file));
                $log = json_decode($cellVal, true);
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

    public function getUserModel($page, $pageNum)
    {
        $offset = $pageNum * ($page - 1);
        $result =  DbSendMessage::getUserModel([], '*', false, '', $offset . ',' . $pageNum);
        $totle = DbSendMessage::countUserModel([]);
        return ['code' => '200', 'totle' => $totle, 'result' => $result];
    }

    public function auditUserModel($id, $status)
    {
        $result =  DbSendMessage::getUserModel(['id' => $id], '*', true);
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

    public function getUserSignature($page, $pageNum)
    {
        $offset = $pageNum * ($page - 1);
        $result =  DbSendMessage::getUserSignature([], '*', false, '', $offset . ',' . $pageNum);
        $totle = DbSendMessage::countUserSignature([]);
        return ['code' => '200', 'total' => $totle, 'result' => $result];
    }

    public function auditUserSignature($id, $audit_status)
    {
        $result =  DbSendMessage::getUserSignature(['id' => $id], '*', true);
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
}
