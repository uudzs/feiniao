<?php
declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;
use think\facade\Route;

class Chapter extends BaseController
{

    /**
     * 章节详情
     * Summary of detail
     * @return \think\response\View
     */
    public function detail()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        if (empty($id)) {
            return to_assign(1, '参数错误');
        }
        $chapter = Db::name('chapter')->field('id,title,bookid,verify,status,chaps,wordnum')->where(array('id' => $id))->find();
        if (empty($chapter)) {
            return to_assign(1, '章节不存在');
        }
        $list = Db::name('chapter')->field('id,title,chaps,create_time')->where(['bookid' => $chapter['bookid'], 'status' => 1, ['verify', 'in', '0,1']])->order('chaps asc')->select()->toArray(); //所有章节
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['chapter_url'] = (string) Route::buildUrl('chapter_detail', ['id' => $v['id']]);
                $list[$k]['title'] = get_full_chapter($v['title'], $v['chaps']);
            }
        }
        View::assign('id', $id);
        View::assign('bookid', $chapter['bookid']);
        View::assign('chapterlsit', $list);
        return view();
    }

}