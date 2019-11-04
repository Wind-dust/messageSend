<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use function Qiniu\json_decode;
use think\Db;

class ServerSocket extends Pzlife {

    // private $bodyData;

    public function Service($content) {
        $contdata = $this->content($content);
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G

        $host          = $contdata['host']; //服务商ip
        $port          = $contdata['port']; //短连接端口号   17890长连接端口号
        $Source_Addr   = $contdata['Source_Addr']; //企业id  企业代码
        $Shared_secret = $contdata['Shared_secret']; //网关登录密码
        $Service_Id    = $contdata['Service_Id'];
        $Dest_Id       = $contdata['Dest_Id']; //短信接入码 短信端口号
        $Sequence_Id   = $contdata['Sequence_Id'];
        $SP_ID         = $contdata['SP_ID'];

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        /*绑定接收的套接流主机和端口,与客户端相对应*/
        if (socket_bind($socket, '127.0.0.1', 8888) == false) {
            echo 'server bind fail:' . socket_strerror(socket_last_error());
            /*这里的127.0.0.1是在本地主机测试，你如果有多台电脑，可以写IP地址*/
        }
        //监听套接流
        if (socket_listen($socket, 4) == false) {
            echo 'server listen fail:' . socket_strerror(socket_last_error());
        }
//让服务器无限获取客户端传过来的信息
        do {
            /*接收客户端传过来的信息*/
            $accept_resource = socket_accept($socket);
            /*socket_accept的作用就是接受socket_bind()所绑定的主机发过来的套接流*/

            if ($accept_resource !== false) {
                /*读取客户端传过来的资源，并转化为字符串*/
                $string = socket_read($accept_resource, 1024);
                /*socket_read的作用就是读出socket_accept()的资源并把它转化为字符串*/
                // $v = base_convert($string, 16, 2);
               
                // echo 'server receive is :' . $v . PHP_EOL; //PHP_EOL为php的换行预定义常量
                if ($string != false) {
                    $string = base_convert($string, 16, 2);
                    // $return_client = 'server receive is : ' . $string . PHP_EOL;
                    $v = base_convert($string, 16, 2);
                    $return_client ='server receive is : '. PHP_EOL. $v;
                    $v = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $string);
                    // $bodyData = socket_read($socket, $v['Total_Length'] - 12);
                    switch ($v['Command_Id'] & 0x0fffffff) {
                        case 0x00000001:
                            // $body = unpack("CStatus/a16AuthenticatorISMG/CVersion", $bodyData);//收到连接请求
                            $back_Command_Id   = 0x80000001; //连接应答
                            $bodyData = pack("C", 1);
                            break;
                        case 0x00000004;
                        $bodyData = pack("C", 1);
                            $back_Command_Id   = 0x80000004; //发送应答
                           
                            break;
                        case  0x00000008; //保持连接
                            $bodyData = pack("C", 1);
                            $back_Command_Id   = 0x80000008; //连接应答
                          
                        break;
                        default:
                            $bodyData = pack("C", 1);
                            $back_Command_Id   = 0x80000008; //连接应答
                        break;
                    }
                    $Total_Length = strlen($bodyData) + 12;
                    $headData     = pack("NNN", $Total_Length, $back_Command_Id, $Sequence_Id);
                    // socket_write($socket, $headData . $bodyData, $Total_Length);
                    print_r($v);
                    print_r($back_Command_Id);
                    /*向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端*/
                    socket_write($accept_resource, $headData . $bodyData, $Total_Length);
                    /*socket_write的作用是向socket_create的套接流写入信息，或者向socket_accept的套接流写入信息*/
                } else {
                    echo 'socket_read is fail';
                }
                /*socket_close的作用是关闭socket_create()或者socket_accept()所建立的套接流*/
                // socket_close($accept_resource);
            }
        } while (true);
        // socket_close($socket);
    }

    public function content($content) {
        if ($content == 1) { //测试
            return [
                'host'          => "127.0.0.1", //服务商ip
                'port'          => "8888", //短连接端口号   17890长连接端口号
                'Source_Addr'   => "101161", //企业id  企业代码
                'Shared_secret' => '5hsey6u9', //网关登录密码
                'Service_Id'    => "217062",
                'Dest_Id'       => "106928080159", //短信接入码 短信端口号
                'Sequence_Id'   => 1,
                'SP_ID'         => "",
            ];
        }
    }

}
