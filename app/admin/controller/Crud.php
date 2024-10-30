<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\Console;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Crud extends BaseController
{
	public function index()
	{
		$prefix = config('database.connections.mysql.prefix');
		//查询所有表信息
		$table_info = Db::query('SHOW TABLE STATUS');
		foreach ($table_info as $k => &$val) {
			$val['a'] = strpos($val['Comment'], '::crud');
			if (strpos($val['Comment'], '::crud') == false) {
				unset($table_info[$k]);
				continue;
			}
			$val['title'] = str_replace($prefix, '', $val['Name']);
			$val['crud'] = $this->check($val['title']);
			$val['Comment'] = str_replace('::crud', '', $val['Comment']);
		}
		View::assign('prefix', $prefix);
		View::assign('table_info', $table_info);
		return view();
	}

	public function check($table, $type = 'c', $path = 'admin')
	{
		!defined('DS') && define('DS', DIRECTORY_SEPARATOR);
		if ($type == 'c') {
			$table = ucfirst(camelize($table));
			$filePath = 'app' . DS  . $path . DS . 'controller' . DS . $table . '.php';
			$realPath = base_path() . $path . DS . 'controller' . DS . $table . '.php';
		}
		if ($type == 'm') {
			$table = ucfirst(camelize($table));
			$filePath = 'app' . DS . $path . DS . 'model' . DS . $table . '.php';
			$realPath = base_path() . $path . DS . 'model' . DS . $table . '.php';
		}
		if ($type == 'v') {
			$table = ucfirst(camelize($table)) . 'Validate';
			$filePath = 'app' . DS . $path . DS . 'validate' . DS . $table . '.php';
			$realPath = base_path() . $path . DS . 'validate' . DS . $table . '.php';
		}
		if ($type == 'l') {
			$filePath = 'app' . DS . $path . DS . 'view' . DS . $table . DS . 'datalist.html';
			$realPath = base_path() . $path . DS . 'view' . DS . $table . DS . 'datalist.html';
		}
		if ($type == 'a') {
			$filePath = 'app' . DS . $path . DS . 'view' . DS . $table . DS . 'add.html';
			$realPath = base_path() . $path . DS . 'view' . DS . $table . DS . 'add.html';
		}
		if ($type == 'e') {
			$filePath = 'app' . DS . $path . DS . 'view' . DS . $table . DS . 'edit.html';
			$realPath = base_path() . $path . DS . 'view' . DS . $table . DS . 'edit.html';
		}
		if ($type == 'r') {
			$filePath = 'app' . DS . $path . DS . 'view' . DS . $table . DS . 'read.html';
			$realPath = base_path() . $path . DS . 'view' . DS . $table . DS . 'read.html';
		}
		if (file_exists($realPath)) {
			return $filePath;
		} else {
			return 0;
		}
	}

	//crud
	public function table()
	{
		$param = get_params();
		$prefix = config('database.connections.mysql.prefix');
		//查询指定表信息
		$table_info = Db::query('SHOW TABLE STATUS LIKE ' . "'" . $param['name'] . "'");
		$detail = $table_info[0];
		$detail['title'] = str_replace($prefix, '', $detail['Name']);
		$detail['Comment'] = str_replace('::crud', '', $detail['Comment']);
		$table_columns = Db::query("SHOW FULL COLUMNS FROM " . $param['name']);
		foreach ($table_columns as $k => &$v) {
			$temp_array = explode(":", $v['Comment']);
			$v['name'] = $temp_array[0];
		}
		//var_dump($table_info);exit;
		//var_dump($table_columns);exit;
		$detail['c'] = $this->check($detail['title'], $type = 'c');
		$detail['m'] = $this->check($detail['title'], $type = 'm');
		$detail['v'] = $this->check($detail['title'], $type = 'v');
		$detail['l'] = $this->check($detail['title'], $type = 'l');
		$detail['a'] = $this->check($detail['title'], $type = 'a');
		$detail['e'] = $this->check($detail['title'], $type = 'e');
		$detail['r'] = $this->check($detail['title'], $type = 'r');
		View::assign('detail', $detail);
		View::assign('columns', $table_columns);
		return view();
	}

	//一键crud
	public function crud()
	{
		$uid = get_login_admin('id');
		if ($uid != 1) {
			return to_assign(1, '只有系统超级管理员才有权限使用一键crud功能！');
		}
		$param = get_params();
		set_cache('crud_v_' . $param['field'], $param['crud_v']);
		set_cache('crud_a_' . $param['field'], $param['crud_a']);
		set_cache('crud_e_' . $param['field'], $param['crud_e']);
		set_cache('crud_r_' . $param['field'], $param['crud_r']);
		set_cache('crud_l_' . $param['field'], $param['crud_l']);
		$t = '-t' . $param['field'];
		$c = '-c' . $param['field'];
		$m = '-m' . $param['name'];
		try {
			$output = Console::call('crud', [$t, $c, $m]);
			//return $output->fetch();
		} catch (\Exception $e) {
			clear_cache('crud_v_' . $param['field']);
			clear_cache('crud_a_' . $param['field']);
			clear_cache('crud_e_' . $param['field']);
			clear_cache('crud_r_' . $param['field']);
			clear_cache('crud_l_' . $param['field']);
			return to_assign(1, $e->getMessage());
		}
	}

	//一键生成菜单
	public function menu($field = '', $name = '')
	{
		$uid = get_login_admin('id');
		if ($uid != 1) {
			return to_assign(1, '只有系统超级管理员才有权限使用一键生成菜单功能！');
		}
		if (empty($field) || empty($name)) {
			return to_assign(1, '参数错误！');
		}
		$path = 'admin/';
		$domain_bind = get_config('app.domain_bind');
		$domain_bind = $domain_bind ? array_flip($domain_bind) : [];
		if (isset($domain_bind['admin']) && $domain_bind['admin']) {
			$path = '';
		}
		$rule = [
			[
				'title'  => $name . '管理',
				'name'   => $name,
				'src'    => '',
				'module' => '',
				'crud'  => $field,
				'menu'   => '1',
				'icon'   => 'bi-folder',
				'son'    => [
					[
						'title'  => $name . '列表',
						'name'   => $name . '列表',
						'src'    => $path . $field . '/datalist',
						'module' => '',
						'crud'  => $field,
						'menu'   => '1',
						'icon'   => '',
						'son'    => [
							[
								'title'  => '新建',
								'name'   => $name,
								'src'    => $path . $field . '/add',
								'module' => '',
								'crud'  => $field,
								'menu'   => '2',
								'icon'   => '',
								'son'    => []
							],
							[
								'title'  => '编辑',
								'name'   => $name,
								'src'    => $path . $field . '/edit',
								'module' => '',
								'crud'  => $field,
								'menu'   => '2',
								'icon'   => '',
								'son'    => []
							],
							[
								'title'  => '查看',
								'name'   => $name,
								'src'    => $path . $field . '/read',
								'module' => '',
								'crud'  => $field,
								'menu'   => '2',
								'icon'   => '',
								'son'    => []
							],
							[
								'title'  => '删除',
								'name'   => $name,
								'src'    => $path . $field . '/del',
								'module' => '',
								'crud'  => $field,
								'menu'   => '2',
								'icon'   => '',
								'son'    => []
							]
						]
					]
				]
			]
		];
		//如果安装过该模块，删除原来的菜单信息
		Db::name('AdminRule')->where('crud', $field)->delete();
		$sort = Db::name('AdminRule')->where('pid', 0)->max('sort');
		$this->add_rule($rule, 0, $sort + 1);
		//更新超级管理员的权限节点
		$rules = Db::name('AdminRule')->column('id');
		$admin_rules = implode(',', $rules);
		$res = Db::name('AdminGroup')->strict(false)->where('id', 1)->update(['rules' => $admin_rules, 'update_time' => time()]);
		if ($res !== false) {
			// 删除后台节点缓存
			clear_cache('adminRules');
			return to_assign();
		} else {
			return to_assign(1, '操作失败');
		}
	}
	//递归插入菜单数据
	protected function add_rule($data, $pid = 0, $sort = 0)
	{
		foreach ($data as $k => $v) {
			$rule = [
				'title'  => $v['title'],
				'name'   => $v['name'],
				'src'    => $v['src'],
				'module' => $v['module'],
				'menu'   => $v['menu'],
				'icon'   => $v['icon'],
				'crud'   => $v['crud'],
				'pid'    => $pid,
				'sort'   => $sort,
				'create_time' => time()
			];
			$new_id = Db::name('AdminRule')->strict(false)->field(true)->insertGetId($rule);
			if (!empty($v['son'] && $new_id)) {
				$this->add_rule($v['son'], $new_id);
			}
		}
	}
}
