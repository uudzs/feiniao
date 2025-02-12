<?php
declare(strict_types=1);
namespace app\api\controller\v1;

use app\api\BaseController;
use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\View;

class Category extends BaseController
{

    /**
     * 控制器中间件 [bigcate、bigcate 不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => ['bigcate', 'smallcate']]
    ];

    /**
     * @api {post} /category/bigcate 获取大类
     */
    public function bigcate()
    {
        $param = get_params();
        $list = Db::name('category')->where(['status' => 1, 'pid' => 0])->order('ordernum asc')->select()->toArray();
        if (isset($param['return']) && $param['return'] == 'fetch') {
            return View::fetch('', ['list' => $list]);
        } else {
            $this->apiSuccess('success', $list);
        }
    }

    /**
     * @api {post} /category/smallcate 获取小类
     */
    public function smallcate()
    {
        $param = get_params();
        if (empty($param['pid'])) {
            $this->apiError('参数错误');
        }
        $list = Db::name('category')->where(['status' => 1, 'pid' => $param['pid']])->order('ordernum asc')->select()->toArray();
        $this->apiSuccess('success', $list);
    }
}