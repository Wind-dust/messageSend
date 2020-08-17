<?php

namespace app\common\db\index;

use app\common\model\UserMultimediaMessage;
use app\common\model\UserMultimediaMessageFrame;
use app\common\model\UserMultimediaMessageLog;
use app\common\model\ModelTemeplate;
use app\common\model\UserModel;
use app\common\model\SensitiveWord;
use app\common\model\UserSignature;
use app\common\model\DevelopCode;
use app\common\model\UserMultimediaTemplate;
use app\common\model\UserMultimediaTemplateFrame;
use app\common\model\SflSendTask;
use app\common\model\SflMultimediaMessage;
use app\common\model\SflMultimediaTemplateFrame;
use app\common\model\SflMultimediaTemplate;
use app\common\model\UserUpriver;
use app\common\model\NumberSource;
use app\common\model\UserSupMessage;
use app\common\model\UserSupMessageFrame;
use app\common\model\UserSupMessageLog;
use app\common\model\UserSupMessageTemplate;
use app\common\model\UserSupMessageTemplateFrame;
use app\common\model\UserSupMessageTemplateThirdReport;
use think\Db;

class DbSendMessage extends Db
{

    /**
     * 获取彩信
     * @param $where
     * @return array
     */
    public function getUserMultimediaMessage($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserMultimediaMessage::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserMultimediaMessage($where)
    {
        return UserMultimediaMessage::where($where)->count();
    }

    public function addUserMultimediaMessage($data)
    {
        $UserMultimediaMessage = new UserMultimediaMessage;
        $UserMultimediaMessage->save($data);
        return $UserMultimediaMessage->id;
    }

    public function editUserMultimediaMessage($data, $id)
    {
        $UserMultimediaMessage = new UserMultimediaMessage;
        return $UserMultimediaMessage->save($data, ['id' => $id]);
    }

    /**
     * 获取彩信内容帧
     * @param $where
     * @return array
     */
    public function getUserMultimediaMessageFrame($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserMultimediaMessageFrame::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserMultimediaMessageFrame($where)
    {
        return UserMultimediaMessageFrame::where($where)->count();
    }


    public function addUserMultimediaMessageFrame($data)
    {
        $UserMultimediaMessageFrame = new UserMultimediaMessageFrame;
        $UserMultimediaMessageFrame->save($data);
        return $UserMultimediaMessageFrame->id;
    }

    public function editUserMultimediaMessageFrame($data, $id)
    {
        $UserMultimediaMessageFrame = new UserMultimediaMessageFrame;
        return $UserMultimediaMessageFrame->save($data, ['id' => $id]);
    }

    public function getUserMultimediaMessageLog($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserMultimediaMessageLog::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserMultimediaMessageLog($where)
    {
        return UserMultimediaMessageLog::where($where)->count();
    }

    public function addUserMultimediaMessageLog($data)
    {
        $UserMultimediaMessageLog = new UserMultimediaMessageLog;
        $UserMultimediaMessageLog->save($data);
        return $UserMultimediaMessageLog->id;
    }

    public function saveAllUserMultimediaMessageLog($data)
    {
        $UserMultimediaMessageLog = new UserMultimediaMessageLog;
        return $UserMultimediaMessageLog->saveAll($data);
    }

    public function editUserMultimediaMessageLog($data, $id)
    {
        $UserMultimediaMessageLog = new UserMultimediaMessageLog;
        return $UserMultimediaMessageLog->save($data, ['id' => $id]);
    }

    public function getModelTemeplate($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = ModelTemeplate::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function addModelTemeplate($data)
    {
        $ModelTemeplate = new ModelTemeplate;
        $ModelTemeplate->save($data);
        return $ModelTemeplate->id;
    }

    public function countModelTemeplate($where)
    {
        return ModelTemeplate::where($where)->count();
    }

    public function editModelTemeplate($data, $id)
    {
        $ModelTemeplate = new ModelTemeplate;
        return $ModelTemeplate->save($data, ['id' => $id]);
    }

    public function getUserModel($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserModel::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function addUserModel($data)
    {
        $UserModel = new UserModel;
        $UserModel->save($data);
        return $UserModel->id;
    }

    public function countUserModel($where)
    {
        return UserModel::where($where)->count();
    }

    public function editUserModel($data, $id)
    {
        $UserModel = new UserModel;
        return $UserModel->save($data, ['id' => $id]);
    }

    public function getSensitiveWord($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = SensitiveWord::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function getUserSignature($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserSignature::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function addUserSignature($data)
    {
        $UserSignature = new UserSignature;
        $UserSignature->save($data);
        return $UserSignature->id;
    }

    public function countUserSignature($where)
    {
        return UserSignature::where($where)->count();
    }

    public function editUserSignature($data, $id)
    {
        $UserSignature = new UserSignature;
        return $UserSignature->save($data, ['id' => $id]);
    }

    public function getDevelopCode($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = DevelopCode::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countDevelopCode($where)
    {
        return DevelopCode::where($where)->count();
    }

    public function updateDevelopCode($data, $id)
    {
        $DevelopCode = new DevelopCode;
        return $DevelopCode->save($data, ['id' => $id]);
    }

    public function getRandomDevelopCode($where, $field, $row = false, $limit = '')
    {
        $obj = DevelopCode::where($where)->orderRand("(" . $field . ")");
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    public function getUserMultimediaTemplate($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserMultimediaTemplate::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function addUserMultimediaTemplate($data)
    {
        $UserMultimediaTemplate = new UserMultimediaTemplate;
        $UserMultimediaTemplate->save($data);
        return $UserMultimediaTemplate->id;
    }

    public function countUserMultimediaTemplate($where)
    {
        return UserMultimediaTemplate::where($where)->count();
    }

    public function editUserMultimediaTemplate($data, $id)
    {
        $UserMultimediaTemplate = new UserMultimediaTemplate;
        return $UserMultimediaTemplate->save($data, ['id' => $id]);
    }

    public function getUserMultimediaTemplateFrame($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserMultimediaTemplateFrame::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function addUserMultimediaTemplateFrame($data)
    {
        $UserMultimediaTemplateFrame = new UserMultimediaTemplateFrame;
        $UserMultimediaTemplateFrame->save($data);
        return $UserMultimediaTemplateFrame->id;
    }

    public function countUserMultimediaTemplateFrame($where)
    {
        return UserMultimediaTemplateFrame::where($where)->count();
    }

    public function editUserMultimediaTemplateFrame($data, $id)
    {
        $UserMultimediaTemplateFrame = new UserMultimediaTemplateFrame;
        return $UserMultimediaTemplateFrame->save($data, ['id' => $id]);
    }

    public function getSflSendTask($where, $field, $row = false, $orderBy = '', $limit = '')
    {
        $obj = SflSendTask::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countSflSendTask($where)
    {
        return SflSendTask::where($where)->count();
    }

    public function editSflSendTask($data, $id)
    {
        $UserSendTask = new SflSendTask;
        return $UserSendTask->save($data, ['id' => $id]);
    }

    public function saveAllSflSendTask($data)
    {
        $SflSendTask = new SflSendTask;
        $rs = $SflSendTask->saveAll($data);
        return $rs;
    }

    public function getSflMultimediaMessage($where, $field, $row = false, $orderBy = '', $limit = '')
    {
        $obj = SflMultimediaMessage::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countSflMultimediaMessage($where)
    {
        return SflMultimediaMessage::where($where)->count();
    }

    public function editSflMultimediaMessage($data, $id)
    {
        $SflMultimediaMessage = new SflMultimediaMessage;
        return $SflMultimediaMessage->save($data, ['id' => $id]);
    }

    public function saveSflMultimediaMessage($data)
    {
        $SflMultimediaMessage = new SflMultimediaMessage;
        $rs = $SflMultimediaMessage->saveAll($data);
        return $rs;
    }

    public function getSflMultimediaTemplate($where, $field, $row = false, $orderBy = '', $limit = '')
    {
        $obj = SflMultimediaTemplate::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countSflMultimediaTemplate($where)
    {
        return SflMultimediaTemplate::where($where)->count();
    }

    public function editSflMultimediaTemplate($data, $id)
    {
        $UserSendTask = new SflMultimediaTemplate;
        return $UserSendTask->save($data, ['id' => $id]);
    }

    public function getSflMultimediaTemplateFrame($where, $field, $row = false, $orderBy = '', $limit = '')
    {
        $obj = SflMultimediaTemplateFrame::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countSflMultimediaTemplateFrame($where)
    {
        return SflMultimediaTemplateFrame::where($where)->count();
    }

    public function editSflMultimediaTemplateFrame($data, $id)
    {
        $UserSendTask = new SflMultimediaTemplateFrame;
        return $UserSendTask->save($data, ['id' => $id]);
    }

    public function getUserUpriver($where, $field, $row = false, $orderBy = '', $limit = '')
    {
        $UserUpriver = new UserUpriver;
        $obj = UserUpriver::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function addUserUpriver($data)
    {
        $UserUpriver = new UserUpriver;
        $UserUpriver->save($data);
        // $UserModel->save($data);
        // return $UserModel->id;
        return $UserUpriver->id;
    }

    public function getNumberSource($where, $field, $row = false, $orderBy = '', $limit = '')
    {
        $NumberSource = new NumberSource;
        $obj = NumberSource::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

   /**
     * 获取超级彩信（视频）
     * @param $where
     * @return array
     */
    public function getUserSupMessage($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserSupMessage::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserSupMessage($where)
    {
        return UserSupMessage::where($where)->count();
    }

    public function addUserSupMessage($data)
    {
        $UserSupMessage = new UserSupMessage;
        $UserSupMessage->save($data);
        return $UserSupMessage->id;
    }

    public function editUserSupMessage($data, $id)
    {
        $UserSupMessage = new UserSupMessage;
        return $UserSupMessage->save($data, ['id' => $id]);
    }

    /**
     * 获取超级彩信（视频）内容
     * @param $where
     * @return array
     */
    public function getUserSupMessageFrame($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserSupMessageFrame::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserSupMessageFrame($where)
    {
        return UserSupMessageFrame::where($where)->count();
    }

    public function addUserSupMessageFrame($data)
    {
        $UserSupMessageFrame = new UserSupMessageFrame;
        $UserSupMessageFrame->save($data);
        return $UserSupMessageFrame->id;
    }

    public function editUserSupMessageFrame($data, $id)
    {
        $UserSupMessageFrame = new UserSupMessageFrame;
        return $UserSupMessageFrame->save($data, ['id' => $id]);
    }

    /**
     * 获取超级彩信（视频）发送日志
     * @param $where
     * @return array
     */
    public function getUserSupMessageLog($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserSupMessageLog::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserSupMessageLog($where)
    {
        return UserSupMessageLog::where($where)->count();
    }

    public function addUserSupMessageLog($data)
    {
        $UserSupMessageLog = new UserSupMessageLog;
        $UserSupMessageLog->save($data);
        return $UserSupMessageLog->id;
    }

    public function editUserSupMessageLog($data, $id)
    {
        $UserSupMessageLog = new UserSupMessageLog;
        return $UserSupMessageLog->save($data, ['id' => $id]);
    }

    /**
     * 获取超级彩信（视频）模板
     * @param $where
     * @return array
     */
    public function getUserSupMessageTemplate($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserSupMessageTemplate::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserSupMessageTemplate($where)
    {
        return UserSupMessageTemplate::where($where)->count();
    }

    public function addUserSupMessageTemplate($data)
    {
        $UserSupMessageTemplate = new UserSupMessageTemplate;
        $UserSupMessageTemplate->save($data);
        return $UserSupMessageTemplate->id;
    }

    public function editUserSupMessageTemplate($data, $id)
    {
        $UserSupMessageTemplate = new UserSupMessageTemplate;
        return $UserSupMessageTemplate->save($data, ['id' => $id]);
    }

    /**
     * 获取超级彩信（视频）模板内容
     * @param $where
     * @return array
     */
    public function getUserSupMessageTemplateFrame($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserSupMessageTemplateFrame::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserSupMessageTemplateFrame($where)
    {
        return UserSupMessageTemplateFrame::where($where)->count();
    }

    public function addUserSupMessageTemplateFrame($data)
    {
        $UserSupMessageTemplateFrame = new UserSupMessageTemplateFrame;
        $UserSupMessageTemplateFrame->save($data);
        return $UserSupMessageTemplateFrame->id;
    }

    public function editUserSupMessageTemplateFrame($data, $id)
    {
        $UserSupMessageTemplateFrame = new UserSupMessageTemplateFrame;
        return $UserSupMessageTemplateFrame->save($data, ['id' => $id]);
    }

    /**
     * 获取超级彩信（视频）通道报备
     * @param $where
     * @return array
     */
    public function getUserSupMessageTemplateThirdReport($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
    {
        $obj = UserSupMessageTemplateThirdReport::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countUserSupMessageTemplateThirdReport($where)
    {
        return UserSupMessageTemplateThirdReport::where($where)->count();
    }

    public function addUserSupMessageTemplateThirdReport($data)
    {
        $UserSupMessageTemplateThirdReport = new UserSupMessageTemplateThirdReport;
        $UserSupMessageTemplateThirdReport->save($data);
        return $UserSupMessageTemplateThirdReport->id;
    }

    public function editUserSupMessageTemplateThirdReport($data, $id)
    {
        $UserSupMessageTemplateThirdReport = new UserSupMessageTemplateThirdReport;
        return $UserSupMessageTemplateThirdReport->save($data, ['id' => $id]);
    }
}
