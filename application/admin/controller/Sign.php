<?php
namespace app\admin\controller;

use app\common\model\Sign as SignModel;
use app\common\model\Company as CompanyModel;
use app\common\model\Traffic as TrafficModel;
use app\common\model\SignTag as SignTagModel;

use app\common\controller\AdminBase;
use think\Config;
use think\Db;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 签到用户管理
 * Class AdminUser
 * @package app\admin\controller
 */
class Sign extends AdminBase
{
    protected $sign_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->sign = new SignModel();
    }

    /**
     * 签到人员管理
     * @param string $keyword
     * @param int    $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
            $map['truename|company_cn|a.email|a.tel|a.rand'] = ['like', "%{$keyword}%"];
        }
        $field = 'a.id,a.seat,a.company_id,b.company_cn,a.truename,a.posts,a.tel,a.email,a.rand,a.is_sign,a.is_fee,a.apply_time,a.is_taboo';
        $sign_list = $this->sign->field($field)->alias('a')->join('os_company b', 'a.company_id = b.id')->where($map)->order('a.id DESC')->paginate(10, false, ['page' => $page]);

        $zsort = $page * 10 - 10 + 1;

        return $this->fetch('index', ['sign_list' => $sign_list, 'keyword' => $keyword, 'zsort' => $zsort]);
    }

    /**
     * 添加签到人员
     * @return mixed
     */
    public function add()
    {
        $company_list = CompanyModel::order('create_time', 'DESC')->select();
        $traffic_list = TrafficModel::all();
        $tag_list = SignTagModel::all();

        return $this->fetch('add', ['company_list' => $company_list, 'traffic_list' => $traffic_list, 'tag_list' => $tag_list]);
    }

    /**
     * 保存签到人员
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->post();
            $validate_result = $this->validate($data, 'Sign');


            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if(!empty($data['tag'])){
                    $data['tag'] = implode(',', $data['tag']);
                }

                $data['rand'] = rand(100000,999999);
                if ($this->sign->allowField(true)->save($data)) {

                    if(!empty($data['tag'])){
                        $rs['user_id'] = $this->sign->id;
                        $tags = explode(',', $data['tag']);
                        foreach ($tags as $k => $v) {
                            $rs['tag_id'] = $v;
                            DB::name('sign_user')->insert($rs);
                        }
                    }

                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑签到人员
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $company = new CompanyModel();
        $traffic = new TrafficModel();
        $company_list = CompanyModel::all();
        $traffic_list = TrafficModel::all();
        $tag_list = SignTagModel::all();

        $sign   = $this->sign->get($id);

        $company_cn  = $company->column('company_cn', 'id');
        $trafficName = $traffic->column('name', 'id');

        $sign['tag'] = explode(',', $sign['tag']);
        return $this->fetch('edit', ['sign' => $sign,'tag_list' => $tag_list ,'company_list' => $company_list, 'traffic_list' => $traffic_list, 'trafficName' => $trafficName,'company_cn' => $company_cn]);
    }

    /**
     * 更新签到人员
     * @param $id
     */

    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'Sign');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $sign = new SignModel();

                //  如果不为空就拆分成字符串
                if(!empty($data['tag'])){
                    $data['tag'] = implode(',', $data['tag']);
                }
                // 将字符串存进数据库tag字段
                if ($sign->allowField(true)->save($data, $id) !== false) {

                    //  存完后进行这一步 在进行判断是不是为空 不为空执行下面代码
                    if(!empty($data['tag'])){ 

                        //  进行拆分成数组
                        $tags = explode(',', $data['tag']);

                        $rs['user_id'] = $id;

                        // 遍历拆分的数组 
                        foreach ($tags as $k => $v) {

                            // 查找sign_user关联表中是否有这条数据
                            $res = DB::name('sign_user')->where('user_id', $id)->where('tag_id', $v)->find();

                            // 如果没有则进行插入，然后查出所有tag_id的数据，与这次更新完的数据相比较，如果不同则进行删除
                            if(!$res){
                                $rs['tag_id'] = $v;
                                DB::name('sign_user')->insert($rs);
                            }  
                            
                            // 将这次更新完tag_id为$v的数据存放进一个数组 
                            $ress[] = DB::name('sign_user')->where('user_id', $id)->where('tag_id', $v)->find();

                        }
                        // 该用户所有数据
                        $alls = DB::name('sign_user')->where('user_id', $id)->select();

                        // 将之转换好方便 array_diff 进行差集比较 
                        foreach($alls as $k=>$v){
                            $bs[] = $v['tag_id'];
                        } 

                        foreach ($ress as $key => $value) {
                            $cs[] = $value['tag_id'];    
                        }    
                        // 获取量两数组的差集
                        $diff = array_diff($bs, $cs);

                        // 进行遍历删除
                        foreach ($diff as $key => $value) {
                            DB::name('sign_user')->where('user_id', $id)->where('tag_id', $value)->delete();                        
                        }

                    } else {
                        //  为空就将空值存入sign表  并将sign_user这张表相关的数据删除
                        $zero['tag'] = '';
                        $sign->save($zero, $id);
                        DB::name('sign_user')->where('user_id', $id)->delete();
                    }

                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除签到人员
     * @param $id
     */
    public function delete($id)
    {
        if ($this->sign->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
    * 导出excel
    */
    public function excel()
    {
        $spreadsheet = new Spreadsheet();

        $worksheet = $spreadsheet->getActiveSheet();

        // 设置表头信息
        $worksheet->setTitle('签到总人数');
        
        // 设置单元格头信息
        $worksheet->setCellValueByColumnAndRow(1, 1, '签到总人数');
        $worksheet->setCellValueByColumnAndRow(1, 2, '姓名');
        $worksheet->setCellValueByColumnAndRow(2, 2, '公司名');
        $worksheet->setCellValueByColumnAndRow(3, 2, '职务');
        $worksheet->setCellValueByColumnAndRow(4, 2, '手机');
        $worksheet->setCellValueByColumnAndRow(5, 2, '邮箱');
        $worksheet->setCellValueByColumnAndRow(6, 2, '签到码');
        $worksheet->setCellValueByColumnAndRow(7, 2, '座位');
        $worksheet->setCellValueByColumnAndRow(8, 2, '是否付费');
        $worksheet->setCellValueByColumnAndRow(9, 2, '是否签到');
        $worksheet->setCellValueByColumnAndRow(10, 2, '饮食习惯');
        $worksheet->setCellValueByColumnAndRow(11, 2, '报名时间');

        //合并单元格
        $worksheet->mergeCells('A1:K1');

        $styleArray = [
            'font' => [
                'bold' => true
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        //设置单元格样式
        $worksheet->getStyle('A1')->applyFromArray($styleArray)->getFont()->setSize(28);

        $worksheet->getStyle('A2:K2')->applyFromArray($styleArray)->getFont()->setSize(14);

        $field = 'a.id,a.company_id,b.company_cn,a.truename,a.posts,a.tel,a.email,a.rand,a.is_taboo,a.seat,a.is_sign,a.is_fee,a.apply_time';
        $sign_list = $this->sign->field($field)->alias('a')->join('os_company b', 'a.company_id = b.id')->order('a.id DESC')->select();
        
        $len = count($sign_list);
        $j = 0;

        for ($i=0; $i < $len; $i++) { 
            $j = $i + 3; //从表格第3行开始
            if($sign_list[$i]['is_fee'] == '1'){
                $fee = '已付费';
            } else {
                $fee = '未付费';
            }
            if($sign_list[$i]['is_sign'] == '1'){
                $signs = '已签到';
            } else {
                $signs = '未签到';
            }
            if($sign_list[$i]['is_taboo'] == '1'){
                $fonts = '清真';
            } else {
                $fonts = '无禁忌';
            }

            $worksheet->setCellValueByColumnAndRow(1, $j, $sign_list[$i]['truename']);
            $worksheet->setCellValueByColumnAndRow(2, $j, $sign_list[$i]['company_cn']);
            $worksheet->setCellValueByColumnAndRow(3, $j, $sign_list[$i]['posts']);
            $worksheet->setCellValueByColumnAndRow(4, $j, $sign_list[$i]['tel']);
            $worksheet->setCellValueByColumnAndRow(5, $j, $sign_list[$i]['email']);
            $worksheet->setCellValueByColumnAndRow(6, $j, $sign_list[$i]['rand']);
            $worksheet->setCellValueByColumnAndRow(7, $j, $sign_list[$i]['seat']);
            $worksheet->setCellValueByColumnAndRow(8, $j, $fee);
            $worksheet->setCellValueByColumnAndRow(9, $j, $signs);
            $worksheet->setCellValueByColumnAndRow(10, $j, $fonts);
            $worksheet->setCellValueByColumnAndRow(11, $j, $sign_list[$i]['apply_time']);

        }

        $styleArrayBody = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '666666'],
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $total_rows = $len + 2;
        //添加所有边框/居中
        $worksheet->getStyle('A1:K1'.$total_rows)->applyFromArray($styleArrayBody);

        $filename = '签到总人数.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
      
    }

    /**
     * 签到返回打印信息
     * @param $id
     */
    public function sign($id)
    {
        // 获取ID的个人信息
        $sign  = $this->sign->get($id);

        $company = new CompanyModel;

        $company_cn  = $company->column('company_cn', 'id');

        // 将个人信息的公司ID转换
        $sign['company_cn'] = $company_cn[$sign['company_id']];

        // 更新签到状态是否付费及签到时间
        $sign_save = $this->sign->get($id);
        $sign_save->is_sign = 1;
        $sign_save->is_fee = 1;
        $sign_save->sign_time = date('Y-m-d H:i:s', time());
        $sign_save->save();

        $sign['code'] = $this->code($id);

        return ['data'=>$sign, 'code'=>200, 'message'=>'获取成功'];  
    }

    public function code($id)
    {   

        // 首先请求获取access_token
        $appid ="wxe2370e4219caf0ce";   // 小程序appid

        $secret = "7f98ff1ff0ed085ea243b22d16882e2d";  // 小程序secret秘钥

        // 请求获取access_token信息
        $token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;  

        $tokens = file_get_contents($token);

        $access_token = json_decode($tokens)->access_token;

        // 用access_token请求获取小程序二维码
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;

        // 需要带的参数
        $data['scene'] = $id;

        // 小程序二维码的地址
        $data['page'] = 'pages/sign/index';
        
        // 二维码宽度
        $data['width'] = 280;

        $data = json_encode($data);

        // 请求赋值
        $da = $this->get_http_array($url, $data);

        $path = 'public/static/code/'.date('YmdHis', time()).$id.'z.png';

        // 将二维码保存到文件夹中
        file_put_contents($path, $da);

        // 将保存的二维码地址返回回去
        return $path;
    }

    public function get_http_array($url,$post_data) 
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1

        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $output = curl_exec($ch);

        curl_close($ch);

        $out = json_decode($output);

        return $output;

    }



}