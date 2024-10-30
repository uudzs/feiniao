<?php

namespace app\admin\model;
use think\model;
use app\admin\model\Keywords;
use think\facade\Db;
class Gallery extends Model
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
        $res = Db::name('GalleryKeywords')->strict(false)->field(true)->insertAll($insert);
    }
    /**
    * 获取分页列表
    * @param $where
    * @param $param
    */
    public function getGalleryList($where, $param)
    {
		$rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
		$order = empty($param['order']) ? 'a.id desc' : $param['order'];
        $list = self::where($where)
		->field('a.*,c.title as cate_title,u.nickname as admin_name')
        ->alias('a')
        ->join('GalleryCate c', 'a.cate_id = c.id')
        ->join('Admin u', 'a.admin_id = u.id')
		->order($order)
		->paginate($rows, false, ['query' => $param])
		->each(function ($item, $key) {
			$type = (int)$item->type;
			$item->type_str = self::$Type[$type];
			$item->count = Db::name('GalleryFile')->where(array('aid'=>$item->id))->count();
		});
		return $list;
    }

    /**
    * 添加数据
    * @param $param
    */
    public function addGallery($param)
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
			
			//图集数据
            $filepathData = isset($param['img_filepath']) ? $param['img_filepath'] : '';
            $titleData = isset($param['img_title']) ? $param['img_title'] : '';
            $idData = isset($param['img_id']) ? $param['img_id'] : 0;
            $nameData = isset($param['img_name']) ? $param['img_name'] : '';
            $descData = isset($param['img_desc']) ? $param['img_desc'] : '';
            $linkData = isset($param['img_link']) ? $param['img_link'] : '';
            $sortData = isset($param['img_sort']) ? $param['img_sort'] : 0;
            $fileData = isset($param['img_file']) ? $param['img_file'] : 0;
			//插入图集数据
			$insertData = [];
			if(is_array($filepathData)){
				foreach ($filepathData as $key => $value) {
					if (!$value) {
						continue;
					}
					$file = [];
					$file['aid'] = $insertId;
					$file['title'] = $titleData[$key];
					$file['desc'] = $descData[$key];
					$file['link'] = $linkData[$key];    
					$file['sort'] = $sortData[$key]; 
					$file['file_id'] = $fileData[$key];
					$file['filepath'] = $filepathData[$key];
					$file['name'] = $nameData[$key];   
					$file['create_time'] = time();
					$insertData[] = $file;				
				}
				Db::name('GalleryFile')->strict(false)->field(true)->insertAll($insertData);
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
    public function editGallery($param)
    {
        try {
            $param['update_time'] = time();
            $this->where('id', $param['id'])->strict(false)->field(true)->update($param);
			//关联关键字
			if (isset($param['keyword_names']) && $param['keyword_names']) {
				Db::name('GalleryKeywords')->where(['aid'=>$param['id']])->delete();
				$keywordArray = explode(',', $param['keyword_names']);
				$res_keyword = $this->insertKeyword($keywordArray,$param['id']);
			}
			
			//图集数据
            $filepathData = isset($param['img_filepath']) ? $param['img_filepath'] : '';
            $titleData = isset($param['img_title']) ? $param['img_title'] : '';
            $idData = isset($param['img_id']) ? $param['img_id'] : 0;
            $nameData = isset($param['img_name']) ? $param['img_name'] : '';
            $descData = isset($param['img_desc']) ? $param['img_desc'] : '';
            $linkData = isset($param['img_link']) ? $param['img_link'] : 0;
            $sortData = isset($param['img_sort']) ? $param['img_sort'] : 0;
            $fileData = isset($param['img_file']) ? $param['img_file'] : 0;
			//插入图集数据
			if ($filepathData) {
				Db::name('GalleryFile')->where(['aid'=>$param['id']])->delete();
				$insertData = [];
				foreach ($filepathData as $key => $value) {
					if (!$value) {
						continue;
					}
					$file = [];
					$file['aid'] = $param['id'];
					$file['title'] = $titleData[$key];
					$file['desc'] = $descData[$key];
					$file['link'] = $linkData[$key];    
					$file['sort'] = $sortData[$key]; 
					$file['file_id'] = $fileData[$key];
					$file['filepath'] = $filepathData[$key];
					$file['name'] = $nameData[$key];   
					$file['create_time'] = time();
					$insertData[] = $file;				
				}
				$res = Db::name('GalleryFile')->strict(false)->field(true)->insertAll($insertData);		
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
    public function getGalleryById($id)
    {
        $info = $this->where('id', $id)->find();
		return $info;
    }

    /**
    * 删除信息
    * @param $id
    * @return array
    */
    public function delGalleryById($id,$type=0)
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

