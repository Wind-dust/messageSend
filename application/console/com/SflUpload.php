<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;
use ZipArchive;

class SflUpload extends Pzlife {
    private $redis;

    /**
     * 数据库连接
     *
     */
    public function dbConnect($databasename) {
        if ($databasename == 'old') {
            return Db::connect(Config::get('database.db_config'));
        } else {
            return Db::connect(Config::get('database.'));
        }
    }

    /**
     * ftp 测试
     */
    public function ftpConfig() {
        return ['host' => '127.0.0.1', 'port' => '8007', 'user' => '', 'password' => ''];
    }

    public function testFtp() {
        $ftp_config = $this->ftpConfig();
        $ftp        = ftp_connect($ftp_config['host'], $ftp_config['port']);
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

    public function sflZip() {
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G

        $zip = new ZipArchive();

        $path      = realpath("") . "/uploads/SFL/";
        $path_data = $this->getDirContent($path);
        // print_r($path_data);
        if ($path_data == false) {
            exit("This Dir IS null");
        }
        try {
            foreach ($path_data as $key => $value) {
                if ($value == 'UnZip') {
                    continue;
                }
                $son_path_data = $this->getDirContent($path . $value);
                if ($son_path_data !== false) {
                  
                    foreach ($son_path_data as $skey => $svalue) {
                        $son_path = $path . $value . "/" . $svalue;
                        // $file = fopen($path.$value."/".$svalue,"r");
                        $file_info = explode('.', $svalue);
                        if ($file_info[1] == 'zip') { //需要解压
                            //开始解压
                            if ($zip->open($son_path) === true) {
                                $unpath = $path . 'UnZip' . "/".$value."/" . $file_info[0];
                                $mcw = $zip->extractTo($unpath); //解压到$route这个目录中
                                $zip->close();
                                //解压完成
                                $unzip = $this->getDirContent($unpath);
                                // print_r($unzip);die;
                                foreach ($unzip as $ukey => $uvalue) {
                                    $un_file_info = explode('.', $svalue);
                                    if ($un_file_info[1] == 'jpg') {//图片
    
                                    }
                                }
                            }
                        } else if ($file_info[1] == 'txt') {
                            $file_data = $this->readForTxt($son_path);
    
                            // print_r($file_data);die;
                        }
                    }
                }
    
            }
            
        } catch (\Exception $e) {
            exception($e);
        }
    }

    function readForTxt($path) {
        // $path = realpath("./") . "/191111.txt";
        if (!is_file($path)) {
            return false;
        }

        $file = fopen($path, "r");
        $data = array();
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            if (!empty($cellVal)) {
                $value = explode(',', $cellVal);
                array_push($data, $value);
            }
        }
        return $data;
    }

    function getDirContent($path) {
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
        $arr  = array();
        $data = scandir($path);
        foreach ($data as $value) {
            if ($value != '.' && $value != '..') {
                $arr[] = $value;
            }
        }
        return $arr;
    }

    public function sftpForSfl() {
        try
        {
            // $sftp = new SFTPConnection("localhost", 8080);
            // $sftp = new SFTPConnection("esftp.sephora.com.cn", 20981);
            // $sftp = new SFTPConnection("10.157.52.197", 20981);
            // $sftp->login("CHN-SMSDATA-sms", "TZYB@zn7");
            // $sftp->uploadFile("/CN-SMSDATA", "/tmp/to_be_received");
            $host     = "47.103.200.251";
            $prot     = "22";
            $username = "root";
            $password = "a!s^d(7)#f@g&h(9)";
            /*  $host = "esftp.sephora.com.cn";
            $prot = "20981";
            $username = "CHN-SMSDATA-sms";
            $password = "TZYB@zn7"; */
            $sftp = new SFTPConnection($host, $prot);
            $sftp->login($username, $password);
            //本地目录
            $local_directory = "/uploads/SFL/";
            //远程目录
            // $remote_directory = "/root/club776/";
            $remote_directory_host = "/CN-SMSDATA/";
            //判断远程目录是否存在
            $address = $sftp->dirExits($remote_directory_host);
            // print_r();die;
            $remote_directory_data = [];
            if ($address) {
                if (!empty($remote_directory_data)) {
                    foreach ($remote_directory_data as $key => $value) {
                        $this_directory = $remote_directory_data . $value . "/";
                        $sms            = $sftp->scanFileSystem($this_directory);
                        print_r($sms);die;
                        if (!empty($sms)) {
                            //下载文件
                            // $sftp->downFile("/root/club776/","/uploads/excel");
                            // $sftp->downFile(realpath("")."/uploads/excel/mysql.sh","/root/club776/mysql.sh");
                            foreach ($sms as $key => $value) {
                                //下载远程文件
                                $sftp->downFile(realpath("") . $local_directory . $value, $this_directory . $value);
                                //上传至七牛云
                            }
                            // ssh2_scp_recv($cn,"\"".$remote_file_name."\"",$local_path."/".$remote_file_name); //OK

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

}

class SFTPConnection {
    private $connection;
    private $sftp;

    public function __construct($host, $port = 22) {
        $this->connection = ssh2_connect($host, $port);
        if (!$this->connection) {
            throw new Exception("Could not connect to $host on port $port.");
        }

    }

    public function login($username, $password) {
        if (!ssh2_auth_password($this->connection, $username, $password)) {
            throw new Exception("Could not authenticate with username $username " .
                "and password $password.");
        }

        $this->sftp = ssh2_sftp($this->connection);
        if (!$this->sftp) {
            throw new Exception("Could not initialize SFTP subsystem.");
        }

    }

    public function uploadFile($local_file, $remote_file) {
        $sftp   = $this->sftp;
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
    public function downFile($local_file, $remote_file) {
        ssh2_scp_recv($this->connection, $remote_file, $local_file);
    }

    /**
     * 判断文件夹是否存在
     * @param string $dir  目录名称
     * @return bool
     */
    public function dirExits($dir) {
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
    public function rename($old_dir, $new_dir) {
        $is_true = ssh2_sftp_rename($this->sftp, $old_dir, $new_dir);
        return $is_true;
    }

    /**
     * 删除文件
     * @param string $dir  例子：'/home/username/dirname/filename'
     * $dir 示例：/var/file/image/404NotFound.png
     * @return bool
     */
    public function delFile($dir) {
        $is_true = ssh2_sftp_unlink($this->sftp, $dir);
        return $is_true;
    }

    /**
     * 获取文件夹下的文件
     * @param string $remote_file 文件路径 例：/var/file/image
     * @return array
     */
    public function scanFileSystem($remote_file) {
        $sftp      = $this->sftp;
        $dir       = "ssh2.sftp://$sftp$remote_file";
        $tempArray = array();
        $handle    = opendir($dir);
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
class Sftp {
    private $connection;
    private $sftp;
    public function __construct($params) {
        $host             = $params['host']; //地址
        $port             = $params['port']; //端口
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
    public function login($login_type, $username, $password = null, $pub_key = null, $pri_key = null) {
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
    public function uploadFile($local_file, $remote_file) {
        $is_true = ssh2_scp_send($this->connection, $local_file, $remote_file, 0777);
        return $is_true;
    }

}
