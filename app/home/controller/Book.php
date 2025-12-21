<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Route;
use app\service\CacheService;

class Book extends BaseController
{

    /**
     * 作品分类
     * Summary of cate
     * @return \think\response\View
     */
    public function cate()
    {
        $ismakecache = $this->usecache();
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        if (intval($id) === $id) {
            $category = Db::name('category')->where(['id' => $id])->find();
        } else {
            $category = Db::name('category')->where(['key' => $id])->find();
            if (!empty($category)) {
                $id = $category['id'];
            }
        }
        View::assign('category', $category);
        View::assign('catid', $id);
        View::assign('id', $id);
        if ($ismakecache) $this->makecache(View::fetch());
        return view();
    }

    /**
     * 列表
     * Summary of list
     */
    public function list()
    {
        if ($this->usecache()) $this->makecache(View::fetch());
        return view();
    }

    /**
     * 全本
     * Summary of quanben
     */
    public function quanben()
    {
        if ($this->usecache()) $this->makecache(View::fetch());
        return view();
    }

    /**
     * 排行
     * Summary of rank
     */
    public function rank()
    {
        if ($this->usecache()) $this->makecache(View::fetch());
        return view();
    }

    /**
     * 作品详情
     * Summary of detail
     */
    public function detail()
    {
        $ismakecache = $this->usecache();
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $book = [];
        $book = \app\common\model\Novel::getBookDetail($id);
        if (empty($book)) {
            $this->error(404);
        }
        if (!ctype_digit((string)$id)) {
            $id = $book['id'];
        }
        auto_run_addons('collect', [
            'type' => 'single_book',
            'book_id' => $id,
        ]);

        $book['bigclassname'] = Db::name('category')->where(['id' => $book['genre']])->cache('category_' . $book['genre'], 86400)->value('name');
        $book['cover'] = get_file($book['cover']);
        $book['words_str'] = wordCount($book['words']);
        $book['author'] = trim($book['author']);
        $book['remark'] = $book['remark'] ? strip_tags($book['remark']) : '';
        $first_chapter = Db::name('chapter')->field('id,bookid,title')->where(['bookid' => $id, 'status' => 1, ['verify', 'in', '0,1']])->cache('chapter_bookid_chapsasc_' . $id, 86400)->order('chaps asc')->find();
        if (!empty($first_chapter)) {
            $book['first_chapter_url'] = (string) furl('chapter_detail', ['id' => $first_chapter['id'], 'bookid' => $book['filename'] ? $book['filename'] : $first_chapter['bookid']]);
            $book['first_chapter_title'] = $first_chapter['title'];
        } else {
            $book['first_chapter_url'] = '';
            $book['first_chapter_title'] = '';
        }
        $chapter = Db::name('chapter')->field('id,bookid,title,create_time,update_time')->where(['bookid' => $id, 'status' => 1, ['verify', 'in', '0,1']])->cache('chapter_bookid_chapsdesc_' . $id, 86400)->order('chaps desc')->find();
        if (!empty($chapter)) {
            $book['chapter_url'] = (string) furl('chapter_detail', ['id' => $chapter['id'], 'bookid' => $book['filename'] ? $book['filename'] : $chapter['bookid']]);
            $book['chapter_title'] = $chapter['title'];
            $book['update_time'] =  $chapter['update_time'] ? date('Y-m-d H:i:s', $chapter['update_time']) : date('Y-m-d H:i:s', $chapter['create_time']);
        } else {
            $book['chapter_url'] = '';
            $book['chapter_title'] = '';
            $book['update_time'] = '';
        }
        $book['authorurl'] = (string) Route::buildUrl('author_detail', ['id' => $book['authorid']])->domain(true);
        View::assign('book', $book);
        View::assign('bid', $id);
        View::config(['view_path' => $this->view_path()]);
        if ($ismakecache) $this->makecache(View::fetch());
        return view();
    }
}
