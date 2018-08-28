<?php
namespace app\common\model;

use think\Db;
use think\Model;

class Overseas extends Model
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