<?php
namespace app\api\controller\v1;

use think\Controller;
use app\common\model\SignTag as SignTagModel;


/**
 * 返回会场
 * Class Sign
 * @package app\api\controller
 */

class SignTag extends Controller
{

    public function index()
    {
    	$sign = new SignTagModel();

    	$data = $sign::all();

    	return json(['data' => $data, 'code' => 200, 'message' => '请求成功']);
    }
  
}