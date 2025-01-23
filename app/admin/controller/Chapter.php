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

    var $uid;
    var $model;

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
        $param = get_params();
        if (request()->isAjax()) {
            // 检验完整性
            try {
                validate(ChapterValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $book = Db::name('book')->where(array('id' => $param['bookid']))->find();
            if (empty($book)) {
                to_assign(1, '作品不存在');
            }
            $serial = intval($param['serial']);
            if (empty($serial)) $serial = 1;
            $title = '第' . numConvertWord($serial) . '章 ' . trim($param['title']);
            $istitle = Db::name('chapter')->where(['bookid' => $book['id'], 'title' => $title])->find();
            if (!empty($istitle)) {
                to_assign(1, '章节名称重复，无法发布。');
            }
            $info = $param['content'];
            list($wordnum, $content) = countWordsAndContent($info, true);
            if (empty($content) || empty($wordnum)) {
                to_assign(1, '章节内容为空，无法发布。');
            }
            $config = get_system_config('content');
            $chapter_min_num = isset($config['chapter_min_num']) ? intval($config['chapter_min_num']) : 0;
            $chapter_max_num = isset($config['chapter_max_num']) ? intval($config['chapter_max_num']) : 0;
            if ($chapter_min_num > 0 && $wordnum < $chapter_min_num) {
                to_assign(1, '章节内容不能少于' . $chapter_min_num . '字');
            }
            if ($chapter_max_num > 0 && $wordnum > $chapter_max_num) {
                to_assign(1, '章节内容字数大于' . $chapter_max_num . '字，无法发布。');
            }
            $chaptertable = calc_hash_db($book['id']); //章节内容表名
            $data = [
                'title' => $title,
                'bookid' => $book['id'],
                'authorid' => $book['authorid'],
                'title' => $title,
                'chaps' => $serial,
                'content' => $content,
                'wordnum' => $wordnum,
                'firstverifyword' => $wordnum,
                'status' => 1,
                'verify' => 1,
                'draft' => 0,
                'trial_time' => 0,
                'create_time' => time(),
                'firstpasstime' => time()
            ];
            $sid = Db::name('chapter')->strict(false)->field(true)->insertGetId($data);
            if ($sid !== false) {
                Db::name($chaptertable)->strict(false)->field(true)->insertGetId(['sid' => $sid, 'info' => $content]);
                to_assign(0, '添加成功');
            } else {
                to_assign(1, '操作失败');
            }
        } else {
            $book = Db::name('book')->where(array('id' => $param['bid']))->find();
            if (empty($book)) {
                to_assign(1, '作品不存在');
            }
            $chapter = Db::name('chapter')->where(array('bookid' => $param['bid']))->order('chaps desc')->value('chaps');
            if (!empty($chapter)) {
                $serial = intval($chapter) + 1;
            } else {
                $serial = 1;
            }
            $chapstitle = '第' . numConvertWord($serial) . '章 '; //章节序号名称
            View::assign('book', $book);
            View::assign('serial', $serial);
            View::assign('chapstitle', $chapstitle);
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
            $config = get_system_config('content');
            $chapter_min_num = isset($config['chapter_min_num']) ? intval($config['chapter_min_num']) : 0;
            $chapter_max_num = isset($config['chapter_max_num']) ? intval($config['chapter_max_num']) : 0;
            if ($chapter_min_num > 0 && $wordnum < $chapter_min_num) {
                to_assign(1, '章节内容不能少于' . $chapter_min_num . '字');
            }
            if ($chapter_max_num > 0 && $wordnum > $chapter_max_num) {
                to_assign(1, '章节内容字数大于' . $chapter_max_num . '字，无法发布。');
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
        Db::name('chapter_draft')->where(['cid' => $id])->delete(); //草稿箱
        Db::name('chapter_verify')->where(['cid' => $id])->delete(); //审核库
        Db::name($chaptertable)->where(['sid' => $id])->delete();
        return to_assign();
    }
}
