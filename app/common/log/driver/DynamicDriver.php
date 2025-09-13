<?php
declare (strict_types = 1);

namespace app\common\log\driver;

use think\contract\LogHandlerInterface;
use think\facade\Config;

/**
 * 动态日志驱动，根据配置决定是否记录日志
 */
class DynamicDriver implements LogHandlerInterface
{
    protected $config;
    
    public function __construct($config = [])
    {
        $this->config = $config;
    }
    
    /**
     * 日志写入接口
     * @access public
     * @param array $log 日志信息
     * @return bool
     */
    public function save(array $log): bool
    {
        // 检查日志是否启用
        $logConfig = Config::get('log');
        $isEnabled = $logConfig['enabled'] ?? false;
        
        // 如果日志未启用，直接返回true
        if (!$isEnabled) {
            return true;
        }
        
        // 如果启用，使用文件驱动记录日志
        $fileDriver = new \think\log\driver\File(app(), $this->config);
        return $fileDriver->save($log);
    }
}