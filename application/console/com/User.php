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
}
