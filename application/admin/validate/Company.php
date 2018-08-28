<?php
namespace app\admin\validate;

use think\Validate;

/**
 * 管理员验证器
 * Class AdminUser
 * @package app\admin\validate
 */
class Company extends Validate
{
    protected $rule = [
        'company_cn'       => 'require|unique:company',
        'agent'            => 'require',
        // 'posts'            => 'require',
        // 'tel'              => 'require',
        // 'email'            => 'require'
    ];

    protected $message = [
        'company_cn.require'         => '请输入中文公司名',
        'company_cn.unique'          => '中文公司名已存在',
        'agent.require'         => '请输入负责人',
        // 'posts.require'         => '请输入负责人职务',
        // 'tel.require'         => '请输入电话号码',
        // 'email.require'         => '请输入邮箱号码'
    ];
}