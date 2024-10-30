<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Chapter as ChapterModel;
use app\admin\validate\ChapterValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Chapter extends BaseController
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new ChapterModel();
        $this->uid = get_login_admin('id');
    }
    /**
     * 数据列表
     */
    public function datalist()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $where = ['bookid' => $param['bid'], 'trial_time' => 0];
            if (!empty($param['keywords'])) {
                $where[] = ['title', 'like', '%' . $param['keywords'] . '%'];
            }
            $param['order'] = 'chaps asc';
            $list = $this->model->getChapterList($where, $param);
            return table_assign(0, '', $list);
        } else {
            View::assign('bid', $param['bid']);
            View::assign('title', $param['title']);
            return view();
        }
    }

    public function chapterlist()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $where = ['status' => 1, 'bookid' => $param['bid'], ['verify', '<>', 2]];
            if (!empty($param['keywords'])) {
                $where[] = ['title', 'like', '%' . $param['keywords'] . '%'];
            }
            $param['order'] = 'chaps asc';
            $list = $this->model->getChapterList($where, $param);
            return table_assign(0, '', $list);
        } else {
            View::assign('bid', $param['bid']);
            return view();
        }
    }

    //审核
    public function verify()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $where = ['verifytime' => 9999, 'trial_time' => 0];
            if (!empty($param['keywords'])) {
                $where[] = ['title', 'like', '%' . $param['keywords'] . '%'];
            }
            $param['order'] = 'create_time desc';
            $list = $this->model->getChapterList($where, $param);
            $list = $list->toArray();
            foreach ($list['data'] as $k => $v) {
                $book = Db::name('book')->where(['id' => $v['bookid']])->find();
                if (!empty($book)) {
                    $list['data'][$k]['author'] = $book['author'];
                    $list['data'][$k]['booktitle'] = $book['title'];
                } else {
                    $list['data'][$k]['author'] = '--';
                    $list['data'][$k]['booktitle'] = '--';
                }
            }
            return table_assign(0, '', $list);
        } else {
            return view();
        }
    }

    /**
     * 添加
     */
    public function add()
    {
        if (request()->isAjax()) {
            $param = get_params();
            // 检验完整性
            try {
                validate(ChapterValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            if (isset($param["trial_time"])) {
                $param["trial_time"] = $param["trial_time"] ? strtotime($param["trial_time"]) : 0;
            }
            $this->model->addChapter($param);
        } else {
            return view();
        }
    }


    /**
     * 编辑
     */
    public function edit()
    {
        $param = get_params();
        if (request()->isAjax()) {
            // 检验完整性
            try {
                validate(ChapterValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $id = $param['id'];
            $content = $param['content'];
            unset($param['content']);
            $chapter = Db::name('chapter')->where(['id' => $id])->find();
            if ($chapter['verify'] != 1) {
                return to_assign(1, '只有已审理核章节才能编辑章节内容');
            }
            $verify = Db::name('chapter_verify')->where('cid', $id)->find();
            if (!empty($verify)) {
                return to_assign(1, '请先审核章节');
            }
            list($wordnum, $content) = countWordsAndContent($content, true);
            if (empty($content) || empty($wordnum)) {
                to_assign(1, '章节内容为空，无法发布。');
            }
            if ($wordnum < 1000) {
                to_assign(1, '章节内容不能少于1000字');
            }
            if ($wordnum > 10000) {
                to_assign(1, '章节内容字数大于10000字，无法发布。');
            }
            $param['update_time'] = time();
            $param['wordnum'] = $wordnum;
            $param['verifypeople'] = get_login_admin('nickname');
            $param['verifytime'] = time();
            $param['verifyresult'] = '编辑修改章节内容';
            Db::name('chapter')->where(['id' => $id])->strict(false)->field(true)->update($param);
            $chaptertable = calc_hash_db($chapter['bookid']); //章节内容表名
            Db::name($chaptertable)->where(['sid' => $id])->strict(false)->field(true)->update(['info' => $content]);
            $res = Db::name('book')->where(['id' => $chapter['bookid']])->strict(false)->field(true)->update(['update_time' => time()]);
            return to_assign();
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $chapter = Db::name('chapter')->where(['id' => $id])->find();
            // if ($chapter['verify'] != 1) {
            //     return to_assign(1, '只有已审理核章节才能编辑章节内容');
            // }
            $verify = Db::name('chapter_verify')->where('cid', $id)->find();
            if (!empty($verify)) {
                return to_assign(1, '请先审核章节');
            }
            $chaptertable = calc_hash_db($chapter['bookid']); //章节内容表名
            $content = Db::name($chaptertable)->where(['sid' => $id])->find();
            if (!empty($content)) {
                $chapter['info'] = htmlspecialchars_decode($content['info']);
                View::assign('chapter', $chapter);
                return view();
            } else {
                return to_assign(1, '记录不存在');
            }
        }
    }


    /**
     * 查看信息
     */
    public function read()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $id = isset($param['id']) ? $param['id'] : 0;
            if (empty($id)) {
                return to_assign(1, '章节ID为空');
            }
            $chapter = Db::name('chapter')->where(['id' => $id])->find();
            if (empty($chapter)) {
                return to_assign(1, '章节不存在');
            }
            //如果是拒绝
            if (isset($param['verifyresult'])) {
                if (empty($param['verifyresult'])) {
                    return to_assign(1, '拒绝理由为空');
                }
                $param['verify'] = 2;
                $param['verifytime'] = time();
            } else {
                $param['verify'] = 1;
                $param['verifytime'] = time();
                //首次审核
                if (empty($chapter['firstpasstime'])) {
                    $param['firstpasstime'] = time();
                    $param['firstverifyword'] = $chapter['wordnum'];
                }
            }
            $param['verifypeople'] = get_login_admin('nickname');
            $chaptertable = calc_hash_db($chapter['bookid']); //章节内容表名
            Db::name('chapter')->where(['id' => $id])->strict(false)->field(true)->update($param);
            if ($param['verify'] == 1) {
                $res = Db::name('book')->where(['id' => $chapter['bookid']])->strict(false)->field(true)->update(['update_time' => time()]);
            }
            return to_assign();
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $chapter = Db::name('chapter')->where(['id' => $id])->find();
            $verify = Db::name('chapter_verify')->where('cid', $id)->find();
            if (!empty($verify)) {
                return to_assign(1, '请前往【修改章节审核】');
            }
            $chaptertable = calc_hash_db($chapter['bookid']); //章节内容表名
            $content = Db::name($chaptertable)->where(['sid' => $id])->find();
            if (!empty($content)) {
                $chapter['info'] = htmlspecialchars_decode($content['info']);
                $replace = array("&nbsp;", "<br>");
                $search = array(" ", "\n");
                $chapter['info'] = str_replace($search, $replace, $chapter['info']);
                View::assign('chapter', $chapter);
                return view();
            } else {
                return to_assign(1, '记录不存在');
            }
        }
    }

    /**
     * 删除
     * type=0,逻辑删除，默认
     * type=1,物理删除
     */
    public function del()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        if (empty($id)) {
            return to_assign(1, '章节ID为空');
        }
        $chapter = $this->model->getChapterById($id);
        if (empty($chapter)) {
            return to_assign(1, '章节不存在');
        }
        $chaptertable = calc_hash_db($chapter['bookid']); //章节内容表名
        Db::name('chapter')->where(['id' => $id])->delete();
        Db::name('chapter_draft')->where(['cid' => $id])->delete();//草稿箱
        Db::name('chapter_verify')->where(['cid' => $id])->delete();//审核库
        Db::name($chaptertable)->where(['sid' => $id])->delete();
        return to_assign();
    }
}
