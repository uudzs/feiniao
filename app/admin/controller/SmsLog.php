<?php

declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\SmsLog as SmsLogModel;
use app\admin\validate\SmsLogValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class SmsLog extends BaseController
{
	/**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new SmsLogModel();
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
			
            $list = $this->model->getSmsLogList($where,$param);
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
                validate(SmsLogValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
			if(isset($param["expire_time"])){
					$param["expire_time"]= $param["expire_time"]?strtotime($param["expire_time"]):0;
				}
				
            $this->model->addSmsLog($param);
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
                validate(SmsLogValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
			if(isset($param["expire_time"])){
					$param["expire_time"]= $param["expire_time"]?strtotime($param["expire_time"]):0;
				}
				
            $this->model->editSmsLog($param);
        }else{
			$id = isset($param['id']) ? $param['id'] : 0;
			$detail = $this->model->getSmsLogById($id);
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
		$id = isset($param['id']) ? $param['id'] : 0;
		$detail = $this->model->getSmsLogById($id);
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
		$id = isset($param['id']) ? $param['id'] : 0;
		$type = isset($param['type']) ? $param['type'] : 0;
        $this->model->delSmsLogById($id,$type);
   }
}
