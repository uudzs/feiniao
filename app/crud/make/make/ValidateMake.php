<?php

namespace app\crud\make\make;

use app\crud\make\ToAutoMake;
use Symfony\Component\VarExporter\VarExporter;
use think\console\Output;
use think\facade\App;
use think\facade\Db;

class ValidateMake implements ToAutoMake
{
    public function check($table, $path)
    {
		!defined('DS') && define('DS', DIRECTORY_SEPARATOR);
		
        $validateName = ucfirst(camelize($table)) . 'Validate';
        $validateFilePath = base_path() . $path . DS . 'validate' . DS . $validateName . '.php';

        if (!is_dir(base_path() . $path . DS . 'validate')) {
            mkdir(base_path() . $path . DS . 'validate', 0755, true);
        }

        if (file_exists($validateFilePath)) {
            $output = new Output();
            $output->error("$validateName.php已经存在");
            exit;
        }
    }
    
    public function make($table, $path, $other)
    {
        $validateTpl = dirname(dirname(__DIR__)) . '/tpl/validate.tpl';
        $tplContent = file_get_contents($validateTpl);

        $model = ucfirst(camelize($table));
        $filePath = empty($path) ? '' : DS . $path;
        $namespace = empty($path) ? '\\' : '\\' . $path . '\\';

        //$prefix = config('database.connections.mysql.prefix');
        //$column = Db::query('SHOW FULL COLUMNS FROM `' . $prefix . $table . '`');
		$column = get_cache('crud_v_'.$table);
        $rule = [];
        $message = [];
        foreach ($column as $vo) {
            $rule[$vo['field']] = 'require';
            $message[$vo['field'].'.require'] = $vo['title'].'不能为空';
        }

        $ruleArr = VarExporter::export($rule);
        $messageArr = VarExporter::export($message);

        $tplContent = str_replace('<namespace>', $namespace, $tplContent);
        $tplContent = str_replace('<model>', $model, $tplContent);
        $tplContent = str_replace('<rule>', '' . $ruleArr, $tplContent);
        $tplContent = str_replace('<message>', $messageArr, $tplContent);

        file_put_contents(base_path() . $filePath . DS . 'validate' . DS . $model . 'Validate.php', $tplContent);
    }
}