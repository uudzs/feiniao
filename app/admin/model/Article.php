<?php

namespace app\admin\model;
use think\model;
use app\admin\model\Keywords;
use think\facade\Db;
class Article extends Model
{
	
	public static $Type = ['普通','精华','热门','推荐'];
	
    //插入关键字
    public function insertKeyword($keywordArray = [], $aid = 0)
    {
        $insert = [];
        $time = time();
        foreach ($keywordArray as $key => $value) {
            if (!$value) {
                continue;
            }
            $keywords_id = (new Keywords())->increase($value);
            $insert[] = ['aid' => $aid,
                'keywords_id' => $keywords_id,
                'create_time' => $time,
            ];
        }
        $res = Db::name('ArticleKeywords')->strict(false)->field(true)->insertAll($insert);
    }
    /**
    * 获取分页列表
    * @param $where
    * @param $param
    */
    public function getArticleList($where, $param)
    {
		$rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
		$order = empty($param['order']) ? 'a.id desc' : $param['order'];
        $list = self::where($where)
		->field('a.*,c.id as cate_id,c.title as cate_title,u.nickname as admin_name')
        ->alias('a')
        ->join('ArticleCate c', 'a.cate_id = c.id')
        ->join('Admin u', 'a.admin_id = u.id')
		->order($order)
		->paginate($rows, false, ['query' => $param])
		->each(function ($item, $key) {
			$type = (int)$item->type;
			$item->type_str = self::$Type[$type];
		});
		return $list;
    }

    /**
    * 添加数据
    * @param $param
    */
    public function addArticle($param)
    {
		$insertId = 0;
        try {
			$param['create_time'] = time();
			$insertId = $this->strict(false)->field(true)->insertGetId($param);
			//关联关键字
			if (isset($param['keyword_names']) && $param['keyword_names']) {
				$keywordArray = explode(',', $param['keyword_names']);
				$res_keyword = $this->insertKeyword($keywordArray,$insertId);
			}
			add_log('add', $insertId, $param);
        } catch(\Exception $e) {
			return to_assign(1, '操作失败，原因：'.$e->getMessage());
        }
		return to_assign(0,'操作成功',['aid'=>$insertId]);
    }

    /**
    * 编辑信息
    * @param $param
    */
    public function editArticle($param)
    {
        try {
            $param['update_time'] = time();
            $this->where('id', $param['id'])->strict(false)->field(true)->update($param);
			//关联关键字
			if (isset($param['keyword_names']) && $param['keyword_names']) {
				\think\facade\Db::name('ArticleKeywords')->where(['aid'=>$param['id']])->delete();
				$keywordArray = explode(',', $param['keyword_names']);
				$res_keyword = $this->insertKeyword($keywordArray,$param['id']);
			}
			add_log('edit', $param['id'], $param);
        } catch(\Exception $e) {
			return to_assign(1, '操作失败，原因：'.$e->getMessage());
        }
		return to_assign();
    }
	

    /**
    * 根据id获取信息
    * @param $id
    */
    public function getArticleById($id)
    {
        $info = $this->where('id', $id)->find();
		return $info;
    }

    /**
    * 删除信息
    * @param $id
    * @return array
    */
    public function delArticleById($id,$type=0)
    {
		if($type==0){
			//逻辑删除
			try {
				$param['delete_time'] = time();
				$this->where('id', $id)->update(['delete_time'=>time()]);
				add_log('delete', $id);
			} catch(\Exception $e) {
				return to_assign(1, '操作失败，原因：'.$e->getMessage());
			}
		}
		else{
			//物理删除
			try {
				$this->where('id', $id)->delete();
				add_log('delete', $id);
			} catch(\Exception $e) {
				return to_assign(1, '操作失败，原因：'.$e->getMessage());
			}
		}
		return to_assign();
    }
}

