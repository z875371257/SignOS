<?php
namespace app\admin\validate;

use think\Validate;

class Sign extends Validate
{
    protected $rule = [
        'company_id'        => 'require',
      	'truename'			=> 'require',
      	'tel'           	=> 'number|length:11',
      	'email'				=> 'email',
      	'is_fee'			=> 'require'
    ];

    protected $message = [
        'company_id.require'  => '请选择公司名',
        'truename.require'	  => '请输入真实姓名',
        'tel.number'          => '手机号格式错误',
        'tel.length'          => '手机号长度错误',
        'email.email'         => '邮箱格式错误',
        'is_fee.require'      => '请选择是否付费'
    ];

}