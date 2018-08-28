<?php
namespace app\admin\controller;

use app\common\model\Company as CompanyModel;
use app\common\model\Stay as StayModel;
use app\common\controller\AdminBase;
use think\Config;
use think\Db;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
 * 注册公司管理
 * Class Company
 * @package app\admin\controller
 */
class Company extends AdminBase
{
    protected $company;


    protected function _initialize()
    {
        parent::_initialize();
        $this->company        = new CompanyModel();
        $this->stay        = new StayModel();
    }

    /**
     * 公司管理
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {   
        $map = [];

        if ($keyword) {
            $map['company_cn|posts|agent|tel|email'] = ['like', "%{$keyword}%"];
        }

        $company = $this->company->where($map)->order('id DESC')->paginate(10, false, ['page' => $page]);

        $zsort = $page * 10 - 10 + 1;

        return $this->fetch('index', ['company' => $company, 'keyword' => $keyword, 'zsort' => $zsort]);
    }

    /**
     * 添加公司
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存公司
     * @param 
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'Company');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($this->company->allowField(true)->save($data)) {
                    $stay['company_id'] = $this->company->id;
                    $this->stay->save($stay);
                    $this->success('保存成功', 'admin/sign/add');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑公司
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $company             = $this->company->find($id);
        return $this->fetch('edit', ['company' => $company]);
    }

    /**
     * 更新公司
     * @param $id
     * @param $group_id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'company');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $company_model = new CompanyModel();
                if ($company_model->allowField(true)->save($data, $id) !== false) {
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除公司
     * @param $id
     */
    public function delete($id)
    {
        if ($this->company->destroy($id)) {
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
        $worksheet->setTitle('注册公司总表');
        
        // 设置单元格头信息
        $worksheet->setCellValueByColumnAndRow(1, 1, '注册公司总表');
        $worksheet->setCellValueByColumnAndRow(1, 2, '公司名');
        $worksheet->setCellValueByColumnAndRow(2, 2, '经办人');
        $worksheet->setCellValueByColumnAndRow(3, 2, '职务');
        $worksheet->setCellValueByColumnAndRow(4, 2, '手机号');
        $worksheet->setCellValueByColumnAndRow(5, 2, '邮箱');
        $worksheet->setCellValueByColumnAndRow(6, 2, '参会费');
        $worksheet->setCellValueByColumnAndRow(7, 2, '备注');
        $worksheet->setCellValueByColumnAndRow(8, 2, '添加时间');

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

        $company = $this->company->order('id DESC')->select();

        $len = count($company);
        $j = 0;
        for ($i=0; $i < $len; $i++) { 
            $j = $i + 3; //从表格第3行开始
            $worksheet->setCellValueByColumnAndRow(1, $j, $company[$i]['company_cn']);
            $worksheet->setCellValueByColumnAndRow(2, $j, $company[$i]['agent']);
            $worksheet->setCellValueByColumnAndRow(3, $j, $company[$i]['posts']);
            $worksheet->setCellValueByColumnAndRow(4, $j, $company[$i]['tel']);
            $worksheet->setCellValueByColumnAndRow(5, $j, $company[$i]['email']);
            $worksheet->setCellValueByColumnAndRow(6, $j, $company[$i]['join_fee']);
            $worksheet->setCellValueByColumnAndRow(7, $j, $company[$i]['fee_remark']);
            $worksheet->setCellValueByColumnAndRow(8, $j, $company[$i]['create_time']);
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
        $worksheet->getStyle('A1:H'.$total_rows)->applyFromArray($styleArrayBody);

        $filename = '注册公司总表.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
      
    }
}