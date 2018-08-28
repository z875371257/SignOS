<?php
namespace app\admin\validate;

use think\Validate;

class Overseas extends Validate
{
    protected $rule = [
        // 'uname'         => 'require|unique:uname',
        // 'company'         => 'require',
        // 'email'            => 'email'
    ];

    protected $message = [
        // 'username.require'         => '请输入联系人',
        // 'username.unique'          => '联系人已存在',
        // 'company.require'         => '请输入公司名',
        // 'email.email'              => '邮箱格式错误'
    ];
}