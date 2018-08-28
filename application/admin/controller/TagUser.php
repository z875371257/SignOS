<?php
namespace app\admin\controller;

use app\common\model\Sign as SignModel;
use app\common\model\SignTag as SignTagModel;
use app\common\model\Traffic as TrafficModel;

use app\common\controller\AdminBase;
use think\Config;
use think\Db;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
 * 分会场签到
 * Class TagUser
 * @package app\admin\controller
 */
class TagUser extends AdminBase
{


    protected function _initialize()
    {
        parent::_initialize();
        $this->sign = new SignModel();
        $this->sign_tag = new SignTagModel();
        $this->traffic = new TrafficModel();
        
    }

    /**
     * 分会场管理
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
        $field = 'a.id,a.seat,a.company_id,b.company_cn,a.truename,a.posts,a.tel,a.email,a.rand,a.is_sign,a.is_fee,a.apply_time,a.is_taboo,a.tag';
        $sign_list = $this->sign->field($field)->alias('a')->join('os_company b', 'a.company_id = b.id')->where($map)->order('a.id DESC')->paginate(10, false, ['page' => $page]);

        $trafficName = $this->traffic->column('name', 'id');

        $sign_tag = $this->sign_tag::all();

        $sign_user = DB::name('sign_user')->select();

        $zsort = $page * 10 - 10 + 1;

        return $this->fetch('index', ['zsort' => $zsort, 'sign_list' => $sign_list, 'keyword' => $keyword, 'traffic' => $trafficName, 'sign_tag' => $sign_tag, 'sign_user' => $sign_user]);
 
    }

    /**
    * 导出excel
    */
    public function excel()
    {   
        // 所有会场信息
        $sign_tag = $this->sign_tag::all();

        $spreadsheet = new Spreadsheet();

        $worksheet = $spreadsheet->getActiveSheet();

        // 设置表头信息
        $worksheet->setTitle('分会场签到表');
        
        // 设置单元格头信息
        $worksheet->setCellValueByColumnAndRow(1, 1, '分会场签到表');
        $worksheet->setCellValueByColumnAndRow(1, 2, '姓名');
        $worksheet->setCellValueByColumnAndRow(2, 2, '公司名');
        $worksheet->setCellValueByColumnAndRow(3, 2, '职务');
        $worksheet->setCellValueByColumnAndRow(4, 2, '手机');
        $worksheet->setCellValueByColumnAndRow(5, 2, '邮箱');
        $worksheet->setCellValueByColumnAndRow(6, 2, '报名时间');
        foreach ($sign_tag as $k => $v) {
            $worksheet->setCellValueByColumnAndRow($k+7, 2, $v['name']);
        }

        //合并单元格
        $worksheet->mergeCells('A1:R1');

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

        $worksheet->getStyle('A2:R2')->applyFromArray($styleArray)->getFont()->setSize(14);

        $field = 'a.id,a.company_id,b.company_cn,a.truename,a.posts,a.tel,a.email,a.apply_time,a.tag';
        $sign_list = $this->sign->field($field)->alias('a')->join('os_company b', 'a.company_id = b.id')->order('a.id DESC')->select();
        
        $len = count($sign_list);
        $j = 0;

        for ($i=0; $i < $len; $i++) { 
            $j = $i + 3; //从表格第3行开始
        
            $worksheet->setCellValueByColumnAndRow(1, $j, $sign_list[$i]['truename']);
            $worksheet->setCellValueByColumnAndRow(2, $j, $sign_list[$i]['company_cn']);
            $worksheet->setCellValueByColumnAndRow(3, $j, $sign_list[$i]['posts']);
            $worksheet->setCellValueByColumnAndRow(4, $j, $sign_list[$i]['tel']);
            $worksheet->setCellValueByColumnAndRow(5, $j, $sign_list[$i]['email']);
            $worksheet->setCellValueByColumnAndRow(6, $j, $sign_list[$i]['apply_time']);

            foreach ($sign_tag as $k => $v) {

                $signs = '';
                $tags = explode(',', $sign_list[$i]['tag']);

                foreach ($tags as $key => $value) {
                    if($v['id'] == $value){
                        if(SignStatus($v['id'], $value)['status']){  
                            $signs = '已签到';
                        } else { 
                            $signs = '未签到';
                        }
                    }
                }

                $worksheet->setCellValueByColumnAndRow($k+7, $j, $signs);

            }

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
        $worksheet->getStyle('A1:R1'.$total_rows)->applyFromArray($styleArrayBody);

        $filename = '分会场签到表.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
      
    }

}