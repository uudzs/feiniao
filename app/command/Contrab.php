<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Contrab extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('contrab')
            ->setDescription('the contrab command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        call_user_func('app\api\controller\Contrab::run');
        //$output->writeln('contrab');
    }
}
