<?php

declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\<model> as <model>Model;
use app\admin\validate\<model>Validate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class <controller> extends BaseController
{

    var $uid;
    var $model;

	/**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new <model>Model();
		$this->uid = get_login_admin('id');
    }
    /**
    * 数据列表
    */
    public function datalist()
    {
        if (request()->isAjax()) {
			$param = get_params();
			$where = [];
			<wheremap>
            $list = $this->model->get<model>List($where,$param);
            return table_assign(0, '', $list);
        }
        else{
            return view();
        }
    }

    /**
    * 添加
    */
    public function add()
    {
        if (request()->isAjax()) {		
			$param = get_params();	
			
            // 检验完整性
            try {
                validate(<model>Validate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
			<timeadd>
            $this->model->add<model>($param);
        }else{
			return view();
		}
    }
	

    /**
    * 编辑
    */
    public function edit()
    {
		$param = get_params();
		
        if (request()->isAjax()) {			
            // 检验完整性
            try {
                validate(<model>Validate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
			<timeedit>
            $this->model->edit<model>($param);
        }else{
			$<pk> = isset($param['<pk>']) ? $param['<pk>'] : 0;
			$detail = $this->model->get<model>ById($<pk>);
			if (!empty($detail)) {
				View::assign('detail', $detail);
				return view();
			}
			else{
				throw new \think\exception\HttpException(404, '找不到页面');
			}			
		}
    }


    /**
    * 查看信息
    */
    public function read()
    {
        $param = get_params();
		$<pk> = isset($param['<pk>']) ? $param['<pk>'] : 0;
		$detail = $this->model->get<model>ById($<pk>);
		if (!empty($detail)) {
			View::assign('detail', $detail);
			return view();
		}
		else{
			throw new \think\exception\HttpException(404, '找不到页面');
		}
    }

    /**
    * 删除
	* type=0,逻辑删除，默认
	* type=1,物理删除
    */
    public function del()
    {
        $param = get_params();
		$<pk> = isset($param['<pk>']) ? $param['<pk>'] : 0;
		$type = isset($param['type']) ? $param['type'] : 1;
        $this->model->del<model>ById($<pk>,$type);
   }
}
