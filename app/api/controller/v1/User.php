<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;
use app\admin\model\Follow;
use app\admin\model\User as UserModel;
use think\Image;
use PHPQRCode\QRcode;

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
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($pid)) {
            $this->apiError('empty');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
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
                $this->apiSuccess('success', ['fid' => $fid]);
            } else {
                $this->apiError('fail', ['fid' => 0]);
            }
        } else {
            //取消收藏！
            if (Db::name('favorites')->where(['user_id' => JWT_UID, 'pid' => $pid])->delete()) {
                $this->apiSuccess('success', ['fid' => 0]);
            } else {
                $this->apiError('fail', ['fid' => 0]);
            }
        }
    }

    /**
     * 添加或取消关注
     * Summary of follow
     * @return void
     */
    public function follow()
    {
        $param = get_params();
        $from_id = isset($param['from_id']) ?  intval($param['from_id']) : 0; //ID
        $type = isset($param['type']) ? intval($param['type']) : 1; //1作者2用户
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($from_id)) {
            $this->apiError('empty');
        }
        if (JWT_UID == $from_id) {
            $this->apiError('user.refusefollow');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
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
                $this->apiSuccess('success', ['fid' => $fid]);
            } else {
                $this->apiError('fail');
            }
        } else {
            //取消关注！
            Db::name('follow')->where(['user_id' => JWT_UID, 'from_id' => $from_id])->delete();
            $this->apiSuccess('success', []);
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($avatar)) {
            $this->apiError('empty');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        $imageType = '';
        if (preg_match('/^data:image\/(\w+);base64,/', $avatar, $matches)) {
            $imageType = $matches[1]; // 获取图片类型，例如 'jpeg', 'png', 'gif' 等
        } else {
            $imageType = strtolower(pathinfo($avatar, PATHINFO_EXTENSION));
        }
        if (!in_array($imageType, ['jpg', 'png', 'jpeg', 'gif'])) {
            $this->apiError('common.notpicture');
        }
        // 日期前綴
        $img_name = md5('avatar' . JWT_UID . time());
        $path = date('Ymd', time()) . '/';
        $upload_path = app()->getRootPath() . 'public/storage/' . $path;
        if (!createDirectory($upload_path)) {
            $this->apiError('407');
        }
        $filename = $img_name . "." . $imageType;
        $localpath = get_config('filesystem.disks.public.url') . '/' . $path . $filename;
        $save_path = $upload_path . $filename;
        $base64Data = str_replace('data:image/' . $imageType . ';base64,', '', $avatar);
        $imageData = base64_decode($base64Data);
        if (false === @file_put_contents($save_path, $imageData)) {
            $this->apiError('common.nopermission');
        }
        $result = Db::name('user')->where('id', $user['id'])->update(['headimgurl' => $localpath, 'update_time' => time()]);
        if ($result === false) {
            $this->apiError('fail');
        } else {
            $this->apiSuccess('success');
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($securitypwd)) {
            $this->apiError('empty');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if ($user['securitypwd']) {
            if (empty($oldsecuritypwd)) {
                $this->apiError('user.oldpassempty');
            }
            if (!password_verify($oldsecuritypwd, $user['securitypwd'])) {
                $this->apiError('user.oldpasserr');
            }
        }
        $securitypwd = password_hash($securitypwd, PASSWORD_DEFAULT);
        $result = Db::name('user')->where('id', $user['id'])->update(['securitypwd' => $securitypwd]);
        if ($result === false) {
            $this->apiError('fail');
        } else {
            $this->apiSuccess('success');
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($nickname)) {
            $this->apiError('empty');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if ($nickname == $user['nickname']) {
            $this->apiError('repeat');
        }
        $count = Db::name('user')->where([['nickname', '=', $nickname], ['id', '<>', $user['id']]])->count();
        if (intval($count) > 0) {
            $this->apiError('common.alreadyused');
        }
        $result = Db::name('user')->where('id', $user['id'])->update(['nickname' => $nickname, 'update_time' => time()]);
        if ($result === false) {
            $this->apiError('fail');
        } else {
            $this->apiSuccess('success');
        }
    }

    /**
     * 设置性别
     * Summary of sex
     * @return void
     */
    public function sex()
    {
        $param = get_params();
        $sex = intval($param['sex']);
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($sex)) {
            $this->apiError('empty');
        }
        if (!in_array($sex, [1, 2])) {
            $this->apiError('empty');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if (intval($user['sex']) > 0) {
            $this->apiError('407');
        }
        $result = Db::name('user')->where('id', $user['id'])->update(['sex' => $sex, 'update_time' => time()]);
        if ($result === false) {
            $this->apiError('fail');
        } else {
            $this->apiSuccess('success');
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($mobile) || empty($code) || empty($securitypwd)) {
            $this->apiError('empty');
        }
        $verif = Db::name('sms_log')->where(array('account' => $mobile, 'code' => $code))->find();
        if (empty($verif)) {
            $this->apiError('login.smsnotsend');
        } else {
            if ($verif['expire_time'] < time()) {
                $this->apiError('login.smsexpire');
            }
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if ($mobile == $user['mobile']) {
            $this->apiError('repeat');
        }
        if (empty($user['securitypwd'])) {
            $this->apiError('user.setuppass');
        }
        if (!password_verify($securitypwd, $user['securitypwd'])) {
            $this->apiError('user.oldpasserr');
        }
        $count = Db::name('user')->where([['mobile', '=', $mobile], ['id', '<>', $user['id']]])->count();
        if (intval($count) > 0) {
            $this->apiError('common.alreadyused');
        }
        $uid = $user['id'];
        $conf = get_system_config('reward');
        $task = Db::name('task')->where(['user_id' => $uid, 'taskid' => $conf['mobile_id'], 'status' => 0])->find();
        if (intval($conf['mobile']) > 0 && $task && intval($user['mobile']) <= 0) {
            Db::startTrans();
            try {
                // 执行数据库操作
                Db::name('user')->where('id', $uid)->inc('coin', intval($task['reward']))->update();
                add_coin_log($uid, intval($task['reward']), 1, lang('reward.bindphone'));
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
            $this->apiError('fail');
        } else {
            $this->apiSuccess('success');
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($code)) {
            $this->apiError('empty');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if (intval($user['inviter'] > 0)) {
            $this->apiError('repeat');
        }
        $member = Db::name('user')->where(['qrcode_invite' => $code])->find();
        if (empty($member)) {
            $this->apiError('404');
        }
        $result = Db::name('user')->where('id', $user['id'])->update(['inviter' => $member['id'], 'update_time' => time()]);
        if ($result === false) {
            $this->apiError('fail');
        } else {
            $this->apiSuccess('success');
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
        $inviteurl = trim($param['inviteurl']);
        if (!defined('JWT_UID') || empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($path) || empty($inviteurl)) {
            $this->apiError('empty');
        }
        $conf = get_system_config('invite');
        $bglist = [];
        if (!empty($conf['bglist'])) {
            $bglist = explode(',', $conf['bglist']);
        }
        if (count($bglist) <= 0) {
            $this->apiError('empty');
        }
        if (!in_array($path, $bglist)) {
            $this->apiError('404');
        }
        $bgPath = CMS_ROOT . "public" . $path;
        if (!is_file($bgPath)) {
            $this->apiError('404');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if (empty($user['qrcode_invite'])) {
            $this->apiError('404');
        }
        $title = $conf['invite_content'];
        $replace = array(get_system_config('web', 'title'), $user['nickname']);
        $search = array('{sitename}', "{nickname}");
        $title = str_replace($search, $replace, $title);

        try {
            // 保存目录
            $savePath = get_config('filesystem.disks.public.root') . '/invite/' . $user['id'] . '/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
            $filename = set_password($user['id'], $user['salt']);
            $filePath = $savePath . $filename . '.jpg';
            $posterPath = get_config('filesystem.disks.public.url') . '/invite/' . $user['id'] . '/' . $filename . '.jpg';

            $qrFile = $savePath . 'poster_qrcode.png';

            if (!is_file($qrFile)) {
                $logoPath = CMS_ROOT . 'public/static/home/images/logo-invite.png';
                if (!is_file($logoPath)) {
                    $this->apiError('404');
                }

                // 生成基础二维码[1,6](@ref)
                QRcode::png($inviteurl, $qrFile, 'L', 6, 2);

                // 添加LOGO到二维码中心
                $QR = imagecreatefromstring(file_get_contents($qrFile));
                $logo = imagecreatefromstring(file_get_contents($logoPath));

                $QR_width = imagesx($QR);
                $QR_height = imagesy($QR);
                $logo_width = imagesx($logo);
                $logo_height = imagesy($logo);

                // 调整LOGO大小 - 确保所有计算结果为整数
                $logo_qr_width = intval($QR_width / 5);
                if ($logo_qr_width < 1) $logo_qr_width = 1;

                $scale = $logo_width / $logo_qr_width;
                $logo_qr_height = intval($logo_height / $scale);
                if ($logo_qr_height < 1) $logo_qr_height = 1;

                // 计算LOGO位置（居中）
                $from_width = intval(($QR_width - $logo_qr_width) / 2);
                $from_height = intval(($QR_height - $logo_qr_height) / 2);

                // 重新组合图片并调整大小 - 所有尺寸参数确保为整数
                imagecopyresampled($QR, $logo, $from_width, $from_height, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);

                // 添加文字 - 长按识别二维码
                $textColor = imagecolorallocate($QR, 0, 0, 0);
                $fontSize = 12;
                $text = lang('user.longpressqrcode');

                // 获取文字尺寸并确保为整数
                $textBbox = imagettfbbox($fontSize, 0, CMS_ROOT . 'public/static/home/font/hanchengwangtianxigufengti.ttf', $text);
                $textWidth = intval($textBbox[2] - $textBbox[0]);
                $textHeight = intval($textBbox[1] - $textBbox[7]);

                // 计算文字位置并确保为整数
                $textX = intval(($QR_width - $textWidth) / 2);
                $textY = intval($QR_height - 10);

                // 确保字体大小也是整数
                $fontSize = intval($fontSize);

                imagettftext(
                    $QR,
                    $fontSize,  // 字体大小（整数）
                    0,          // 角度（整数）
                    $textX,     // X坐标（整数）
                    $textY,     // Y坐标（整数）
                    $textColor,
                    CMS_ROOT . 'public/static/home/font/hanchengwangtianxigufengti.ttf',
                    $text
                );

                // 保存最终二维码
                imagepng($QR, $qrFile);

                // 释放内存
                imagedestroy($QR);
                imagedestroy($logo);
            }

            if (!is_file($qrFile)) {
                return json(['code' => 1, 'msg' => lang('fail')])->header(['Content-Type' => 'application/json']);
            }

            // 加载背景图片
            $image = Image::open($bgPath);
            $width = $image->width();
            $height = $image->height();

            $textWidth = $width - intval($width * 0.4);
            $textfontPath = CMS_ROOT . 'public/static/home/font/hanchengwangtianxigufengti.ttf';

            $image = imagecreatefromjpeg($bgPath);

            // 设置文字颜色
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

            $fontsize = 25;
            $content = self::autowrap($fontsize, 0, $textfontPath, $title, $textWidth);

            $x = intval($width * 0.2);
            $y = intval($height * 0.55);
            imagettftext($image, $fontsize, 0, $x, $y, $textColor, $textfontPath, $content);

            // 计算二维码位置
            $qrCodeSize = getimagesize($qrFile);
            $qrCodeWidth = intval($qrCodeSize[0]);
            $qrCodeHeight = intval($qrCodeSize[1]);

            $x = intval(($width - $qrCodeWidth) / 2);
            $y = intval(($height - $qrCodeHeight - $height * 0.12));

            $qrCode = imagecreatefrompng($qrFile);
            imagecopy($image, $qrCode, $x, $y, 0, 0, $qrCodeWidth, $qrCodeHeight);

            imagejpeg($image, $filePath, 100);
            imagedestroy($image);

            if (is_file($filePath)) {
                return json(['code' => 0, 'msg' => lang('success'), 'data' => ['path' => $posterPath]])->header(['Content-Type' => 'application/json']);
            } else {
                return json(['code' => 1, 'msg' => lang('fail')])->header(['Content-Type' => 'application/json']);
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()])->header(['Content-Type' => 'application/json']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()])->header(['Content-Type' => 'application/json']);
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $uid = JWT_UID;
        $user = Db::name('user')->where(['id' => $uid])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if (isset($param['bookshelf']) && $param['bookshelf']) {
            $list = json_decode($param['bookshelf'], true);
            foreach ($list as $key => $v) {
                $fav = Db::name('favorites')->where(['user_id' => $uid, 'pid' => $v['bookId']])->find();
                if (empty($fav)) {
                    $data = array(
                        "user_id" => $uid,
                        "pid" => $v['bookId'],
                        "create_time" => time(),
                    );
                    Db::name('favorites')->strict(false)->field(true)->insertGetId($data);
                }
            }
        }
        if (!isset($param['limit']) || intval($param['limit']) <= 0) {
            $param['limit'] = get_config('app.page_size');
        }
        $order = empty($param['order']) ? 'create_time desc' : $param['order'];
        $list =  Db::name('favorites')->where(['user_id' => $uid])->order($order)->paginate($param['limit'], false);
        $result = $list->toArray();
        $modelName = \think\facade\App::initialize()->http->getName();
        foreach ($result['data'] as $k => $v) {
            $book = Db::name('book')->field('id as bookId,title,cover,author,authorid,chapters,isfinish,genre,subgenre,filename')->where(['id' => $v['pid']])->find();
            if (!empty($book)) {
                $result['data'][$k]['authorurl'] = str_replace($modelName, 'home', (string) Route::buildUrl('author_detail', ['id' => $book['authorid']]));
                $result['data'][$k]['cover'] = get_file($book['cover']);
                $result['data'][$k]['bookId'] = $book['bookId'];
                $result['data'][$k]['title'] = $book['title'];
                $result['data'][$k]['author'] = $book['author'];
                $result['data'][$k]['authorid'] = $book['authorid'];
                $result['data'][$k]['chapters'] = $book['chapters'];
                $result['data'][$k]['isfinish'] = $book['isfinish'];
                $result['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $result['data'][$k]['bigcatetitle'] = Db::name('category')->where(['id' => $book['genre']])->value('name');
                $result['data'][$k]['sellcatetitle'] = Db::name('category')->where(['id' => $book['subgenre']])->value('name');
                $result['data'][$k]['url'] = str_replace($modelName, 'home', (string) Route::buildUrl('book_detail', ['id' => $book['filename'] ? $book['filename'] : $book['bookId']]));
            } else {
                unset($result['data'][$k]);
                continue;
            }
            $readhistory = Db::name('readhistory')->field('IF(update_time = 0, create_time, update_time) AS order_time,create_time,update_time,chapter_id')->where(['user_id' => $uid, 'book_id' => $v['pid']])->order('order_time desc')->find();
            if (!empty($readhistory)) {
                $result['data'][$k]['chapter_id'] = $readhistory['chapter_id'];
            } else {
                $result['data'][$k]['chapter_id'] = 0;
            }
            $total = Db::name('chapter')->where(['bookid' => $v['pid'], 'status' => 1, ['verify', 'in', '0,1']])->count();
            $reads = Db::name('readhistory')->where(['user_id' => $uid, 'book_id' => $v['pid']])->count();
            if ($total == 0 || $reads < 0) {
                $result['data'][$k]['speed'] = 0;
            } else {
                $result['data'][$k]['speed'] = round(($reads / $total) * 100, 2);
            }
        }
        $starttime = strtotime("today midnight");
        $result['todayreadnum'] = Db::name('readhistory')->where(['user_id' => $uid, ['create_time', '>=', $starttime]])->count();
        $this->apiSuccess('success', $result);
    }

    /**
     * 关注列表
     * Summary of followlist
     * @return void
     */
    public function followlist()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        $uid = JWT_UID;
        $user = Db::name('user')->where(['id' => $uid])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        $where = ['user_id' => $uid];
        $param['order'] = 'create_time desc';
        if (!isset($param['limit']) || intval($param['limit']) <= 0) {
            $param['limit'] = 1000;
        }
        $list = (new Follow())->getFollowList($where, $param);
        $result = $list->toArray();
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                if ($v['type'] == 1) {
                    $author = Db::name('author')->where(['id' => $v['from_id']])->find();
                    if (empty($author)) {
                        unset($result['data'][$k]);
                        continue;
                    }
                    $result['data'][$k]['link'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('author_detail', ['id' => $author['id']]));
                    $result['data'][$k]['headimg'] = get_file($author['headimg'], 1);
                    $result['data'][$k]['nickname'] = $author['nickname'];
                    $result['data'][$k]['book_count'] = Db::name('book')->where(['status' => 1, 'authorid' => $v['from_id']])->count();;
                } else {
                    $user = Db::name('user')->where(['id' => $v['from_id']])->find();
                    if (empty($user)) {
                        unset($result['data'][$k]);
                        continue;
                    }
                    $result['data'][$k]['link'] = 'javascript:;';
                    $result['data'][$k]['headimg'] = get_file($user['headimgurl'], 1);
                    $result['data'][$k]['nickname'] = $user['nickname'];
                    $result['data'][$k]['book_count'] = 0;
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
        $this->apiSuccess('success', $result);
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $uid = JWT_UID;
        $user = Db::name('user')->where(['id' => $uid])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        $bid = trim($param['bid']);
        if (strpos($bid, ',') !== false) {
            Db::name('favorites')->where(['user_id' => JWT_UID, ['pid', 'in', $bid]])->delete();
        } else {
            Db::name('favorites')->where(['user_id' => JWT_UID, 'pid' => intval($bid)])->delete();
        }
        $this->apiSuccess('success', []);
    }

    public function readlog()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        $uid = JWT_UID;
        $user = Db::name('user')->where(['id' => $uid])->find();
        if (empty($user)) {
            $this->apiError('404');
        }

        // 参数处理
        $page = isset($param['page']) ? max(1, min(intval($param['page']), 10)) : 1;
        $rows = isset($param['limit']) ? intval($param['limit']) : get_config('app.page_size');
        $offset = ($page - 1) * $rows;

        // 1. 获取有效阅读记录总数（按book_id去重）
        $total = Db::name('readhistory')
            ->where('user_id', $uid)
            ->group('book_id')
            ->count();

        // 2. 获取分页后的最新阅读记录
        $subQuery = Db::name('readhistory')
            ->field('book_id, MAX(GREATEST(IFNULL(update_time,0), create_time)) as max_time')
            ->where('user_id', $uid)
            ->group('book_id')
            ->buildSql();

        $readHistoryList = Db::name('readhistory')
            ->alias('rh') // 正确使用别名
            ->field('rh.*')
            ->join(
                [$subQuery => 'latest'],
                'rh.book_id = latest.book_id AND 
            GREATEST(IFNULL(rh.update_time,0), rh.create_time) = latest.max_time'
            )
            ->order('latest.max_time DESC')
            ->limit($offset, $rows)
            ->select()
            ->toArray();

        if (empty($readHistoryList)) {
            $this->apiSuccess('success', ['data' => [], 'total' => $total]);
        }

        // 3. 批量获取关联数据
        $bookIds = array_column($readHistoryList, 'book_id');
        $chapterIds = array_column($readHistoryList, 'chapter_id');

        // 获取书籍信息
        $books = Db::name('book')
            ->field('id, id as bookid, author, title as booktitle, authorid, filename, cover, chapters')
            ->whereIn('id', $bookIds)
            ->select()
            ->toArray();
        $bookMap = array_column($books, null, 'id');

        // 验证章节有效性（批量处理）
        $existChapters = Db::name('chapter')
            ->field('id')
            ->whereIn('id', $chapterIds)
            ->select()
            ->toArray();
        $existChapterIds = array_column($existChapters, 'id');

        // 过滤无效章节记录
        $validRecords = [];
        foreach ($readHistoryList as $item) {
            if (!in_array($item['chapter_id'], $existChapterIds)) {
                Db::name('readhistory')->where('chapter_id', $item['chapter_id'])->delete();
                continue;
            }
            $validRecords[] = $item;
        }

        // 4. 获取附加数据
        // 阅读次数统计
        $readCounts = Db::name('readhistory')
            ->field('book_id, COUNT(DISTINCT chapter_id) as read_count') // 修改别名
            ->where('user_id', $uid)
            ->whereIn('book_id', $bookIds)
            ->group('book_id')
            ->select()
            ->toArray();
        $readCountMap = array_column($readCounts, 'read_count', 'book_id'); // 同步修改映射
        // 收藏状态
        $favBooks = Db::name('favorites')
            ->field('pid')
            ->where('user_id', $uid)
            ->whereIn('pid', $bookIds)
            ->select()
            ->toArray();
        $favBookIds = array_column($favBooks, 'pid');

        // 5. 构建响应数据
        $modelname = \think\facade\App::initialize()->http->getName();
        $result = [];
        foreach ($validRecords as $item) {
            if (!isset($bookMap[$item['book_id']])) continue;

            $book = $bookMap[$item['book_id']];
            $merged = array_merge($book, $item);

            // 计算总章节数
            if ($merged['chapters'] <= 0) {
                $merged['chapters'] = Db::name('chapter')
                    ->where(['bookid' => $merged['id'], 'status' => 1, ['verify', 'in', '0,1']])
                    ->count();
            }

            // 计算阅读进度
            $reads = $readCountMap[$merged['bookid']] ?? 0;
            $merged['speed'] = ($merged['chapters'] > 0)
                ? round(($reads / $merged['chapters']) * 100, 2)
                : 0;

            // 生成URL
            $merged['authorurl'] = str_replace(
                $modelname,
                'home',
                (string)url('author_detail', ['id' => $merged['authorid']])
            );
            $merged['bookurl'] = str_replace(
                $modelname,
                'home',
                (string)url('book_detail', ['id' => $merged['filename'] ?: $merged['id']])
            );
            $merged['chapterurl'] = str_replace(
                $modelname,
                'home',
                (string)furl('chapter_detail', ['id' => $merged['chapter_id'], 'bookid' => $merged['filename'] ? $merged['filename'] : $merged['id']])
            );

            // 格式化时间戳
            $merged['create_time'] = date('Y-m-d H:i:s', $merged['create_time']);
            $merged['isfav'] = in_array($merged['id'], $favBookIds) ? 1 : 0;
            $merged['cover'] = get_file($merged['cover']);
            $result[] = $merged;
        }

        // 6. 返回结果
        $res = [
            'data' => $result,
            'total' => $total
        ];
        $this->apiSuccess('success', $res);
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $conf = get_system_config('reward');
        if (empty($conf) || intval($conf['open']) !== 1) {
            $this->apiError('407');
        }
        $uid = JWT_UID;
        $today = date('Y-m-d'); // 当天日期
        // 检查今天是否已签到
        $isSigned = Db::name('sign_log')->where('user_id', $uid)->where('sign_date', $today)->find();
        if ($isSigned) {
            $this->apiError('repeat');
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
                $reward = isset($conf['day_8_reward']) ? intval($conf['day_8_reward']) : 0;
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
                add_coin_log($uid, $reward, 1, lang('reward.sign'));
                Db::name('sign_log')->strict(false)->field(true)->insertGetId($data);
                // 提交事务
                Db::commit();
                $this->apiSuccess('success');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->apiError('fail');
            }
        } else {
            $result = Db::name('sign_log')->strict(false)->field(true)->insertGetId($data);
            if ($result != false) {
                $this->apiSuccess('success');
            } else {
                $this->apiError('fail');
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $chapter_id = isset($param['chapter_id']) ? intval($param['chapter_id']) : 0;
        $book_id = isset($param['book_id']) ? intval($param['book_id']) : 0;
        if (empty($book_id) || empty($chapter_id)) {
            $this->apiError('empty');
        }
        $conf = get_system_config('reward');
        if (empty($conf)) {
            $this->apiError('407');
        }
        $book = Db::name('book')->field('id')->where('id', $book_id)->find();
        if (empty($book)) {
            $this->apiError('404');
        }
        $chapter = Db::name('chapter')->field('id')->where('id', $chapter_id)->find();
        if (empty($chapter)) {
            $this->apiError('404');
        }
        $uid = JWT_UID;
        $today = date('Y-m-d'); // 当天日期
        // 检查今天是否已点赞
        $like = Db::name('like_log')->where(['user_id' => $uid, 'book_id' => $book_id, 'chapter_id' => $chapter_id, 'like_date' => $today])->count();
        if (intval($like) > 0) {
            $this->apiError('repeat');
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
                        add_coin_log($uid, $reward, 1, lang('reward.daylike'));
                        Db::name('task')->where('id', $already['id'])->update(['status' => 1, 'update_time' => time()]);
                        // 提交事务
                        Db::commit();
                        $this->apiSuccess('success');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $this->apiError('fail');
                    }
                } else {
                    $this->apiSuccess('success');
                }
            } else {
                $this->apiSuccess('success');
            }
        } else {
            $this->apiError('fail');
        }
    }

    /**
     * 获取点赞
     * Summary of likelist
     * @return void
     */
    public function likelist()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        $uid = JWT_UID;
        $user = Db::name('user')->where(['id' => $uid])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if (!isset($param['limit']) || intval($param['limit']) <= 0) {
            $param['limit'] = get_config('app.page_size');
        }
        $order = empty($param['order']) ? 'create_time desc' : $param['order'];
        $list =  Db::name('like_log')->where(['user_id' => $uid])->group('book_id')->order($order)->paginate($param['limit'], false);
        $result = $list->toArray();
        $modelName = \think\facade\App::initialize()->http->getName();
        foreach ($result['data'] as $k => $v) {
            $book = Db::name('book')->field('id as bookId,title,cover,author,authorid,chapters,isfinish,genre,subgenre,filename')->where(['id' => $v['book_id']])->find();
            if (!empty($book)) {
                $result['data'][$k]['authorurl'] = str_replace($modelName, 'home', (string) Route::buildUrl('author_detail', ['id' => $book['authorid']]));
                $result['data'][$k]['cover'] = get_file($book['cover']);
                $result['data'][$k]['bookId'] = $book['bookId'];
                $result['data'][$k]['title'] = $book['title'];
                $result['data'][$k]['author'] = $book['author'];
                $result['data'][$k]['authorid'] = $book['authorid'];
                $result['data'][$k]['chapters'] = $book['chapters'];
                $result['data'][$k]['isfinish'] = $book['isfinish'];
                $result['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $result['data'][$k]['bigcatetitle'] = Db::name('category')->where(['id' => $book['genre']])->value('name');
                $result['data'][$k]['sellcatetitle'] = Db::name('category')->where(['id' => $book['subgenre']])->value('name');
                $result['data'][$k]['url'] = str_replace($modelName, 'home', (string) Route::buildUrl('book_detail', ['id' => $book['filename'] ? $book['filename'] : $book['bookId']]));
            } else {
                unset($result['data'][$k]);
                continue;
            }
            $likelist =  Db::name('like_log')->where(['user_id' => $uid, 'book_id' => $v['book_id']])->order('create_time desc')->select()->toArray();
            foreach ($likelist as $key => $value) {
                $likelist[$key]['chapter_title'] = Db::name('chapter')->where(['id' => $value['chapter_id'], 'status' => 1, ['verify', 'in', '0,1']])->value('title');
            }
            $result['data'][$k]['list'] = $likelist;
        }
        $this->apiSuccess('success', $result);
    }

    /**
     * 银行卡列表
     * Summary of bankcard
     * @return void
     */
    public function bankcard()
    {
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
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
        $this->apiSuccess('success', $list);
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $card_no = isset($param['card_no']) ? intval($param['card_no']) : 0;
        $mobile = isset($param['mobile']) ? intval($param['mobile']) : 0;
        $bank_name = isset($param['bank_name']) ? trim($param['bank_name']) : '';
        $full_name = isset($param['full_name']) ? trim($param['full_name']) : '';
        $card_image = isset($param['card_image']) ? trim($param['card_image']) : '';
        $bank_address = isset($param['bank_address']) ? trim($param['bank_address']) : '';
        if (!self::validateMobile((string)$mobile)) {
            $this->apiError('paramerror');
        }
        if (!self::isValidBankCardNumber((string)$card_no)) {
            $this->apiError('paramerror');
        }
        if (empty($bank_name) || empty($full_name) || empty($card_image) || empty($bank_address)) {
            $this->apiError('empty');
        }
        $card = Db::name('bank_card')->where(['card_no' => $card_no])->find();
        if (!empty($card)) {
            $this->apiError('repeat');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if (intval($user['realname_status']) !== 1) {
            $this->apiError('user.authentication');
        }
        if ($full_name != $user['name']) {
            $this->apiError('inconsistent');
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
                    add_coin_log(JWT_UID, $reward, 1, lang('reward.bindaccount'));
                    Db::name('task')->where('id', $task['id'])->update(['status' => 1, 'update_time' => time()]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
            $this->apiSuccess('success');
        } else {
            $this->apiError('fail');
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $id = isset($param['id']) ? intval($param['id']) : 0;
        if (empty($id)) {
            $this->apiError('empty');
        }
        $card = Db::name('bank_card')->where(['id' => $id])->find();
        if (empty($card)) {
            $this->apiError('404');
        }
        if ($card['user_id'] != JWT_UID) {
            $this->apiError('404');
        }
        Db::name('bank_card')->where(['id' => $id])->delete();
        $this->apiSuccess('success', []);
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $realname = isset($param['realname']) ? trim($param['realname']) : '';
        $id_card_photo = isset($param['id_card_photo']) ? trim($param['id_card_photo']) : '';
        $id_card = isset($param['id_card']) ? trim($param['id_card']) : '';
        if (empty($realname) || empty($id_card_photo) || empty($id_card)) {
            $this->apiError('empty');
        }
        if (!isIdcard($id_card)) {
            $this->apiError('paramerror');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if ($user['realname_status'] == 1) {
            $this->apiError('repeat');
        }
        if ($user['realname_status'] == 2) {
            $this->apiError('examineing');
        }
        $card = Db::name('user')->where(['id_card' => $id_card])->find();
        if (!empty($card)) {
            if ($card['id'] != $user['id']) {
                $this->apiError('common.alreadyused');
            }
        }
        $res = Db::name('user')->where(['id' => $user['id']])->strict(false)->field(true)->update(['name' => $realname, 'id_card_photo' => $id_card_photo, 'id_card' => $id_card, 'realname_status' => 2, 'update_time' => time()]);
        if ($res) {
            $this->apiSuccess('success', []);
        } else {
            $this->apiError('fail');
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $where = ['inviter' => JWT_UID, 'status' => 1];
        $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
        $list = (new UserModel())->field('id,nickname,headimgurl,register_time')->where($where)
            ->order('register_time desc')
            ->paginate($rows, false, ['query' => $param])
            ->each(function ($item, $key) {
                $item->register_time = date('Y-m-d H:i:s', $item->register_time);
            });
        $this->apiSuccess('success', $list);
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $nickname = isset($param['nickname']) ? trim($param['nickname']) : '';
        $mobile = isset($param['mobile']) ? trim($param['mobile']) : '';
        $password = isset($param['password']) ? trim($param['password']) : '';
        if (empty($nickname) || empty($mobile) || empty($password)) {
            $this->apiError('empty');
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            $this->apiError('paramerror');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if (intval($user['author_id'] > 0)) {
            $this->apiError('repeat');
        }
        $author = Db::name('author')->where(['nickname' => $nickname])->find();
        if (!empty($author)) {
            $this->apiError('common.alreadyused');
        }
        $author = Db::name('author')->where(['mobile' => $mobile])->find();
        if (!empty($author)) {
            $this->apiError('common.alreadyused');
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
                    add_coin_log(JWT_UID, $reward, 1, lang('reward.becomeauthor'));
                    Db::name('task')->where('id', $task['id'])->update(['status' => 1, 'update_time' => time()]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
            $this->apiSuccess('success');
        } else {
            $this->apiError('fail');
        }
    }
}
