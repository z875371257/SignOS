<?php
namespace app\admin\validate;

use think\Validate;

class Stay extends Validate
{
    protected $rule = [
        'company_id'         => 'require|unique:stay',
      
    ];

    protected $message = [
        'company_id.require'         => '请输入公司名',
        'company_id.unique'          => '该公司住宿已存在',
    ];
}