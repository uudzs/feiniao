<?php

namespace app\crud\make\make;

use app\crud\make\ToAutoMake;
use think\facade\App;
use think\facade\Db;
use think\console\Output;

class ControllerMake implements ToAutoMake
{
    public function check($controller, $path)
    {
        !defined('DS') && define('DS', DIRECTORY_SEPARATOR);

        $controller = ucfirst(camelize($controller));
        $controllerFilePath = base_path() . $path . DS . 'controller' . DS . $controller . '.php';

        if (!is_dir(base_path() . $path . DS . 'controller')) {
            mkdir(base_path() . $path . DS . 'controller', 0755, true);
        }
        
        if (file_exists($controllerFilePath)) {
            $output = new Output();
            $output->error("$controller.php已经存在");
            exit;
        }
    }

    public function make($controller, $path, $table)
    {
        $controllerTpl = dirname(dirname(__DIR__)) . '/tpl/controller.tpl';
        $tplContent = file_get_contents($controllerTpl);

        $controller = ucfirst(camelize($controller));
        $model = ucfirst(camelize($table));
        $filePath = empty($path) ? '' : DS . $path;
        $namespace = empty($path) ? '\\' : '\\' . $path . '\\';

        $prefix = config('database.connections.mysql.prefix');
        $column = Db::query('SHOW FULL COLUMNS FROM `' . $prefix . $table . '`');
        $pk = '';
        foreach ($column as $vo) {
            if ($vo['Key'] == 'PRI') {
                $pk = $vo['Field'];
                break;
            }
        }
		$wheremap = '';
		foreach ($column as $vo) {
            if ($vo['Field'] == 'delete_time') {
                $wheremap='$where[] = ["delete_time","=",0];';
                break;
            }
        }

		$add_column = get_cache('crud_a_'.$table);
		$edit_column = get_cache('crud_e_'.$table);
		
		$timeadd = '';
		$timeedit = '';
		foreach ($add_column as $key => $vo) {
			if($vo['type'] == 'datetime'){
		$timeadd.='if(isset($param["'.$vo['field'].'"])){
					';
	$timeadd.='$param["'.$vo['field'].'"]= $param["'.$vo['field'].'"]?strtotime($param["'.$vo['field'].'"]):0;
				';
	$timeadd.='}
				';
			}
		}
		
		foreach ($edit_column as $key => $vo) {
			if($vo['type'] == 'datetime'){
		$timeedit.='if(isset($param["'.$vo['field'].'"])){
					';
	$timeedit.='$param["'.$vo['field'].'"]= $param["'.$vo['field'].'"]?strtotime($param["'.$vo['field'].'"]):0;
				';
	$timeedit.='}
				';
			}
		}

        $tplContent = str_replace('<namespace>', $namespace, $tplContent);
        $tplContent = str_replace('<controller>', $controller, $tplContent);
        $tplContent = str_replace('<model>', $model, $tplContent);
        $tplContent = str_replace('<pk>', $pk, $tplContent);
        $tplContent = str_replace('<wheremap>', $wheremap, $tplContent);
        $tplContent = str_replace('<timeadd>', $timeadd, $tplContent);
        $tplContent = str_replace('<timeedit>', $timeedit, $tplContent);

        file_put_contents(base_path() . $filePath . DS . 'controller' . DS . $controller . '.php', $tplContent);

		/*
        // 检测BaseController是否存在
        if (!file_exists(App::getAppPath() . $filePath . DS . 'controller' . DS . 'BaseController.php')) {

            $controllerTpl = dirname(dirname(__DIR__)) . '/tpl/baseController.tpl';
            $tplContent = file_get_contents($controllerTpl);

            $tplContent = str_replace('<namespace>', $namespace, $tplContent);

            file_put_contents(base_path() . $filePath . DS . 'controller' . DS . 'BaseController.php', $tplContent);
        }
		*/
    }
}