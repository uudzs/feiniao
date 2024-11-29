<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;
use app\admin\model\Book as BookModel;

class Book extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];


    /**
     * 作品详情
     * Summary of detail
     */
    public function detail()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        if (empty($id)) {
            $this->apiError('参数错误');
        }
        $uid = JWT_UID;
        $detail = Db::name('book')->where(['id' => $id])->find();
        $model_name = \think\facade\App::initialize()->http->getName();
        if ($detail) {
            $detail['bigclassname'] = Db::name('category')->where(['id' => $detail['genre']])->value('name');
            $detail['smallclassname'] = Db::name('category')->where(['id' => $detail['subgenre']])->value('name');
            $detail['bigclassname'] = $detail['bigclassname'] ?: '-';
            $detail['smallclassname'] = $detail['smallclassname'] ?: '-';
            $detail['cover'] = get_file($detail['cover']);
            $detail['issign'] = Db::name('author')->where(['id' => $detail['authorid']])->value('issign');
            $detail['words'] = wordCount($detail['words']);
            $detail['uptime'] = $detail['update_time'] ? date('Y-m-d H:i:s', $detail['update_time']) : date('Y-m-d H:i:s', $detail['create_time']);
            $detail['chapter'] = Db::name('chapter')->field('id,title,chaps,create_time')->where(['bookid' => $id, 'status' => 1, ['verify', 'in', '0,1']])->order('chaps asc')->select()->toArray(); //所有章节
            $first_chapter = $last_chapter = [];
            if (!empty($detail['chapter'])) {
                $first_chapter = $detail['chapter'][0]; //第一章
                $last_chapter = end($detail['chapter']); //最后一章
                foreach ($detail['chapter'] as $k => $v) {
                    $url = (string) Route::buildUrl('chapter_detail', ['id' => $v['id']]);
                    $detail['chapter'][$k]['chapter_url'] = str_replace($model_name, 'home', $url);
                    $detail['chapter'][$k]['title'] = get_full_chapter($v['title'], $v['chaps']);
                }
            }
            $detail['first_chapter'] = $first_chapter;
            $detail['last_chapter'] = $last_chapter;
            $ip = app('request')->ip();
            $where = ['book_id' => $id];
            if ($uid) {
                $where['user_id'] = $uid;
                $detail['fav'] = Db::name('favorites')->where(['user_id' => $uid, 'pid' => $detail['id']])->find();
                $detail['fav'] = $detail['fav'] ?: '';
                $detail['follow'] = Db::name('follow')->where(['user_id' => $uid, 'from_id' => $detail['authorid']])->find();
                $detail['follow'] = $detail['follow'] ?: '';
            } else {
                $where['ip'] = $ip;
                $detail['fav'] = '';
                $detail['follow'] = '';
            }
            //查询是否有该书记录
            $reads = Db::name('readhistory')->field('IF(update_time = 0, create_time, update_time) AS order_time,id,update_time,create_time,title,chapter_id,book_id')->where($where)->order('order_time desc')->find();
            //查询是否有该章节记录
            if (!empty($reads)) {
                $detail['chapter_url'] = (string) Route::buildUrl('chapter_detail', ['id' => $reads['chapter_id']]);
            } else {
                if ($first_chapter) {
                    $detail['chapter_url'] = (string) Route::buildUrl('chapter_detail', ['id' => $first_chapter['id']]);
                } else {
                    $detail['chapter_url'] = 'javascript:;';
                }
            }
            $detail['chapter_url'] = str_replace($model_name, 'home', $detail['chapter_url']);
            $detail['authorurl'] = str_replace($model_name, 'home', (string) Route::buildUrl('author_detail', ['id' => $detail['authorid']]));
            if (!empty($last_chapter)) {
                $detail['chaptertime'] = time_tran($last_chapter['create_time']);
            } else {
                $detail['chaptertime'] = '';
            }
            if (!empty($detail['remark'])) {
                $detail['remark'] = htmlspecialchars_decode($detail['remark']);
                $replace = array("&nbsp;", "<br>", "<br>");
                $search = array(" ", "\n", '\n');
                $detail['remark'] = str_replace($search, $replace, $detail['remark']);
            }
            if (!empty($detail['label']) && strpos($detail['label'], ',') !== false) {
                $detail['label'] = explode(',', $detail['label']);
            } else {
                $detail['label'] = [];
            }
        } else {
            $this->apiError('作品不存在');
        }
        $this->apiSuccess('请求成功', $detail);
    }

    /**
     * 获取作品列表
     * Summary of booklist
     * @return void
     */
    public function booklist()
    {
        $param = get_params();
        $where = ['status' => 1];
        if (isset($param['keywords']) && !empty($param['keywords'])) {
            $where[] = ['title|author', 'like', '%' . $param['keywords'] . '%'];
        }
        //最多可以载加多少页
        if (isset($param['page']) && intval($param['page']) > 10) {
            $param['page'] = 10;
        }
        if (isset($param['subgenre']) && !empty($param['subgenre'])) {
            $where[] = ['subgenre', '=', $param['subgenre']];
        }
        if (isset($param['genre']) && !empty($param['genre'])) {
            $where[] = ['genre', '=', $param['genre']];
        }
        if (isset($param['bookid']) && !empty($param['bookid'])) {
            $where[] = ['id', '<>', $param['bookid']];
        }
        if (isset($param['authorid']) && !empty($param['authorid'])) {
            $where[] = ['authorid', '=', $param['authorid']];
        }
        if (isset($param['isfinish']) && !empty($param['isfinish'])) {
            $where[] = ['isfinish', '=', $param['isfinish']];
        }
        if (isset($param['words']) && !empty($param['words'])) {
            $words = intval($param['words']);
            //300万字以上
            if ($words == 1) {
                $where[] = ['words', '>=', 10000 * 300];
            }
            //100万字以上
            if ($words == 2) {
                $where[] = ['words', '>=', 10000 * 100];
            }
            //50万字以上
            if ($words == 3) {
                $where[] = ['words', '>=', 10000 * 50];
            }
            //30万字以下
            if ($words == 4) {
                $where[] = ['words', '<=', 10000 * 30];
            }
            //30-50万字
            if ($words == 5) {
                $where[] = ['words', '>=', 10000 * 30];
                $where[] = ['words', '<=', 10000 * 50];
            }
            //50-100万字
            if ($words == 6) {
                $where[] = ['words', '>=', 10000 * 50];
                $where[] = ['words', '<=', 10000 * 100];
            }
            //100-300万字
            if ($words == 7) {
                $where[] = ['words', '>=', 10000 * 100];
                $where[] = ['words', '<=', 10000 * 300];
            }
        }
        if (!isset($param['order']) || empty($param['order'])) {
            $param['order'] = 'sort DESC';
        }
        if (isset($param['limit'])) {
            $param['limit'] = intval($param['limit']);
        }
        $list = (new BookModel())->getBookList($where, $param);
        $result = $list->toArray();
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $author = Db::name('author')->where(['id' => $v['authorid']])->find();
                if (empty($author)) {
                    unset($result['data'][$k]);
                    continue;
                }
                $result['data'][$k]['headpic'] = get_file($author['headimg']);
                $result['data'][$k]['cover_str'] = get_file($v['cover']);
                $result['data'][$k]['isfinish_str'] = intval($v['isfinish']) == 2 ? '完结' : '连载';
                $result['data'][$k]['words_str'] = intval($v['words']) > 0 ? wordCount($v['words']) : 0;
                $result['data'][$k]['authorurl'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('author_detail', ['id' => $v['authorid']]));
                $result['data'][$k]['url'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('book_detail', ['id' => $v['id']]));
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
        $this->apiSuccess('请求成功', $result);
    }
}
