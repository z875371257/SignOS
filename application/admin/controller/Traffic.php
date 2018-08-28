<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;

use app\common\model\Sign as SignModel;
use think\Db;

/**
 * 交通工具分类
 * Class Traffic
 * @package app\admin\controller
 */
class Traffic extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();
        $this->sign = new SignModel();
    }

    /**
     * 交通工具分类
     * @return mixed
     */
    public function index()
    {
        $slide_category_list = Db::name('traffic')->select();

        return $this->fetch('index', ['slide_category_list' => $slide_category_list]);
    }

    /**
     * 添加分类
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存分类
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (Db::name('traffic')->insert($data)) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 编辑分类
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $slide_category = Db::name('traffic')->find($id);

        return $this->fetch('edit', ['slide_category' => $slide_category]);
    }

    /**
     * 更新分类
     * @throws \think\Exception
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (Db::name('traffic')->update($data) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除分类
     * @param $id
     * @throws \think\Exception
     */
    public function delete($id)
    {
        if (Db::name('traffic')->delete($id) !== false) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    

}