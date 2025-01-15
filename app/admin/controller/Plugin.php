<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;
// 插件服务
use app\admin\facade\ThinkAddons;

class Plugin extends BaseController
{

    var $uid;
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->uid = get_login_admin('id');
    }

    // 列表
    public function datalist()
    {
        if (request()->isAjax()) {
            $param = get_params();
            // 获取插件列表
            $list = ThinkAddons::localAddons();
            // 渲染输出
            $result = [
                'total' => count($list),
                'data' => $list,
            ];
            return table_assign(0, '', $result);
        } else {
            return view();
        }
    }

    // 插件配置信息保存
    public function config()
    {
        $param = get_params();
        if (request()->isAjax()) {
            if (Request::isPost()) {
                $result = ThinkAddons::configPost(Request::except(['file'], 'post'));
                return to_assign($result['code'], $result['msg']);
            }
        } else {
            try {
                $config = ThinkAddons::config($param['name']);
                // 如果插件自带配置模版的话加载插件自带的，否则调用表单构建器
                $file = app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $param['name'] . DIRECTORY_SEPARATOR . 'config.html';
                View::assign('name', $param['name']);
                if (file_exists($file)) {
                    View::assign([
                        'config' => $config
                    ]);
                    return view($file);
                } else {
                    View::assign('config', $config);
                    return view();
                }
            } catch (\Exception $e) {
                // 处理HttpException
                return json(['code' => 1, 'msg' => $e->getMessage(), 'data' => []]);
            }
        }
    }

    // 更改插件状态 [启用/禁用]
    public function state(string $id)
    {
        $result = ThinkAddons::state($id);
        return to_assign($result['code'], $result['msg']);
    }

    // 安装插件
    public function install(string $id)
    {
        $result = ThinkAddons::install($id);
        return to_assign($result['code'], $result['msg']);
    }

    // 卸载插件
    public function uninstall(string $id)
    {
        $result = ThinkAddons::uninstall($id);
        return to_assign($result['code'], $result['msg']);
    }

    // =========================================

    // 生成表单信息
    private function makeAddColumns(array $config)
    {
        // 判断是否开启了分组
        if (ThinkAddons::checkConfigGroup($config) === false) {
            // 未开启分组
            return $this->makeAddColumnsArr($config);
        } else {
            $columns = [];
            // 开启分组
            foreach ($config as $k => $v) {
                $columns[$k] = $this->makeAddColumnsArr($v);
            }
            return $columns;
        }
    }

    // 生成表单返回数组
    private function makeAddColumnsArr(array $config)
    {
        $columns = [];
        foreach ($config as $k => $field) {
            // 初始化
            $field['name'] = $field['name'] ?? $field['title'];
            $field['field'] = $k;
            $field['tips'] = $field['tips'] ?? '';
            $field['required'] = $field['required'] ?? 0;
            $field['group'] = $field['group'] ?? '';
            if (!isset($field['setup'])) {
                $field['setup'] = [
                    'default' => $field['value'] ?? '',
                    'extra_attr' => $field['extra_attr'] ?? '',
                    'extra_attr' => $field['extra_attr'] ?? '',
                    'extra_class' => $field['extra_class'] ?? '',
                    'placeholder' => $field['placeholder'] ?? '',
                ];
            }

            if ($field['type'] == 'text') {
                $columns[] = [
                    $field['type'],                // 类型
                    $field['field'],               // 字段名称
                    $field['name'],                // 字段别名
                    $field['tips'],                // 提示信息
                    $field['setup']['default'],    // 默认值
                    $field['group'],               // 标签组，可以在文本框前后添加按钮或者文字
                    $field['setup']['extra_attr'], // 额外属性
                    $field['setup']['extra_class'], // 额外CSS
                    $field['setup']['placeholder'], // 占位符
                    $field['required'],            // 是否必填
                ];
            } elseif ($field['type'] == 'textarea' || $field['type'] == 'password') {
                $columns[] = [
                    $field['type'],                       // 类型
                    $field['field'],                      // 字段名称
                    $field['name'],                       // 字段别名
                    $field['tips'],                       // 提示信息
                    $field['setup']['default'],           // 默认值
                    $field['setup']['extra_attr'],        // 额外属性
                    $field['setup']['extra_class'] ?? '', // 额外CSS
                    $field['setup']['placeholder'] ?? '', // 占位符
                    $field['required'],                   // 是否必填
                ];
            } elseif ($field['type'] == 'radio' || $field['type'] == 'checkbox') {
                $columns[] = [
                    $field['type'],                // 类型
                    $field['field'],               // 字段名称
                    $field['name'],                // 字段别名
                    $field['tips'],                // 提示信息
                    $field['options'],             // 选项（数组）
                    $field['setup']['default'],    // 默认值
                    $field['setup']['extra_attr'], // 额外属性 extra_attr
                    '',                            // 额外CSS extra_class
                    $field['required'],            // 是否必填
                ];
            } elseif ($field['type'] == 'select' || $field['type'] == 'select2') {
                $columns[] = [
                    $field['type'],                       // 类型
                    $field['field'],                      // 字段名称
                    $field['name'],                       // 字段别名
                    $field['tips'],                       // 提示信息
                    $field['options'],                    // 选项（数组）
                    $field['setup']['default'],           // 默认值
                    $field['setup']['extra_attr'],        // 额外属性
                    $field['setup']['extra_class'] ?? '', // 额外CSS
                    $field['setup']['placeholder'] ?? '', // 占位符
                    $field['required'],                   // 是否必填
                ];
            } elseif ($field['type'] == 'number') {
                $columns[] = [
                    $field['type'],                       // 类型
                    $field['field'],                      // 字段名称
                    $field['name'],                       // 字段别名
                    $field['tips'],                       // 提示信息
                    $field['setup']['default'],           // 默认值
                    '',                                   // 最小值
                    '',                                   // 最大值
                    $field['setup']['step'],              // 步进值
                    $field['setup']['extra_attr'],        // 额外属性
                    $field['setup']['extra_class'],       // 额外CSS
                    $field['setup']['placeholder'] ?? '', // 占位符
                    $field['required'],                   // 是否必填
                ];
            } elseif ($field['type'] == 'hidden') {
                $columns[] = [
                    $field['type'],                      // 类型
                    $field['field'],                     // 字段名称
                    $field['setup']['default'] ?? '',    // 默认值
                    $field['setup']['extra_attr'] ?? '', // 额外属性 extra_attr
                ];
            } elseif ($field['type'] == 'date' || $field['type'] == 'time' || $field['type'] == 'datetime') {
                // 使用每个字段设定的格式
                if ($field['type'] == 'time') {
                    $field['setup']['format'] = str_replace("HH", "h", $field['setup']['format']);
                    $field['setup']['format'] = str_replace("mm", "i", $field['setup']['format']);
                    $field['setup']['format'] = str_replace("ss", "s", $field['setup']['format']);
                    $format = $field['setup']['format'] ?? 'H:i:s';
                } else {
                    $field['setup']['format'] = str_replace("yyyy", "Y", $field['setup']['format']);
                    $field['setup']['format'] = str_replace("mm", "m", $field['setup']['format']);
                    $field['setup']['format'] = str_replace("dd", "d", $field['setup']['format']);
                    $field['setup']['format'] = str_replace("hh", "h", $field['setup']['format']);
                    $field['setup']['format'] = str_replace("ii", "i", $field['setup']['format']);
                    $field['setup']['format'] = str_replace("ss", "s", $field['setup']['format']);
                    $format = $field['setup']['format'] ?? 'Y-m-d H:i:s';
                }
                $field['setup']['default'] = $field['setup']['default'] > 0 ? date($format, $field['setup']['default']) : '';
                $columns[] = [
                    $field['type'],                // 类型
                    $field['field'],               // 字段名称
                    $field['name'],                // 字段别名
                    $field['tips'],                // 提示信息
                    $field['setup']['default'],    // 默认值
                    $field['setup']['format'],     // 日期格式
                    $field['setup']['extra_attr'], // 额外属性 extra_attr
                    '',                            // 额外CSS extra_class
                    $field['setup']['placeholder'], // 占位符
                    $field['required'],            // 是否必填
                ];
            } elseif ($field['type'] == 'daterange') {
                $columns[] = [
                    $field['type'],                       // 类型
                    $field['field'],                      // 字段名称
                    $field['name'],                       // 字段别名
                    $field['tips'],                       // 提示信息
                    $field['setup']['default'],           // 默认值
                    $field['setup']['format'],            // 日期格式
                    $field['setup']['extra_attr'] ?? '',  // 额外属性
                    $field['setup']['extra_class'] ?? '', // 额外CSS
                    $field['required'],                   // 是否必填
                ];
            } elseif ($field['type'] == 'tag') {
                $columns[] = [
                    $field['type'],                       // 类型
                    $field['field'],                      // 字段名称
                    $field['name'],                       // 字段别名
                    $field['tips'],                       // 提示信息
                    $field['setup']['default'],           // 默认值
                    $field['setup']['extra_attr'] ?? '',  // 额外属性
                    $field['setup']['extra_class'] ?? '', // 额外CSS
                    $field['required'],                   // 是否必填
                ];
            } elseif ($field['type'] == 'image' || $field['type'] == 'images' || $field['type'] == 'file' || $field['type'] == 'files') {
                $columns[] = [
                    $field['type'],                       // 类型
                    $field['field'],                      // 字段名称
                    $field['name'],                       // 字段别名
                    $field['tips'],                       // 提示信息
                    $field['setup']['default'],           // 默认值
                    $field['setup']['size'],              // 限制大小（单位kb）
                    $field['setup']['ext'],               // 文件后缀
                    $field['setup']['extra_attr'] ?? '',  // 额外属性
                    $field['setup']['extra_class'] ?? '', // 额外CSS
                    $field['setup']['placeholder'] ?? '', // 占位符
                    $field['required'],                   // 是否必填
                ];
            } elseif ($field['type'] == 'editor') {
                $columns[] = [
                    $field['type'],                       // 类型
                    $field['field'],                      // 字段名称
                    $field['name'],                       // 字段别名
                    $field['tips'],                       // 提示信息
                    $field['setup']['default'],           // 默认值
                    $field['setup']['heidht'] ?? 0,       // 高度
                    $field['setup']['extra_attr'] ?? '',  // 额外属性
                    $field['setup']['extra_class'] ?? '', // 额外CSS
                    $field['required'],                   // 是否必填
                ];
            } elseif ($field['type'] == 'color') {
                $columns[] = [
                    $field['type'],                       // 类型
                    $field['field'],                      // 字段名称
                    $field['name'],                       // 字段别名
                    $field['tips'],                       // 提示信息
                    $field['setup']['default'],           // 默认值
                    $field['setup']['extra_attr'] ?? '',  // 额外属性
                    $field['setup']['extra_class'] ?? '', // 额外CSS
                    $field['setup']['placeholder'] ?? '', // 占位符
                    $field['required'],                   // 是否必填
                ];
            }
        }
        return $columns;
    }

    // 生成列表页额外JS
    private function makeExtraJs()
    {
        $js = '<script type="text/javascript">
                // 安装
                $.operate.pluginInstall = function(id) {
                    var url = \'' . url('install') . '\';
                    $.modal.confirm("确认要安装?", function () {
                        var data = {"id": id};
                        $.operate.submit(url, "post", "json", data);
                    });
                }
                // 卸载
                $.operate.pluginUninstall = function(id) {
                    var url = \'' . url('uninstall') . '\';
                    $.modal.confirm("确认要卸载?", function () {
                        var data = {"id": id};
                        $.operate.submit(url, "post", "json", data);
                    });
                }
            </script>';
        return $js;
    }
}
