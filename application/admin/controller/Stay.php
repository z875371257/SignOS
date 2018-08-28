<?php
namespace app\admin\controller;

use app\common\model\Company as CompanyModel;
use app\common\model\Stay as StayModel;
use app\common\controller\AdminBase;
use think\Db;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
 * 公司住宿管理
 * Class Slide
 * @package app\admin\controller
 */
class Stay extends AdminBase
{

    protected function _initialize()
    {
        parent::_initialize();
        $this->stay  = new StayModel();

    }

    /**
     * 公司住宿管理
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
     
        $map = [];

        if ($keyword) {
            $map['b.company_cn|a.hotel'] = ['like', "%{$keyword}%"];
        }
        $field = 'a.id,a.company_id,a.hotel,b.company_cn,a.stay1, a.stay1_addtime, a.stay1_endtime, a.stay2, a.stay2_addtime, a.stay2_endtime, a.stay3, a.stay3_addtime, a.stay3_endtime';
        $stay_list = $this->stay->field($field)->alias('a')->join('os_company b', 'a.company_id = b.id')->where($map)->order('a.id DESC')->paginate(10, false, ['page' => $page]);

        $zsort = $page * 10 - 10 + 1;

        return $this->fetch('index', ['stay_list' => $stay_list, 'keyword' => $keyword, 'zsort' => $zsort]);
    }

  
    /**
     * 添加公司住宿
     * @return mixed
     */
    public function add()
    {
        $company_list = CompanyModel::all();

        return $this->fetch('add', ['company_list' => $company_list]);
    }

    /**
     * 保存公司住宿
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'Stay');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $stay_model = new StayModel();
                if ($stay_model->allowField(true)->save($data)) {
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑公司住宿
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $company = new CompanyModel;
        $company_list = CompanyModel::all();
        $stay   = $this->stay->get($id);

        $company_cn  = $company->column('company_cn', 'id');
        return $this->fetch('edit', ['stay' => $stay, 'company_list' => $company_list, 'company_cn' => $company_cn]);
    }

    /**
     * 更新公司住宿
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'Stay');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $stay_model = new StayModel();
                if ($stay_model->allowField(true)->save($data, $id) !== false) {
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除公司住宿
     * @param $id
     */
    public function delete($id)
    {
        if (SlideModel::destroy($id)) {
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
        $worksheet->setTitle('公司住宿信息表');
        
        // 设置单元格头信息
        $worksheet->setCellValueByColumnAndRow(1, 1, '公司住宿信息表');
        $worksheet->setCellValueByColumnAndRow(1, 2, '公司名中文');
        $worksheet->setCellValueByColumnAndRow(2, 2, '入住酒店');
        $worksheet->setCellValueByColumnAndRow(3, 2, '标间房');
        $worksheet->setCellValueByColumnAndRow(4, 2, '标间房入住时间');
        $worksheet->setCellValueByColumnAndRow(5, 2, '标间房退房时间');
        $worksheet->setCellValueByColumnAndRow(6, 2, '大床房');
        $worksheet->setCellValueByColumnAndRow(7, 2, '大床房入住时间');
        $worksheet->setCellValueByColumnAndRow(8, 2, '大床房退房时间');
        $worksheet->setCellValueByColumnAndRow(9, 2, '其它房');
        $worksheet->setCellValueByColumnAndRow(10, 2, '其它房入住时间');
        $worksheet->setCellValueByColumnAndRow(11, 2, '其它房退房时间');

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

        $field = 'a.id,a.company_id,b.company_cn,a.hotel,a.stay1, a.stay1_addtime, a.stay1_endtime, a.stay2, a.stay2_addtime, a.stay2_endtime, a.stay3, a.stay3_addtime, a.stay3_endtime';
        $stay_list = $this->stay->field($field)->alias('a')->join('os_company b', 'a.company_id = b.id')->order('a.id DESC')->select();
    
        $len = count($stay_list);
        $j = 0;
        for ($i=0; $i < $len; $i++) { 
            $j = $i + 3; //从表格第3行开始
            $worksheet->setCellValueByColumnAndRow(1, $j, $stay_list[$i]['company_cn']);
            $worksheet->setCellValueByColumnAndRow(2, $j, $stay_list[$i]['hotel']);
            $worksheet->setCellValueByColumnAndRow(3, $j, $stay_list[$i]['stay1']);
            $worksheet->setCellValueByColumnAndRow(4, $j, $stay_list[$i]['stay1_addtime']);
            $worksheet->setCellValueByColumnAndRow(5, $j, $stay_list[$i]['stay1_endtime']);
            $worksheet->setCellValueByColumnAndRow(6, $j, $stay_list[$i]['stay2']);
            $worksheet->setCellValueByColumnAndRow(7, $j, $stay_list[$i]['stay2_addtime']);
            $worksheet->setCellValueByColumnAndRow(8, $j, $stay_list[$i]['stay2_endtime']);
            $worksheet->setCellValueByColumnAndRow(9, $j, $stay_list[$i]['stay3']);
            $worksheet->setCellValueByColumnAndRow(10, $j, $stay_list[$i]['stay3_addtime']);
            $worksheet->setCellValueByColumnAndRow(11, $j, $stay_list[$i]['stay3_endtime']);
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
        $worksheet->getStyle('A1:K'.$total_rows)->applyFromArray($styleArrayBody);

        $filename = '公司住宿信息表.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
      
    }

}