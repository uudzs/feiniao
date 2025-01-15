<?php
 
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Gallery as GalleryModel;
use app\admin\validate\GalleryValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Gallery extends BaseController

{

    var $uid;
    var $model;
    
	/**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new GalleryModel();
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
			if (!empty($param['keywords'])) {
                $where[] = ['a.id|a.title|a.desc|c.title', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['cate_id'])) {
                $where[] = ['a.cate_id', '=', $param['cate_id']];
            }
            $where[] = ['a.delete_time', '=', 0];
            $list = $this->model->getGalleryList($where, $param);
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
                validate(GalleryValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
			$param['admin_id'] = $this->uid;
            $this->model->addGallery($param);
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
                validate(GalleryValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $this->model->editGallery($param);
        }else{
			$id = isset($param['id']) ? $param['id'] : 0;
			$detail = $this->model->getGalleryById($id);
			if (!empty($detail)) {
				//关键字
				$keywrod_array = Db::name('GalleryKeywords')
					->field('i.aid,i.keywords_id,k.title')
					->alias('i')
					->join('keywords k', 'k.id = i.keywords_id', 'LEFT')
					->order('i.create_time asc')
					->where(array('i.aid' => $id, 'k.status' => 1))
					->select()->toArray();

				$detail['keyword_ids'] = implode(",", array_column($keywrod_array, 'keywords_id'));
				$detail['keyword_names'] = implode(',', array_column($keywrod_array, 'title'));
				$detail['keyword_array'] = $keywrod_array;
				
				//图集
				$gallery_array = Db::name('GalleryFile')
					->order('create_time asc')
					->where(array('aid' => $id))
					->select()->toArray();
				$detail['gallery_array'] = $gallery_array;
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
		$detail = $this->model->getGalleryById($id);
		if (!empty($detail)) {
			//关键字
			$keywrod_array = Db::name('GalleryKeywords')
				->field('i.aid,i.keywords_id,k.title')
				->alias('i')
				->join('keywords k', 'k.id = i.keywords_id', 'LEFT')
				->order('i.create_time asc')
				->where(array('i.aid' => $id, 'k.status' => 1))
				->select()->toArray();

			$detail['keyword_ids'] = implode(",", array_column($keywrod_array, 'keywords_id'));
			$detail['keyword_names'] = implode(',', array_column($keywrod_array, 'title'));
			$detail['keyword_array'] = $keywrod_array;
			
			//图集
			$gallery_array = Db::name('GalleryFile')
				->order('create_time asc')
				->where(array('aid' => $id))
				->select()->toArray();
			$detail['gallery_array'] = $gallery_array;
			View::assign('detail', $detail);
			return view();
		}
		else{
			throw new \think\exception\HttpException(404, '找不到页面');
		}
    }

    /**
    * 删除
    */
    public function del()
    {
        $param = get_params();
		$id = isset($param['id']) ? $param['id'] : 0;
		$type = isset($param['type']) ? $param['type'] : 0;

        $this->model->delGalleryById($id,$type);
   }
}
