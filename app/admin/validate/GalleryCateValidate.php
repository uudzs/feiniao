<?php
namespace app\admin\validate;
use think\Validate;
use think\facade\Db;

class GalleryCateValidate extends Validate
{
	// 自定义验证规则
	protected function checkOne($value,$rule,$data=[])
	{
		$count = Db::name('GalleryCate')->where([['title','=',$data['title']],['id','<>',$data['id']],['delete_time','=',0]])->count();
		return $count == 0 ? true : false;
	}
	
    protected $rule = [
		'title' => 'require|checkOne',
	];

    protected $message = [
		'title.require' => '分类名称不能为空',
		'title.checkOne' => '同样的分类名称已经存在',
	];
}