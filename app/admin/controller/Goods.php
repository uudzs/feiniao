<?php

declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Goods as GoodsModel;
use app\admin\validate\GoodsValidate;
use think\exception\ValidateException;
use HTMLPurifier;
use HTMLPurifier_Config;
use think\facade\Db;
use think\facade\View;

class Goods extends BaseController

{
	/**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new GoodsModel();
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
                $where[] = ['a.id|a.title|a.desc|a.content|c.title', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['cate_id'])) {
                $where[] = ['a.cate_id', '=', $param['cate_id']];
            }
            $where[] = ['a.delete_time', '=', 0];
			$param['order'] = 'a.sort asc';
            $list = $this->model->getGoodsList($where, $param);
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
                validate(GoodsValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
			if (isset($param['tag_values']) && $param['tag_values']) {
				$param['tag_values'] = implode(',',$param['tag_values']);
			}
			if(empty($param['desc'])){
				$param['desc'] = getDescriptionFromContent($param['content'], 100);
			}
			// 创建HTMLPurifier配置对象
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.DefinitionID', 'html5-definitions');
			$config->set('HTML.DefinitionRev', 1);
			$config->set('HTML.ForbiddenAttributes', ['width', 'height']);
			//$config->set('HTML.Allowed', 'p,b,a[href],pre[class],code,blockquote,img[src],table,tr,th,td,ul,li,ol,dl,dt,dd');
			$config->set('HTML.ForbiddenElements',array('script'),true);//设置拒绝使用的tagname
			if ($def = $config->maybeGetRawHTMLDefinition()) {
				$def->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
					'src' => 'URI',
					'type' => 'Text',
					'poster' => 'URI',
					'preload' => 'Enum#auto,metadata,none',
					'controls' => 'Bool',
				]);
				$def->addElement('source', 'Block', 'Flow', 'Common', [
					'src' => 'URI',
					'type' => 'Text',
				]);
			}		
			// 创建HTMLPurifier对象
			$purifier = new HTMLPurifier($config);
			//防止xss,过滤输入并输出结果
			//$param['content'] = '测试<script>alert(0);</script>';
			$param['content'] = $purifier->purify($param['content']);
			
			$param['admin_id'] = $this->uid;
            $this->model->addGoods($param);
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
                validate(GoodsValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
			if (isset($param['tag_values']) && $param['tag_values']) {
				$param['tag_values'] = implode(',',$param['tag_values']);
			}
			// 创建HTMLPurifier配置对象
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.DefinitionID', 'html5-definitions');
			$config->set('HTML.DefinitionRev', 1);
			$config->set('HTML.ForbiddenAttributes', ['width', 'height']);
			//$config->set('HTML.Allowed', 'p,b,a[href],pre[class],code,blockquote,img[src],table,tr,th,td,ul,li,ol,dl,dt,dd');
			$config->set('HTML.ForbiddenElements',array('script'),true);//设置拒绝使用的tagname
			if ($def = $config->maybeGetRawHTMLDefinition()) {
				$def->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
					'src' => 'URI',
					'type' => 'Text',
					'poster' => 'URI',
					'preload' => 'Enum#auto,metadata,none',
					'controls' => 'Bool',
				]);
				$def->addElement('source', 'Block', 'Flow', 'Common', [
					'src' => 'URI',
					'type' => 'Text',
				]);
			}		
			// 创建HTMLPurifier对象
			$purifier = new HTMLPurifier($config);
			//防止xss,过滤输入并输出结果
			//$param['content'] = '测试<script>alert(0);</script>';
			$param['content'] = $purifier->purify($param['content']);
			
            $this->model->editGoods($param);
        }else{
			$id = isset($param['id']) ? $param['id'] : 0;
			$detail = $this->model->getGoodsById($id);
			if (!empty($detail)) {
				//轮播图
				if(!empty($detail['banner'])) {
					$detail['banner_array'] = explode(',',$detail['banner']);
				}
				//关键字
				$keywrod_array = Db::name('GoodsKeywords')
					->field('i.aid,i.keywords_id,k.title')
					->alias('i')
					->join('keywords k', 'k.id = i.keywords_id', 'LEFT')
					->order('i.create_time asc')
					->where(array('i.aid' => $id, 'k.status' => 1))
					->select()->toArray();

				$detail['keyword_ids'] = implode(",", array_column($keywrod_array, 'keywords_id'));
				$detail['keyword_names'] = implode(',', array_column($keywrod_array, 'title'));
				
				//标签设置
				$detail['tag1'] = $detail['tag2'] = $detail['tag3'] = $detail['tag4'] = $detail['tag5'] = $detail['tag6'] =0;
				if(!empty($detail['tag_values'])) {
					$tag_values_array = explode(',', $detail['tag_values']);
					if(in_array('1', $tag_values_array)){
						$detail['tag1'] = 1;
					}
					if(in_array('2', $tag_values_array)){
						$detail['tag2'] = 1;
					}
					if(in_array('3', $tag_values_array)){
						$detail['tag3'] = 1;
					}
					if(in_array('4', $tag_values_array)){
						$detail['tag4'] = 1;
					}
					if(in_array('5', $tag_values_array)){
						$detail['tag5'] = 1;
					}
					if(in_array('6', $tag_values_array)){
						$detail['tag6'] = 1;
					}
				}
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
		$detail = $this->model->getGoodsById($id);
		if (!empty($detail)) {
			//分类名
			$detail['cate_name'] = Db::name('GoodsCate')->where('id',$detail['cate_id'])->value('title');
			//轮播图
			if(!empty($detail['banner'])) {
				$detail['banner_array'] = explode(',',$detail['banner']);
			}
			//关键字
			$keywrod_array = Db::name('GoodsKeywords')
				->field('i.aid,i.keywords_id,k.title')
				->alias('i')
				->join('keywords k', 'k.id = i.keywords_id', 'LEFT')
				->order('i.create_time asc')
				->where(array('i.aid' => $id, 'k.status' => 1))
				->select()->toArray();

			$detail['keyword_ids'] = implode(",", array_column($keywrod_array, 'keywords_id'));
			$detail['keyword_names'] = implode(',', array_column($keywrod_array, 'title'));
			
			//标签设置
			$detail['tag1'] = $detail['tag2'] = $detail['tag3'] = $detail['tag4'] = $detail['tag5'] = $detail['tag6'] =0;
			if(!empty($detail['tag_values'])) {
				$tag_values_array = explode(',', $detail['tag_values']);
				if(in_array('1', $tag_values_array)){
					$detail['tag1'] = 1;
				}
				if(in_array('2', $tag_values_array)){
					$detail['tag2'] = 1;
				}
				if(in_array('3', $tag_values_array)){
					$detail['tag3'] = 1;
				}
				if(in_array('4', $tag_values_array)){
					$detail['tag4'] = 1;
				}
				if(in_array('5', $tag_values_array)){
					$detail['tag5'] = 1;
				}
				if(in_array('6', $tag_values_array)){
					$detail['tag6'] = 1;
				}
			}
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

        $this->model->delGoodsById($id,$type);
   }
}
