<?php
namespace app\common\model;

use think\Db;
use think\Model;

class SignUser extends Model
{
    /**
     * 用户对应签到点
     * @return false|static[]
     */
    public function getTrafficList()
    {
        $slide_category_list = self::all();

        return $slide_category_list;
    }
}