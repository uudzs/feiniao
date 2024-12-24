<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use app\admin\model\Pages as PagesModel;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;

class Pages extends BaseController
{
	public function detail()
	{
		$ismakecache = $this->usecache();
		$param = get_params();
		$name = isset($param['name']) ? trim($param['name']) : '';
		if (empty($name)) {
			throw new \think\exception\HttpException(406, '访问错误');
		}
		$detail = Db::name('Pages')->where(['name' => $name])->find();
		if (empty($detail)) {
			throw new \think\exception\HttpException(406, '找不到相关记录');
		}
		//轮播图
		if (!empty($detail['banner'])) {
			$detail['banner_array'] = explode(',', $detail['banner']);
		}
		//关键字
		$keyword_array = Db::name('PagesKeywords')
			->field('i.aid,i.keywords_id,k.title')
			->alias('i')
			->join('keywords k', 'k.id = i.keywords_id', 'LEFT')
			->order('i.create_time asc')
			->where(array('i.aid' => $detail['id'], 'k.status' => 1))
			->select()->toArray();
		$detail['keyword_ids'] = implode(",", array_column($keyword_array, 'keywords_id'));
		$detail['keyword_names'] = implode(',', array_column($keyword_array, 'title'));
		$detail['keyword_array'] = $keyword_array;
		PagesModel::where('id', $detail['id'])->inc('read')->update();
		View::assign('detail', $detail);
		if ($ismakecache) $this->makecache(View::fetch($detail['template']));
		return view($detail['template']);
	}
}
