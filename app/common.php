<?php

// 应用公共文件
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;
use avatars\MDAvatars;
use think\Image;
use think\facade\Route;

//设置缓存
function set_cache($key, $value, $date = 86400)
{
    Cache::set($key, $value, $date);
}

//读取缓存
function get_cache($key)
{
    return Cache::get($key);
}

//清空缓存
function clear_cache($key)
{
    Cache::clear($key);
}


//读取文件配置
function get_config($key)
{
    return Config::get($key);
}

//读取系统配置
function get_system_config($name, $key = '')
{
    $config = [];
    if (get_cache('system_config' . $name)) {
        $config = get_cache('system_config' . $name);
    } else {
        $conf = Db::name('config')->where('name', $name)->find();
        if (!empty($conf['content'])) {
            $config = unserialize($conf['content']);
        }
        set_cache('system_config' . $name, $config);
    }
    if ($key == '') {
        return $config;
    } else {
        if (isset($config[$key]) && $config[$key]) {
            return $config[$key];
        }
    }
    return false;
}

//系统信息
function get_system_info($key)
{
    $system = [
        'os' => PHP_OS,
        'php' => PHP_VERSION,
        'upload_max_filesize' => get_cfg_var("upload_max_filesize") ? get_cfg_var("upload_max_filesize") : "不允许上传附件",
        'max_execution_time' => get_cfg_var("max_execution_time") . "秒 ",
    ];
    if (empty($key)) {
        return $system;
    } else {
        return $system[$key];
    }
}

//获取url参数
function get_params($key = "")
{
    return Request::instance()->param($key);
}

//生成一个不会重复的字符串
function make_token()
{
    $str = md5(uniqid(md5(microtime(true)), true));
    $str = sha1($str); //加密
    return $str;
}

//随机字符串，默认长度10
function set_salt($num = 10)
{
    $str = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
    $salt = substr(str_shuffle($str), 10, $num);
    return $salt;
}
//密码加密
function set_password($pwd, $salt)
{
    return md5(md5($pwd . $salt) . $salt);
}

//判断cms是否完成安装
function is_installed()
{
    static $isInstalled;
    if (empty($isInstalled)) {
        $isInstalled = file_exists(CMS_ROOT . 'config/install.lock');
    }
    return $isInstalled;
}

//判断cms是否存在模板
function isTemplate($url = '')
{
    static $isTemplate;
    if (empty($isTemplate)) {
        $isTemplate = file_exists(CMS_ROOT . 'app/' . $url);
    }
    return $isTemplate;
}

/**
 * 返回json数据，用于接口
 * @param    integer    $code
 * @param    string     $msg
 * @param    array      $data
 * @param    string     $url
 * @param    integer    $httpCode
 * @param    array      $header
 * @param    array      $options
 * @return   json
 */
function to_assign($code = 0, $msg = "操作成功", $data = [], $url = '', $httpCode = 200, $header = [], $options = [])
{
    $res = ['code' => $code];
    $res['msg'] = $msg;
    $res['url'] = $url;
    if (is_object($data)) {
        $data = $data->toArray();
    }
    $res['data'] = $data;
    $response = \think\Response::create($res, "json", $httpCode, $header, $options);
    throw new \think\exception\HttpResponseException($response);
}

/**
 * 适配layui数据列表的返回数据方法，用于接口
 * @param    integer    $code
 * @param    string     $msg
 * @param    array      $data
 * @param    integer    $httpCode
 * @param    array      $header
 * @param    array      $options
 * @return   json
 */
function table_assign($code = 0, $msg = '请求成功', $data = [], $httpCode = 200, $header = [], $options = [])
{
    $res['code'] = $code;
    $res['msg'] = $msg;
    if (is_object($data)) {
        $data = $data->toArray();
    }
    if (!empty($data['total'])) {
        $res['count'] = $data['total'];
    } else {
        $res['count'] = 0;
    }
    $res['data'] = $data['data'];
    $response = \think\Response::create($res, "json", $httpCode, $header, $options);
    throw new \think\exception\HttpResponseException($response);
}

//菜单转为父子菜单
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'list', $root = 0)
{
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[$data[$pk]] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][$data[$pk]] = &$list[$key];
                }
            }
        }
    }
    return $tree;
}
/**
 * 时间戳格式化
 * @param int    $time
 * @param string $format 默认'Y-m-d H:i'，x代表毫秒
 * @return string 完整的时间显示
 */
function time_format($time = NULL, $format = 'Y-m-d H:i:s')
{
    $usec = $time = $time === null ? '' : $time;
    if (strpos($time, '.') !== false) {
        list($usec, $sec) = explode(".", $time);
    } else {
        $sec = 0;
    }

    return $time != '' ? str_replace('x', $sec, date($format, intval($usec))) : '';
}

/**
 * 根据附件表的id返回url地址
 * @param  [type] $id [description]
 */
function get_file($id)
{
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    $arr_url = parse_url($_SERVER['HTTP_HOST']);
    $sitepath = $http_type . ($arr_url['path'] ? $arr_url['path'] : $_SERVER['HTTP_HOST']) . '/';
    if ($id) {
        $id2num = intval($id);
        if ($id2num == $id) {
            $geturl = Db::name("file")->where(['id' => $id])->find();
            if (!empty($geturl)) {
                if ($geturl['status'] == 1) {
                    //审核通过
                    $url = $geturl['filepath'];
                    return $url;
                } else {
                    //待审核
                    return $sitepath . 'static/assets/init/images/data-none.png';
                }
            } else {
                //不通过
                return $sitepath . 'static/assets/init/images/data-none.png';
            }
        } else {
            if ($id == 'undefined') {
                return $sitepath . 'static/assets/init/images/data-none.png';
            }
            if ($id && strpos(trim($id), 'nocover') !== false) {
                return $sitepath . 'static/assets/init/images/data-none.png';
            }
            if ($id && strpos(trim($id), 'Public/author/image/cover/') !== false) {
                return $sitepath . str_replace("Public/author/image/cover/", "static/author/cover/", $id);
            }
            if (strpos($id, 'http') !== false) {
                return $id;
            }
            return \think\facade\App::initialize()->http->getName() == 'api' ? $sitepath . $id : $id;
        }
    }
    return $sitepath . 'static/assets/init/images/data-none.png';
}

function get_file_list($dir)
{
    $list = [];
    if (is_dir($dir)) {
        $info = opendir($dir);
        while (($file = readdir($info)) !== false) {
            //echo $file.'<br>';
            $pathinfo = pathinfo($file);
            if ($pathinfo['extension'] == 'html') {   //只获取符合后缀的文件
                array_push($list, $pathinfo);
            }
        }
        closedir($info);
    }
    return $list;
}

//获取当前登录用户的信息
function get_login_user($key = "")
{
    $session_user = get_config('app.session_user');
    if (\think\facade\Session::has($session_user)) {
        $user = \think\facade\Session::get($session_user);
        if (!empty($key)) {
            if (isset($user[$key])) {
                return $user[$key];
            } else {
                return '';
            }
        } else {
            return $user;
        }
    } else {
        return '';
    }
}

//获取当前登录作者的信息
function get_login_author($key = "")
{
    $session_author = get_config('app.session_author');
    if (\think\facade\Session::has($session_author)) {
        $cms_user = \think\facade\Session::get($session_author);
        if (!empty($key)) {
            if (isset($cms_user[$key])) {
                return $cms_user[$key];
            } else {
                return '';
            }
        } else {
            return $cms_user;
        }
    } else {
        return '';
    }
}

/**
 * 判断访客是否是蜘蛛
 */
function isRobot($except = '')
{
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
    $botchar = "/(baidu|google|spider|soso|yahoo|sohu-search|yodao|robozilla|AhrefsBot)/i";
    $except ? $botchar = str_replace($except . '|', '', $botchar) : '';
    if (preg_match($botchar, $ua)) {
        return true;
    }
    return false;
}

/**
 * 客户操作日志
 * @param string $type 操作类型 login reg add edit view delete down join sign play order pay
 * @param string    $param_str 操作内容
 * @param int    $param_id 操作内容id
 * @param array  $param 提交的参数
 */
function add_user_log($type, $param_str = '', $param_id = 0, $param = [])
{
    $request = request();
    $title = '未知操作';
    $type_action = get_config('log.user_action');
    if ($type_action[$type]) {
        $title = $type_action[$type];
    }
    if ($type == 'login') {
        $login_user = Db::name('User')->where(array('id' => $param_id))->find();
        if ($login_user['nickname'] == '') {
            $login_user['nickname'] = $login_user['name'];
        }
        if ($login_user['nickname'] == '') {
            $login_user['nickname'] = $login_user['mobile'];
        }
    } else {
        $login_user = get_login_user();
        if (empty($login_user)) {
            $login_user = [];
            $login_user['id'] = 0;
            $login_user['nickname'] = '游客';
            if (isRobot()) {
                $login_user['nickname'] = '蜘蛛';
                $type = 'spider';
                $title = '爬行';
            }
        } else {
            if ($login_user['nickname'] == '') {
                $login_user['nickname'] = $login_user['mobile'];
            }
        }
    }
    $content = $login_user['nickname'] . '在' . date('Y-m-d H:i:s') . '执行了' . $title . '操作';
    if ($param_str != '') {
        $content = $login_user['nickname'] . '在' . date('Y-m-d H:i:s') . $title . '了' . $param_str;
    }
    $data = [];
    $data['uid'] = $login_user['id'];
    $data['nickname'] = $login_user['nickname'];
    $data['type'] = $type;
    $data['title'] = $title;
    $data['content'] = $content;
    $data['param_id'] = $param_id;
    $data['param'] = json_encode($param);
    $data['module'] = strtolower(app('http')->getName());
    $data['controller'] = strtolower(app('request')->controller());
    $data['function'] = strtolower(app('request')->action());
    $data['ip'] = app('request')->ip();
    $data['create_time'] = time();
    Db::name('UserLog')->strict(false)->field(true)->insert($data);
}

/**
 * 邮件发送
 * @param $to    接收人
 * @param string $subject 邮件标题
 * @param string $content 邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function send_email($to, $subject = '', $content = '')
{
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $email_config = Db::name('config')
        ->where('name', 'email')
        ->find();
    $config = unserialize($email_config['content']);

    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->isSMTP();
    $mail->SMTPDebug = 0;

    //调试输出格式
    //$mail->Debugoutput = 'html';
    //smtp服务器
    $mail->Host = $config['smtp'];
    //端口 - likely to be 25, 465 or 587
    $mail->Port = $config['smtp_port'];
    if ($mail->Port == '465') {
        $mail->SMTPSecure = 'ssl'; // 使用安全协议
    }
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //发送邮箱
    $mail->Username = $config['smtp_user'];
    //密码
    $mail->Password = $config['smtp_pwd'];
    //Set who the message is to be sent from
    $mail->setFrom($config['email'], $config['from']);
    //回复地址
    //$mail->addReplyTo('replyto@example.com', 'First Last');
    //接收邮件方
    if (is_array($to)) {
        foreach ($to as $v) {
            $mail->addAddress($v);
        }
    } else {
        $mail->addAddress($to);
    }

    $mail->isHTML(true); // send as HTML
    //标题
    $mail->Subject = $subject;
    //HTML内容转换
    $mail->msgHTML($content);
    $status = $mail->send();
    if ($status) {
        return true;
    } else {
        //  echo "Mailer Error: ".$mail->ErrorInfo;// 输出错误信息
        //  die;
        return false;
    }
}

/*
 * 下划线转驼峰
 * 思路:
 * step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
 * step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
 */
function camelize($uncamelized_words, $separator = '_')
{
    $uncamelized_words = $separator . str_replace($separator, " ", strtolower($uncamelized_words));
    return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator);
}

/**
 * 驼峰命名转下划线命名
 * 思路:
 * 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
 */
function uncamelize($camelCaps, $separator = '_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}

function list_dir($dir)
{
    $files = glob(CMS_ROOT . '/' . $dir . '/*');
    foreach ($files as $v) {
        if (is_file($v))
            continue;
        $v = basename($v);
        $dirs[] = $v;
    }
    return $dirs;
}

//生成uuid
if (!function_exists('uuid')) {
    function uuid($prefix = '')
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8);
        $uuid .= substr($chars, 8, 4);
        $uuid .= substr($chars, 12, 4);
        $uuid .= substr($chars, 16, 4);
        $uuid .= substr($chars, 20, 12);
        return $prefix . $uuid;
    }
}

if (!function_exists('get_url')) {
    function get_url($url)
    {
        $ch = curl_init();
        $header[] = "";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }
}

if (!function_exists('calc_hash_db')) {
    /**
     * 分库分表算法
     * s表示有多少个表
     * u表示hash的参数|作品ID
     **/
    function calc_hash_db($u, $s = 10)
    {
        $tables = config('database.connections.mysql.chapter_Tables');
        $s = intval($tables) > 0 ? intval($tables) : $s;
        $h = sprintf("%u", crc32($u));
        $h1 = intval(fmod($h, $s));
        return 'chapter_content_' . $h1;
    }
}

/**
 * @param $idcard 身份证号
 * @return string
 * 根据身份证号判断年龄和年龄分段
 */
if (!function_exists('getAgeByIDCard')) {
    function getAgeByIDCard($idcard)
    {
        //过了这年的生日才算多了1周岁
        if (empty($idcard)) {
            return 0;
        }
        $date = strtotime(substr($idcard, 6, 8)); //获得出生年月日的时间戳
        $today = strtotime('today'); //获得今日的时间戳
        $diff = floor(($today - $date) / 86400 / 365); //得到两个日期相差的大体年数
        //strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
        $age = strtotime(substr($idcard, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;
        $age = (int) $age;
        return $age;
    }
}

/**
 * 数字转换为中文
 */
if (!function_exists('money_converse')) {
    function money_converse($num)
    {
        $char = array("零", "一", "二", "三", "四", "五", "六", "七", "八", "九");
        $dw = array("", "十", "百", "千", "万", "亿", "兆");
        $retval = "";
        $proZero = false;
        if (intval($num) == $num) {
            $num = intval($num);
        }
        for ($i = 0; $i < strlen($num); $i++) {
            if ($i > 0) {
                $temp = (int) (($num % pow(10, $i + 1)) / pow(10, $i));
            } else {
                $temp = (int) ($num % pow(10, 1));
            }
            if ($proZero == true && $temp == 0) {
                continue;
            }
            if ($temp == 0) {
                $proZero = true;
            } else {
                $proZero = false;
            }
            if ($proZero) {
                if ($retval == "") {
                    continue;
                }
                $retval = $char[$temp] . $retval;
            } else {
                $retval = $char[$temp] . $dw[$i] . $retval;
            }
        }
        if ($retval == "一十") {
            $retval = "十";
        }
        return $retval;
    }
}

//数字转大写数字
if (!function_exists('numConvertWord')) {
    function numConvertWord($num)
    {
        $chiNum = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $chiUni = array('', '十', '百', '千', '万', '十', '百', '千', '亿');
        $chiStr = '';
        $num_str = (string) $num;
        $count = strlen($num_str);
        $last_flag = true; //上一个 是否为0
        $zero_flag = true; //是否第一个
        $temp_num = null; //临时数字
        $chiStr = ''; //拼接结果
        if ($count == 2) {
            //两位数
            $temp_num = $num_str[0];
            $chiStr = $temp_num == 1 ? $chiUni[1] : $chiNum[$temp_num] . $chiUni[1];
            $temp_num = $num_str[1];
            $chiStr .= $temp_num == 0 ? '' : $chiNum[$temp_num];
        } else if ($count > 2) {
            $index = 0;
            for ($i = $count - 1; $i >= 0; $i--) {
                $temp_num = $num_str[$i];
                if ($temp_num == 0) {
                    if (!$zero_flag && !$last_flag) {
                        $chiStr = $chiNum[$temp_num] . $chiStr;
                        $last_flag = true;
                    }

                    if ($index == 4 && $temp_num == 0) {
                        $chiStr = "万" . $chiStr;
                    }
                } else {
                    if ($i == 0 && $temp_num == 1 && $index == 1 && $index == 5) {
                        $chiStr = $chiUni[$index % 9] . $chiStr;
                    } else {
                        $chiStr = $chiNum[$temp_num] . $chiUni[$index % 9] . $chiStr;
                    }
                    $zero_flag = false;
                    $last_flag = false;
                }
                $index++;
            }
        } else {
            $chiStr = $chiNum[$num_str[0]];
        }
        return $chiStr;
    }
}

//数字转大写数字
if (!function_exists('get_full_chapter')) {
    function get_full_chapter($title = '', $seria = 0)
    {
        if (empty($title)) {
            return $title;
        }
        if (preg_match_all("/([第]?[\d一二三四五六七八九零十百千]+[章节])([^\r\n]+)/u", $title, $arr)) {
            return $title;
        }
        return '第' . numConvertWord(intval($seria)) . '章 ' . $title;
    }
}

//字数统计
if (!function_exists('countWordsAndContent')) {
    function countWordsAndContent($content, $filter = false)
    {
        if (!empty($content)) {
            if ($filter) {
                $str = htmlspecialchars_decode($content);
                $str = str_replace(array("\r", "\n", '\r\n', '\r', '\n', '<br>', '<div>'), array("\n", "\n", "\n", "\n", "\n", "\n", "\n"), $str); //换行
                $search = array("　", "&nbsp;", "", "	");
                $replace = array(" ", " ", " ", " ");
                $str = str_replace($search, $replace, $str);
                $str = strip_tags($str);
                //去除多余空行
                $str = explode("\n", $str);
                $str = array_filter($str, function ($value) {
                    return (trim($value) !== NULL && trim($value) !== "" && trim($value) !== " " && trim($value) !== " ");
                });
                $str = implode("\n", $str);
                $cnt = str_replace(array("\n", " "), array("", ""), $str);
            }
            if (!$filter) {
                $str = str_replace(array("\r\n", "\r", "\n", '\r\n', '\r', '\n'), '<br>', $content);
                $str = preg_replace('/\s+/', '&nbsp;', $str);
                $str = htmlspecialchars_decode($str);
                $str = preg_replace("/\"/is", "", $str);
                $str = str_replace('&emsp;', "", $str);
                //$cnt = strip_tags(str_replace(array('&nbsp;'), "", $str));
                $cnt = str_replace(array('&nbsp;', '<br>'), "", $str);
            }
            //新算法
            $arr = mb_str_split($cnt);
            $cn = 0;
            $en = 0;
            foreach ($arr as $k => $v) {
                if (strlen($v) == 3) {
                    $cn++;
                } else {
                    $en++;
                }
            }
            $len = $cn + $en;
            return [$len, $str];
        }
        return [0, ''];
    }
}

if (!function_exists('get_real_ip')) {
    function get_real_ip()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            return $arr[0] ? trim($arr[0]) : $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return get_client_ip();
        }
    }
}

//根据身份证号得到年龄、生日、性别
if (!function_exists('idcardToAgeBirthdaySex')) {
    function idcardToAgeBirthdaySex($idcard = '')
    {
        if (empty($idcard) || !isIdcard($idcard)) {
            return false;
        }
        $sex = substr($idcard, (strlen($idcard) == 18 ? -2 : -1), 1) % 2 ? '1' : '2'; //18位身份证取性别，倒数第二位奇数是男，偶数是女；
        $birthday = strlen($idcard) == 15 ? ('19' . substr($idcard, 6, 6)) : substr($idcard, 6, 8); //取身份证年月日；
        $birthdays = strtotime(strlen($idcard) == 15 ? ('19' . substr($idcard, 6, 6)) : substr($idcard, 6, 8)); //身份证年月日转换成时间戳
        $today = strtotime('today'); //取当天日期；
        $diff = floor(($today - $birthdays) / 86400 / 365); //用时间戳相减算出年龄；
        $age = strtotime(substr($idcard, 6, 8) . '+' . $diff . 'years') > $today ? ($diff + 1) : $diff; //取出年龄值；
        return ['sex' => $sex, 'birthday' => date('Y-m-d', $birthdays), 'age' => $age];
    }
}

//身份证验证
if (!function_exists('isIdcard')) {
    function isIdcard($id)
    {
        $id = strtoupper($id);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = [];
        if (!preg_match($regx, $id)) {
            return false;
        }
        if (15 == strlen($id)) {
            // 检查15位
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
            @preg_match($regx, $id, $arr_split);
            // 检查生日日期是否正确
            $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            if (!strtotime($dtm_birth)) {
                return false;
            } else {
                return true;
            }
        } else {
            // 检查18位
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            //检查生日日期是否正确
            if (!strtotime($dtm_birth)) {
                return false;
            } else {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X能够认为是数字10。
                $arr_int = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
                $arr_ch = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
                $sign = 0;
                for ($i = 0; $i < 17; $i++) {
                    $b = (int) $id[$i];
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($id, 17, 1)) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }
}

if (!function_exists('makecover')) {
    function makecover($bid)
    {
        if (empty($bid)) {
            return false;
        }
        $book = Db::name('book')->where(['id' => $bid])->find();
        if (empty($book)) {
            return false;
        }
        $category = Db::name('category')->where(['id' => $book['genre']])->find();
        if (empty($category)) {
            return false;
        }
        $user = Db::name('author')->where(['id' => $book['authorid']])->find();
        if (empty($user)) {
            return false;
        }
        $title = $book['title'];
        $bgPath = CMS_ROOT . 'public/static/home/images/default_cover_bg.png';
        $cateiconpath = CMS_ROOT . 'public/static/home/images/cateicon/';
        $catefile = $cateiconpath . $category['id'] . '.png';
        if (!is_file($catefile)) {
            $catefile = $cateiconpath . 'default.png';
        }
        try {
            //保存目录
            $savePath = get_config('filesystem.disks.public.root') . '/cover/' . $book['authorid'] . '/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
            $filePath = $savePath . $book['id'] . '.png';
            $coverPath = get_config('filesystem.disks.public.url') . '/cover/' . $book['authorid'] . '/' . $book['id'] . '.png';
            // 水印文字
            $author_name = $user['nickname'] . ' 著';
            // 加载图片
            $image = Image::open($bgPath);
            // 设置水印文字的字体、大小、颜色、位置等属性
            $bookfontPath = CMS_ROOT . 'public/static/home/font/SourceHanSansCN-Heavy.otf'; // 字体文件路径标题
            $authorfontPath = CMS_ROOT . 'public/static/home/font/Alibaba-PuHuiTi-Regular.otf'; // 字体文件路径作者
            $color = '#60412d'; // 水印颜色
            $image->water($catefile, [179, 60], 50); //添加分类图标
            // 作品名称
            $arr = mb_str_split($title);
            $len = count($arr);
            if ($len > 7 && $len <= 20) {
                //三行
                if ($len > 14) {
                    $line1 = implode('', array_slice($arr, 0, 7));
                    $line2 = implode('', array_slice($arr, 7, 7));
                    $line3 = implode('', array_slice($arr, 14));
                    $image->text($line1, $bookfontPath, 50, $color, 5, [0, -40]);
                    $image->text($line2, $bookfontPath, 50, $color, 5, [0, 60]);
                    $image->text($line3, $bookfontPath, 50, $color, 5, [0, 160]);
                    $image->text($author_name, $authorfontPath, 30, $color, 5, [0, 280])->save($filePath); //添加作者
                } else {
                    // 两行
                    $line1 = implode('', array_slice($arr, 0, 7));
                    $line2 = implode('', array_slice($arr, 7));
                    $image->text($line1, $bookfontPath, 50, $color, 5, [0, -20]);
                    $image->text($line2, $bookfontPath, 50, $color, 5, [0, 90]);
                    // 添加水印
                    $image->text($author_name, $authorfontPath, 30, $color, 5, [0, 280])->save($filePath); //添加作者
                }
            } else {
                //一行
                $image->text($title, $bookfontPath, 50, $color, 5, [0, 0]);
                // 添加水印
                $image->text($author_name, $authorfontPath, 30, $color, 5, [0, 220])->save($filePath); //添加作者
            }
            if (is_file($filePath)) {
                return $coverPath;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('make_avatars')) {
    function make_avatars($char)
    {
        $defaultData = array(
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'S',
            'Y',
            'Z',
            '0',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            '零',
            '壹',
            '贰',
            '叁',
            '肆',
            '伍',
            '陆',
            '柒',
            '捌',
            '玖',
            '拾',
            '一',
            '二',
            '三',
            '四',
            '五',
            '六',
            '七',
            '八',
            '九',
            '十'
        );
        if (isset($char)) {
            $Char = $char;
        } else {
            $Char = $defaultData[mt_rand(0, count($defaultData) - 1)];
        }
        $OutputSize = min(512, empty($_GET['size']) ? 36 : intval($_GET['size']));
        $Avatar = new MDAvatars($Char, 256, 1);
        $avatar_name = '/avatars/avatar_256_' . set_salt(10) . time() . '.png';
        $path = get_config('filesystem.disks.public.url') . $avatar_name;
        $res = $Avatar->Save('.' . $path, 256);
        $Avatar->Free();
        return $path;
    }
}

//字符串截取
if (!function_exists('dsubstr')) {
    function dsubstr($string, $length, $suffix = '', $start = 0)
    {
        if ($start) {
            $tmp = dsubstr($string, $start);
            $string = substr($string, strlen($tmp));
        }
        $strlen = strlen($string);
        if ($strlen <= $length)
            return $string;
        $string = str_replace(array('&quot;', '&lt;', '&gt;'), array('"', '<', '>'), $string);
        $length = $length - strlen($suffix);
        $str = '';
        $n = $tn = $noc = 0;
        while ($n < $strlen) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n++;
            }
            if ($noc >= $length)
                break;
        }
        if ($noc > $length)
            $n -= $tn;
        $str = substr($string, 0, $n);
        $str = str_replace(array('"', '<', '>'), array('&quot;', '&lt;', '&gt;'), $str);
        return $str == $string ? $str : $str . $suffix;
    }
}
if (!function_exists('isMobile')) {
    function isMobile(): bool
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA'])) {
            return (bool) stristr($_SERVER['HTTP_VIA'], "wap"); // 找不到为flase,否则为TRUE
        }
        // 判断手机发送的客户端标志,兼容性有待提高
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientKeywords = array(
                'mobile',
                'nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientKeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        if (isset($_SERVER['HTTP_ACCEPT'])) { // 协议法，因为有可能不准确，放到最后判断
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== FALSE) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === FALSE || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('time_tran')) {
    function time_tran($time = NULL)
    {
        $text = '';
        $time = $time === NULL || $time > time() ? time() : intval($time);
        $t = time() - $time; //时间差 （秒）
        $y = date('Y', $time) - date('Y', time()); //是否跨年
        switch ($t) {
            case $t == 0:
                $text = '刚刚';
                break;
            case $t < 60:
                $text = $t . '秒前'; // 一分钟内
                break;
            case $t < 60 * 60:
                $text = floor($t / 60) . '分钟前'; //一小时内
                break;
            case $t < 60 * 60 * 24:
                $text = floor($t / (60 * 60)) . '小时前'; // 一天内
                break;
            case $t < 60 * 60 * 24 * 3:
                $text = floor($time / (60 * 60 * 24)) == 1 ? '昨天 ' . date('H:i', $time) : '前天 ' . date('H:i', $time); //昨天和前天
                break;
            case $t < 60 * 60 * 24 * 30:
                $text = date('m月d日 H:i', $time); //一个月内
                break;
            case $t < 60 * 60 * 24 * 365 && $y == 0:
                $text = date('m月d日', $time); //一年内
                break;
            default:
                $text = date('Y年m月d日', $time); //一年以前
                break;
        }
        return $text;
    }
}

//获取邀请码
if (!function_exists('get_invite_code')) {
    function get_invite_code()
    {
        $invite_code = set_salt();
        $res = Db::name('user')->where(['qrcode_invite' => $invite_code])->find();
        if (!empty($res)) {
            get_invite_code();
        }
        return $invite_code;
    }
}

//随机用户昵称
if (!function_exists('randNickname')) {
    function randNickname()
    {
        $nickname = get_system_config('web', 'admin_title') . '_' . set_salt(10);
        $data = Db::name('user')->where(array('nickname' => $nickname))->find();
        if (empty($data)) {
            return $nickname;
        } else {
            randNickname();
        }
    }
}

//随机作者昵称
if (!function_exists('randNicknameAuthor')) {
    function randNicknameAuthor()
    {
        $nickname = get_system_config('web', 'admin_title') . '_' . set_salt(10);
        $data = Db::name('author')->where(array('nickname' => $nickname))->find();
        if (empty($data)) {
            return $nickname;
        } else {
            randNicknameAuthor();
        }
    }
}

//金币记录
if (!function_exists('add_coin_log')) {
    function add_coin_log($user_id = 0, $number = 0, $type = 1, $title = '未知操作')
    {
        if (intval($user_id) > 0 && intval($number) > 0) {
            $user = Db::name('user')->where(['id' => $user_id])->find();
            if (!empty($user)) {
                $data = [];
                $data['user_id'] = $user['id'];
                $data['type'] = $type;
                $data['title'] = $title;
                $data['amount'] = intval($number);
                $data['balance'] = $user['coin'];
                $data['ip'] = app('request')->ip();
                $data['create_time'] = time();
                return Db::name('coin_log')->strict(false)->field(true)->insert($data);
            }
        }
        return 0;
    }
}

// 检查是否是微信环境  
if (!function_exists('isWeChat')) {
    function isWeChat()
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
    }
}

if (!function_exists('model')) {
    function model($table)
    {
        return Db::name($table);
    }
}
if (!function_exists('wordCount')) {
    function wordCount($words)
    {
        if (!$words) return $words;
        $words = intval($words);
        if ($words > 1000 && $words < 10000) {
            $words = sprintf("%.1f", $words / 1000) . '千字';
        } else if ($words > 10000) {
            $words = sprintf("%.1f", $words / 10000) . '万字';
        } else {
            $words = $words . '字';
        }
        return $words;
    }
}
if (!function_exists('get_seo_str')) {
    function get_seo_str($type, $name = '', $content = '', $data = [])
    {
        $tags = ['{网站名}', '{大类名}', '{小类名}', '{书名}', '{作品标签}', '{作品简介}', '{作品签约状态}', '{作者}', '{作者所有作品名}', '{作者注册时间}', '{作者签约状态}', '{点击量}', '{年份}', '{连载状态}', '{作品字数}', '{章节数}', '{章节名}', '{域名}', '{邀请金币}'];
        if ($type == 'key') return $tags;
        $system_config = get_system_config('web');
        $seo_config = get_system_config('seo');
        $site_title = (isset($seo_config['site_title']) && $seo_config['site_title']) ? $seo_config['site_title'] : $system_config['title'];
        $domain = (isset($seo_config['domain']) && $seo_config['domain']) ? $seo_config['domain'] : '';
        if (empty($content)) {
            if (empty($type) || empty($name)) return $site_title;
            if (!isset($seo_config[$name]) || empty($seo_config[$name])) return $site_title;
            $content = $seo_config[$name];
        }
        if (empty($content)) return $site_title;
        $year = date('Y');
        $content = str_replace('{网站名}', $site_title, $content);
        $content = str_replace('{年份}', $year, $content);
        $content = str_replace('{域名}', $domain, $content);
        switch ($type) {
            case 'home':
                # 首页
                return $content;
                break;
            case 'classify':
                # 栏目页
                if (empty($data)) {
                    $category = Db::name('category')->where("status", 1)->orderRaw('RAND()')->limit(1)->select()->toArray();
                    $category = $category ? $category[0] : [];
                }
                if (isset($data['cateid']) && intval($data['cateid']) > 0) {
                    $category = Db::name('category')->where(['id' => $data['cateid']])->find();
                }
                $genre = $subgenre = [];
                if (!empty($category)) {
                    if (intval($category['pid']) > 0) {
                        $genre = Db::name('category')->where(['id' => $category['pid']])->find();
                        $subgenre = $category;
                    } else {
                        $genre = $category;
                    }
                }
                unset($category);
                if (strpos($content, '{大类名}') !== false && $genre) {
                    $content = str_replace('{大类名}', $genre['name'], $content);
                } else {
                    $content = str_replace('{大类名}', '', $content);
                }
                if (strpos($content, '{小类名}') !== false && $subgenre) {
                    $content = str_replace('{小类名}', $subgenre['name'], $content);
                } else {
                    $content = str_replace('{小类名}', '', $content);
                }
                return $content;
                break;
            case 'shuku':
                # 书库               
                return $content;
                break;
            case 'top':
                # 排行
                return $content;
                break;
            case 'quanben':
                # 全本
                return $content;
                break;
            case 'invite':
                # 邀请
                if (strpos($content, '{邀请金币}') !== false) {
                    $coin = get_system_config('reward', 'invite_reward');
                    $content = str_replace('{邀请金币}', $coin, $content);
                }
                return $content;
                break;
            case 'task':
                # 任务
                return $content;
                break;
            case 'book':
                # 书页
                if (empty($data)) {
                    $book = Db::name('book')->where("status", 1)->orderRaw('RAND()')->limit(1)->select()->toArray();
                    $book = $book ? $book[0] : [];
                }
                if (isset($data['bookid']) && intval($data['bookid']) > 0) {
                    $book = Db::name('book')->where(['id' => $data['bookid']])->find();
                }
                $content = seo_book_tag($content, $seo_config, $book);
                return $content;
                break;
            case 'author':
                # 作者
                $author = [];
                if (empty($data)) {
                    $author = Db::name('author')->field('id,nickname,create_time,issign')->where("status", 1)->orderRaw('RAND()')->limit(1)->select()->toArray();
                    $author = $author ? $author[0] : [];
                }
                if (isset($data['authorid']) && intval($data['authorid']) > 0) {
                    $author = Db::name('author')->field('id,nickname,create_time,issign')->where(['id' => $data['authorid']])->find();
                }
                if (!empty($author)) {
                    $content = str_replace('{作者}', $author['nickname'], $content);
                    if (strpos($content, '{作者注册时间}') !== false) {
                        $content = str_replace('{作者注册时间}', date('Y-m-d', $author['create_time']), $content);
                    }
                    if (strpos($content, '{作者签约状态}') !== false) {
                        $content = str_replace('{作者签约状态}', (intval($author['issign']) == 1 ? '已签约' : '待签约'), $content);
                    }
                    if (strpos($content, '{作者所有作品名}') !== false) {
                        $books = Db::name('book')->field('title')->where(['authorid' => $author['id']])->select()->toArray();
                        $titles = [];
                        foreach ($books as $k => $v) {
                            if (intval($seo_config['book_mark']) == 1) {
                                $titles[] = '《' . $v['title'] . '》';
                            } else {
                                $titles[] = $v['title'];
                            }
                        }
                        $title_str = implode($seo_config['book_split'], $titles);
                        $content = str_replace('{作者所有作品名}', $title_str, $content);
                    }
                } else {
                    $content = str_replace('{作者}', '', $content);
                    $content = str_replace('{作者注册时间}', '', $content);
                    $content = str_replace('{作者签约状态}', '', $content);
                    $content = str_replace('{作者所有作品名}', '', $content);
                }
                return $content;
                break;
            case 'chapter_list':
                # 目录页
                if (empty($data)) {
                    $book = Db::name('book')->where("status", 1)->orderRaw('RAND()')->limit(1)->select()->toArray();
                    $book = $book ? $book[0] : [];
                }
                if (isset($data['bookid']) && intval($data['bookid']) > 0) {
                    $book = Db::name('book')->where(['id' => $data['bookid']])->find();
                }
                $content = seo_book_tag($content, $seo_config, $book);
                return $content;
                break;
            case 'chapter':
                # 章节页
                if (empty($data)) {
                    $chapter = Db::name('chapter')->where("status", 1)->orderRaw('RAND()')->limit(1)->select()->toArray();
                    $chapter = $chapter ? $chapter[0] : [];
                }
                if (isset($data['chapterid']) && intval($data['chapterid']) > 0) {
                    $chapter = Db::name('chapter')->where(['id' => $data['chapterid']])->find();
                }
                if (!empty($chapter)) {
                    if (strpos($content, '{章节名}') !== false) {
                        $content = str_replace('{章节名}', $chapter['title'], $content);
                    }
                    $book = Db::name('book')->where(['id' => $chapter['bookid']])->find();
                    $content = seo_book_tag($content, $seo_config, $book);
                } else {
                    $content = str_replace('{章节名}', '', $content);
                }
                return $content;
                break;
            default:
                return $content;
                break;
        }
    }
}
if (!function_exists('seo_book_tag')) {
    function seo_book_tag($content, $seo_config, $book)
    {
        if (!empty($book)) {
            if (strpos($content, '{作品标签}') !== false) {
                $label_str = '';
                if (!empty($book['label'])) {
                    $label =  explode(',', $book['label']);
                    if (!empty($label)) {
                        $label_str = implode($seo_config['book_split'], $book['label']);
                    }
                }
                if (!empty($book['label_custom'])) {
                    $label =  explode(',', $book['label_custom']);
                    if (!empty($label)) {
                        $label_str .= implode($seo_config['book_split'], $book['label_custom']);
                    }
                }
                $content = str_replace('{作品标签}', $label_str, $content);
            }
            if (intval($seo_config['book_mark']) == 1) {
                $content = str_replace('{书名}', ('《' . $book['title'] . '》'), $content);
            } else {
                $content = str_replace('{书名}', $book['title'], $content);
            }
            $content = str_replace('{作者}', $book['author'], $content);
            list($num, $remark) = countWordsAndContent($book['remark']);
            if ($num > 0) {
                $remark = strip_tags($remark);
                if (strpos($content, '{作品简介}') !== false) {
                    $content = str_replace('{作品简介}', $remark, $content);
                }
                if (strpos($content, '{作品简介|len=') !== false) {
                    $before = "{作品简介|len=";
                    $end = "}";
                    $pattern = "/" . $before . "(.*?)" . $end . "/U";
                    preg_match_all($pattern, $content, $matches);
                    if (isset($matches[1]) && isset($matches[1][1])) {
                        $len = intval($matches[1][1]);
                        if ($num > $len) {
                            $remark = mb_substr($remark, 0, $len, 'UTF-8');
                        }
                        $content = preg_replace('/\{作品简介|len.*\}/i', $remark, $content);
                    } else {
                        $content = preg_replace('/\{作品简介|len.*\}/i', $remark, $content);
                    }
                }
            }
            if (strpos($content, '{大类名}') !== false) {
                if (!empty($book['genre'])) {
                    $category = Db::name('category')->where(['id' => $book['genre']])->value('name');
                    $content = str_replace('{大类名}', $category, $content);
                } else {
                    $content = str_replace('{大类名}', '', $content);
                }
            }
            if (strpos($content, '{小类名}') !== false) {
                if (!empty($book['subgenre'])) {
                    $category = Db::name('category')->where(['id' => $book['subgenre']])->value('name');
                    $content = str_replace('{小类名}', $category, $content);
                } else {
                    $content = str_replace('{小类名}', '', $content);
                }
            }
            if (strpos($content, '{点击量}') !== false) {
                $content = str_replace('{点击量}', $book['hits'], $content);
            }
            if (strpos($content, '{章节数}') !== false) {
                $content = str_replace('{章节数}', $book['chapters'], $content);
            }
            if (strpos($content, '{作品字数}') !== false) {
                $content = str_replace('{作品字数}', (wordCount($book['words'])), $content);
            }
            if (strpos($content, '{连载状态}') !== false) {
                $content = str_replace('{连载状态}', (intval($book['isfinish']) == 2 ? '完结' : '连载'), $content);
            }
            if (strpos($content, '{作品签约状态}') !== false) {
                $content = str_replace('{作品签约状态}', (intval($book['issign']) == 1 ? '已签约' : '待签约'), $content);
            }
            if (strpos($content, '{作者签约状态}') !== false) {
                $issign = Db::name('author')->where(['id' => $book['authorid']])->value('issign');
                $content = str_replace('{作者签约状态}', (intval($issign) == 1 ? '已签约' : '待签约'), $content);
            }
        } else {
            $content = str_replace('{作品标签}', '', $content);
            $content = str_replace('{书名}', '', $content);
            $content = str_replace('{作者}', '', $content);
            $content = str_replace('{作品简介}', '', $content);
            $content = str_replace('{大类名}', '', $content);
            $content = str_replace('{小类名}', '', $content);
            $content = str_replace('{点击量}', '', $content);
            $content = str_replace('{章节数}', '', $content);
            $content = str_replace('{作品字数}', '', $content);
            $content = str_replace('{连载状态}', '', $content);
            $content = str_replace('{作品签约状态}', '', $content);
            $content = str_replace('{作者签约状态}', '', $content);
            if (strpos($content, '{作品简介|len=') !== false) {
                $content = preg_replace('/\{作品简介|len.*\}/i', '', $content);
            }
        }
        return $content;
    }
}