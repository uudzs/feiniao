<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;

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
        if (intval($id) ==  $id) {
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
        if (intval($id) ==  $id) {
            Db::name('book')->where('id', $id)->inc('hits')->update();
        } else {
            $book = Db::name('book')->where('filename', $id)->find();
            if (!empty($book)) {
                $id = $book['id'];
                Db::name('book')->where('id', $id)->inc('hits')->update();                
            }
        }
        View::assign('bid', $id);
        if ($ismakecache) $this->makecache(View::fetch());
        return view();
    }
}
