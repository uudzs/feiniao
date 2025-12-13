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
        if (intval($id) !== $id) {
            $id = decrypt_chapter_id($id);
        }
        if ($id <= 0) {
            $this->error(404);
        }
        $chapter = \app\common\model\Chapter::getChapterDetail($id);
        if (empty($chapter)) {
            $this->error(404);
        }
        $book = \app\common\model\Novel::getBookDetail((int)$chapter['bookid']);
        if (empty($book)) {
            $this->error(404);
        }
        $book['cover'] = get_file($book['cover']);
        $cacheKey = 'chapter_list_' . $chapter['bookid'];
        $list = \app\service\CacheService::remember($cacheKey, function () use ($chapter) {
            return \app\common\model\Chapter::field('id,bookid,title,chaps,create_time')->where(['bookid' => $chapter['bookid'], 'status' => 1, ['verify', 'in', '0,1']])->order('chaps asc')->select()->toArray();
        });
        $data = [];
        $config = get_system_config('content');
        if ($config['chapter_pages_content_open']) {
            $chapter['chapteraccess'] = chapterCheckAccess($id);
            $hide_content = $chapter['chapteraccess'] !== 1;
            $chapter['hide_content'] = $hide_content;
            if (!$hide_content) {
                auto_run_addons('collect', [
                    'type' => 'single_chapter',
                    'chapter_id' => $id
                ]);
                $content = Content::get($chapter['bookid'], $chapter['id']);
                if ($content && mb_strlen($content) > 0) {
                    $content = htmlspecialchars_decode($content);
                    $content = preg_replace('/<br\s?\/?>\r?\n?/i', "\n", $content);
                    if (isset($config['chapter_refuse_collection_open']) && $config['chapter_refuse_collection_open']) {
                        $paragraphs = $this->splitContent($content);
                        $content = $this->shuffleParagraphs($paragraphs);
                    } else {
                        $paragraphs = explode("\n", $content);
                        $paragraphs = array_map('trim', $paragraphs);
                        $paragraphs = array_filter($paragraphs);
                        $content = implode("\n", array_map(function ($p) {
                            return "<p>" . $p . "</p>";
                        }, $paragraphs));
                    }
                }
            } else {
                $content = lang('common.nopermission');
            }
            //前一章
            $front = Db::name('chapter')->field('id,bookid,title')->where(['bookid' => $chapter['bookid'], 'status' => 1, ['verify', 'in', '0,1'], ['chaps', '<', $chapter['chaps']]])->order('chaps DESC')->find();
            if (!empty($front)) {
                $front_url =  (string)furl('chapter_detail', ['id' => $front['id'], 'bookid' => $book['filename'] ? $book['filename'] : $front['bookid']]);
            } else {
                $front_url = '';
            }
            //后一章
            $after = Db::name('chapter')->field('id,bookid,title')->where(['bookid' => $chapter['bookid'], 'status' => 1, ['verify', 'in', '0,1'], ['chaps', '>', $chapter['chaps']]])->order('chaps ASC')->find();
            if (!empty($after)) {
                $after_url = (string)furl('chapter_detail', ['id' => $after['id'], 'bookid' => $book['filename'] ? $book['filename'] : $after['bookid']]);
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
        $data['book'] = $book;
        View::config(['view_path' => $this->view_path()]);
        if ($ismakecache) $this->makecache(View::fetch('detail', $data));
        return view('detail', $data);
    }

    private function splitContent($content)
    {
        return array_filter(
            preg_split('/\r\n|\n|\r/', $content),
            function ($line) {
                return trim($line) !== '';
            }
        );
    }

    private function shuffleParagraphs($paragraphs)
    {
        $indexed = [];
        foreach ($paragraphs as $index => $text) {
            $indexed[$index] = $text;
        }
        $keys = array_keys($indexed);
        shuffle($keys);

        $shuffled = [];
        foreach ($keys as $key) {
            $shuffled[$key] = $indexed[$key];
        }

        return $shuffled;
    }
}
