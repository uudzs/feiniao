<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;
use app\admin\model\Book as BookModel;

class Task extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];

    /**
     * 领取任务
     * Summary of gettask
     * @return void
     */
    public function gettask()
    {
        $param = get_params();
        $name = isset($param['name']) ? trim($param['name']) : '';
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        if (empty($name)) {
            $this->apiError('参数为空');
        }
        $conf = get_system_config('reward');
        $type = '';
        foreach ($conf as $k => $v) {
            if ($name == $v) {
                $type = $k;
                break;
            }
        }
        $data = [
            'user_id' => JWT_UID,
            'taskid' => $name,
            'task_date' => date('Y-m-d'),
            'status' => 0,
            'ip' => app('request')->ip(),
            'create_time' => time()
        ];
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        $vip = Db::name('vip_log')->where(['status' => 1, 'user_id' => JWT_UID, ['expire_time', '>', time()]])->find();
        $vip_reward = 0;
        if (!empty($vip)) {
            if (floatval($conf['vip_reward']) > 1) {
                $vip_reward = floatval($conf['vip_reward']);
            }
        }
        if ($type == 'account_id') {
            $data['type'] = 1;
            $data['title'] = '绑定收款账号奖励';
            $data['reward'] = $vip_reward > 1 ? floor($vip_reward * floatval($conf['account'])) : $conf['account'];
        }
        if ($type == 'mobile_id') {
            if (!empty($user['mobile'])) {
                $this->apiError('手机已绑定，不可领取。');
            }
            $data['type'] = 1;
            $data['title'] = '绑定手机号奖励';
            $data['reward'] = $vip_reward > 1 ? floor($vip_reward * floatval($conf['mobile'])) : $conf['mobile'];
        }
        if ($type == 'author_id') {
            if (isset($user['author_id']) && intval($user['author_id']) > 0) {
                $this->apiError('已经是作者，不可领取。');
            }
            $data['type'] = 1;
            $data['title'] = '成为作者奖励';
            $data['reward'] = $vip_reward > 1 ? floor($vip_reward * floatval($conf['author'])) : $conf['author'];
        }
        if ($type == 'vip_id') {
            if (!empty($vip)) {
                $this->apiError('已经是VIP，不可领取。');
            }
            $data['type'] = 1;
            $data['title'] = '成为VIP奖励';
            $data['reward'] = $vip_reward > 1 ? floor($vip_reward * floatval($conf['vip'])) : $conf['vip'];
        }
        if ($type == 'chapter_id') {
            $data['type'] = 2;
            $data['title'] = '每日阅读章节奖励';
            $data['reward'] = $vip_reward > 1 ? floor($vip_reward * floatval($conf['chapter_reward'])) : $conf['chapter_reward'];
        }
        if ($type == 'like_id') {
            $data['type'] = 2;
            $data['title'] = '每日点赞奖励奖励';
            $data['reward'] = $vip_reward > 1 ? floor($vip_reward * floatval($conf['like_reward'])) : $conf['like_reward'];
        }
        if ($data['type'] == 2) {
            $already = Db::name('task')->where(['user_id' => JWT_UID, 'taskid' => $name, 'task_date' => $data['task_date']])->find();
        } else {
            $already = Db::name('task')->where(['user_id' => JWT_UID, 'taskid' => $name])->find();
        }
        if (!empty($already)) {
            $this->apiSuccess('已领取过了');
        }
        $result = Db::name('task')->strict(false)->field(true)->insertGetId($data);
        if ($result != false) {
            $this->apiSuccess('任务领取成功');
        } else {
            $this->apiError('任务领取失败');
        }
    }

    /**
     * 任务首页
     * Summary of index
     * @return void
     */
    public function index()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $uid = JWT_UID;
        $res = [];
        $conf = get_system_config('reward');
        //一次性任务
        $res['account'] = Db::name('task')->where(['user_id' => $uid, 'taskid' => $conf['account_id']])->find();
        $res['account'] = $res['account'] ?: '';
        $res['mobile'] = Db::name('task')->where(['user_id' => $uid, 'taskid' => $conf['mobile_id']])->find();
        $res['author'] = Db::name('task')->where(['user_id' => $uid, 'taskid' => $conf['author_id']])->find();
        $res['vip'] = Db::name('task')->where(['user_id' => $uid, 'taskid' => $conf['vip_id']])->find();
        $today = date('Y-m-d'); // 当天日期
        //阅读章节
        $res['chapter'] = Db::name('task')->where(['user_id' => $uid, 'task_date' => $today, 'taskid' => $conf['chapter_id']])->find();
        //点赞
        $res['like'] = Db::name('task')->where(['user_id' => $uid, 'task_date' => $today, 'taskid' => $conf['like_id']])->find();
        //日任务
        $res['other'] = Db::name('task')->where(['user_id' => $uid, ['type', 'in', '3,4,5,6']])->order('create_time Desc')->select()->toArray(); //所有其他任务
        $this->apiSuccess('请求成功', $res);
    }
}
