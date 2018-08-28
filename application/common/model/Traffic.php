<?php
namespace app\common\model;

use think\Db;
use think\Model;

class Traffic extends Model
{
    /**
     * 获取交通分类列表
     * @return false|static[]
     */
    public function getTrafficList()
    {
        $slide_category_list = self::all();

        return $slide_category_list;
    }
}