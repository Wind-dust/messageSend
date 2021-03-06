<?php

namespace app\common\db\index;

use app\common\model\UserMultimediaMessage;
use app\common\model\UserMultimediaMessageFrame;
use app\common\model\UserMultimediaMessageLog;
use app\common\model\ModelTemeplate;
use app\common\model\UserModel;
use app\common\model\SensitiveWord;
use app\common\model\UserSignature;
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

    public function getUserUserMultimediaMessageLog($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '')
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
}
