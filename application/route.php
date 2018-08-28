<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;

Route::resource('api/:version/signtag','api/:version.SignTag');   //注册一个资源路由，对应restful各个方法
Route::resource('api/:version/signuser','api/:version.SignUser');   //注册一个资源路由，对应restful各个方法

return [

    '__pattern__' => [
        'name' => '\w+',
    ],

];
