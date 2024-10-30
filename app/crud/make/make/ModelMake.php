<?php

namespace app\crud\make\make;

use app\crud\make\ToAutoMake;
use think\facade\App;
use think\facade\Db;
use think\console\Output;

class ModelMake implements ToAutoMake
{
    public function check($table, $path)
    {
        !defined('DS') && define('DS', DIRECTORY_SEPARATOR);

        $modelName = ucfirst(camelize($table));
        $modelFilePath = base_path() . $path . DS . 'model' . DS . $modelName . '.php';

        if (!is_dir(base_path() . $path . DS . 'model')) {
            mkdir(base_path() . $path . DS . 'model', 0755, true);
        }

        if (file_exists($modelFilePath)) {
            $output = new Output();
            $output->error("$modelName.php已经存在");
            exit;
        }
    }

    public function make($table, $path, $other)
    {
        $controllerTpl = dirname(dirname(__DIR__)) . '/tpl/model.tpl';
        $tplContent = file_get_contents($controllerTpl);

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
		$fieldlist=$pk;
		$list_column = get_cache('crud_l_'.$table);
		foreach ($list_column as $key => $vo) {
			$fieldlist.=','.$vo['field'];
		}

        $tplContent = str_replace('<namespace>', $namespace, $tplContent);
        $tplContent = str_replace('<model>', $model, $tplContent);
        $tplContent = str_replace('<pk>', $pk, $tplContent);
        $tplContent = str_replace('<fieldlist>', $fieldlist, $tplContent);

        file_put_contents(base_path() . $path . DS . 'model' . DS . $model . '.php', $tplContent);
    }
}