<?php

namespace addons\changyan\controller;

use think\facade\Db;
use think\facade\View;
use app\admin\facade\ThinkAddons;
use think\facade\App;
use think\facade\Session;
use think\facade\Request;
use think\Response;
use think\facade\Cookie;
use Firebase\JWT\JWT;
use think\facade\Route;
use think\captcha\facade\Captcha;

class Index
{

    // 配置信息
    private $config = [];
    private $addons_name = 'changyan';

    // 初始化
    public function __construct()
    {
        $config    = ThinkAddons::config($this->addons_name);
        $configVal = [];
        foreach ($config as $k => $v) {
            $configVal[$k] = $v['value'] ?? '';
        }
        $this->config = $configVal;
        View::assign('config', $config);
        require_once app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $this->addons_name . DIRECTORY_SEPARATOR . 'common.php';
    }

    private function auth()
    {
        $session_admin = get_config('app.session_admin');
        if (!Session::has($session_admin)) {
            die;
        }
    }

    private function temp($action = '')
    {
        return App::getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $this->addons_name . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . app('request')->controller() . DIRECTORY_SEPARATOR . ($action ? $action : app('request')->action()) . '.html';
    }

    public function site()
    {
        $this->auth();
        if (request()->isAjax()) {
            $param = get_params();
            $where = [];
            if (!empty($param['keywords'])) {
                $where[] = ['name', 'like', '%' . $param['keywords'] . '%'];
                unset($param['keywords']);
            }
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $order = empty($param['order']) ? 'create_time desc' : $param['order'];
            $list = Db::name('addons_changyan_site')->where($where)->order($order)->paginate($rows, false, ['query' => $param]);
            return table_assign(0, '', $list);
        }
    }

    public function comments()
    {
        $this->auth();
        $param = get_params();
        $id = isset($param['id']) ?  intval($param['id']) : '';
        if (request()->isAjax()) {
            $where = ['siteid' => $id];
            if (!empty($param['keywords'])) {
                $where[] = ['booktitle|chaptertitle', 'like', '%' . $param['keywords'] . '%'];
                unset($param['keywords']);
            }
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $order = empty($param['order']) ? 'create_time desc' : $param['order'];
            $list = Db::name('addons_changyan_topic')->where($where)->group('bookid')->order($order)->paginate($rows, false, ['query' => $param]);
            return table_assign(0, '', $list);
        } else {
            return view($this->temp(), ['id' => $id]);
        }
    }

    public function read()
    {
        $this->auth();
        $param = get_params();
        $bookid = isset($param['bookid']) ?  intval($param['bookid']) : 0;
        $replyid = isset($param['replyid']) ?  intval($param['replyid']) : 0;
        if (request()->isAjax()) {
            if ($replyid > 0) {
                $list = Db::name('addons_changyan_comments')->where(['replyid' => $replyid])->select();
                $list = $list->toArray();
                foreach ($list as $k => $v) {
                    $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                    $list[$k]['replynum'] = Db::name('addons_changyan_comments')->where('replyid', $v['id'])->count();
                }
                return table_assign(0, '', ['data' => $list]);
            }
            $where = ['bookid' => $bookid, 'replyid' => 0];
            if (!empty($param['keywords'])) {
                $where[] = ['booktitle|chaptertitle|nickname', 'like', '%' . $param['keywords'] . '%'];
                unset($param['keywords']);
            }
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $order = empty($param['order']) ? 'create_time desc' : $param['order'];
            $list = Db::name('addons_changyan_comments')->where($where)->order($order)->paginate($rows, false, ['query' => $param]);
            $list = $list->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $list['data'][$k]['replynum'] = Db::name('addons_changyan_comments')->where('replyid', $v['id'])->count();
            }
            return table_assign(0, '', $list);
        } else {
            return view($this->temp(), ['bookid' => $bookid]);
        }
    }

    public function sohucs()
    {
        $param = get_params();
        $id = isset($param['id']) ?  intval($param['id']) : 0;
        if (empty($id)) return '';
        $arr = ['sid' => $this->config['siteid']];
        $session_user = get_config('app.session_user');
        if (Cookie::has($session_user)) {
            $arr['uid'] = Cookie::get($session_user);
        }
        $arr['cid'] = $id;
        $sid = base64_encode(http_build_query($arr));
        return to_assign(0, '', ['sid' => $sid]);
    }

    public function add()
    {
        $this->auth();
        $param = get_params();
        if (request()->isAjax()) {
            $name = isset($param['name']) ?  trim($param['name']) : '';
            $url = isset($param['url']) ? $param['url'] : '';
            if (empty($name) && empty($url)) {
                return to_assign(1, '参数错误');
            }
            if (empty($this->config['username']) && empty($this->config['password'])) {
                return to_assign(1, '请先登录畅言账号');
            }
            $paramsArr = array(
                'username' => $this->config['username'],
                'password' => $this->config['password'],
                'isv_name' => $name,
                'url' => $url
            );
            $res = httpRequest('http://changyan.kuaizhan.com/extension/add-isv', 'POST', $paramsArr);
            if (isset($res['code']) && $res['code'] == 0) {
                $appid = trim($res['data']['appid']);
                $appkey = trim($res['data']['isv_app_key']);
                $paramsArr = array(
                    'app_id' => $appid
                );
                $conf = httpRequest('http://changyan.kuaizhan.com/getConf', 'GET', $paramsArr);
                $data = [
                    'appId' => $appid,
                    'appKey' => $appkey,
                    'conf' => $conf,
                    'name' => $name,
                    'status' => 1,
                    'url' => $url,
                    'create_time' => time()
                ];
                $result = Db::name('addons_changyan_site')->strict(false)->field(true)->insertGetId($data);
                if ($result !== false) {
                    return to_assign(0, '添加成功');
                } else {
                    return to_assign(1, '添加失败');
                }
            } else {
                return to_assign(1, $res['msg']);
            }
        } else {
            return view($this->temp());
        }
    }

    public function sync()
    {
        $this->auth();
        if (request()->isAjax()) {
            $param = get_params();
            $username = isset($param['username']) ?  trim($param['username']) : '';
            $password = isset($param['password']) ?  trim($param['password']) : '';
            $captcha = isset($param['captcha']) ? $param['captcha'] : '';
            if (empty($username) || empty($password)) {
                return to_assign(1, '参数错误');
            }
            if (empty($captcha)) {
                return to_assign(1, '参数错误');
            }
            if (!captcha_check($captcha)) {
                return to_assign(1, '验证码错误');
            }
            $paramsArr = array(
                'username' => $username,
                'password' => $password,
            );
            $res = httpRequest('http://changyan.kuaizhan.com/extension/login', 'POST', $paramsArr);
            if (isset($res['code']) && $res['code'] == 0) {
                $file = app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $this->addons_name . DIRECTORY_SEPARATOR . 'config.php';
                if (!is_file($file)) {
                    return to_assign(1, '配置文件不存在');
                }
                $config = include $file;
                if (empty($config)) {
                    return to_assign(1, '配置文件为空');
                }
                $config['username']['value'] = $username;
                $config['password']['value'] = $password;
                if ($handle = fopen($file, 'w')) {
                    fwrite($handle, "<?php\n\n" . "return " . var_export($config, TRUE) . ";\n");
                    fclose($handle);
                } else {
                    return to_assign(1, '文件没有写入权限');
                }
                if (isset($res['data']['isvs']) && $res['data']['isvs']) {
                    foreach ($res['data']['isvs'] as $k => $v) {
                        $site = Db::name('addons_changyan_site')->where(['appId' => $v['appId']])->find();
                        $paramsArr = array(
                            'app_id' => $v['appId']
                        );
                        $conf = httpRequest('http://changyan.kuaizhan.com/getConf', 'GET', $paramsArr);
                        $arr = array(
                            'appId' => $v['appId'],
                            'appKey' => $v['appKey'],
                            'conf' => $conf,
                            'auditMode' => $v['auditMode'],
                            'exchange' => $v['exchange'],
                            'exchangeAuditType' => $v['exchangeAuditType'],
                            'exchangeLimit' => $v['exchangeLimit'],
                            'filter' => $v['filter'],
                            'isvAuditType' => $v['isvAuditType'],
                            'isvType' => $v['isvType'],
                            'name' => $v['name'],
                            'officialUserId' => $v['officialUserId'],
                            'qualityExchange' => $v['qualityExchange'],
                            'sso' => $v['sso'],
                            'status' => $v['status'],
                            'sysAudit' => $v['sysAudit'],
                            'url' => $v['url'],
                            'vip' => $v['vip'],
                            'create_time' => $v['ctime'] ? (intval($v['ctime']) / 1000) : 0,
                        );
                        if (empty($site)) {
                            $arr['id'] = $v['id'];
                            Db::name('addons_changyan_site')->strict(false)->field(true)->insertGetId($arr);
                        } else {
                            Db::name('addons_changyan_site')->where('id', $v['id'])->strict(false)->field(true)->update($arr);
                        }
                    }
                    return to_assign();
                } else {
                    return to_assign(1, '登录成功，站点为空。');
                }
            } else {
                return to_assign(1, $res['msg']);
            }
        } else {
            return view($this->temp());
        }
    }

    public function edit()
    {
        $this->auth();
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        if (request()->isAjax()) {
        } else {
            $detail = Db::name('addons_changyan_site')->where('id', $id)->find();
            View::assign('detail', $detail);
            return view($this->temp());
        }
    }

    public function enable()
    {
        $this->auth();
        if (request()->isAjax()) {
            $param = get_params();
            $id = isset($param['id']) ? intval($param['id']) : 0;
            if (empty($id)) {
                return to_assign(1, '参数错误');
            }
            $site = Db::name('addons_changyan_site')->where(['id' => $id])->find();
            if (empty($site)) {
                return to_assign(1, '站点信息不存在');
            }
            if (intval($site['status']) != 1) {
                return to_assign(1, '站点被禁止');
            }
            $file = app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $this->addons_name . DIRECTORY_SEPARATOR . 'config.php';
            if (!is_file($file)) {
                return to_assign(1, '配置文件不存在');
            }
            $config = include $file;
            if (empty($config)) {
                return to_assign(1, '配置文件为空');
            }
            $appId = $site['appId'];
            $conf = $site['conf'];
            //PC端安装代码
            $pc_script = <<< EOT
                <script charset="utf-8" type="text/javascript" src="https://cy-cdn.kuaizhan.com/upload/changyan.js" ></script>
                <script type="text/javascript">
                window.changyan.api.config({
                    appid: '$appId',
                    conf: '$conf'
                });
                </script>
            EOT;
            $pc_script = base64_encode($pc_script);
            //WAP端安装代码
            $wap_script = <<< EOT
            <script id="changyan_mobile_js" charset="utf-8" type="text/javascript" src="https://cy-cdn.kuaizhan.com/upload/mobile/wap-js/changyan_mobile.js?client_id=$appId&conf=$conf"></script>
            EOT;
            $wap_script = base64_encode($wap_script);
            //自适应安装代码
            $adaptive_script = <<< EOT
            <script type="text/javascript"> 
            (function(){
                var appid = '$appId'; 
                var conf = '$conf'; 
                var width = window.innerWidth || document.documentElement.clientWidth; 
                if (width < 1000) {
                    var head = document.getElementsByTagName('head')[0]||document.head||document.documentElement;
                    var script = document.createElement('script');
                    script.type = 'text/javascript';
                    script.charset = 'utf-8';
                    script.id = 'changyan_mobile_js';
                    script.src = 'https://cy-cdn.kuaizhan.com/upload/mobile/wap-js/changyan_mobile.js?client_id=' + appid + '&conf=' + conf;
                    head.appendChild(script);
                } else { 
                    var loadJs=function(d,a){
                        var c=document.getElementsByTagName("head")[0]||document.head||document.documentElement;
                        var b=document.createElement("script");
                        b.setAttribute("type","text/javascript");
                        b.setAttribute("charset","UTF-8");
                        b.setAttribute("src",d);
                        if(typeof a==="function"){
                            if(window.attachEvent){
                                b.onreadystatechange=function(){
                                    var e=b.readyState;
                                    if(e==="loaded"||e==="complete"){
                                        b.onreadystatechange=null;
                                        a()
                                    }
                                }
                            }else{
                                b.onload=a
                            }
                        }
                        c.appendChild(b)
                    };
                    loadJs("https://cy-cdn.kuaizhan.com/upload/changyan.js",function(){
                        window.changyan.api.config({appid:appid,conf:conf})
                    });
                }
            })();
            </script>
            EOT;
            $adaptive_script = base64_encode($adaptive_script);
            //PC打分版安装代码
            $pc_scoring_script = <<< EOT
            <script charset="utf-8" type="text/javascript" src="https://cy-cdn.kuaizhan.com/upload/changyan.js" ></script>
            <script type="text/javascript">
            window._config = { showScore: true };
            window.changyan.api.config({
                appid: '$appId',
                conf: '$conf'
            });
            </script>
            EOT;
            $pc_scoring_script = base64_encode($pc_scoring_script);
            $config['siteid']['value'] = $id;
            $config['pc_script']['value'] = $pc_script;
            $config['wap_script']['value'] = $wap_script;
            $config['adaptive_script']['value'] = $adaptive_script;
            $config['pc_scoring_script']['value'] = $pc_scoring_script;
            if ($handle = fopen($file, 'w')) {
                fwrite($handle, "<?php\n\n" . "return " . var_export($config, TRUE) . ";\n");
                fclose($handle);
            } else {
                return to_assign(1, '文件没有写入权限');
            }
            return to_assign();
        } else {
            return to_assign(1, '请求失败');
        }
    }

    public function login()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $username = isset($param['username']) ?  trim($param['username']) : '';
            $password = isset($param['password']) ?  trim($param['password']) : '';
            $captcha = isset($param['captcha']) ? $param['captcha'] : '';
            if (empty($username) || empty($password) || empty($captcha)) {
                return to_assign(1, '参数错误');
            }
            if (!captcha_check($captcha)) {
                return to_assign(1, '验证码错误');
            }
            $user = Db::name('user')->where(['username' => $username])->find();
            if (empty($user)) {
                return to_assign(1, '用户不存在');
            }
            $pwd = set_password($password, $user['salt']);
            if ($pwd !== $user['password']) {
                return to_assign(1, '密码错误');
            }
            $data = [
                'last_login_time' => time(),
                'last_login_ip' => request()->ip(),
                'login_num' => $user['login_num'] + 1,
            ];
            $res = Db::name('user')->where(['id' => $user['id']])->update($data);
            if ($res) {
                $config = get_system_config('token');
                JWT::$leeway = 60; //当前时间减去60，把时间留点余地
                $time = time(); //当前时间
                $arr = [
                    'iss' => $config['iss'], //签发者 可选
                    'aud' => $config['aud'], //接收该JWT的一方，可选
                    'iat' => $time, //签发时间
                    'nbf' => $time - 1, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                    'exp' => $time + $config['exptime'], //过期时间,这里设置2个小时
                    'data' => [
                        //自定义信息，不要定义敏感信息
                        'userid' => $user['id'],
                    ]
                ];
                $token = JWT::encode($arr, $config['secrect'], 'HS256');
                $session_user = get_config('app.session_user');
                Cookie::set($session_user, $user['id']);
                return to_assign(0, '登录成功', ['token' => $token]);
            } else {
                return to_assign(1, '登录失败');
            }
        } else {
            $session_user = get_config('app.session_user');
            if (Cookie::has($session_user)) {
                redirect((string) Route::buildUrl('/'))->send();
            }
            return view($this->temp());
        }
    }

    public function register()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $username = isset($param['username']) ?  trim($param['username']) : '';
            $password = isset($param['password']) ?  trim($param['password']) : '';
            $confirmPassword = isset($param['confirmPassword']) ?  trim($param['confirmPassword']) : '';
            $nickname = isset($param['nickname']) ?  trim($param['nickname']) : '';
            $captcha = isset($param['captcha']) ? $param['captcha'] : '';
            $agree = isset($param['agree']) ? $param['agree'] : '';
            if (empty($username) || empty($password) || empty($confirmPassword) || empty($nickname) || empty($agree)) {
                return to_assign(1, '参数错误');
            }
            if (empty($captcha)) {
                return to_assign(1, '参数错误');
            }
            if (!captcha_check($captcha)) {
                return to_assign(1, '验证码错误');
            }
            if ($agree != 'on') {
                return to_assign(1, '您必须勾选同意用户协议才能注册');
            }
            if ($password != $confirmPassword) {
                return to_assign(1, '两次密码输入不一致');
            }
            $user = Db::name('user')->where(['username' => $username])->find();
            if (!empty($user)) {
                return to_assign(1, '此用户名已被注册');
            }
            $user = Db::name('user')->where(['nickname' => $nickname])->find();
            if (!empty($user)) {
                return to_assign(1, '此昵称已被注册');
            }
            $add = [];
            $add['salt'] = set_salt(20);
            $add['username'] = $username;
            $add['mobile'] = '';
            $add['coin'] = 0;
            $add['password'] = set_password($password, $add['salt']);
            $add['register_time'] = time();
            $add['mobile_status'] = 0;
            $add['headimgurl'] = '';
            $add['nickname'] = $nickname;
            $add['qrcode_invite'] = get_invite_code();
            $add['register_ip'] = request()->ip();
            $uid = Db::name('user')->strict(false)->field(true)->insertGetId($add);
            if ($uid !== false) {
                return to_assign(0, '注册成功，请登录。');
            } else {
                return to_assign(1, '注册失败');
            }
        } else {
            return view($this->temp());
        }
    }

    public function getuserinfo()
    {
        $userinfo = ['is_login' => 0];
        $session_user = get_config('app.session_user');
        if (Cookie::has($session_user)) {
            $uid = Cookie::get($session_user);
            if (!empty($uid)) {
                $user = Db::name('user')->where(['id' => $uid])->find();
                if (!empty($user)) {
                    $userinfo = array(
                        "is_login" => 1,
                        "user" => array(
                            "user_id" => $user['id'],
                            "nickname" => $user['nickname'] ? $user['nickname'] : get_system_config('web', 'title') . '用户',
                            "img_url" => get_file($user['headimgurl']),
                            "profile_url" => (string) Route::buildUrl('/'),
                            "sign" => "**"
                        )
                    );
                }
            }
        }
        return Response::create($userinfo, 'jsonp');
    }

    public function logout()
    {
        $session_user = get_config('app.session_user');
        Cookie::delete($session_user);
        return view($this->temp());
    }

    /**
     * 验证码
     * Summary of captcha
     * @return void
     */
    public function captcha()
    {
        return Captcha::create();
    }

    public function changyan2local()
    {
        $this->auth();
        @set_time_limit(0);
        @ini_set('memory_limit', '256M');
        @date_default_timezone_set('PRC');
        $site = Db::name('addons_changyan_site')->where('id', $this->config['siteid'])->find();
        if (!empty($site)) {
            $appId = $site['appId'];
            $params = array(
                'appId' => $appId,
                'date' => '2024-01-01 00:00:00'
            );
            $lasttopic = Db::name('addons_changyan_topic')->order('create_time desc')->find();
            if (!empty($lasttopic)) {
                $params['date'] = date('Y-m-d H:i:s', $lasttopic['create_time']);
            }
            $response = httpRequest("http://changyan.kuaizhan.com/admin/api/recent-comment-topics/", 'GET', $params);
            if (isset($response['success']) && intval($response['success']) == 1) {
                if (isset($response['topics']) && $response['topics']) {
                    $new_topic = $new_comment = 0;
                    foreach ($response['topics'] as $val) {
                        $arr = base64_decode($val['topic_source_id']);
                        parse_str($arr, $topic_param);
                        $bookid = $chapterid = 0;
                        $booktitle = $chaptertitle = '';
                        //作品book
                        if (isset($topic_param['bid']) && intval($topic_param['bid']) > 0) {
                            $bookid = intval($topic_param['bid']);
                            $booktitle = Db::name('book')->where('id', $bookid)->value('title');
                        }
                        //章节chapter
                        if (isset($topic_param['cid']) && intval($topic_param['cid']) > 0) {
                            $chapterid = intval($topic_param['cid']);
                            $chaptertitle = Db::name('chapter')->where('id', $chapterid)->value('title');
                        }
                        $topic = Db::name('addons_changyan_topic')->where('id', $val['topic_id'])->find();
                        if (empty($topic)) {
                            $add = [
                                'id' => $val['topic_id'],
                                'siteid' => $topic_param['sid'],
                                'topic_source_id' => $val['topic_source_id'],
                                'topic_url' => $val['topic_url'],
                                'bookid' => $bookid,
                                'booktitle' => $booktitle,
                                'chapterid' => $chapterid,
                                'chaptertitle' => $chaptertitle,
                                'create_time' => time()
                            ];
                            Db::name('addons_changyan_topic')->strict(false)->field(true)->insertGetId($add);
                            $new_topic++;
                        }
                        $paramsArr = array(
                            'client_id' => $appId,
                            'style' => 'floor',
                            'order_by' => 'time_asc',
                            'page_no' => 1,
                            'page_size' => 100,
                            'topic_id' => $val['topic_id']
                        );
                        $common = [
                            'sourceid' => $val['topic_source_id'],
                            'siteid' => $topic_param['sid'],
                        ];
                        $commentsArray = httpRequest('http://changyan.sohu.com/api/2/topic/comments', 'GET', $paramsArr);
                        if (isset($commentsArray['comments']) && !empty($commentsArray['comments'])) {
                            foreach ($commentsArray['comments'] as $k => $v) {
                                $comment = Db::name('addons_changyan_comments')->where('id', $v['comment_id'])->find();
                                if (!empty($comment)) {
                                    continue;
                                }
                                $add = [
                                    'id' => $v['comment_id'],
                                    'thirdid' => $v['comment_id'], //三方评论唯一ID
                                    'replyid' => $v['reply_id'], //回复的评论ID，没有为0
                                    'referid' => 0,
                                    'bookid' => $bookid,
                                    'booktitle' => $booktitle,
                                    'chapterid' => $chapterid,
                                    'chaptertitle' => $chaptertitle,
                                    'userid' => intval($v['user_id']) > 0 ? intval($v['user_id']) : 0
                                ];
                                if (isset($v['passport']) && $v['passport']) {
                                    $add['nickname'] = isset($v['passport']['nickname']) ? $v['passport']['nickname'] : '';
                                    $add['headimg'] = isset($v['passport']['img_url']) ? $v['passport']['img_url'] : '';
                                    if (empty($add['userid']) && isset($v['passport']['user_id'])) {
                                        $add['userid'] = intval($v['passport']['user_id']) > 0 ? intval($v['passport']['user_id']) : 0;
                                    }
                                }
                                $add['content'] = $v['content'];
                                $add['from'] = $v['from']; //评论来源
                                $add['ip'] = $v['ip'];
                                $add['status'] = intval($v['status']);
                                $add['attachs'] = serialize($v['attachments']); //附件列表type:1为图片、2为语音、3为视频
                                $add['create_time'] = $v['create_time'] ? (intval($v['create_time']) / 1000) : time();
                                $result = array_merge($common, $add);
                                $new_comment++;
                                Db::name('addons_changyan_comments')->strict(false)->field(true)->insertGetId($result);
                            }
                        }
                    }
                    $message = '同步新主题：' . $new_topic . ' 条，新评论：' . $new_topic . ' 条。';
                } else {
                    $message = '无新评论';
                }
            } else {
                $message = '无新评论';
            }
        } else {
            $message = '站点不存在';
        }
        return view($this->temp(), ['message' => $message]);
    }

    public function callback()
    {
        $param = get_params();
        if (isset($param['data']) && $param['data']) {
            $data =  json_decode($param['data'], true);
            if ($data && isset($data['comments']) && isset($data['sourceid'])) {
                $arr = base64_decode($data['sourceid']);
                parse_str($arr, $params);
                if (isset($params['sid'])) {
                    $site = Db::name('addons_changyan_site')->where('id', $params['sid'])->find();
                    if (!empty($site)) {
                        $userid = $bookid = $chapterid = 0;
                        $booktitle = $chaptertitle = '';
                        //用户user
                        if (isset($params['uid']) && intval($params['uid']) > 0) {
                            $userid = intval($params['uid']);
                        }
                        //作品book
                        if (isset($params['bid']) && intval($params['bid']) > 0) {
                            $bookid = intval($params['bid']);
                            $booktitle = Db::name('book')->where('id', $bookid)->value('title');
                        }
                        //章节chapter
                        if (isset($params['cid']) && intval($params['cid']) > 0) {
                            $chapterid = intval($params['cid']);
                            $chapter = Db::name('chapter')->field('id,bookid,title')->where('id', $chapterid)->find();
                            if (!empty($chapter)) {
                                $chaptertitle = $chapter['title'];
                                if (!isset($params['bid']) || empty($params['bid'])) {
                                    $bookid = $chapter['bookid'];
                                    $booktitle = Db::name('book')->where('id', $bookid)->value('title');
                                }
                            }
                        }
                        $common = [
                            'sourceid' => $data['sourceid'],
                            'siteid' => $params['sid'],
                        ];
                        foreach ($data['comments'] as $k => $v) {
                            $add = [
                                'id' => $v['cmtid'],
                                'thirdid' => $v['cmtid'], //三方评论唯一ID
                                'replyid' => $v['replyid'], //回复的评论ID，没有为0
                                'referid' => $v['referid'],
                                'bookid' => $bookid,
                                'booktitle' => $booktitle,
                                'chapterid' => $chapterid,
                                'chaptertitle' => $chaptertitle,
                                'userid' => $userid
                            ];
                            if (isset($v['user']) && $v['user']) {
                                $add['nickname'] = isset($v['user']['nickname']) ? $v['user']['nickname'] : '';
                                $add['headimg'] = isset($v['user']['usericon']) ? $v['user']['usericon'] : '';
                                if (empty($add['userid']) && isset($v['user']['userid'])) {
                                    $add['userid'] = intval($v['user']['userid']);
                                }
                            }
                            $add['content'] = $v['content'];
                            $add['spnum'] = $v['spcount']; //评论被顶次数
                            $add['opnum'] = $v['opcount']; //评论被踩次数
                            $add['from'] = $v['from']; //评论来源
                            $add['ip'] = $v['ip'];
                            $add['status'] = intval($v['status']);
                            $add['channeltype'] = intval($v['channeltype']);
                            $add['attachs'] = serialize($v['attachment']); //附件列表type:1为图片、2为语音、3为视频
                            $add['create_time'] = $v['ctime'] ? (intval($v['ctime']) / 1000) : time();
                            $result = array_merge($common, $add);
                            Db::name('addons_changyan_comments')->strict(false)->field(true)->insertGetId($result);
                        }
                    }
                }
            }
        }
    }
}
