<?php
namespace app\common\model;

use think\Model;

/**
 * 公司模型
 * Class Company
 * @package app\common\model
 */
class Company extends Model
{
    protected $insert = ['create_time'];

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }
}