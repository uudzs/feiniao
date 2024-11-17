<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use app\admin\model\Article as ArticleModel;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;

class Article extends BaseController
{
	public function detail()
	{
		$param = get_params();
		$id = isset($param['id']) ? intval($param['id']) : 0;
		if (empty($id)) {
			throw new \think\exception\HttpException(406, '访问错误');
		}
		$detail = (new ArticleModel())->getArticleById($id);
		if (empty($detail)) {
			throw new \think\exception\HttpException(406, '找不到相关记录');
		}
		ArticleModel::where('id', $param['id'])->inc('read')->update();
		$keyword_array = Db::name('ArticleKeywords')
			->field('i.aid,i.keywords_id,k.title')
			->alias('i')
			->join('keywords k', 'k.id = i.keywords_id', 'LEFT')
			->order('i.create_time asc')
			->where(array('i.aid' => $id, 'k.status' => 1))
			->select()->toArray();
		$detail['keyword_names'] = implode(',', array_column($keyword_array, 'title'));
		View::assign('seotitle', $detail['title']);
		View::assign('seokeywords', $detail['keyword_names']);
		View::assign('seodescription', strip_tags($detail['desc']));
		View::assign('detail', $detail);
		if (!Request::isMobile() && !isWeChat()) {
			hook("makehtml", ['content' => View::fetch()]);
		}
		return View();
	}
}
