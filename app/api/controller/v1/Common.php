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
use think\facade\Validate;

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
                if (isset($result['code']) && intval($result['code']) === 0) {
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
     * 获取指定广告位广告内容（优化版）
     * @return \think\response\Json
     */
    public function recommend()
    {
        try {
            $param = get_params();
            $pid = trim((string)$param['pid'] ?? '');
            $page = max(1, intval($param['page'] ?? 1));
            $pagesize = max(0, intval($param['pagesize'] ?? 0));

            // 参数校验
            if (empty($pid)) {
                throw new \Exception('404');
            }

            // 获取广告位配置
            $adverList = $this->getValidAdverList($pid);
            if (empty($adverList)) {
                throw new \Exception('404');
            }

            // 批量获取广告内容
            $result = [];
            foreach ($adverList as $adver) {
                $paginator = $this->getPaginatedAdvData($adver, $page, $pagesize);
                $result[$adver['id']] = $this->enrichAdvData($paginator, $adver);
            }
            if (strpos($pid, ',') !== false) {
                return json(['code' => 0, 'data' => $result, 'msg' => lang('success')]);
            } else {
                return json(['code' => 0, 'data' => $result[$pid], 'msg' => lang('success')]);
            }
            // return $this->apiSuccess('success', $result);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage());
        }
    }

    /**
     * 获取有效广告位列表
     */
    private function getValidAdverList($pid)
    {
        $pids = is_array($pid) ? $pid : explode(',', $pid);
        return Db::name('adver')
            ->where('id', 'in', $pids)
            ->where('status', 1)
            ->cache(300) // 缓存5分钟
            ->column('*', 'id');
    }

    /**
     * 获取分页广告内容
     */
    private function getPaginatedAdvData($adver, $page, $pagesize)
    {
        $limit = $this->calculateLimit($pagesize, $adver['viewnum']);
        $currentTime = time();
        $query = Db::name('advsr')
            ->where('adver_id', $adver['id'])
            ->where('status', 1)
            ->where('start_time', '<', $currentTime)
            ->where(function ($query) use ($currentTime) {
                $query->where('end_time', '<=', 0)
                    ->whereOr('end_time', '>=', $currentTime); // 修正方法名
            })
            ->field('id,title,adver_id,type,link,books,images,introduction,color')
            ->order('level DESC');
        return $query->paginate(
            max(1, $limit),  // 确保最小分页数
            false,
            ['page' => $page]
        );
    }

    /**
     * 增强广告数据
     */
    private function enrichAdvData($paginator, $adver)
    {
        $bookIds = array_filter(array_column($paginator->items(), 'books'));
        $booksData = $this->getBooksData($bookIds);
        return array_map(function ($item) use ($adver, $booksData, $paginator) {
            $item['width'] = $adver['width'];
            $item['height'] = $adver['height'];
            $item['isendpage'] = $paginator->currentPage() >= $paginator->lastPage();
            if ($item['books'] && isset($booksData[$item['books']])) {
                $item = array_merge($item, $booksData[$item['books']]);
            }
            if ($item['books'] && !isset($booksData[$item['books']])) {
                $modelName = \think\facade\App::initialize()->http->getName();
                $item = array_merge(
                    $item,
                    [
                        'author' => '',
                        'authorid' => 0,
                        'headimg' => get_file('', 1),
                        'genre' => '',
                        'finish' => '',
                        'chapters' => 0,
                        'hits' => 0,
                        'url' => str_replace($modelName, 'home', (string) Route::buildUrl('book_detail', ['id' => $item['books']])),
                        'cover' => get_file('')
                    ]
                );
            }
            $item['images'] = get_file($item['images']);
            return $item;
        }, $paginator->items());
    }

    /**
     * 批量获取书籍数据
     */
    private function getBooksData($bookIds)
    {
        if (empty($bookIds)) return [];

        // 批量查询书籍信息
        $books = Db::name('book')
            ->whereIn('id', $bookIds)
            ->field('id,authorid,author,genre,subgenre,words,chapters,isfinish,hits,cover,filename')
            ->cache(600)
            ->select()
            ->column(null, 'id');

        // 批量查询分类信息
        $genreIds = array_unique(array_column($books, 'genre'));
        $genres = Db::name('category')
            ->whereIn('id', $genreIds)
            ->cache(600)
            ->column('name', 'id');

        // 批量查询作者头像
        $authorIds = array_unique(array_column($books, 'authorid'));
        $authors = Db::name('author')
            ->whereIn('id', $authorIds)
            ->cache(600)
            ->column('headimg', 'id');

        // 组合数据
        $result = [];
        $modelName = \think\facade\App::initialize()->http->getName();
        foreach ($books as $id => $book) {
            $result[$id] = [
                'author' => trim($book['author']),
                'authorid' => $book['authorid'],
                'headimg' => get_file($authors[$book['authorid']] ?? '', 1),
                'genre' => $genres[$book['genre']] ?? '',
                'finish' => $book['isfinish'] == 2 ? lang('finish') : lang('serialize'),
                'chapters' => $book['chapters'],
                'hits' => $book['hits'],
                'url' => str_replace($modelName, 'home', (string) Route::buildUrl('book_detail', ['id' => $book['filename'] ? $book['filename'] : $book['id']])),
                'cover' => get_file($book['cover'])
            ];
        }
        return $result;
    }
    /**
     * 计算实际限制条数
     */
    private function calculateLimit($pagesize, $viewnum)
    {
        if ($pagesize > 0) return $pagesize;
        if ($viewnum > 0) return $viewnum;
        return get_config('app.page_size', 15);
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
        $user['headimgurl'] = get_file($user['headimgurl'], 1);
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
        $username = preg_replace('/\s+/', '', $username);
        $mobile = preg_replace('/\s+/', '', $mobile);
        $email = preg_replace('/\s+/', '', $email);
        if (empty($mobile) && empty($username) && empty($email)) {
            $this->apiError('empty');
        }
        $user = [];
        $power = get_system_config('power');
        if (!isset($power['login_open']) || empty($power['login_open'])) {
            $this->apiError('407');
        }
        if ($mobile) {
            if (empty($password) && empty($param['code'])) {
                $this->apiError('empty');
            }
            if (empty($password)) {
                $isSmsLogin = get_addons_type('smssend');
                if (empty($isSmsLogin)) {
                    $this->apiError('login.prohibitsmslogin');
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
            $validate = Validate::rule([
                'username' => 'require|regex:^[a-zA-Z0-9_]+$',
            ]);
            $validate->message([
                'username.require' => lang('empty'),
                'username.regex' => lang('paramerror'),
            ]);
            if (!$validate->check($param)) {
                return json([
                    'code' => 1,
                    'msg' => $validate->getError()
                ])->header(['Content-Type' => 'application/json']);
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
            $isSmsLogin = get_addons_type('smssend');
            if (empty($isSmsLogin)) {
                $this->apiError('login.prohibitsmslogin');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->apiError('login.emailerr');
            }
            if (!isset($param['code']) || empty($param['code'])) {
                $this->apiError('login.captchaempty');
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
            if (isset($power['register_open']) && intval($power['register_open']) !== 1) {
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
        if (isset($power['register_open']) && intval($power['register_open']) !== 1) {
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
                if (isset($result['code']) && intval($result['code']) === 0) {
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
                $this->apiError('407');
            }
        }
        $this->apiError('407');
    }
}
