<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use think\facade\Request;
use think\facade\Cookie;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;
use think\Image;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\captcha\facade\Captcha;

class Common extends BaseController
{

    /**
     * 控制器中间件 [登录、token 不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => ['login', 'register', 'token', 'service', 'captcha']]
    ];

    /**
     * 上传
     * Summary of upload
     * @return void
     */
    public function upload()
    {
        $param = get_params();
        if (request()->file('file')) {
            $file = request()->file('file');
        } else {
            return to_assign(1, '没有选择上传文件');
        }
        $sha1 = $file->hash('sha1');
        $md5 = $file->hash('md5');
        $rule = [
            'image' => 'jpg,png,jpeg,gif',
            'doc' => 'doc,docx,ppt,pptx,xls,xlsx,pdf',
            'file' => 'zip,gz,7z,rar,tar',
            'video' => 'mpg,mp4,mpeg,avi,wmv,mov,flv,m4v',
        ];
        $fileExt = $rule['image'] . ',' . $rule['doc'] . ',' . $rule['file'] . ',' . $rule['video'];
        $fileSize = 100 * 1024 * 1024;
        if (isset($param['type']) && $param['type']) {
            $fileExt = $rule[$param['type']];
        }
        if (isset($param['size']) && $param['size']) {
            $fileSize = $param['size'];
        }
        $validate = \think\facade\Validate::rule([
            'image' => 'require|fileSize:' . $fileSize . '|fileExt:' . $fileExt,
        ]);
        $file_check['image'] = $file;
        if (!$validate->check($file_check)) {
            return to_assign(1, $validate->getError());
        }
        // 日期前綴
        $dataPath = date('Ym');
        $use = 'thumb';
        $filename = \think\facade\Filesystem::disk('public')->putFile($dataPath, $file, function () use ($md5) {
            return $md5;
        });
        if ($filename) {
            $path = get_config('filesystem.disks.public.url');
            $filepath = $path . '/' . $filename;
            if (isset($param['thumb'])) {
                $realPath = CMS_ROOT . "public" . $path . '/' . $filename;
                $image = Image::open($realPath);
                // 按照原图的比例生成一个最大为500*500的缩略图并保存为thumb.png
                $image->thumb(500, 500, Image::THUMB_CENTER)->save($realPath . '_thumb.' . $file->extension());
                $filepath = $filepath . '_thumb.' . $file->extension();
            } else {
                $realPath = CMS_ROOT . "public" . $path . '/' . $filename;
            }
            $config_web = get_system_config('web');
            if (is_array($config_web)) {
                // 阿里云oss
                if (isset($config_web['upload_driver']) && $config_web['upload_driver'] == 2) {
                    if (get_addons_is_enable('aliyunoss')) {
                        $urlArr = hook('aliyunOssHook', ['url' => $filename]);
                        $urlArr = json_decode($urlArr, true);
                        if (isset($urlArr['error']) && $urlArr['error'] == 0) {
                            $filepath = $urlArr['data'];
                        } else {
                            return to_assign(1, $urlArr['msg']);
                        }
                    } else {
                        return to_assign(1, '未开启OSS上传功能');
                    }
                }
                //腾讯云cos
                if (isset($config_web['upload_driver']) && $config_web['upload_driver'] == 3) {
                    if (get_addons_is_enable('qcloudcos')) {
                        $urlArr = hook('qcloudCosHook', ['url' => $filename]);
                        $urlArr = json_decode($urlArr, true);
                        if (isset($urlArr['error']) && $urlArr['error'] == 0) {
                            $filepath = $urlArr['data'];
                        } else {
                            return to_assign(1, $urlArr['msg']);
                        }
                    } else {
                        return to_assign(1, '未开启COS上传功能');
                    }
                }
            }
            //写入到附件表
            $data = [];
            $data['filepath'] = $filepath;
            $data['name'] = $file->getOriginalName();
            $data['mimetype'] = $file->getOriginalMime();
            $data['fileext'] = $file->extension();
            $data['filesize'] = $file->getSize();
            $data['filename'] = $filename;
            $data['sha1'] = $sha1;
            $data['md5'] = $md5;
            $data['module'] = \think\facade\App::initialize()->http->getName();
            $data['action'] = app('request')->action();
            $data['uploadip'] = app('request')->ip();
            $data['create_time'] = time();
            $data['user_id'] = JWT_UID;
            $data['admin_id'] = 0;
            $data['use'] = request()->has('use') ? request()->param('use') : $use; //附件用处
            $res['id'] = Db::name('file')->insertGetId($data);
            $res['filepath'] = $data['filepath'];
            $res['name'] = $data['name'];
            $res['filename'] = $data['filename'];
            //普通上传返回
            return to_assign(0, '上传成功', $res);
        } else {
            return to_assign(1, '上传失败，请重试');
        }
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

    /**
     * 获取指定广告位广告内容
     * Summary of recommend
     * @return void
     */
    public function recommend()
    {
        $param = get_params();
        $pid = trim($param['pid']); //广告位ID 说明：可以同时取多个广告位内容，以英文逗号区分
        $page = (isset($param['page']) && intval($param['page']) > 0) ? intval($param['page']) : 1; //页码
        $pagesize = isset($param['pagesize']) ? intval($param['pagesize']) : 0; //条数
        if (empty($pid)) {
            $this->apiError('参数错误');
        }
        $time = time();
        $table = config('database.connections.mysql.prefix') . 'advsr';
        $condition = '';
        $result = [];
        if (strpos($pid, ',') !== false) {
            $adver = Db::name('adver')->where('id', 'in', $pid)->select()->toArray();
            $pids = explode(',', $pid);
            $adver = array_column($adver, null, 'id');
            foreach ($pids as $key => $value) {
                $list = [];
                if (isset($adver[$value]) && intval($adver[$value]['status']) == 1) {
                    $limit = $pagesize > 0 ?: (intval($adver[$value]['viewnum']) > 0 ? intval($adver[$value]['viewnum']) : '');
                    //取总数
                    $count = Db::query("SELECT count(id) as cnt FROM `{$table}` WHERE `status`=:status AND `adver_id`=:adver_id AND `start_time`<:stime AND (`end_time`<=0 OR `end_time`>=:etime) LIMIT 1", ['status' => 1, 'adver_id' => $value, 'stime' => $time, 'etime' => $time]);
                    $total = intval($count[0]['cnt']);
                    if ($total > 0) {
                        $isendpage = false;
                        $maxpage = ceil($total / $limit); //最大页数                        
                        if ($page >= $maxpage) {
                            $isendpage = true;
                        }
                        $condition = ($limit * ($page - 1)) . ',' . $limit;
                        $list = Db::query("SELECT `id`,`title`,`adver_id`,`type`,`link`,`start_time`,`end_time`,`color`,`books`,`images`,`introduction` FROM `{$table}` WHERE `status`=:status AND `adver_id`=:adver_id AND `start_time`<:stime AND (`end_time`<=0 OR `end_time`>=:etime) ORDER BY `level` DESC LIMIT {$condition}", ['status' => 1, 'adver_id' => $value, 'stime' => $time, 'etime' => $time]);
                        foreach ($list as $k => $v) {
                            if (intval($v['books']) > 0) {
                                $book = Db::name('book')->where(['id' => $v['books']])->find();
                                if (!empty($book)) {
                                    $list[$k]['author'] = $book['author'];
                                    if (!empty($book['genre'])) {
                                        $list[$k]['genre'] = Db::name('category')->where(['id' => $book['genre']])->value('name');
                                    } else {
                                        $list[$k]['genre'] = '';
                                    }
                                    $v['images'] = $v['images'] ? $v['images'] : $book['cover'];
                                    $list[$k]['chapters'] = $book['chapters'];
                                    $list[$k]['isfinish'] = $book['isfinish'];
                                    $list[$k]['finish'] = intval($book['isfinish']) == 2 ? '完结' : '连载';
                                    $list[$k]['url'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('book_detail', ['id' => $book['filename'] ? $book['filename'] : $book['id']]));
                                } else {
                                    $list[$k]['isfinish'] = 1;
                                    $list[$k]['finish'] = '';
                                    $list[$k]['author'] = '';
                                    $list[$k]['genre'] = '';
                                    $list[$k]['chapters'] = 0;
                                    $list[$k]['url'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('book_detail', ['id' => $v['books']]));
                                }
                            } else {
                                $list[$k]['finish'] = '';
                                $list[$k]['author'] = '';
                                $list[$k]['genre'] = '';
                                $list[$k]['url'] = '';
                                $list[$k]['chapters'] = 0;
                                $list[$k]['isfinish'] = 1;
                            }
                            $list[$k]['images'] = get_file($v['images']);
                            $list[$k]['width'] = $adver[$value]['width'];
                            $list[$k]['height'] = $adver[$value]['height'];
                            $list[$k]['isendpage'] = $isendpage;
                        }
                    }
                }
                $result[$value] = $list;
            }
        } else {
            $adver = Db::name('adver')->where(['id' => intval($pid)])->find();
            if (empty($adver)) {
                $this->apiError('广告位不存在');
            }
            if (intval($adver['status']) != 1) {
                $this->apiError('广告位已禁止');
            }
            $limit = $pagesize > 0 ?: (intval($adver['viewnum']) > 0 ? intval($adver['viewnum']) : '');
            //取总数
            $count = Db::query("SELECT count(id) as cnt FROM `{$table}` WHERE `status`=:status AND `adver_id`=:adver_id AND `start_time`<:stime AND (`end_time`<=0 OR `end_time`>=:etime)", ['status' => 1, 'adver_id' => $pid, 'stime' => $time, 'etime' => $time]);
            $total = intval($count[0]['cnt']);
            if ($total > 0) {
                $isendpage = false;
                $maxpage = ceil($total / $limit); //最大页数
                if ($page >= $maxpage) {
                    $isendpage = true;
                }
                $condition = ($limit * ($page - 1)) . ',' . $limit;
                $result = Db::query("SELECT `id`,`title`,`adver_id`,`type`,`link`,`start_time`,`end_time`,`color`,`books`,`images`,`introduction` FROM `{$table}` WHERE `status`=:status AND `adver_id`=:adver_id AND `start_time`<:stime AND (`end_time`<=0 OR `end_time`>=:etime) ORDER BY `level` DESC LIMIT {$condition}", ['status' => 1, 'adver_id' => $pid, 'stime' => $time, 'etime' => $time]);
                foreach ($result as $k => $v) {
                    if (intval($v['books']) > 0) {
                        $book = Db::name('book')->where(['id' => $v['books']])->find();
                        if (!empty($book)) {
                            $result[$k]['author'] = $book['author'];
                            if (!empty($book['genre'])) {
                                $result[$k]['genre'] = Db::name('category')->where(['id' => $book['genre']])->value('name');
                            } else {
                                $result[$k]['genre'] = '';
                            }
                            $result[$k]['finish'] = intval($book['isfinish']) == 2 ? '完结' : '连载';
                            $result[$k]['chapters'] = $book['chapters'];
                            $result[$k]['isfinish'] = $book['isfinish'];
                            $result[$k]['url'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('book_detail', ['id' => $book['filename'] ? $book['filename'] : $book['id']]));
                            $v['images'] = $v['images'] ? $v['images'] : $book['cover'];
                        } else {
                            $result[$k]['finish'] = '';
                            $result[$k]['author'] = '';
                            $result[$k]['genre'] = '';
                            $result[$k]['chapters'] = 0;
                            $result[$k]['isfinish'] = 1;
                            $result[$k]['url'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('book_detail', ['id' => $v['books']]));
                        }
                    } else {
                        $result[$k]['isfinish'] = 1;
                        $result[$k]['finish'] = '';
                        $result[$k]['author'] = '';
                        $result[$k]['genre'] = '';
                        $result[$k]['url'] = '';
                        $result[$k]['chapters'] = 0;
                    }
                    $result[$k]['images'] = get_file($v['images']);
                    $result[$k]['width'] = $adver['width'];
                    $result[$k]['height'] = $adver['height'];
                    $result[$k]['isendpage'] = $isendpage;
                }
            }
        }
        $this->apiSuccess('请求成功', $result);
    }

    /**
     * 获取token
     * Summary of token
     * @return void
     */
    public function token()
    {
        $token = Request::header('Token');
        $config = get_system_config('token');
        JWT::$leeway = 60; //当前时间减去60，把时间留点余地
        $time = time(); //当前时间
        if ($token) {
            if (count(explode('.', $token)) != 3) {
                $this->apiError('token错误', 1);
            }
            try {
                $decoded = JWT::decode($token, new Key($config['secrect'], 'HS256')); //HS256方式，这里要和签发的时候对应
                $data = json_decode(json_encode($decoded), TRUE);
                $jwt_data = $data['data'];
                $uid = $jwt_data['userid'];
                $arr = [
                    'iss' => $config['iss'], //签发者 可选
                    'aud' => $config['aud'], //接收该JWT的一方，可选
                    'iat' => $time, //签发时间
                    'nbf' => $time - 1, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                    'exp' => $time + $config['exptime'], //过期时间,这里设置2个小时
                    'data' => [
                        //自定义信息，不要定义敏感信息
                        'userid' => $uid,
                    ]
                ];
                $token = JWT::encode($arr, $config['secrect'], 'HS256');
                return json(['code' => 0, 'msg' => '请求成功', 'data' => ['token' => $token]]);
            } catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
                return json(['code' => 403, 'msg' => '签名错误']);
            } catch (\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
                $arr = [
                    'iss' => $config['iss'], //签发者 可选
                    'aud' => $config['aud'], //接收该JWT的一方，可选
                    'iat' => $time, //签发时间
                    'nbf' => $time - 1, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                    'exp' => $time + $config['exptime'], //过期时间,这里设置2个小时
                    'data' => [
                        //自定义信息，不要定义敏感信息
                        'userid' => '',
                    ]
                ];
                $token = JWT::encode($arr, $config['secrect'], 'HS256');
                return json(['code' => 0, 'msg' => '请求成功', 'data' => ['token' => $token]]);
            } catch (\Firebase\JWT\ExpiredException $e) {  // token过期
                $arr = [
                    'iss' => $config['iss'], //签发者 可选
                    'aud' => $config['aud'], //接收该JWT的一方，可选
                    'iat' => $time, //签发时间
                    'nbf' => $time - 1, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                    'exp' => $time + $config['exptime'], //过期时间,这里设置2个小时
                    'data' => [
                        //自定义信息，不要定义敏感信息
                        'userid' => '',
                    ]
                ];
                $token = JWT::encode($arr, $config['secrect'], 'HS256');
                return json(['code' => 0, 'msg' => '请求成功', 'data' => ['token' => $token]]);
            } catch (\Exception $e) {  //其他错误
                return json(['code' => 404, 'msg' => '非法请求']);
            } catch (\UnexpectedValueException $e) {  //其他错误
                return json(['code' => 404, 'msg' => '非法请求']);
            } catch (\DomainException $e) {  //其他错误
                return json(['code' => 404, 'msg' => '非法请求']);
            }
        } else {
            $arr = [
                'iss' => $config['iss'], //签发者 可选
                'aud' => $config['aud'], //接收该JWT的一方，可选
                'iat' => $time, //签发时间
                'nbf' => $time - 1, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                'exp' => $time + $config['exptime'], //过期时间,这里设置2个小时
                'data' => [
                    //自定义信息，不要定义敏感信息
                    'userid' => '',
                ]
            ];
            $token = JWT::encode($arr, $config['secrect'], 'HS256');
            $this->apiSuccess('请求成功', ['token' => $token]);
        }
    }

    /**
     * 获取我的信息
     * Summary of mine
     * @return void
     */
    public function mine()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $user = Db::name('user')->field('nickname,username,name,mobile,headimgurl,email,mobile_status,sex,desc,birthday,level,status,country,province,city,company,address,depament,position,qrcode_invite,coin,inviter,securitypwd,realname_status,id_card,author_id')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在', 98);
        }
        if (empty($user['qrcode_invite'])) {
            $qrcode_invite = get_invite_code();
            Db::name('user')->where('id', JWT_UID)->update(['qrcode_invite' => $qrcode_invite]);
            $user['qrcode_invite'] = $qrcode_invite;
        }
        $user['mobile'] = $user['mobile'] ? substr_replace($user['mobile'], '****', 3, 4) : '';
        $user['id_card'] = $user['id_card'] ? substr_replace($user['id_card'], '****', 3, 4) : '';
        $user['headimgurl'] = get_file($user['headimgurl']);
        if (!empty($user['email'])) {
            $parts = explode('@', $user['email']);
            $replaceLength = strlen($parts[0]) - 2;
            $parts[0] = str_repeat('*', $replaceLength) . substr($parts[0], -$replaceLength);
            $user['email'] = implode('@', $parts);
        }
        $apply_coin = Db::name('withdraw')->where(['user_id' => JWT_UID, 'status' => 0])->sum('coin'); //提现中
        $user['follow'] = Db::name('follow')->where(['user_id' => JWT_UID])->count(); //关注
        $user['like'] = Db::name('like_log')->where(['user_id' => JWT_UID])->count(); //点赞
        $user['favorites'] = Db::name('favorites')->where(['user_id' => JWT_UID])->count(); //书架
        $user['withdrawn'] = Db::name('withdraw')->where(['user_id' => JWT_UID, 'status' => 1])->sum('coin'); //已提现
        if (intval($apply_coin) > 0) {
            if (intval($apply_coin) > intval($user['coin'])) {
                $user['coin'] = 0;
            } else {
                $user['coin'] = intval($user['coin']) - intval($apply_coin);
            }
        }
        //连续签到天数
        $consecutive_days = 0;
        //今天
        $today = date('Y-m-d');
        $consecutive_days = Db::name('sign_log')->where('user_id', JWT_UID)->where('sign_date', $today)->value('consecutive_days');
        $user['todaysign'] = $consecutive_days ? 1 : 0;
        if (intval($consecutive_days) <= 0) {
            //前一天
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $consecutive_days = Db::name('sign_log')->where('user_id', JWT_UID)->where('sign_date', $yesterday)->value('consecutive_days');
        }
        $vip = Db::name('vip_log')->where(['status' => 1, 'user_id' => JWT_UID])->order('expire_time desc')->find();
        if (!empty($vip) && intval($vip['expire_time']) > time()) {
            $user['isvip'] = 1;
            $user['viptime'] = date('Y-m-d', $vip['expire_time']);
        } else {
            $user['isvip'] = 0;
            $user['viptime'] = '--';
        }
        $user['vip_reward'] = floatval(get_system_config('reward', 'vip_reward'));
        $user['setspwd'] = $user['securitypwd'] ? 0 : 1;
        unset($user['securitypwd']);
        $user['consecutive_days'] = intval($consecutive_days);
        $user['level_title'] = Db::name('UserLevel')->where(['id' => $user['level']])->value('title');
        $user['gender'] = $user['sex'];
        $user['sex'] = ($user['sex'] == 1) ? '男' : ($user['sex'] == 2 ? '女' : '未知');
        $this->apiSuccess('请求成功', ['userinfo' => $user]);
    }

    /**
     * 登录|注册
     * Summary of login
     * @return void
     */
    public function login()
    {
        $param = get_params();
        $email = isset($param['email']) ? trim($param['email']) : '';
        $username = isset($param['username']) ? trim($param['username']) : '';
        $mobile = isset($param['mobile']) ? trim($param['mobile']) : '';
        $password = isset($param['password']) ? trim($param['password']) : '';
        $invite_code = isset($param['invite_code']) ? trim($param['invite_code']) : '';
        if (empty($mobile) && empty($username) && empty($email)) {
            $this->apiError('参数错误');
        }
        $user = [];
        if ($mobile) {
            if (empty($param['code'])) {
                $this->apiError('参数错误');
            }
            $code = intval($param['code']);
            if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
                $this->apiError('手机号不正确');
            }
            $verif = Db::name('sms_log')->where(array('account' => $mobile, 'code' => $code))->find();
            if (empty($verif)) {
                $this->apiError('短信未发送');
            } else {
                if ($verif['expire_time'] < time()) {
                    $this->apiError('短信已超时');
                }
            }
            $user = Db::name('user')->where(['mobile' => $mobile])->find();
        }
        if ($username) {
            if (empty($password)) {
                $this->apiError('参数错误');
            }
            $user = Db::name('user')->where(['username' => $username])->find();
            if (empty($user)) {
                $this->apiError('未找到此用户');
            }
            $pwd = set_password($password, $user['salt']);
            if ($pwd !== $user['password']) {
                $this->apiError('密码错误');
            }
        }
        if ($email) {
            if (empty($param['code'])) {
                $this->apiError('参数错误');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->apiError('邮箱格式不正确');
            }
            $code = intval($param['code']);
            $verif = Db::name('sms_log')->where(array('account' => $email, 'code' => $code))->find();
            if (empty($verif)) {
                $this->apiError('验证码未发送');
            } else {
                if ($verif['expire_time'] < time()) {
                    $this->apiError('验证码已超时');
                }
            }
            $user = Db::name('user')->where(['email' => $email])->find();
        }
        // 校验
        if (empty($user)) {
            $session_invite = get_config('app.session_invite');
            $invite = Cookie::get($session_invite);
            $invite = $invite ?: $invite_code;
            $pid = 0;
            if (!empty($invite)) {
                $senior = Db::name('user')->where(['qrcode_invite' => $invite])->find();
                if (!empty($senior)) {
                    $pid = $senior['id'];
                }
            }
            $add = [];
            $add['salt'] = set_salt(20);
            $add['username'] = $username;
            $add['email'] = $email;
            $add['mobile'] = $mobile;
            $add['coin'] = 0;
            $add['inviter'] = $pid;
            $add['password'] = set_password(set_salt(20), $add['salt']);
            $add['register_time'] = time();
            $add['mobile_status'] = $mobile ? 1 : 0;
            $add['headimgurl'] = '';
            $add['nickname'] = randNickname();
            $add['qrcode_invite'] = get_invite_code();
            $add['register_ip'] = request()->ip();
            $uid = Db::name('user')->strict(false)->field(true)->insertGetId($add);
            if (!$uid) {
                $this->apiError('登录失败');
            }
            $user = Db::name('user')->where(['id' => $uid])->find();
            if (!empty($user)) {
                //发放奖励
                $conf = get_system_config('reward');
                if (intval($conf['mobile']) > 0 && $mobile) {
                    Db::startTrans();
                    try {
                        // 执行数据库操作
                        Db::name('user')->where('id', $uid)->inc('coin', intval($conf['mobile']))->update();
                        add_coin_log($uid, intval($conf['mobile']), 1, '绑定手机号奖励');
                        Db::name('task')->strict(false)->field(true)->insertGetId([
                            'user_id' => $uid,
                            'taskid' => $conf['mobile_id'],
                            'type' => 1,
                            'status' => 1,
                            'title' => '绑定手机号奖励',
                            'task_date' => date('Y-m-d'),
                            'reward' => intval($conf['mobile']),
                            'ip' => app('request')->ip(),
                            'create_time' => time()
                        ]);
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                    }
                }
                //邀请
                if (!empty($invite)) {
                    Cookie::delete($session_invite);
                    if ($pid > 0) {
                        //邀请奖励
                        if (intval($conf['invite_reward']) > 0) {
                            Db::startTrans();
                            try {
                                // 执行数据库操作
                                Db::name('user')->where('id', $pid)->inc('coin', intval($conf['invite_reward']))->update();
                                add_coin_log($pid, intval($conf['invite_reward']), 2, '邀请一个好友奖励，好友ID：' . $uid);
                                Db::name('task')->strict(false)->field(true)->insertGetId([
                                    'user_id' => $pid,
                                    'taskid' => $uid,
                                    'type' => 3,
                                    'status' => 1,
                                    'title' => '邀请一个好友奖励',
                                    'task_date' => date('Y-m-d'),
                                    'reward' => intval($conf['invite_reward']),
                                    'ip' => app('request')->ip(),
                                    'create_time' => time()
                                ]);
                                // 提交事务
                                Db::commit();
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                            }
                        }
                        //先生成奖励任务
                        Db::name('task')->strict(false)->field(true)->insertGetId([
                            'user_id' => $pid,
                            'taskid' => $uid,
                            'type' => 4,
                            'status' => 0,
                            'title' => '注册当天首次阅读章节',
                            'task_date' => date('Y-m-d'),
                            'reward' => intval($conf['invite_1_level']),
                            'ip' => app('request')->ip(),
                            'create_time' => time()
                        ]);
                        Db::name('task')->strict(false)->field(true)->insertGetId([
                            'user_id' => $pid,
                            'taskid' => $uid,
                            'type' => 5,
                            'status' => 0,
                            'title' => '注册开始连续3天阅读章节',
                            'task_date' => date('Y-m-d'),
                            'reward' => intval($conf['invite_2_level']),
                            'ip' => app('request')->ip(),
                            'create_time' => time()
                        ]);
                        Db::name('task')->strict(false)->field(true)->insertGetId([
                            'user_id' => $pid,
                            'taskid' => $uid,
                            'type' => 6,
                            'status' => 0,
                            'title' => '注册开始连续7天阅读章节',
                            'task_date' => date('Y-m-d'),
                            'reward' => intval($conf['invite_3_level']),
                            'ip' => app('request')->ip(),
                            'create_time' => time()
                        ]);
                    }
                }
            }
        }
        if (empty($user)) {
            $this->apiError('注册失败');
        }
        $data = [
            'last_login_time' => time(),
            'last_login_ip' => request()->ip(),
            'login_num' => $user['login_num'] + 1,
        ];
        $res = Db::name('user')->where(['id' => $user['id']])->update($data);
        if ($res) {
            add_user_log('api', '登录');
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
            $this->apiSuccess('登录成功', ['token' => $token]);
        }
        $this->apiError('注册失败');
    }

    public function register()
    {
        $param = get_params();
        $username = isset($param['username']) ?  trim($param['username']) : '';
        $password = isset($param['password']) ?  trim($param['password']) : '';
        $confirmPassword = isset($param['confirmPassword']) ?  trim($param['confirmPassword']) : '';
        $nickname = isset($param['nickname']) ?  trim($param['nickname']) : '';
        $captcha = isset($param['captcha']) ? $param['captcha'] : '';
        $invite_code = isset($param['invite_code']) ? trim($param['invite_code']) : '';
        if (empty($username) || empty($password) || empty($confirmPassword) || empty($nickname)) {
            $this->apiError('参数错误');
        }
        if (empty($captcha)) {
            $this->apiError('参数错误');
        }
        if (!captcha_check($captcha)) {
            $this->apiError('验证码错误');
        }
        if ($password != $confirmPassword) {
            $this->apiError('两次密码输入不一致');
        }
        $user = Db::name('user')->where(['username' => $username])->find();
        if (!empty($user)) {
            $this->apiError('此用户名已被注册');
        }
        $user = Db::name('user')->where(['nickname' => $nickname])->find();
        if (!empty($user)) {
            $this->apiError('此昵称已被注册');
        }
        $session_invite = get_config('app.session_invite');
        $invite = Cookie::get($session_invite);
        $invite = $invite ?: $invite_code;
        $pid = 0;
        if (!empty($invite)) {
            $senior = Db::name('user')->where(['qrcode_invite' => $invite])->find();
            if (!empty($senior)) {
                $pid = $senior['id'];
            }
        }
        $add = [];
        $add['salt'] = set_salt(20);
        $add['username'] = $username;
        $add['mobile'] = '';
        $add['coin'] = 0;
        $add['inviter'] = $pid;
        $add['password'] = set_password($password, $add['salt']);
        $add['register_time'] = time();
        $add['mobile_status'] = 0;
        $add['headimgurl'] = '';
        $add['nickname'] = $nickname;
        $add['qrcode_invite'] = get_invite_code();
        $add['register_ip'] = request()->ip();
        $uid = Db::name('user')->strict(false)->field(true)->insertGetId($add);
        if (!$uid) {
            $this->apiError('注册失败');
        }
        $user = Db::name('user')->where(['id' => $uid])->find();
        if (!empty($user)) {
            //发放奖励
            $conf = get_system_config('reward');
            //邀请
            if (!empty($invite)) {
                Cookie::delete($session_invite);
                if ($pid > 0) {
                    //邀请奖励
                    if (intval($conf['invite_reward']) > 0) {
                        Db::startTrans();
                        try {
                            // 执行数据库操作
                            Db::name('user')->where('id', $pid)->inc('coin', intval($conf['invite_reward']))->update();
                            add_coin_log($pid, intval($conf['invite_reward']), 2, '邀请一个好友奖励，好友ID：' . $uid);
                            Db::name('task')->strict(false)->field(true)->insertGetId([
                                'user_id' => $pid,
                                'taskid' => $uid,
                                'type' => 3,
                                'status' => 1,
                                'title' => '邀请一个好友奖励',
                                'task_date' => date('Y-m-d'),
                                'reward' => intval($conf['invite_reward']),
                                'ip' => app('request')->ip(),
                                'create_time' => time()
                            ]);
                            // 提交事务
                            Db::commit();
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                        }
                    }
                    //先生成奖励任务
                    Db::name('task')->strict(false)->field(true)->insertGetId([
                        'user_id' => $pid,
                        'taskid' => $uid,
                        'type' => 4,
                        'status' => 0,
                        'title' => '注册当天首次阅读章节',
                        'task_date' => date('Y-m-d'),
                        'reward' => intval($conf['invite_1_level']),
                        'ip' => app('request')->ip(),
                        'create_time' => time()
                    ]);
                    Db::name('task')->strict(false)->field(true)->insertGetId([
                        'user_id' => $pid,
                        'taskid' => $uid,
                        'type' => 5,
                        'status' => 0,
                        'title' => '注册开始连续3天阅读章节',
                        'task_date' => date('Y-m-d'),
                        'reward' => intval($conf['invite_2_level']),
                        'ip' => app('request')->ip(),
                        'create_time' => time()
                    ]);
                    Db::name('task')->strict(false)->field(true)->insertGetId([
                        'user_id' => $pid,
                        'taskid' => $uid,
                        'type' => 6,
                        'status' => 0,
                        'title' => '注册开始连续7天阅读章节',
                        'task_date' => date('Y-m-d'),
                        'reward' => intval($conf['invite_3_level']),
                        'ip' => app('request')->ip(),
                        'create_time' => time()
                    ]);
                }
            }
        }
        if (empty($user)) {
            $this->apiError('注册失败');
        }
        $this->apiSuccess('注册成功');
    }

    /**
     * 退出
     * Summary of logout
     * @return void
     */
    public function logout()
    {
        $this->apiSuccess('退出成功', []);
    }

    /**
     * 短信发送
     * Summary of smssend
     * @return void
     */
    public function smssend()
    {
        $param = get_params();
        $mobile = isset($param['mobile']) ? trim($param['mobile']) : '';
        //发送配置
        $config_web = get_system_config('web');
        $verif = Db::name('sms_log')->where(array('account' => $mobile))->find();
        if (!empty($verif)) {
            if ($verif['expire_time'] > time()) {
                $this->apiError('已发出的验证码还有效，请输入！');
            }
        }
        $code = mt_rand(100000, 999999);
        //邮箱
        if (filter_var($mobile, FILTER_VALIDATE_EMAIL)) {
            $content = '【' . $config_web['title'] . '】验证码：' . $code . '（15分钟内有效），请勿泄露验证码，如非本人操作，请忽略。';
            $send = send_email($mobile, $config_web['title'] . '注册邮件', $content);
            if ($send === true) {
                if (!empty($verif)) {
                    $data = array(
                        'account' => $mobile,
                        'count' => $verif['count']++,
                        'send_time' => time(),
                        'expire_time' => time() + 900,
                        'code' => $code,
                    );
                    $res = Db::name('sms_log')->where(['id' => $verif['id']])->strict(false)->field(true)->update($data);
                    if ($res) {
                        $this->apiSuccess('发送成功', []);
                    } else {
                        $this->apiError('发送失败');
                    }
                } else {
                    $data = array(
                        'account' => $mobile,
                        'count' => 1,
                        'send_time' => time(),
                        'expire_time' => time() + 900,
                        'code' => $code,
                    );
                    $id = Db::name('sms_log')->strict(false)->field(true)->insertGetId($data);
                    if ($id > 0) {
                        $this->apiSuccess('发送成功', []);
                    } else {
                        $this->apiError('发送失败');
                    }
                }
            } else {
                $this->apiError('发送失败：' . $send);
            }
        }
        //手机
        if (preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            if (get_addons_is_enable('aliyunsms')) {
                $result = hook('aliyunSmsSendHook', ['code' => $code, 'phone' => $mobile]);
                $result = json_decode($result, true);
                if ($result && is_array($result) && $result['Code'] == 'OK') {
                    if (!empty($verif)) {
                        $data = array(
                            'account' => $mobile,
                            'count' => $verif['count']++,
                            'send_time' => time(),
                            'expire_time' => time() + 900,
                            'code' => $code,
                        );
                        $res = Db::name('sms_log')->where(['id' => $verif['id']])->strict(false)->field(true)->update($data);
                        if ($res) {
                            $this->apiSuccess('发送成功', []);
                        } else {
                            $this->apiError('发送失败');
                        }
                    } else {
                        $data = array(
                            'account' => $mobile,
                            'count' => 1,
                            'send_time' => time(),
                            'expire_time' => time() + 900,
                            'code' => $code,
                        );
                        $id = Db::name('sms_log')->strict(false)->field(true)->insertGetId($data);
                        if ($id > 0) {
                            $this->apiSuccess('发送成功', []);
                        } else {
                            $this->apiError('发送失败');
                        }
                    }
                } else {
                    $this->apiError($result['Message']);
                }
            }
        }
        $this->apiError('禁止相关功能');
    }
}
