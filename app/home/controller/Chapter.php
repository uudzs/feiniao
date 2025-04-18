<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Route;
use content\Content;

class Chapter extends BaseController
{

    /**
     * 章节详情
     * Summary of detail
     * @return \think\response\View
     */
    public function detail()
    {
        $ismakecache = $this->usecache();
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        if (empty($id)) {
            $this->error(404);
        }
        $chapter = Db::name('chapter')->field('id,title,bookid,verify,status,chaps,wordnum,create_time')->where(array('id' => $id))->find();
        if (empty($chapter)) {
            $this->error(404);
        }
        $list = Db::name('chapter')->field('id,bookid,title,chaps,create_time')->where(['bookid' => $chapter['bookid'], 'status' => 1, ['verify', 'in', '0,1']])->order('chaps asc')->select()->toArray(); //所有章节
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['chapter_url'] = (string) Route::buildUrl('chapter_detail', ['id' => $v['id'], 'bookid' => $v['bookid']]);
                $list[$k]['title'] = get_full_chapter($v['title'], $v['chaps']);
            }
        }
        $data = [];
        if (get_system_config('content', 'chapter_pages_content_open')) {
            $content = Content::get($chapter['bookid'], $chapter['id']);
            if ($content && mb_strlen($content) > 0) {
                $content = htmlspecialchars_decode($content);
                $content = preg_replace('/<br\s?\/?>\r?\n?/i', "\n", $content);
                $paragraphs = explode("\n", $content);
                $paragraphs = array_map('trim', $paragraphs);
                $paragraphs = array_filter($paragraphs);
                $content = implode("\n", array_map(function ($p) {
                    return "<p>" . $p . "</p>";
                }, $paragraphs));
            } else {
                $content = '';
            }
            //前一章
            $front = Db::name('chapter')->field('id,bookid,title')->where(['bookid' => $chapter['bookid'], 'status' => 1, ['verify', 'in', '0,1'], ['chaps', '<', $chapter['chaps']]])->order('chaps DESC')->find();
            if (!empty($front)) {
                $front_url =  (string) Route::buildUrl('chapter_detail', ['id' => $front['id'], 'bookid' => $front['bookid']]);
            } else {
                $front_url = '';
            }
            //后一章
            $after = Db::name('chapter')->field('id,bookid,title')->where(['bookid' => $chapter['bookid'], 'status' => 1, ['verify', 'in', '0,1'], ['chaps', '>', $chapter['chaps']]])->order('chaps ASC')->find();
            if (!empty($after)) {
                $after_url = (string) Route::buildUrl('chapter_detail', ['id' => $after['id'], 'bookid' => $after['bookid']]);
            } else {
                $after_url = '';
            }
            $data['content'] = $content;
            $data['front_url'] = $front_url;
            $data['after_url'] = $after_url;
        }
        $data['chapter'] = $chapter;
        $data['id'] = $id;
        $data['bookid'] = $chapter['bookid'];
        $data['chapterlist'] = $list;
        $data['book'] = Db::name('book')->where('id', $data['bookid'])->find();
        View::config(['view_path' => $this->view_path()]);
        if ($ismakecache) $this->makecache(View::fetch('detail', $data));
        return view('detail', $data);
    }
}
