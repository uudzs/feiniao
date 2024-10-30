<?php

namespace app\crud\command;

use app\crud\make\AutoMake;
use app\crud\make\make\ValidateMake;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class CrudValidate extends Command
{
    protected function configure()
    {
        $this->setName('auto crud')
            ->addOption('table', 't', Option::VALUE_OPTIONAL, 'the table name', null)
            ->addOption('controller', 'c', Option::VALUE_OPTIONAL, 'the controller name', null)
            ->addOption('name', 'm', Option::VALUE_OPTIONAL, 'the name', null)
        ->setDescription('auto make crud file');
    }

    protected function execute(Input $input, Output $output)
    {
        $table = $input->getOption('table');
        if (!$table) {
            $output->error("请输入 -t 表名");
            exit;
        }

        $controller = $input->getOption('controller');
        if (!$controller) {
            $output->error("请输入 -c 控制器名");
            exit;
        }

		$path = 'admin';
        $name = $input->getOption('name');
        if (!$name) {
            $name = '';
        }

        $make = new AutoMake();

        // 执行生成validate策略
        $make->executeText(new ValidateMake());
        $make->executeCreate($table, $path, '');
		
        $output->info($name . "validate make success");
    }
}