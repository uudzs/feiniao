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
        Auth::class => ['except' => ['login', 'register', 'token', 'captcha']]
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
            $this->apiError('upload.empty');
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
            $this->apiError('upload.err');
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
            $obj = auto_run_addons('storage', ['url' => $filename]);
            if ($obj) {
                $result = isset($obj[0]) ? $obj[0] : $obj;
                if (!isJson($result)) $this->apiError('fail');
                $result = json_decode($result, true);
                if (isset($result['code']) && intval($result['code']) == 0) {
                    $filepath = $result['data'] ?: $filepath;
                } else {
                    $this->apiError('fail');
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
            $this->apiSuccess('success', $res);
        } else {
            $this->apiError('fail');
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
            $this->apiError('404');
        }
        $time = time();
        $table = config('database.connections.mysql.prefix') . 'advsr';
        $condition = '';
        $result = [];
        $modelName = \think\facade\App::initialize()->http->getName();
        if (strpos($pid, ',') !== false) {
            $adver = Db::name('adver')->where('id', 'in', $pid)->select()->toArray();
            $pids = explode(',', $pid);
            $adver = array_column($adver, null, 'id');
            foreach ($pids as $key => $value) {
                $list = [];
                if (isset($adver[$value]) && intval($adver[$value]['status']) == 1) {
                    $limit = $pagesize > 0 ? $pagesize : (intval($adver[$value]['viewnum']) > 0 ? intval($adver[$value]['viewnum']) : 0);
                    if ($limit <= 0) $limit = get_config('app.page_size');
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
                        $list = Db::query("SELECT `id`,`title`,`adver_id`,`type`,`link`,`start_time`,`end_time`,`color`,`books`,`images`,`introduction`,`create_time` FROM `{$table}` WHERE `status`=:status AND `adver_id`=:adver_id AND `start_time`<:stime AND (`end_time`<=0 OR `end_time`>=:etime) ORDER BY `level` DESC LIMIT {$condition}", ['status' => 1, 'adver_id' => $value, 'stime' => $time, 'etime' => $time]);
                        foreach ($list as $k => $v) {
                            if (intval($v['books']) > 0) {
                                $book = Db::name('book')->where(['id' => $v['books']])->find();
                                if (!empty($book)) {
                                    $list[$k]['author'] = $book['author'];
                                    $list[$k]['authorid'] = $book['authorid'];
                                    $list[$k]['headimg'] = get_file(Db::name('author')->where(['id' => $book['authorid']])->value('headimg'));
                                    if (!empty($book['genre'])) {
                                        $list[$k]['genre'] = Db::name('category')->where(['id' => $book['genre']])->value('name');
                                    } else {
                                        $list[$k]['genre'] = '';
                                    }
                                    $v['images'] = $v['images'] ? $v['images'] : $book['cover'];
                                    $list[$k]['chapters'] = $book['chapters'];
                                    $list[$k]['isfinish'] = $book['isfinish'];
                                    $list[$k]['hits'] = $book['hits'];
                                    $list[$k]['bigcate'] = $book['genre'];
                                    $list[$k]['subgenre'] = $book['subgenre'];
                                    $list[$k]['words'] = $book['words'];
                                    $list[$k]['finish'] = intval($book['isfinish']) == 2 ? lang('finish') : lang('serialize');
                                    $list[$k]['url'] = str_replace($modelName, 'home', (string) Route::buildUrl('book_detail', ['id' => $book['filename'] ? $book['filename'] : $book['id']]));
                                } else {
                                    $list[$k]['isfinish'] = 1;
                                    $list[$k]['finish'] = '';
                                    $list[$k]['author'] = '';
                                    $list[$k]['headimg'] = '';
                                    $list[$k]['genre'] = '';
                                    $list[$k]['bigcate'] = 0;
                                    $list[$k]['authorid'] = 0;
                                    $list[$k]['subgenre'] = 0;
                                    $list[$k]['words'] = 0;
                                    $list[$k]['hits'] = 0;
                                    $list[$k]['chapters'] = 0;
                                    $list[$k]['url'] = str_replace($modelName, 'home', (string) Route::buildUrl('book_detail', ['id' => $v['books']]));
                                }
                            } else {
                                $list[$k]['finish'] = '';
                                $list[$k]['author'] = '';
                                $list[$k]['headimg'] = '';
                                $list[$k]['genre'] = '';
                                $list[$k]['url'] = '';
                                $list[$k]['bigcate'] = 0;
                                $list[$k]['authorid'] = 0;
                                $list[$k]['subgenre'] = 0;
                                $list[$k]['words'] = 0;
                                $list[$k]['chapters'] = 0;
                                $list[$k]['hits'] = 0;
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
                $this->apiError('404');
            }
            if (intval($adver['status']) != 1) {
                $this->apiError('407');
            }
            $limit = $pagesize > 0 ? $pagesize : (intval($adver['viewnum']) > 0 ? intval($adver['viewnum']) : 0);
            if ($limit <= 0) $limit = get_config('app.page_size');
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
                $result = Db::query("SELECT `id`,`title`,`adver_id`,`type`,`link`,`start_time`,`end_time`,`color`,`books`,`images`,`introduction`,`create_time` FROM `{$table}` WHERE `status`=:status AND `adver_id`=:adver_id AND `start_time`<:stime AND (`end_time`<=0 OR `end_time`>=:etime) ORDER BY `level` DESC LIMIT {$condition}", ['status' => 1, 'adver_id' => $pid, 'stime' => $time, 'etime' => $time]);
                foreach ($result as $k => $v) {
                    if (intval($v['books']) > 0) {
                        $book = Db::name('book')->where(['id' => $v['books']])->find();
                        if (!empty($book)) {
                            $result[$k]['author'] = $book['author'];
                            $result[$k]['authorid'] = $book['authorid'];
                            $result[$k]['headimg'] = get_file(Db::name('author')->where(['id' => $book['authorid']])->value('headimg'));
                            if (!empty($book['genre'])) {
                                $result[$k]['genre'] = Db::name('category')->where(['id' => $book['genre']])->value('name');
                            } else {
                                $result[$k]['genre'] = '';
                            }
                            $result[$k]['finish'] = intval($book['isfinish']) == 2 ? lang('finish') : lang('serialize');
                            $result[$k]['chapters'] = $book['chapters'];
                            $result[$k]['isfinish'] = $book['isfinish'];
                            $result[$k]['subgenre'] = $book['subgenre'];
                            $result[$k]['words'] = $book['words'];
                            $result[$k]['bigcate'] = $book['genre'];
                            $result[$k]['hits'] = $book['hits'];
                            $result[$k]['url'] = str_replace($modelName, 'home', (string) Route::buildUrl('book_detail', ['id' => $book['filename'] ? $book['filename'] : $book['id']]));
                            $v['images'] = $v['images'] ? $v['images'] : $book['cover'];
                        } else {
                            $result[$k]['finish'] = '';
                            $result[$k]['author'] = '';
                            $result[$k]['headimg'] = '';
                            $result[$k]['genre'] = '';
                            $result[$k]['chapters'] = 0;
                            $result[$k]['authorid'] = 0;
                            $result[$k]['words'] = 0;
                            $result[$k]['bigcate'] = 0;
                            $result[$k]['subgenre'] = 0;
                            $result[$k]['hits'] = 0;
                            $result[$k]['isfinish'] = 1;
                            $result[$k]['url'] = str_replace($modelName, 'home', (string) Route::buildUrl('book_detail', ['id' => $v['books']]));
                        }
                    } else {
                        $result[$k]['isfinish'] = 1;
                        $result[$k]['finish'] = '';
                        $result[$k]['author'] = '';
                        $result[$k]['headimg'] = '';
                        $result[$k]['genre'] = '';
                        $result[$k]['url'] = '';
                        $result[$k]['chapters'] = 0;
                        $result[$k]['authorid'] = 0;
                        $result[$k]['hits'] = 0;
                        $result[$k]['words'] = 0;
                        $result[$k]['bigcate'] = 0;
                        $result[$k]['subgenre'] = 0;
                    }
                    $result[$k]['images'] = get_file($v['images']);
                    $result[$k]['width'] = $adver['width'];
                    $result[$k]['height'] = $adver['height'];
                    $result[$k]['isendpage'] = $isendpage;
                }
            }
        }
        $this->apiSuccess('success', $result);
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
        JWT::$leeway = 60;
        $time = time();
        if ($token) {
            if (count(explode('.', $token)) != 3) {
                $this->apiError('common.tokenerr');
            }
            try {
                $decoded = JWT::decode($token, new Key($config['secrect'], 'HS256'));
                $data = json_decode(json_encode($decoded), TRUE);
                $jwt_data = $data['data'];
                $uid = $jwt_data['userid'];
                $arr = [
                    'iss' => $config['iss'],
                    'aud' => $config['aud'],
                    'iat' => $time,
                    'nbf' => $time - 1,
                    'exp' => $time + $config['exptime'],
                    'data' => [
                        'userid' => $uid,
                    ]
                ];
                $token = JWT::encode($arr, $config['secrect'], 'HS256');
                $this->apiSuccess('success', ['token' => $token]);
            } catch (\Firebase\JWT\SignatureInvalidException $e) {
                $this->apiError('common.signerr', [], 403);
            } catch (\Firebase\JWT\BeforeValidException $e) {
                $arr = [
                    'iss' => $config['iss'],
                    'aud' => $config['aud'],
                    'iat' => $time,
                    'nbf' => $time - 1,
                    'exp' => $time + $config['exptime'],
                    'data' => [
                        'userid' => '',
                    ]
                ];
                $token = JWT::encode($arr, $config['secrect'], 'HS256');
                $this->apiSuccess('success', ['token' => $token]);
            } catch (\Firebase\JWT\ExpiredException $e) {
                $arr = [
                    'iss' => $config['iss'],
                    'aud' => $config['aud'],
                    'iat' => $time,
                    'nbf' => $time - 1,
                    'exp' => $time + $config['exptime'],
                    'data' => [
                        'userid' => '',
                    ]
                ];
                $token = JWT::encode($arr, $config['secrect'], 'HS256');
                $this->apiSuccess('success', ['token' => $token]);
            } catch (\Exception $e) {
                $this->apiError('403', [], 404);
            } catch (\UnexpectedValueException $e) {
                $this->apiError('403', [], 404);
            } catch (\DomainException $e) {
                $this->apiError('403', [], 404);
            }
        } else {
            $arr = [
                'iss' => $config['iss'],
                'aud' => $config['aud'],
                'iat' => $time,
                'nbf' => $time - 1,
                'exp' => $time + $config['exptime'],
                'data' => [

                    'userid' => '',
                ]
            ];
            $token = JWT::encode($arr, $config['secrect'], 'HS256');
            $this->apiSuccess('success', ['token' => $token]);
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $user = Db::name('user')->field('nickname,username,name,mobile,headimgurl,email,mobile_status,sex,desc,birthday,level,status,country,province,city,company,address,depament,position,qrcode_invite,coin,inviter,securitypwd,realname_status,id_card,author_id')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404', [], 98);
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
        $user['sex'] = ($user['sex'] == 1) ? lang('common.male') : ($user['sex'] == 2 ?  lang('common.female') : lang('common.unknown'));
        $this->apiSuccess('success', ['userinfo' => $user]);
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
            $this->apiError('empty');
        }
        $user = [];
        $power = get_system_config('power');
        if ($mobile) {
            if (empty($password) && empty($param['code'])) {
                $this->apiError('empty');
            }
            if (empty($password)) {
                if (isset($power['login_type'])) {
                    if (!in_array('sms', $power['login_type'])) {
                        $this->apiError('login.prohibitsmslogin');
                    }
                }
                $code = intval($param['code']);
                if (empty($code)) {
                    $this->apiError('login.captchaempty');
                }
                if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
                    $this->apiError('login.phoneerr');
                }
                $verif = Db::name('sms_log')->where(array('account' => $mobile, 'code' => $code))->find();
                if (empty($verif)) {
                    $this->apiError('login.smsnotsend');
                } else {
                    if ($verif['expire_time'] < time()) {
                        $this->apiError('login.smsexpire');
                    }
                }
                $user = Db::name('user')->where(['mobile' => $mobile])->find();
            } else {
                if (isset($power['login_type'])) {
                    if (!in_array('account', $power['login_type'])) {
                        $this->apiError('login.prohibitaccountlogin');
                    }
                }
                $user = Db::name('user')->where(['mobile' => $mobile])->find();
                if (empty($user)) {
                    $this->apiError('404');
                }
                $pwd = set_password($password, $user['salt']);
                if ($pwd !== $user['password']) {
                    $this->apiError('login.passerr');
                }
            }
        }
        if ($username) {
            if (isset($power['login_type'])) {
                if (!in_array('account', $power['login_type'])) {
                    $this->apiError('login.prohibitaccountlogin');
                }
            }
            if (empty($password)) {
                $this->apiError('empty');
            }
            $user = Db::name('user')->where(['username' => $username])->find();
            if (empty($user)) {
                $this->apiError('404');
            }
            $pwd = set_password($password, $user['salt']);
            if ($pwd !== $user['password']) {
                $this->apiError('login.passerr');
            }
        }
        if ($email) {
            if (isset($power['login_type'])) {
                if (!in_array('sms', $power['login_type'])) {
                    $this->apiError('login.prohibitsmslogin');
                }
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->apiError('login.emailerr');
            }
            $code = intval($param['code']);
            if (empty($code)) {
                $this->apiError('login.captchaempty');
            }
            $verif = Db::name('sms_log')->where(array('account' => $email, 'code' => $code))->find();
            if (empty($verif)) {
                $this->apiError('login.smsnotsend');
            } else {
                if ($verif['expire_time'] < time()) {
                    $this->apiError('login.smsexpire');
                }
            }
            $user = Db::name('user')->where(['email' => $email])->find();
        }
        // 校验
        if (empty($user)) {
            if (isset($power['register_open']) && intval($power['register_open']) != 1) {
                $this->apiError('404');
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
                $this->apiError();
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
                        add_coin_log($uid, intval($conf['mobile']), 1, lang('reward.bindphone'));
                        Db::name('task')->strict(false)->field(true)->insertGetId([
                            'user_id' => $uid,
                            'taskid' => $conf['mobile_id'],
                            'type' => 1,
                            'status' => 1,
                            'title' => lang('reward.bindphone'),
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
                                add_coin_log($pid, intval($conf['invite_reward']), 2, lang('reward.invitefriend') . lang('reward.friendid') . $uid);
                                Db::name('task')->strict(false)->field(true)->insertGetId([
                                    'user_id' => $pid,
                                    'taskid' => $uid,
                                    'type' => 3,
                                    'status' => 1,
                                    'title' => lang('reward.invitefriend'),
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
                            'title' => lang('reward.firstread'),
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
                            'title' => lang('reward.day3read'),
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
                            'title' => lang('reward.day7read'),
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
            $this->apiError('fail');
        }
        $data = [
            'last_login_time' => time(),
            'last_login_ip' => request()->ip(),
            'login_num' => $user['login_num'] + 1,
        ];
        $res = Db::name('user')->where(['id' => $user['id']])->update($data);
        if ($res) {
            $config = get_system_config('token');
            JWT::$leeway = 60;
            $time = time();
            $arr = [
                'iss' => $config['iss'],
                'aud' => $config['aud'],
                'iat' => $time,
                'nbf' => $time - 1,
                'exp' => $time + $config['exptime'],
                'data' => [

                    'userid' => $user['id'],
                ]
            ];
            $token = JWT::encode($arr, $config['secrect'], 'HS256');
            $this->apiSuccess('success', ['token' => $token]);
        }
        $this->apiError('fail');
    }

    public function third()
    {
        $param = get_params();
        $openid = isset($param['openid']) ? trim($param['openid']) : '';
        $unionid = isset($param['unionid']) ? trim($param['unionid']) : '';
        $platform = isset($param['platform']) ? trim($param['platform']) : '';
        $apptype = isset($param['apptype']) ? trim($param['apptype']) : '';
        $nickname = isset($param['nickname']) ? trim($param['nickname']) : '';
        $headimg = isset($param['headimg']) ? trim($param['headimg']) : '';
        $expires_in = isset($param['expires_in']) ? trim($param['expires_in']) : 0;
        $access_token = isset($param['access_token']) ? trim($param['access_token']) : '';
        if (empty($openid) && empty($unionid)) $this->apiError('empty');
        if (empty($platform)) $this->apiError('empty');
        $where = ['platform' => $platform];
        if (!empty($unionid)) {
            $where['unionid'] = $unionid;
        } else {
            $where['openid'] = $openid;
        }
        $third = Db::name('third')->where($where)->find();
        if (empty($third)) {
            $session_invite = get_config('app.session_invite');
            $invite = Cookie::get($session_invite);
            $pid = 0;
            if (!empty($invite)) {
                $senior = Db::name('user')->where(['qrcode_invite' => $invite])->find();
                if (!empty($senior)) {
                    $pid = $senior['id'];
                }
            }
            $salt = set_salt(20);
            $add = [
                'nickname' => $nickname ? $nickname : randNickname(),
                'inviter' => $pid,
                'salt' => $salt,
                'coin' => 0,
                'mobile_status' => 0,
                'headimgurl' => $headimg ? $headimg : '',
                'email' => $platform == 'apple' ? $openid : '',
                'password' => set_password(set_salt(20), $salt),
                'register_time' => time(),
                'qrcode_invite' => get_invite_code(),
                'register_ip' => request()->ip(),
                'last_login_time' => time(),
                'last_login_ip' => request()->ip(),
                'login_num' => 1,
            ];
            $uid = Db::name('user')->strict(false)->field(true)->insertGetId($add);
            if ($uid) {
                $member = Db::name('user')->where(['id' => $uid])->find();
                if (!empty($member)) {
                    $data = [
                        'user_id' => $uid,
                        'platform' => $platform,
                        'apptype' => $apptype ?: 'app',
                        'unionid' => $unionid,
                        'openid' => $openid,
                        'openname' => $nickname,
                        'access_token' => $access_token,
                        'refresh_token' => '',
                        'expires_in' => $expires_in,
                        'createtime' => time(),
                        'logintime' => time(),
                    ];
                    $data['expiretime'] = time() + intval($expires_in);
                    $tid = Db::name('third')->strict(false)->field(true)->insertGetId($data);
                    //邀请
                    if (!empty($invite)) {
                        $conf = get_system_config('reward');
                        Cookie::delete($session_invite);
                        if ($pid > 0) {
                            //邀请奖励
                            if (intval($conf['invite_reward']) > 0) {
                                Db::startTrans();
                                try {
                                    // 执行数据库操作
                                    Db::name('user')->where('id', $pid)->inc('coin', intval($conf['invite_reward']))->update();
                                    add_coin_log($pid, intval($conf['invite_reward']), 2, lang('reward.invitefriend') . lang('reward.friendid') . $uid);
                                    Db::name('task')->strict(false)->field(true)->insertGetId([
                                        'user_id' => $pid,
                                        'taskid' => $uid,
                                        'type' => 3,
                                        'status' => 1,
                                        'title' => lang('reward.invitefriend'),
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
                                'title' => lang('reward.firstread'),
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
                                'title' => lang('reward.day3read'),
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
                                'title' => lang('reward.day7read'),
                                'task_date' => date('Y-m-d'),
                                'reward' => intval($conf['invite_3_level']),
                                'ip' => app('request')->ip(),
                                'create_time' => time()
                            ]);
                        }
                    }
                }
            }
        } else {
            $member = Db::name('user')->where(['id' => $third['user_id']])->find();
            if (!empty($member)) {
                $data = [
                    'openname' => $nickname,
                    'access_token' => $access_token,
                    'expires_in' => $expires_in,
                    'updatetime' => time(),
                    'logintime' => time(),
                ];
                $data['expiretime'] = time() + intval($expires_in);
                Db::name('third')->where('id', $third['id'])->update($data);
                $data = [
                    'last_login_time' => time(),
                    'last_login_ip' => request()->ip(),
                    'login_num' => $member['login_num'] + 1,
                ];
                $res = Db::name('user')->where(['id' => $member['id']])->update($data);
            }
        }
        //登录
        if (!empty($member)) {
            $wechatcnf = get_system_config('token');
            JWT::$leeway = 60;
            $time = time();
            $arr = [
                'iss' => $wechatcnf['iss'],
                'aud' => $wechatcnf['aud'],
                'iat' => $time,
                'nbf' => $time - 1,
                'exp' => $time + $wechatcnf['exptime'],
                'data' => [
                    'userid' => $member['id'],
                ]
            ];
            $token = JWT::encode($arr, $wechatcnf['secrect'], 'HS256');
            if ($token) {
                $session_user = get_config('app.session_user');
                Cookie::set($session_user, $token);
                $this->apiSuccess('success', ['token' => $token]);
            } else {
                $this->apiError('fail');
            }
        } else {
            $this->apiError('fail');
        }
    }

    public function pages()
    {
        $param = get_params();
        $name = isset($param['name']) ? trim($param['name']) : '';
        if (empty($name)) {
            $this->apiError('empty');
        }
        $res = Db::name('pages')->where(['status' => 1, 'name' => $name])->find();
        $this->apiSuccess('success', $res ?: []);
    }

    public function system()
    {
        $param = get_params();
        $config = isset($param['config']) ? $param['config'] : '';
        $name = isset($param['name']) ? trim($param['name']) : '';
        if (empty($config) && empty($name)) {
            $this->apiError('empty');
        }
        if (empty($config)) {
            $this->apiError('empty');
        }
        $res = [];
        if ($name) {
            $res = get_system_config($config, $name);
        } else {
            $res = get_system_config($config);
        }
        if ($config == 'web' && isset($res['logo'])) {
            $res['logo'] = get_file($res['logo']);
        }
        $this->apiSuccess('success', $res);
    }

    public function register()
    {
        $power = get_system_config('power');
        if (isset($power['register_open']) && intval($power['register_open']) != 1) {
            $this->apiError('403');
        }
        $param = get_params();
        $username = isset($param['username']) ?  trim($param['username']) : '';
        $password = isset($param['password']) ?  trim($param['password']) : '';
        $confirmPassword = isset($param['confirmPassword']) ?  trim($param['confirmPassword']) : '';
        $nickname = isset($param['nickname']) ?  trim($param['nickname']) : '';
        $captcha = isset($param['captcha']) ? $param['captcha'] : '';
        $isapp = isset($param['isapp']) ? intval($param['isapp']) : 0;
        $invite_code = isset($param['invite_code']) ? trim($param['invite_code']) : '';
        if (empty($username) || empty($password) || empty($confirmPassword) || empty($nickname)) {
            $this->apiError('empty');
        }
        if (empty($isapp)) {
            if (empty($captcha)) {
                $this->apiError('empty');
            }
            if (!captcha_check($captcha)) {
                $this->apiError('login.captchaerr');
            }
        }
        if ($password != $confirmPassword) {
            $this->apiError('register.twopasserr');
        }
        $user = Db::name('user')->where(['username' => $username])->find();
        if (!empty($user)) {
            $this->apiError('register.alreadyreg');
        }
        $user = Db::name('user')->where(['nickname' => $nickname])->find();
        if (!empty($user)) {
            $this->apiError('register.nicknamealreadyreg');
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
            $this->apiError('fail');
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
                            add_coin_log($pid, intval($conf['invite_reward']), 2, lang('reward.invitefriend') . lang('reward.friendid') . $uid);
                            Db::name('task')->strict(false)->field(true)->insertGetId([
                                'user_id' => $pid,
                                'taskid' => $uid,
                                'type' => 3,
                                'status' => 1,
                                'title' => lang('reward.invitefriend'),
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
                        'title' => lang('reward.firstread'),
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
                        'title' => lang('reward.day3read'),
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
                        'title' => lang('reward.day7read'),
                        'task_date' => date('Y-m-d'),
                        'reward' => intval($conf['invite_3_level']),
                        'ip' => app('request')->ip(),
                        'create_time' => time()
                    ]);
                }
            }
        }
        if (empty($user)) {
            $this->apiError('fail');
        }
        $this->apiSuccess('success');
    }

    /**
     * 退出
     * Summary of logout
     * @return void
     */
    public function logout()
    {
        $this->apiSuccess('success', []);
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
                $this->apiError('common.captchavalid');
            }
        }
        $code = mt_rand(100000, 999999);
        //邮箱
        if (filter_var($mobile, FILTER_VALIDATE_EMAIL)) {
            $send = send_email($mobile, $config_web['title'] . lang('register.regemail'), lang('common.smstemplate', ['title' => $config_web['title'], 'code' => $code]));
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
                        $this->apiSuccess('success', []);
                    } else {
                        $this->apiError('fail');
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
                        $this->apiSuccess('success', []);
                    } else {
                        $this->apiError('fail');
                    }
                }
            } else {
                $this->apiError('fail');
            }
        }
        //手机
        if (preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            $obj = auto_run_addons('smssend', ['code' => $code, 'phone' => $mobile]);
            if ($obj) {
                $result = isset($obj[0]) ? $obj[0] : $obj;
                if (!isJson($result)) $this->apiError('fail');
                $result = json_decode($result, true);
                if (isset($result['code']) && intval($result['code']) == 0) {
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
                            $this->apiSuccess('success', []);
                        } else {
                            $this->apiError('fail');
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
                            $this->apiSuccess('success', []);
                        } else {
                            $this->apiError('fail');
                        }
                    }
                } else {
                    $this->apiError('fail');
                }
            } else {
                $this->apiError('fail');
            }
        }
        $this->apiError('407');
    }
}
