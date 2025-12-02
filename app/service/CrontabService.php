<?php

namespace app\service;

class CrontabService
{
    /**
     * 载入插件计划任务
     * @return array
     */
    public static function loadAddonsCrontab(): array
    {
        $commands = [];
        // 动态加载插件命令
        $addonsPath = app()->getRootPath() . 'addons';
        if (is_dir($addonsPath)) {
            $plugins = scandir($addonsPath);
            $action = 'config';

            foreach ($plugins as $name) {
                if ($name === '.' || $name === '..' || is_file($addonsPath . DIRECTORY_SEPARATOR . $name)) {
                    continue;
                }

                $addonDir = $addonsPath . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
                if (!is_dir($addonDir)) {
                    continue;
                }

                $file = trim($name);

                $config = parse_ini_file($addonDir . 'info.ini', true);
                if (!$config || !is_array($config)) continue;
                if (!isset($config['install']) || !isset($config['status'])) continue;
                if (!$config['install'] || !$config['status']) continue;

                $class = "\\addons\\{$file}\\Crontab";

                if (class_exists($class)) {
                    try {
                        $object = app($class);
                        if (method_exists($object, $action)) {
                            $pluginCommands = $object->$action();
                            // 如果返回的是闭包，且闭包没有参数，尝试执行闭包
                            if ($pluginCommands instanceof \Closure) {
                                // 尝试反射获取闭包参数个数
                                $reflection = new \ReflectionFunction($pluginCommands);
                                if ($reflection->getNumberOfRequiredParameters() == 0) {
                                    $pluginCommands = $pluginCommands();
                                }
                            }
                            if (is_array($pluginCommands)) {
                                $commands = array_merge($commands, $pluginCommands);
                            }
                        }
                    } catch (\Exception $e) {
                        continue;
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
            }
        }
        return $commands;
    }
}
