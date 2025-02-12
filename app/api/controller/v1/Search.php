<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;
use app\admin\model\Book as BookModel;

class Search extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];

    /**
     * 搜索功能
     * Summary of index
     * @return void
     */
    public function index()
    {
        $param = get_params();
        $keyword = isset($param['keywords']) ? trim($param['keywords']) : '';
        $client = isset($param['client']) ? intval($param['client']) : 1;
        if (empty($keyword)) {
            $this->apiSuccess('请求成功', []);
        }
        $where = [];
        if (!empty($keyword)) {
            $where[] = ['title|author', 'like', '%' . $keyword . '%'];
        }
        //最多可以载加多少页
        if (isset($param['page']) && intval($param['page']) > 10) {
            $param['page'] = 10;
        }
        $list = (new BookModel())->getBookList($where, $param);
        $result = $list->toArray();
        $total = $result['total'];
        //是否登录
        if (defined('JWT_UID') && JWT_UID) {
            $data = [
                'type' => 1,
                'client' => $client,
                'create_time' => time(),
                'keyword' => $keyword,
                'resnum' => $total,
                'user_id' => JWT_UID
            ];
        } else {
            $data = [
                'type' => 1,
                'client' => $client,
                'create_time' => time(),
                'keyword' => $keyword,
                'resnum' => $total,
                'user_id' => 0
            ];
        }
        foreach ($result['data'] as $k => $v) {
            if (strpos($v['title'], $keyword) !== false) {
                $result['data'][$k]['searchtype'] = 1;
            } elseif (strpos($v['author'], $keyword) !== false) {
                $result['data'][$k]['searchtype'] = 2;
                $author = Db::name('author')->where(['id' => $v['authorid']])->find();
                if (empty($author)) {
                    unset($result['data'][$k]);
                    continue;
                }
                $result['data'][$k]['headpic'] = get_file($author['headimg']);
                $result['data'][$k]['regdate'] = date('Y-m-d', $author['create_time']);
                $result['data'][$k]['bookcount'] = Db::name('book')->where(['authorid' => $v['authorid']])->count();
                $result['data'][$k]['authorurl'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('author_detail', ['id' => $v['authorid']]));
            } else {
                $result['data'][$k]['searchtype'] = 1;
            }
            $result['data'][$k]['url'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('book_detail', ['id' => $v['filename'] ? $v['filename'] : $v['id']]));
        }
        //写记录
        $res = Db::name('search_log')->strict(false)->field(true)->insertGetId($data);
        $this->apiSuccess('请求成功', $result);
    }

    /**
     * 搜索词
     * Summary of keywords
     * @return void
     */
    public function keywords()
    {
        $param = get_params();
        $page = (isset($param['page']) && intval($param['page']) > 0) ? intval($param['page']) : 1; //页码
        $limit = (isset($param['limit']) && intval($param['limit']) > 0) ? intval($param['limit']) : 10; //条数
        //最多可以载加多少页
        if (isset($param['page']) && intval($param['page']) > 10) {
            $param['page'] = 10;
        }
        $order = 'resnum desc';
        $start = $limit * ($page - 1);
        $list = Db::name('search_log')
            ->field('count(id) as total,type,keyword,user_id,resnum,create_time')
            ->group('keyword')
            ->order($order)
            ->limit($start, $limit)
            ->select();
        $this->apiSuccess('请求成功', $list);
    }

    /**
     * 搜索记录
     * Summary of searchlog
     * @return void
     */
    public function searchlog()
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
        $page = (isset($param['page']) && intval($param['page']) > 0) ? intval($param['page']) : 1; //页码
        $limit = (isset($param['limit']) && intval($param['limit']) > 0) ? intval($param['limit']) : 10; //条数
        //最多可以载加多少页
        if (isset($param['page']) && intval($param['page']) > 10) {
            $param['page'] = 10;
        }
        $order = 'create_time desc';
        $start = $limit * ($page - 1);
        $list = Db::name('search_log')
            ->field('id,type,keyword,user_id,resnum,create_time')
            ->where(['user_id' => $uid])
            ->group('keyword')
            ->order($order)
            ->limit($start, $limit)
            ->select();
        $this->apiSuccess('请求成功', $list);
    }

    /**
     * 删除搜索记录
     * Summary of delsearchlog
     * @return void
     */
    public function delsearchlog()
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
        $keyword = $param['keyword'];
        Db::name('search_log')->where(['keyword' => $keyword, 'user_id' => $uid])->delete();
        $this->apiSuccess('删除成功', []);
    }
}
