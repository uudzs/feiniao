<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;

class User extends BaseController
{
    /**
     * 个人中心
     * Summary of index
     * @return \think\response\View
     */
    public function index()
    {
        return view();
    }

    /**
     * 阅读记录
     * Summary of readlog
     * @return \think\response\View
     */
    public function readlog()
    {
        return view();
    }

    /**
     * 个人资料
     * Summary of profile
     * @return \think\response\View
     */
    public function profile()
    {
        return view();
    }

    /**
     * 输入邀请码
     * Summary of invite
     * @return void
     */
    public function invite()
    {
        return view();
    }

    /**
     * 昵称
     * Summary of nickname
     * @return void
     */
    public function nickname()
    {
        return view();
    }

    /**
     * 实名
     * Summary of realname
     * @return void
     */
    public function realname()
    {
        return view();
    }

    /**
     * 手机绑定
     * Summary of phone
     * @return void
     */
    public function phone()
    {
        return view();
    }

    /**
     * 安全密码
     * Summary of security
     * @return void
     */
    public function security()
    {
        return view();
    }

    /**
     * 客服
     * Summary of service
     * @return void
     */
    public function service()
    {
        return view();
    }

    /**
     * 关于
     * Summary of about
     * @return void
     */
    public function about()
    {
        return view();
    }

    /**
     * 银行卡管理
     * Summary of bankcard
     * @return void
     */
    public function bankcard()
    {
        return view();
    }

    /**
     * 实名认证
     * Summary of realnameauth
     * @return void
     */
    public function realnameauth()
    {
        return view();
    }

    /**
     * 成为作者
     * Summary of author
     * @return void
     */
    public function author()
    {
        return view();
    }

    /**
     * 举报
     * Summary of report
     * @return void
     */
    public function report()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $report_contact = isset($param['report_contact']) ? trim($param['report_contact']) : '';
            $report_content = isset($param['report_content']) ? trim($param['report_content']) : '';
            $code = isset($param['code']) ? trim($param['code']) : '';
            if (!captcha_check($code)) {
                return to_assign(1, '验证码错误');
            }
            if (empty($report_contact) || empty($report_content)) {
                return to_assign(1, '参数错误');
            }
            $data = [
                'contact' => $report_contact,
                'introduce' => $report_content,
                'user_id' => get_login_user('id') ? get_login_user('id') : 0,
                'create_time' => time(),
                'ip' => request()->ip(),
                'status' => 0
            ];
            $id = Db::name('report')->strict(false)->field(true)->insertGetId($data);
            if ($id) {
                return to_assign(0, '举报成功');
            }
            return to_assign(1, '举报失败');
        } else {
            $refererUrl = Request::instance()->server('HTTP_REFERER', '');
            View::assign('refererUrl', $refererUrl);
            return view();
        }
    }
}
