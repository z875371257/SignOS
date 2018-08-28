<?php
namespace app\admin\controller;

use app\common\model\Overseas as OverseasModel;
use app\common\controller\AdminBase;
use think\Db;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 海外订阅用户
 * Class Overseas
 * @package app\admin\controller 
 */
class Overseas extends AdminBase
{
	protected function _initialize()
    {
        parent::_initialize();

        $this->overseas_model = new OverseasModel();
  	
    }

    /**
     * 海外用户列表
     * @return mixed
     */
	public function index($keyword = '', $page = 1)
	{
		$map = [];
        if ($keyword) {
            $map['company|uname|nationality'] = ['like', "%{$keyword}%"];
        }
        $overseas_list = $this->overseas_model->where($map)->order('id DESC')->paginate(15, false, ['page' => $page]);

        return $this->fetch('index', ['overseas_list' => $overseas_list, 'keyword' => $keyword]);

	}

	/**
     * 添加海外用户
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存海外用户
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->post();
            $validate_result = $this->validate($data, 'Overseas');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($this->overseas_model->allowField(true)->save($data)) {
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑用户
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $overseas = $this->overseas_model->find($id);

        return $this->fetch('edit', ['overseas' => $overseas]);
    }

    /**
     * 更新用户
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'Overseas');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($this->overseas_model->allowField(true)->save($data, $id) !== false) {
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除用户
     * @param $id
     */
    public function delete($id)
    {
        if ($this->overseas_model->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 邮箱发送页面
     * @param $id 
     */
	public function send_sms( $id = '' )
	{	
		if( !empty($id) ){
			$res = $this->overseas_model->where('id', $id)->select();
		} else {
        	$res = $this->overseas_model->order('id DESC')->select();
		}

		return $this->fetch('send_sms',['res'=>$res]);
	}

	/**
     * 邮箱发送
     * @param $data
     */
	public function send()
	{	
        set_time_limit(0);      

        $title = 'CSP News Weekly';

        $content = file_get_contents('news0820.html');

        while(true) {
            $res = $this->overseas_model->where('status', 1)->order('id DESC')->find();
            if(!$res){
                break;
            }
            $send = $this->send_email( $res['email'], $title, $content );
            $res['status']  = '0';
            $res->save();
        }

        echo '发送完毕';
	}

	public function send_email($to, $title, $content)
	{

		$mail = new phpmailer;
		try {

		    $mail->isSMTP();                                      // Set mailer to use SMTP
		    $mail->CharSet="UTF-8";
		    $mail->Host = 'smtp.exmail.qq.com';  // Specify main and backup SMTP servers
		    $mail->SMTPAuth = true;                               // Enable SMTP authentication
		    $mail->Username = 'Chris@cspplaza.com';                 // SMTP username
		    $mail->Password = 'Plaza201805';                           // SMTP password
		    $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
		    $mail->Port = 465;                                    // TCP port to connect to
		    $mail->AddReplyTo("Chris@cspplaza.com","Chris@cspplaza.com");  //回复地址
		    $mail->setFrom('Chris@cspplaza.com');   //发信人
		    $mail->FromName = "CSPPLAZA";
		    $mail->addAddress($to);     // 收件人
		    //Content
		    $mail->Subject = $title;    //邮件标题
		    $mail->Body    = $content;	//邮箱内容	  
		    $mail->isHTML(true);       // Set email format to HTML
 		
		    $res = $mail->send();

		    if($res){
		    	return 1;
		    }else{
		    	return 0;
		    }

		} catch (Exception $e) {
		   	return 0;
		}
	}
	

}