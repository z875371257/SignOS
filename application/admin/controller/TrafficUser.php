<?php
namespace app\admin\controller;

use app\common\model\Sign as SignModel;
use app\common\model\Traffic as TrafficModel;

use app\common\controller\AdminBase;
use think\Config;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 接机管理
 * Class TrafficUser
 * @package app\admin\controller
 */
class TrafficUser extends AdminBase
{
    protected $admin_user_model;
    protected $auth_group_model;
    protected $auth_group_access_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->sign = new SignModel();
        $this->traffic = new TrafficModel();
        
    }

    /**
     * 接机管理
     * @param string $keyword
     * @param int    $page
     * @return mixed
     */

    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
            $map['truename|company_cn|a.email|a.tel'] = ['like', "%{$keyword}%"];
        }
        $field = 'a.id,a.seat,a.company_id,b.company_cn,a.truename,a.posts,a.tel,a.email,a.rand,a.is_sign,a.is_fee,a.apply_time,a.is_taboo,a.come_id,a.go_id';
        $sign_list = $this->sign->field($field)->alias('a')->join('os_company b', 'a.company_id = b.id')->where($map)->order('a.id DESC')->paginate(10, false, ['page' => $page]);

        $trafficName = $this->traffic->column('name', 'id');

        $zsort = $page * 10 - 10 + 1;

        return $this->fetch('index', ['sign_list' => $sign_list, 'keyword' => $keyword, 'traffic' => $trafficName, 'zsort' => $zsort]);
 
    }

    /**
    * 导出excel
    */
    public function excel()
    {   
        $trafficName = $this->traffic->column('name', 'id');

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
        $worksheet->setCellValueByColumnAndRow(6, 2, '来程交通');
        $worksheet->setCellValueByColumnAndRow(7, 2, '返程交通');
        $worksheet->setCellValueByColumnAndRow(8, 2, '报名时间');

        //合并单元格
        $worksheet->mergeCells('A1:H1');

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

        $worksheet->getStyle('A2:H2')->applyFromArray($styleArray)->getFont()->setSize(14);

        $field = 'a.id,a.company_id,b.company_cn,a.truename,a.posts,a.tel,a.email,a.apply_time,a.come_id,a.go_id';
        $sign_list = $this->sign->field($field)->alias('a')->join('os_company b', 'a.company_id = b.id')->order('a.id DESC')->select();
        
        $len = count($sign_list);
        $j = 0;

        for ($i=0; $i < $len; $i++) { 
            $j = $i + 3; //从表格第3行开始

            if( $sign_list[$i]['come_id'] != 0){
                $come = $trafficName[$sign_list[$i]['come_id']];
            } else {
                $come = '暂无';
            }

            if( $sign_list[$i]['go_id'] != 0){
                $go = $trafficName[$sign_list[$i]['go_id']];
            } else {
                $go = '暂无';
            }


            $worksheet->setCellValueByColumnAndRow(1, $j, $sign_list[$i]['truename']);
            $worksheet->setCellValueByColumnAndRow(2, $j, $sign_list[$i]['company_cn']);
            $worksheet->setCellValueByColumnAndRow(3, $j, $sign_list[$i]['posts']);
            $worksheet->setCellValueByColumnAndRow(4, $j, $sign_list[$i]['tel']);
            $worksheet->setCellValueByColumnAndRow(5, $j, $sign_list[$i]['email']);
            $worksheet->setCellValueByColumnAndRow(6, $j, $come);
            $worksheet->setCellValueByColumnAndRow(7, $j, $go);
            $worksheet->setCellValueByColumnAndRow(8, $j, $sign_list[$i]['apply_time']);
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
        $worksheet->getStyle('A1:H1'.$total_rows)->applyFromArray($styleArrayBody);

        $filename = '接送机信息.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
      
    }

}