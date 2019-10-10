<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::get('think', function () {
    return 'hello,ThinkPHP5!';
});

//Route::group('index', function () {
//    Route::rule('hello/:name/[:sign]/[:timestamp]', 'index/index/hello');
//    Route::rule('curl/:name', 'index/testcurl/index');
//})->pattern(['name' => '\w+']);

//Route::rule('hello/:name/[:sign]/[:timestamp]', 'index/index/hello');

//Route::get('admin/hello/[:name]', 'admin/index/hello');

return [

];
