<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\ChapterVerify as ChapterVerifyModel;
use app\admin\validate\ChapterVerifyValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class ChapterVerify extends BaseController
{

    var $model;
    var $uid;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new ChapterVerifyModel();
        $this->uid = get_login_admin('id');
    }
    /**
     * 数据列表
     */
    public function datalist()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = [];
            if (!empty($param['keywords'])) {
                $where[] = ['title', 'like', '%' . $param['keywords'] . '%'];
            }
            $list = $this->model->getChapterVerifyList($where, $param);
            $list = $list ? $list->toArray() : [];
            foreach ($list['data'] as $key => $value) {
                $book = Db::name('book')->where(['id' => $value['bid']])->find();
                if (!empty($book)) {
                    $list['data'][$key]['btitle'] = $book['title'];
                    $list['data'][$key]['author'] = $book['author'];
                } else {
                    $list['data'][$key]['btitle'] = '';
                    $list['data'][$key]['author'] = '';
                }
            }
            return table_assign(0, '', $list);
        } else {
            return view();
        }
    }

    //审核
    public function verify()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $id = isset($param['id']) ? $param['id'] : 0;
            unset($param['id']);
            if (empty($id)) {
                return to_assign(1, 'ID为空');
            }
            $verify = $this->model->getChapterVerifyById($id);
            if (empty($verify)) {
                return to_assign(1, '信息不存在');
            }
            $content = '';
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
                list($wordnum, $content) = countWordsAndContent($verify['content'], true);
                if (empty($content) || empty($wordnum)) {
                    return to_assign(1, '章节内容为空。');
                }
                $param['wordnum'] = $wordnum;
            }
            $param['verifypeople'] = get_login_admin('nickname');
            if ($param['verify'] == 1) {
                $param['update_time'] = time();
                $param['trial_time'] = 0;
            }
            if ($param['verify'] == 1) {
                $param['title'] = $verify['title'];
            }
            Db::name('chapter')->where(['id' => $verify['cid']])->strict(false)->field(true)->update($param);
            if ($param['verify'] == 1) {
                $chaptertable = calc_hash_db($verify['bid']); //章节内容表名
                Db::name($chaptertable)->where(['sid' => $verify['cid']])->strict(false)->field(true)->update(['info' => $content]);
                //更新时间
                $res = Db::name('book')->where(['id' => $verify['bid']])->strict(false)->field(true)->update(['update_time' => time()]);
                Db::name('chapter_verify')->where('id', $id)->delete(); //删除
            }
            return to_assign(0, '操作成功');
        } else {
            return to_assign(1, '禁止访问');
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
                validate(ChapterVerifyValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $this->model->addChapterVerify($param);
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
                validate(ChapterVerifyValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $this->model->editChapterVerify($param);
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $detail = $this->model->getDdChapterVerifyById($id);
            if (!empty($detail)) {
                View::assign('detail', $detail);
                return view();
            } else {
                throw new \think\exception\HttpException(404, '找不到页面');
            }
        }
    }


    /**
     * 查看信息
     */
    public function read()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $detail = $this->model->getChapterVerifyById($id);
        if (!empty($detail)) {
            $detail['content'] = htmlspecialchars_decode($detail['content']);
            $replace = array("&nbsp;", "<br>");
            $search = array(" ", "\n");
            $detail['content'] = str_replace($search, $replace, $detail['content']);
            $book = Db::name('book')->where(['id' => $detail['bid']])->find();
            $chapter = Db::name('chapter')->where(['id' => $detail['cid']])->find();
            if (!empty($book)) {
                $detail['btitle'] = $book['title'];
                $detail['author'] = $book['author'];
            } else {
                $detail['btitle'] = '--';
                $detail['author'] = '--';
            }
            if (!empty($chapter)) {
                $chaptertable = calc_hash_db($detail['bid']); //章节内容表名
                $chapter['content'] = Db::name($chaptertable)->where(['sid' => $detail['cid']])->value('info');
                $chapter['content'] = htmlspecialchars_decode($chapter['content']);
                $chapter['content'] = str_replace($search, $replace, $chapter['content']);
            } else {
                $chapter['content'] = '';
            }
            View::assign('chapter', $chapter);
            View::assign('detail', $detail);
            return view();
        } else {
            throw new \think\exception\HttpException(404, '找不到页面');
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
        $this->model->delChapterVerifyById($id);
    }
}
