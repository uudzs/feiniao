<?php

namespace app\command;

use app\service\ReverseCollectService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;

class CollectDispatch extends Command
{
    protected function configure()
    {
        $this->setName('collect:dispatch')
            ->setDescription('Dispatch pending reverse collection jobs')
            ->addOption('watch', 'w', Option::VALUE_NONE, 'Keep polling the queue')
            ->addOption('interval', 'i', Option::VALUE_OPTIONAL, 'Polling interval in seconds', 5)
            ->addOption('limit', 'l', Option::VALUE_OPTIONAL, 'Jobs per dispatch cycle', 20);
    }
    
    protected function execute(Input $input, Output $output)
    {
        $watch = (bool)$input->getOption('watch');
        $interval = max(2, (int)$input->getOption('interval'));
        $limit = max(1, (int)$input->getOption('limit'));
        $lockHandle = fopen(runtime_path() . 'collect_dispatch.lock', 'c');
        if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
            $output->writeln('Another collect dispatcher is already running.');
            return 1;
        }
        do {
            $r = ReverseCollectService::dispatch($limit);
            if (!$watch || $r['accepted'] || $r['failed']) {
                $output->writeln(date('Y-m-d H:i:s') . " accepted={$r['accepted']} failed={$r['failed']}");
            }
            if ($watch) sleep($interval);
        } while ($watch);
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        return 0;
    }
}
