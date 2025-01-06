<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;
use app\admin\model\Readhistory;
use app\admin\model\Favorites;
use app\admin\model\User as UserModel;
use think\Image;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class User extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];

    /**
     * 添加或取消收藏
     * Summary of favorites
     * @return void
     */
    public function favorites()
    {
        $param = get_params();
        $pid = intval($param['bookid']); //ID
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        if (empty($pid)) {
            $this->apiError('参数错误');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        $fav = Db::name('favorites')->where(['user_id' => JWT_UID, 'pid' => $pid])->find();
        if (empty($fav)) {
            $data = array(
                "user_id" => JWT_UID,
                "pid" => $pid,
                "create_time" => time(),
            );
            $fid = Db::name('favorites')->strict(false)->field(true)->insertGetId($data);
            if ($fid != false) {
                $this->apiSuccess('添加成功', ['fid' => $fid]);
            } else {
                $this->apiError('添加失败');
            }
        } else {
            //取消收藏！
            Db::name('favorites')->where(['user_id' => JWT_UID, 'pid' => $pid])->delete();
            $this->apiSuccess('取消成功', []);
        }
    }

    /**
     * 添加或取消关注
     * Summary of favorites
     * @return void
     */
    public function follow()
    {
        $param = get_params();
        $from_id = isset($param['from_id']) ?  intval($param['from_id']) : 0; //ID
        $type = isset($param['type']) ? intval($param['type']) : 1; //1作者2用户
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        if (empty($from_id)) {
            $this->apiError('参数错误');
        }
        if (JWT_UID == $from_id) {
            $this->apiError('不能关注自己');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        $follow = Db::name('follow')->where(['user_id' => JWT_UID, 'from_id' => $from_id])->find();
        if (empty($follow)) {
            $data = array(
                "user_id" => JWT_UID,
                "type" => $type,
                "from_id" => $from_id,
                "create_time" => time(),
            );
            $fid = Db::name('follow')->strict(false)->field(true)->insertGetId($data);
            if ($fid != false) {
                $this->apiSuccess('添加成功', ['fid' => $fid]);
            } else {
                $this->apiError('添加失败');
            }
        } else {
            //取消关注！
            Db::name('follow')->where(['user_id' => JWT_UID, 'from_id' => $from_id])->delete();
            $this->apiSuccess('取消成功', []);
        }
    }

    /**
     * 上传头像
     * Summary of avatar
     * @return void
     */
    public function avatar()
    {
        $param = get_params();
        $avatar = trim($param['avatar']);
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        if (empty($avatar)) {
            $this->apiError('参数错误');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        $result = Db::name('user')->where('id', $user['id'])->update(['headimgurl' => $avatar, 'update_time' => time()]);
        if ($result === false) {
            $this->apiError('上传失败');
        } else {
            $this->apiSuccess('上传成功');
        }
    }

    /**
     * 设置安全密码
     * Summary of security
     * @return void
     */
    public function security()
    {
        $param = get_params();
        $securitypwd = trim($param['securitypwd']);
        $oldsecuritypwd = trim($param['oldsecuritypwd']);
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        if (empty($securitypwd)) {
            $this->apiError('参数错误');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        if ($user['securitypwd']) {
            if (empty($oldsecuritypwd)) {
                $this->apiError('旧安全密码为空');
            }
            if (!password_verify($oldsecuritypwd, $user['securitypwd'])) {
                $this->apiError('旧安全密码错误');
            }
        }
        $securitypwd = password_hash($securitypwd, PASSWORD_DEFAULT);
        $result = Db::name('user')->where('id', $user['id'])->update(['securitypwd' => $securitypwd]);
        if ($result === false) {
            $this->apiError('设置失败');
        } else {
            $this->apiSuccess('设置成功');
        }
    }

    /**
     * 设置昵称
     * Summary of nickname
     * @return void
     */
    public function nickname()
    {
        $param = get_params();
        $nickname = trim($param['nickname']);
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        if (empty($nickname)) {
            $this->apiError('参数错误');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        if ($nickname == $user['nickname']) {
            $this->apiError('昵称相同');
        }
        $count = Db::name('user')->where([['nickname', '=', $nickname], ['id', '<>', $user['id']]])->count();
        if (intval($count) > 0) {
            $this->apiError('此昵称已被使用');
        }
        $result = Db::name('user')->where('id', $user['id'])->update(['nickname' => $nickname, 'update_time' => time()]);
        if ($result === false) {
            $this->apiError('设置失败');
        } else {
            $this->apiSuccess('设置成功');
        }
    }

    /**
     * 设置手机号
     * Summary of mobile
     * @return void
     */
    public function mobile()
    {
        $param = get_params();
        $mobile = intval($param['mobile']);
        $code = intval($param['code']);
        $securitypwd = trim($param['securitypwd']);
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        if (empty($mobile) || empty($code) || empty($securitypwd)) {
            $this->apiError('参数错误');
        }
        $verif = Db::name('sms_log')->where(array('account' => $mobile, 'code' => $code))->find();
        if (empty($verif)) {
            $this->apiError('短信未发送');
        } else {
            if ($verif['expire_time'] < time()) {
                $this->apiError('短信已超时');
            }
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        if ($mobile == $user['mobile']) {
            $this->apiError('新旧手机号相同');
        }
        if (empty($user['securitypwd'])) {
            $this->apiError('请先设置安全密码');
        }
        if (!password_verify($securitypwd, $user['securitypwd'])) {
            $this->apiError('安全密码错误');
        }
        $count = Db::name('user')->where([['mobile', '=', $mobile], ['id', '<>', $user['id']]])->count();
        if (intval($count) > 0) {
            $this->apiError('此手机号已被使用');
        }
        $uid = $user['id'];
        $conf = get_system_config('reward');
        $task = Db::name('task')->where(['user_id' => $uid, 'taskid' => $conf['mobile_id'], 'status' => 0])->find();
        if (intval($conf['mobile']) > 0 && $task && intval($user['mobile']) <= 0) {
            Db::startTrans();
            try {
                // 执行数据库操作
                Db::name('user')->where('id', $uid)->inc('coin', intval($task['reward']))->update();
                add_coin_log($uid, intval($task['reward']), 1, '绑定手机号奖励');
                Db::name('task')->where('id', $task['id'])->update(['status' => 1, 'update_time' => time()]);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
        }
        $result = Db::name('user')->where('id', $user['id'])->update(['mobile' => $mobile, 'update_time' => time()]);
        if ($result === false) {
            $this->apiError('设置失败');
        } else {
            $this->apiSuccess('设置成功');
        }
    }

    /**
     * 绑定邀请码
     * Summary of bindinvitecode
     * @return void
     */
    public function bindinvitecode()
    {
        $param = get_params();
        $code = trim($param['code']);
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        if (empty($code)) {
            $this->apiError('参数错误');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        if (intval($user['inviter'] > 0)) {
            $this->apiError('已经有邀请人了！');
        }
        $member = Db::name('user')->where(['qrcode_invite' => $code])->find();
        if (empty($member)) {
            $this->apiError('邀请用户不存在');
        }
        $result = Db::name('user')->where('id', $user['id'])->update(['inviter' => $member['id'], 'update_time' => time()]);
        if ($result === false) {
            $this->apiError('邀请绑定失败');
        } else {
            $this->apiSuccess('邀请绑定成功');
        }
    }

    /**
     * 生成邀请海报
     * Summary of invite
     * @return void
     */
    public function invite()
    {
        $param = get_params();
        $path = trim($param['path']);
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        if (empty($path)) {
            $this->apiError('参数错误');
        }
        $conf = get_system_config('invite');
        $bglist = [];
        if (!empty($conf['bglist'])) {
            $bglist = explode(',', $conf['bglist']);
        }
        if (count($bglist) <= 0) {
            $this->apiError('未设置皮肤');
        }
        if (!in_array($path, $bglist)) {
            $this->apiError('当前皮肤不存在');
        }
        $bgPath = CMS_ROOT . "public" . $path;
        if (!is_file($bgPath)) {
            $this->apiError('当前皮肤文件不存在');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        if (empty($user['qrcode_invite'])) {
            $this->apiError('邀请码不存在');
        }
        $title = $conf['invite_content'];
        $replace = array(get_system_config('web', 'title'), $user['nickname']);
        $search = array('{sitename}', "{nickname}");
        $title = str_replace($search, $replace, $title);
        try {
            //保存目录
            $savePath = get_config('filesystem.disks.public.root') . '/invite/' . $user['id'] . '/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
            $filename = set_password($user['id'], $user['salt']);
            $filePath = $savePath . $filename . '.jpg';
            $posterPath = get_config('filesystem.disks.public.url') . '/invite/' . $user['id'] . '/' . $filename . '.jpg';
            $url = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('inviteurl', ['name' => $user['qrcode_invite']])->domain(true));
            $qrFile = $savePath . 'qrcode.png';
            $qrwidth = 200;
            if (!is_file($qrFile)) {
                $logoPath = CMS_ROOT . 'public/static/home/images/logo-invite.png';
                if (!is_file($logoPath)) {
                    $this->apiError('LOGO文件不存在');
                }
                $result = Builder::create()
                    ->writer(new PngWriter())
                    ->writerOptions([])
                    ->data($url)
                    ->encoding(new Encoding('UTF-8'))
                    ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                    ->size($qrwidth)
                    ->margin(0)
                    ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
                    ->logoPath($logoPath)
                    ->logoResizeToWidth(50)
                    ->logoResizeToHeight(50)
                    ->labelText('长按识别二维码')
                    ->labelFont(new NotoSans(15))
                    ->labelAlignment(new LabelAlignmentCenter())
                    ->validateResult(false)
                    ->build();
                $result->saveToFile($qrFile);
            }
            if (!is_file($qrFile)) {
                $this->apiError('二维码生成失败');
            }
            //加载图片
            $image = Image::open($bgPath);
            $width = $image->width();
            $height = $image->height();
            $textWidth = $width - intval($width * 0.4); //文本宽度
            $textfontPath = CMS_ROOT . 'public/static/home/font/hanchengwangtianxigufengti.ttf'; // 字体文件路径
            $image = imagecreatefromjpeg($bgPath);
            if ($conf['textColor'] && strpos(trim($conf['textColor']), 'rgb') !== false) {
                $color = str_replace(['rgb(', ')'], ['', ''], $conf['textColor']);
                list($r, $g, $b) = explode(',', trim($color));
                $r = intval(trim($r));
                $g = intval(trim($g));
                $b = intval(trim($b));
            } else {
                $r = 254;
                $g = 247;
                $b = 210;
            }
            $textColor = imagecolorallocate($image, $r, $g, $b);
            $fontsize = 25; //文字大小
            $content = self::autowrap($fontsize, 0, $textfontPath, $title, $textWidth); // 自动换行处理
            $x = intval($width * 0.2);
            $y = intval($height * 0.55);
            imagettftext($image, $fontsize, 0, $x, $y, $textColor, $textfontPath, $content);
            //计算二维码的位置
            $qrCodeSize = getimagesize($qrFile);
            $qrCodeWidth = intval($qrCodeSize[0]);
            $qrCodeHeight =  intval($qrCodeSize[1]);
            $x = intval(($width - $qrCodeWidth) / 2);
            $y = intval(($height - $qrCodeHeight - $height * 0.12));
            $qrCode = imagecreatefrompng($qrFile);
            //将二维码合并到图片上
            imagecopy($image, $qrCode, $x, $y, 0, 0, $qrCodeWidth, $qrCodeHeight);
            imagejpeg($image, $filePath, 100);
            // 释放图片资源
            imagedestroy($image);
            if (is_file($filePath)) {
                return json(['code' => 0, 'msg' => '生成成功', 'data' => ['path' => $posterPath]]);
            } else {
                return json(['code' => 1, 'msg' => '生成失败']);
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 计算文本并换行
     */
    private function autowrap($fontsize, $angle, $fontface, $string, $width)
    {
        $content = "";
        for ($i = 0; $i < mb_strlen($string); $i++) {
            $letter[] = mb_substr($string, $i, 1);
        }
        foreach ($letter as $l) {
            $str = $content . " " . $l;
            $box = imagettfbbox($fontsize, $angle, $fontface, $str);
            if (($box[2] > $width) && ($content !== "")) {
                $content .= "\n";
            }
            $content .= $l;
        }
        return $content;
    }

    /**
     * 获取书架
     * Summary of bookshelf
     * @return void
     */
    public function bookshelf()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $uid = JWT_UID;
        $user = Db::name('user')->where(['id' => $uid])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        $where = ['a.user_id' => $uid];
        $param['order'] = 'a.create_time desc';
        if (!isset($param['limit']) || intval($param['limit']) <= 0) {
            $param['limit'] = 1000;
        }
        $list = (new Favorites())->getFavoritesList($where, $param);
        $result = $list->toArray();
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['authorurl'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('author_detail', ['id' => $v['authorid']]));
                $book = Db::name('book')->where(['id' => $v['pid']])->find();
                if (!empty($book)) {
                    $result['data'][$k]['url'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('book_detail', ['id' => $book['filename'] ? $book['filename'] : $book['id']]));
                } else {
                    $result['data'][$k]['url'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('book_detail', ['id' => $v['pid']]));
                }
                $total = Db::name('chapter')->where(['bookid' => $v['pid'], 'status' => 1, 'verify' => ['in', '0,1']])->count();
                $reads = Db::name('readhistory')->where(['user_id' => $uid, 'book_id' => $v['pid']])->count();
                if ($total == 0 || $reads < 0) {
                    $result['data'][$k]['speed'] = 0;
                } else {
                    $result['data'][$k]['speed'] = round(($reads / $total) * 100, 2);
                }
            }
        } else {
            $result = [
                'data' => [],
                'total' => 0,
                'current_page' => 1,
                'last_page' => 0,
                'per_page' => 0
            ];
        }
        $starttime = strtotime("today midnight");
        $result['todayreadnum'] = Db::name('readhistory')->where(['user_id' => $uid, ['create_time', '>=', $starttime]])->count();
        $this->apiSuccess('请求成功', $result);
    }

    /**
     * 删除书架|支持批量
     * Summary of delbookshelf
     * @return void
     */
    public function delbookshelf()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $uid = JWT_UID;
        $user = Db::name('user')->where(['id' => $uid])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        $bid = trim($param['bid']);
        if (strpos($bid, ',') !== false) {
            $ids = implode(',', explode(',', $bid));
            Db::name('favorites')->where(['user_id' => JWT_UID, ['pid', 'in', $ids]])->delete();
        } else {
            Db::name('favorites')->where(['user_id' => JWT_UID, 'pid' => intval($bid)])->delete();
        }
        $this->apiSuccess('删除成功', []);
    }

    public function readlog()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $uid = JWT_UID;
        $user = Db::name('user')->where(['id' => $uid])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        $where = ['user_id' => $uid];
        //最多可以载加多少页
        if (isset($param['page']) && intval($param['page']) > 10) {
            $param['page'] = 10;
        }
        if (!isset($param['order']) || empty($param['order'])) {
            $param['order'] = 'create_time DESC';
        }
        $list = (new Readhistory())->getReadhistoryBook($where, $param);
        $list = $list->toArray();
        $result = [];
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $book = Db::name('book')->where(['id' => $v['book_id']])->find();
                if (empty($book)) {
                    continue;
                }
                $authorurl = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('author_detail', ['id' => $book['authorid']]));
                $bookurl = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('book_detail', ['id' => $book['filename'] ? $book['filename'] : $book['id']]));
                $newread = Db::name('readhistory')->field('IF(update_time = 0, create_time, update_time) AS order_time,id,update_time,create_time,title,chapter_id,book_id')->where($where)->order('order_time desc')->find();
                $chapterurl = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('chapter_detail', ['id' => $newread['chapter_id']]));
                $total = Db::name('chapter')->where(['bookid' => $v['book_id'], 'status' => 1, 'verify' => ['in', '0,1']])->count();
                $reads = Db::name('readhistory')->where(['user_id' => $uid, 'book_id' => $v['book_id']])->count();
                if ($total == 0 || $reads < 0) {
                    $speed = 0;
                } else {
                    $speed = round(($reads / $total) * 100, 2);
                }
                $result[$k] = $v;
                $result[$k]['authorurl'] = $authorurl;
                $result[$k]['bookurl'] = $bookurl;
                $result[$k]['chapterurl'] = $chapterurl;
                $result[$k]['speed'] = $speed;
                $result[$k]['cover'] = get_file($book['cover']);
                $result[$k]['booktitle'] = $book['title'];
                $result[$k]['author'] = $book['author'];
                $result[$k]['authorid'] = $book['authorid'];
                $result[$k]['isfav'] = Db::name('favorites')->where(['user_id' => $uid, 'pid' => $v['book_id']])->count();
            }
        }
        $res = [
            'data' => $result,
            'total' => $list['total'],
            'current_page' => $list['current_page'],
            'last_page' => $list['last_page'],
            'per_page' => $list['per_page']
        ];
        $this->apiSuccess('请求成功', $res);
    }

    /**
     * 签到
     * Summary of signin
     * @return void
     */
    public function signin()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $conf = get_system_config('reward');
        if (empty($conf) || intval($conf['open']) != 1) {
            $this->apiError('未开启该功能');
        }
        $uid = JWT_UID;
        $today = date('Y-m-d'); // 当天日期
        // 检查今天是否已签到
        $isSigned = Db::name('sign_log')->where('user_id', $uid)->where('sign_date', $today)->find();
        if ($isSigned) {
            $this->apiError('已签过到了');
        }
        // 计算连续签到天数
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $lastSign = Db::name('sign_log')->where('user_id', $uid)->where('sign_date', $yesterday)->find();
        $reward = $consecutiveDays = 0;
        if (empty($lastSign)) {
            $consecutiveDays = 1;
            $reward = intval($conf['day_1_reward']);
        } else {
            $consecutiveDays = intval($lastSign['consecutive_days']) + 1;
            if ($consecutiveDays > 7) {
                $reward = $conf['day_8_reward'];
            } else {
                $key = 'day_' . $consecutiveDays . '_reward';
                $reward = isset($conf[$key]) ? intval($conf[$key]) : 0;
            }
        }
        // 添加签到信息
        $data = [
            'user_id' => $uid,
            'sign_date' => $today,
            'consecutive_days' => $consecutiveDays,
            'ip' => app('request')->ip(),
            'create_time' => time()
        ];
        if ($reward > 0) {
            $vip = Db::name('vip_log')->where(['status' => 1, 'user_id' => $uid, ['expire_time', '>', time()]])->find();
            if (!empty($vip)) {
                if (floatval($conf['vip_reward']) > 1) {
                    $reward = floor($reward * floatval($conf['vip_reward']));
                }
            }
            // 开启事务
            Db::startTrans();
            try {
                // 执行数据库操作
                Db::name('user')->where('id', $uid)->inc('coin', $reward)->update();
                add_coin_log($uid, $reward, 1, '签到奖励');
                Db::name('sign_log')->strict(false)->field(true)->insertGetId($data);
                // 提交事务
                Db::commit();
                return json(['code' => 0, 'msg' => '签到成功']);
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code' => 1, 'msg' => $e->getMessage()]);
            }
        } else {
            $result = Db::name('sign_log')->strict(false)->field(true)->insertGetId($data);
            if ($result != false) {
                $this->apiSuccess('签到成功');
            } else {
                $this->apiError('签到失败');
            }
        }
    }

    /**
     * 点赞
     * Summary of like
     * @return void
     */
    public function like()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $chapter_id = isset($param['chapter_id']) ? intval($param['chapter_id']) : 0;
        $book_id = isset($param['book_id']) ? intval($param['book_id']) : 0;
        if (empty($chapter_id) || empty($chapter_id)) {
            $this->apiError('参数为空');
        }
        $conf = get_system_config('reward');
        if (empty($conf)) {
            $this->apiError('未开启该功能');
        }
        $book = Db::name('book')->field('id')->where('id', $book_id)->find();
        if (empty($book)) {
            $this->apiError('作品不存在');
        }
        $chapter = Db::name('chapter')->field('id')->where('id', $chapter_id)->find();
        if (empty($chapter)) {
            $this->apiError('章节不存在');
        }
        $uid = JWT_UID;
        $today = date('Y-m-d'); // 当天日期
        // 检查今天是否已点赞
        $like = Db::name('like_log')->where(['user_id' => $uid, 'book_id' => $book_id, 'chapter_id' => $chapter_id, 'like_date' => $today])->count();
        if (intval($like) > 0) {
            $this->apiError('已点过赞了');
        }
        //添加信息
        $data = [
            'user_id' => $uid,
            'like_date' => $today,
            'book_id' => $book_id,
            'chapter_id' => $chapter_id,
            'ip' => app('request')->ip(),
            'create_time' => time()
        ];
        $res = Db::name('like_log')->strict(false)->field(true)->insertGetId($data);
        if ($res) {
            //发放奖励
            $conf = get_system_config('reward');
            $like_number = intval($conf['like_number']);
            $reward = intval($conf['like_reward']);
            $name = trim($conf['like_id']);
            if ($like_number > 0 && $reward > 0) {
                $likecount = Db::name('like_log')->where(['user_id' => $uid, 'like_date' => $today])->count();
                $already = Db::name('task')->where(['user_id' => $uid, 'taskid' => $name, 'task_date' => $today, 'status' => 0])->find();
                if (!empty($already) && intval($likecount) >= $like_number) {
                    $vip = Db::name('vip_log')->where(['status' => 1, 'user_id' => $uid, ['expire_time', '>', time()]])->find();
                    if (!empty($vip)) {
                        if (floatval($conf['vip_reward']) > 1) {
                            $reward = floor($reward * floatval($conf['vip_reward']));
                        }
                    }
                    // 开启事务
                    Db::startTrans();
                    try {
                        // 执行数据库操作
                        Db::name('user')->where('id', $uid)->inc('coin', $reward)->update();
                        add_coin_log($uid, $reward, 1, '每日点赞奖励奖励');
                        Db::name('task')->where('id', $already['id'])->update(['status' => 1, 'update_time' => time()]);
                        // 提交事务
                        Db::commit();
                        return json(['code' => 0, 'msg' => '点赞成功']);
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        return json(['code' => 1, 'msg' => $e->getMessage()]);
                    }
                } else {
                    $this->apiSuccess('点赞成功');
                }
            } else {
                $this->apiSuccess('点赞成功');
            }
        } else {
            $this->apiError('点赞失败');
        }
    }

    /**
     * 银行卡列表
     * Summary of bankcard
     * @return void
     */
    public function bankcard()
    {
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $param = get_params();
        $where = ['user_id' => JWT_UID, 'status' => 1];
        $auth_status = isset($param['auth_status']) ? intval($param['auth_status']) : 0;
        if ($auth_status == 1) {
            $where['auth_status'] = 1;
        }
        $list = Db::name('bank_card')->where($where)->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['mobile'] = $v['mobile'] ? substr_replace($v['mobile'], '****', 3, 4) : '';
            $list[$k]['card_no'] = $v['card_no'] ? substr_replace($v['card_no'], '****', 3, 4) : '';
        }
        $this->apiSuccess('请求成功', $list);
    }

    private static function isValidBankCardNumber($cardNumber)
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber); // 移除非数字字符
        $sum = 0;
        $shouldDouble = false; // 标记是否应该翻倍
        // 从最后一位数字开始向前工作
        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $digit = $cardNumber[$i];
            // 如果标记为true，则将数字翻倍，并作相应处理
            if ($shouldDouble) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9; // 等同于相加个位数和十位数
                }
            }
            // 将数字加到总和中
            $sum += $digit;
            // 每一步都改变翻倍标记的值
            $shouldDouble = !$shouldDouble;
        }
        // 如果总和可以被10整除，认为是有效的银行卡号
        return $sum % 10 === 0;
    }

    private static function validateMobile($phone)
    {
        $pattern = "/^1[3-9]\d{9}$/";
        return preg_match($pattern, $phone) ? true : false;
    }

    /**
     * 添加银行卡
     * Summary of cardadd
     * @return void
     */
    public function cardadd()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $card_no = isset($param['card_no']) ? intval($param['card_no']) : 0;
        $mobile = isset($param['mobile']) ? intval($param['mobile']) : 0;
        $bank_name = isset($param['bank_name']) ? trim($param['bank_name']) : '';
        $full_name = isset($param['full_name']) ? trim($param['full_name']) : '';
        $card_image = isset($param['card_image']) ? trim($param['card_image']) : '';
        $bank_address = isset($param['bank_address']) ? trim($param['bank_address']) : '';
        if (!self::validateMobile((string)$mobile)) {
            $this->apiError('手机号有误');
        }
        if (!self::isValidBankCardNumber((string)$card_no)) {
            $this->apiError('银行卡号有误');
        }
        if (empty($bank_name) || empty($full_name) || empty($card_image) || empty($bank_address)) {
            $this->apiError('必填信息未填写');
        }
        $card = Db::name('bank_card')->where(['card_no' => $card_no])->find();
        if (!empty($card)) {
            $this->apiError('此卡已存在');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        if (intval($user['realname_status']) != 1) {
            $this->apiError('请先完成实名认证');
        }
        if ($full_name != $user['name']) {
            $this->apiError('卡主与实名名称不一致');
        }
        $result = Db::name('bank_card')->strict(false)->field(true)->insertGetId([
            'user_id' => JWT_UID,
            'card_no' => $card_no,
            'mobile' => $mobile,
            'bank_name' => $bank_name,
            'full_name' => $full_name,
            'card_image' => $card_image,
            'bank_address' => $bank_address,
            'status' => 1,
            'auth_status' => 0,
            'create_time' => time()
        ]);
        if ($result != false) {
            $conf = get_system_config('reward');
            $task = Db::name('task')->where(['user_id' => JWT_UID, 'taskid' => $conf['account_id'], 'status' => 0])->find();
            if (!empty($task)) {
                $reward = intval($task['reward']);
                // 开启事务
                Db::startTrans();
                try {
                    // 执行数据库操作
                    Db::name('user')->where('id', JWT_UID)->inc('coin', $reward)->update();
                    add_coin_log(JWT_UID, $reward, 1, '绑定收款账号奖励');
                    Db::name('task')->where('id', $task['id'])->update(['status' => 1, 'update_time' => time()]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
            $this->apiSuccess('添加成功');
        } else {
            $this->apiError('添加失败');
        }
    }

    /**
     * 删除银行卡
     * Summary of delbankcard
     * @return void
     */
    public function delbankcard()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $id = isset($param['id']) ? intval($param['id']) : 0;
        if (empty($id)) {
            $this->apiError('参数有误');
        }
        $card = Db::name('bank_card')->where(['id' => $id])->find();
        if (empty($card)) {
            $this->apiError('数据不存在');
        }
        if ($card['user_id'] != JWT_UID) {
            $this->apiError('数据不存在');
        }
        Db::name('bank_card')->where(['id' => $id])->delete();
        $this->apiSuccess('删除成功', []);
    }

    /**
     * 实名认证
     * Summary of realnameauth
     * @return void
     */
    public function realnameauth()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $realname = isset($param['realname']) ? trim($param['realname']) : '';
        $id_card_photo = isset($param['id_card_photo']) ? trim($param['id_card_photo']) : '';
        $id_card = isset($param['id_card']) ? trim($param['id_card']) : '';
        if (empty($realname) || empty($id_card_photo) || empty($id_card)) {
            $this->apiError('参数有误');
        }
        if (!isIdcard($id_card)) {
            $this->apiError('身份证号有误');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        if ($user['realname_status'] == 1) {
            $this->apiError('已实名认证过了');
        }
        if ($user['realname_status'] == 2) {
            $this->apiError('实名认证审核中');
        }
        $card = Db::name('user')->where(['id_card' => $id_card])->find();
        if (!empty($card)) {
            if ($card['id'] != $user['id']) {
                $this->apiError('此身份证已被使用');
            }
        }
        $res = Db::name('user')->where(['id' => $user['id']])->strict(false)->field(true)->update(['name' => $realname, 'id_card_photo' => $id_card_photo, 'id_card' => $id_card, 'realname_status' => 2, 'update_time' => time()]);
        if ($res) {
            $this->apiSuccess('提交成功', []);
        } else {
            $this->apiError('提交失败');
        }
    }

    /**
     * 我的邀请
     * Summary of myinvite
     * @return void
     */
    public function myinvite()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $where = ['inviter' => JWT_UID, 'status' => 1];
        $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
        $list = (new UserModel())->field('id,nickname,headimgurl,register_time')->where($where)
            ->order('register_time desc')
            ->paginate($rows, false, ['query' => $param])
            ->each(function ($item, $key) {
                $item->register_time = date('Y-m-d H:i:s', $item->register_time);
            });
        $this->apiSuccess('成功', $list);
    }

    /**
     * 注册作者
     * Summary of regauthor
     * @return void
     */
    public function regauthor()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $nickname = isset($param['nickname']) ? trim($param['nickname']) : '';
        $mobile = isset($param['mobile']) ? trim($param['mobile']) : '';
        $password = isset($param['password']) ? trim($param['password']) : '';
        if (empty($nickname) || empty($mobile) || empty($password)) {
            $this->apiError('参数错误');
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            $this->apiError('手机号不正确');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        if (intval($user['author_id'] > 0)) {
            $this->apiError('已经是作者了！');
        }
        $author = Db::name('author')->where(['nickname' => $nickname])->find();
        if (!empty($author)) {
            $this->apiError('笔名已被使用');
        }
        $author = Db::name('author')->where(['mobile' => $mobile])->find();
        if (!empty($author)) {
            $this->apiError('手机已被使用');
        }
        $time = (string) time();
        $salt = substr(MD5($time), 0, 6);
        $data = array(
            'mobile' => $mobile,
            'salt' => $salt,
            'password' => sha1(MD5($password) . $salt),
            'ip' => request()->ip(),
            'create_time' => time(),
            'status' => 1,
            'nickname' => $nickname,
        );
        $uid = Db::name('author')->strict(false)->field(true)->insertGetId($data);
        if ($uid !== false) {
            $res = Db::name('user')->where(['id' => $user['id']])->strict(false)->field(true)->update(['author_id' => $uid, 'update_time' => time()]);
            $conf = get_system_config('reward');
            $task = Db::name('task')->where(['user_id' => JWT_UID, 'taskid' => $conf['author_id'], 'status' => 0])->find();
            if (!empty($task)) {
                $reward = intval($task['reward']);
                // 开启事务
                Db::startTrans();
                try {
                    // 执行数据库操作
                    Db::name('user')->where('id', JWT_UID)->inc('coin', $reward)->update();
                    add_coin_log(JWT_UID, $reward, 1, '成为作者奖励');
                    Db::name('task')->where('id', $task['id'])->update(['status' => 1, 'update_time' => time()]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
            $this->apiSuccess('注册成功');
        } else {
            $this->apiError('注册失败');
        }
    }
}
