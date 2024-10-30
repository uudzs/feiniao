<?php

namespace app\crud\make\make;

use app\crud\make\ToAutoMake;
use think\facade\App;
use think\facade\Db;
use think\console\Output;

class ListMake implements ToAutoMake
{
    public function check($table, $path)
    {
        !defined('DS') && define('DS', DIRECTORY_SEPARATOR);

        $modelName = $table;
        $modelFilePath = base_path() . $path . DS . 'view' . DS . $modelName . DS . 'datalist.html';

        if (!is_dir(base_path() . $path . DS . 'view' . DS . $modelName)) {
            mkdir(base_path() . $path . DS . 'view'. DS . $modelName, 0755, true);
        }

        if (file_exists($modelFilePath)) {
            $output = new Output();
            $output->error("$modelName datalist.html已经存在");
            exit;
        }
    }

    public function make($table, $path, $other)
    {
        $listTpl = dirname(dirname(__DIR__)) . '/tpl/list.tpl';
        $tplContent = file_get_contents($listTpl);
        $domain_bind = get_config('app.domain_bind');
        $domain_bind = $domain_bind ? array_flip($domain_bind) : [];
		if(isset($domain_bind['admin']) && $domain_bind['admin']) {
			$apply = '';
		} else {
			$apply = 'admin/';
		}
        $model = $table;
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
		
		$field_column = get_cache('crud_l_'.$table);
		$listitems="{
					fixed: 'left',
					field: 'id',
					title: '编号',
					align: 'center',
					width: 80
				},";
        foreach ($field_column as $key => $vo) {
			if($vo['field'] == 'title'){
				$listitems.="{
					field: '".$vo['field']."',
					title: '".$vo['title']."',
				},"; 
			}else{
				$listitems.="{
					field: '".$vo['field']."',
					title: '".$vo['title']."',
					align: 'center',
					width: 100
				},";
			}        
        }
        $tplContent = str_replace('<namespace>', $namespace, $tplContent);
        $tplContent = str_replace('<model>', $model, $tplContent);
        $tplContent = str_replace('<apply>', $apply, $tplContent);
        $tplContent = str_replace('<listitems>', $listitems, $tplContent);
        $tplContent = str_replace('<name>', $other, $tplContent);
		$tplContent = str_replace('<pk>', $pk, $tplContent);

        file_put_contents(base_path() . $path . DS . 'view' . DS . $model . DS . 'datalist.html', $tplContent);
    }
}