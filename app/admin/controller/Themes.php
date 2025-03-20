<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Config;

class Themes extends BaseController
{

    var $uid;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->uid = get_login_admin('id');
    }
    /**
     * 数据列表
     */
    public function index()
    {
        $floderArr = list_dir('template');
        $config = get_config('theme');
        $themes = [];
        $template_path = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR;
        $separate_path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
        if ($floderArr) {
            foreach ($floderArr as $k => $v) {
                $result = self::getconfig($template_path . $v);
                if (empty($result) || !isset($result['name']) || empty($result['name'])) continue;
                $themeKey = 'template_' . $result['platform'];
                $result['floder'] = $v;
                $result['isuse'] = (isset($config[$themeKey]) && trim($config[$themeKey]) == $v) ? 1 : 0;
                $result['path'] = urlencode($template_path . $v);
                $themes[] = $result;
            }
        }
        if (isset($config['template_separate'])) {
            $path = $separate_path . 'h5';
            if (is_dir($path)) {
                $result = self::getconfig($path);
                if ($result) {
                    $result['floder'] = $config['template_separate'] ?: 'h5';
                    $result['isuse'] = $config['template_separate'] ? 1 : 0;
                    $result['path'] = urlencode($path);
                    $themes[] = $result;
                }
            }
        }
        View::assign('themes', $themes);
        return view();
    }

    static private function getconfig($path)
    {
        $result = [];
        $copyrightPath = $path . DIRECTORY_SEPARATOR . 'copyright.xml';
        if (file_exists($copyrightPath)) {
            $xmlFile = file_get_contents($copyrightPath);
            $ob = simplexml_load_string($xmlFile);
            $json = json_encode($ob);
            $result = json_decode($json, true);
        }
        $cover = $path . DIRECTORY_SEPARATOR . 'cover.jpg';
        if (file_exists($cover)) {
            $imageData = file_get_contents($cover);
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);
            $base64Image = base64_encode($imageData);
            $cover = "data:$mimeType;base64,$base64Image";
            $result['cover'] = $cover;
        } else {
            $result['cover'] = '';
        }
        return $result;
    }

    public function setup()
    {
        if (request()->isAjax()) {
            $param = get_params();
            if (!isset($param['name']) || empty($param['name'])) {
                return to_assign(1, '必要参数为空！');
            }
            if (!isset($param['platform']) || empty($param['platform'])) {
                return to_assign(1, '必要参数为空！');
            }
            $name = trim($param['name']);
            $platform = trim($param['platform']);
            $path = '';
            $themeKey = 'template_' . strtolower($platform);
            if ($themeKey == 'template_separate') {
                $path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $name;
            } else {
                $path = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . $name;
            }
            $result = self::getconfig($path);
            if (empty($result)) {
                return to_assign(1, '没有配置信息！');
            }
            if (!isset($result['name']) || empty($result['name']) || !isset($result['platform']) || empty($result['platform'])) {
                return to_assign(1, '配置信息有误！');
            }
            $config = get_config('theme');
            if (strtolower($result['platform']) == 'mobile') {
                $config['template_mobile'] = $name;
            }
            if (strtolower($result['platform']) == 'pc') {
                $config['template_pc'] = $name;
            }
            if (strtolower($result['platform']) == 'separate') {
                $config['template_separate'] = $name;
            }
            $config_file = app()->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'theme.php';
            if (file_put_contents($config_file, '<?php' . "\n" . 'return ' . var_export($config, true) . ';')) {
                return to_assign(0, '安装成功');
            } else {
                return to_assign(1, '保存失败，请检查权限！');
            }
        }
    }

    public function unload()
    {
        if (request()->isAjax()) {
            $param = get_params();
            if (!isset($param['name']) || empty($param['name'])) {
                return to_assign(1, '必要参数为空！');
            }
            $name = trim($param['name']);
            if (!isset($param['platform']) || empty($param['platform'])) {
                return to_assign(1, '必要参数为空！');
            }
            $platform = trim($param['platform']);
            $config = get_config('theme');
            if (strtolower($platform) == 'mobile') {
                $config['template_mobile'] = '';
            }
            if (strtolower($platform) == 'pc') {
                $config['template_pc'] = '';
            }
            if (strtolower($platform) == 'separate') {
                $config['template_separate'] = '';
            }
            $config_file = app()->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'theme.php';
            if (file_put_contents($config_file, '<?php' . "\n" . 'return ' . var_export($config, true) . ';')) {
                return to_assign(0, '安装成功');
            } else {
                return to_assign(1, '保存失败，请检查权限！');
            }
        }
    }

    public function files()
    {
        $param = get_params();
        $path = urldecode($param['path']);
        $list = $this->file_list($path);
        View::assign('list', $list);
        View::assign('parent_dir', dirname($path));
        return view();
    }

    public function edit()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $path = urldecode($param['path']);
            if (empty($path)) {
                return to_assign(1, '文件地址不能为空！');
            }
            if (!file_exists($path)) return to_assign(1, '文件不存在！');
            if (File::put($path, $param['content'])) {
                return to_assign(0, '保存成功');
            } else {
                return to_assign(1, '保存失败，请检查权限！');
            }
        } else {
            $path = urldecode($param['path']);
            $info = File::read($path);
            View::assign('path', $path);
            View::assign('content', $info);
            return view();
        }
    }

    public function file_list($path, $is_all = FALSE, $exts = '*')
    {
        $file_info = [];
        $list_info = File::list_dir_info($path, $is_all, $exts);
        foreach ($list_info as $key => $value) {
            $file_info[$key] = File::list_info($value);
        }
        return $file_info;
    }
}
class File
{

    static private $contents = [];
    /**
     * 架构函数
     * @access public
     */
    public function __construct() {}

    /**
     * 文件内容读取
     * @access public
     * @param string $filename  文件名
     * @return string     
     */
    static public function read($filename, $type = '')
    {
        return self::get($filename, 'content', $type);
    }

    /**
     * 文件写入
     * @access public
     * @param string $filename  文件名
     * @param string $content  文件内容
     * @return boolean         
     */
    static public function put($filename, $content, $type = '')
    {
        $dir   =  dirname($filename);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        if (false === file_put_contents($filename, $content)) {
            throw new \think\Exception('文件写入错误:' . $filename);
        } else {
            self::$contents[$filename] = $content;
            return true;
        }
    }

    /**
     * 文件追加写入
     * @access public
     * @param string $filename  文件名
     * @param string $content  追加的文件内容
     * @return boolean        
     */
    static public function append($filename, $content, $type = '')
    {
        if (is_file($filename)) {
            $content =  self::read($filename, $type) . $content;
        }
        return self::put($filename, $content, $type);
    }

    /**
     * 加载文件
     * @access public
     * @param string $filename  文件名
     * @param array $vars  传入变量
     * @return void        
     */
    static public function load($_filename, $vars = null)
    {
        if (!is_null($vars))
            extract($vars, EXTR_OVERWRITE);
        include $_filename;
    }

    /**
     * 文件是否存在
     * @access public
     * @param string $filename  文件名
     * @return boolean     
     */
    static public function has($filename, $type = '')
    {
        return is_file($filename);
    }

    /**
     * 文件删除
     * @access public
     * @param string $filename  文件名
     * @return boolean     
     */
    static public function unlink($filename, $type = '')
    {
        unset(self::$contents[$filename]);
        return is_file($filename) ? unlink($filename) : false;
    }

    /**
     * 读取文件信息
     * @access public
     * @param string $filename  文件名
     * @param string $name  信息名 mtime或者content
     * @return boolean     
     */
    static public function get($filename, $name, $type = '')
    {
        if (!isset(self::$contents[$filename])) {
            if (!is_file($filename)) return false;
            self::$contents[$filename] = file_get_contents($filename);
        }
        $content = self::$contents[$filename];
        $info   =   array(
            'mtime'     =>  filemtime($filename),
            'content'   =>  $content
        );
        return $info[$name];
    }

    /**
     * 获取指定路径下的信息
     * @param string $dir 路径
     * @return ArrayObject
     */
    static public function get_dir_info($dir)
    {
        $handle = @opendir($dir); //打开指定目录
        $directory_count = 0;
        $total_size = 0;
        $file_cout = 0;
        while (FALSE !== ($file_path = readdir($handle))) {
            if ($file_path != "." && $file_path != "..") {
                $next_path = $dir . '/' . $file_path;
                if (is_dir($next_path)) {
                    $directory_count++;
                    $result_value = self::get_dir_info($next_path);
                    $total_size += $result_value['size'];
                    $file_cout += $result_value['filecount'];
                    $directory_count += $result_value['dircount'];
                } elseif (is_file($next_path)) {
                    $total_size += filesize($next_path);
                    $file_cout++;
                }
            }
        }
        closedir($handle); //关闭指定目录
        $result_value['size'] = $total_size;
        $result_value['filecount'] = $file_cout;
        $result_value['dircount'] = $directory_count;
        return $result_value;
    }

    /**
     * 列出指定目录下符合条件的文件和文件夹
     * @param string $dirname 路径
     * @param boolean $is_all 是否列出子目录中的文件
     * @param string $exts 需要列出的后缀名文件
     * @param string $sort 数组排序
     * @return ArrayObject
     */
    static public function list_dir_info($dirname, $is_all = FALSE, $exts = '', $sort = 'ASC')
    {
        $sort = strtolower($sort); //将字符转换成小写
        $files = array();
        if (is_dir($dirname)) {
            $fh = opendir($dirname);
            while (($file = readdir($fh)) !== FALSE) {
                if (strcmp($file, '.') == 0 || strcmp($file, '..') == 0) continue;
                $filepath = $dirname . '/' . $file;
                switch ($exts) {
                    case '*':
                        if (is_dir($filepath) && $is_all == TRUE) {
                            $files = array_merge($files, self::list_dir_info($filepath, $is_all, $exts, $sort));
                        }
                        array_push($files, $filepath);
                        break;
                    case 'folder':
                        if (is_dir($filepath) && $is_all == TRUE) {
                            $files = array_merge($files, self::list_dir_info($filepath, $is_all, $exts, $sort));
                            array_push($files, $filepath);
                        } elseif (is_dir($filepath)) {
                            array_push($files, $filepath);
                        }
                        break;
                    case 'file':
                        if (is_dir($filepath) && $is_all == TRUE) {
                            $files = array_merge($files, self::list_dir_info($filepath, $is_all, $exts, $sort));
                        } elseif (is_file($filepath)) {
                            array_push($files, $filepath);
                        }
                        break;
                    default:
                        if (is_dir($filepath) && $is_all == TRUE) {
                            $files = array_merge($files, self::list_dir_info($filepath, $is_all, $exts, $sort));
                        } elseif (preg_match("/\.($exts)/i", $filepath) && is_file($filepath)) {
                            array_push($files, $filepath);
                        }
                        break;
                }
                switch ($sort) {
                    case 'asc':
                        sort($files);
                        break;
                    case 'desc':
                        rsort($files);
                        break;
                    case 'nat':
                        natcasesort($files);
                        break;
                }
            }
            closedir($fh);
            return $files;
        } else {
            return FALSE;
        }
    }
    /**
     * 返回指定文件和目录的信息
     * @param string $file
     * @return ArrayObject
     */
    static public function list_info($file)
    {
        $dir = array();
        $dir['filename']   = basename($file); //返回路径中的文件名部分。
        $dir['pathname']   = realpath($file); //返回绝对路径名。
        $dir['owner']      = fileowner($file); //文件的 user ID （所有者）。
        $dir['perms']      = fileperms($file); //返回文件的 inode 编号。
        $dir['inode']      = fileinode($file); //返回文件的 inode 编号。
        $dir['group']      = filegroup($file); //返回文件的组 ID。
        $dir['path']       = dirname($file); //返回路径中的目录名称部分。
        $dir['atime']      = fileatime($file); //返回文件的上次访问时间。
        $dir['ctime']      = filectime($file); //返回文件的上次改变时间。
        $dir['perms']      = fileperms($file); //返回文件的权限。 
        $dir['size']       = filesize($file); //返回文件大小。
        $dir['type']       = filetype($file); //返回文件类型。
        $dir['ext']        = is_file($file) ? pathinfo($file, PATHINFO_EXTENSION) : ''; //返回文件后缀名
        $dir['mtime']      = filemtime($file); //返回文件的上次修改时间。
        $dir['isDir']      = is_dir($file); //判断指定的文件名是否是一个目录。
        $dir['isFile']     = is_file($file); //判断指定文件是否为常规的文件。
        $dir['isLink']     = is_link($file); //判断指定的文件是否是连接。
        $dir['isReadable'] = is_readable($file); //判断文件是否可读。
        $dir['isWritable'] = is_writable($file); //判断文件是否可写。
        $dir['isUpload']   = is_uploaded_file($file); //判断文件是否是通过 HTTP POST 上传的。
        return $dir;
    }
}
