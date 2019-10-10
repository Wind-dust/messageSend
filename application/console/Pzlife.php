<?php

namespace app\console;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Env;

class Pzlife extends Command {
    protected function configure() {
        $this->setName('console')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->addArgument('params', Argument::OPTIONAL, "your params")
            ->addOption('method', null, Option::VALUE_REQUIRED, 'method')
            ->setDescription('Say Hello');
    }

    protected function execute(Input $input, Output $output) {
        $commond = trim($input->getFirstArgument());
        $name    = trim($input->getArgument('name'));
        $params  = trim($input->getArgument('params'));
//        if ($input->hasOption('city')) {
//            $city = PHP_EOL . 'From ' . $input->getOption('city');
//        } else {
//            $city = '';
//        }
//        $output->writeln(self::class);die;
        $className = 'app\console\com\\' . ucfirst($commond);
        $params    = explode('}{', rtrim(ltrim($params, '{'), '}'));
        if ($commond == 'curl') {
            $method = trim($input->getOption('method'));
            $method = !empty($method) ? $method : 'get';
            array_unshift($params, $method);
        }
        call_user_func_array([new $className(), $name], $params);
    }

    protected function post($requestUrl, $data) {
        // 初始化一个 cURL 对象
        $curl = curl_init();
        // 设置你需要抓取的URL
//        curl_setopt($curl, CURLOPT_URL, 'http://local.pzlife.com/hello/aaa/' . $paramHash);
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        // 设置header 响应头是否输出
        curl_setopt($curl, CURLOPT_HEADER, 0);

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        // 1如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 运行cURL，请求网页
        $data = curl_exec($curl);
        // 关闭URL请求
        curl_close($curl);
        // 显示获得的数据
        print_r($data);
        die;
    }

    protected function get($requestUrl) {
        // 初始化一个 cURL 对象
        $curl = curl_init();
        // 设置你需要抓取的URL
//        curl_setopt($curl, CURLOPT_URL, 'http://local.pzlife.com/hello/aaa/' . $paramHash);
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        // 设置header 响应头是否输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        // 1如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 运行cURL，请求网页
        $data = curl_exec($curl);
        // 关闭URL请求
        curl_close($curl);
        // 显示获得的数据
        print_r($data);
        die;
    }
}