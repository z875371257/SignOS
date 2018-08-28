<?php
namespace app\api\controller\v1;

use think\Controller;
use app\common\model\SignUser as SignUserModel;
use app\common\model\Sign as SignModel;


/**
 * Class SignUser
 * @param $uid    用户ID
 * @param $tagid  签到处ID
 * @package app\api\controller
 * @return {0:签到成功, 1:已经签到过了, 2:走错会场了}
 */

class SignUser extends Controller
{

    public function index($uid, $tagid)
    {
    	$sign_user = new SignUserModel();

    	$res = $sign_user::where('user_id', $uid)->where('tag_id', $tagid)->find();

    	$id = $res['id'];
    	if($res){
    		if($res['status'] == 0){
	    		$sign_user->save(['status' => 1], ['id' => $id]);
	    		$data = 0;
	    	} else {
	    		$data = 1;
	    	}
    	}else{
    		$data = 2;
    	}
    	

    	return json(['data' => $data, 'code' => 200, 'message' => '请求成功']);
    }
  
}