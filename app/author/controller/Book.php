<?php

declare(strict_types=1);

namespace app\author\controller;

use app\author\BaseController;
use think\facade\Db;
use think\facade\View;
use think\Image;
use Overtrue\Pinyin\Pinyin;

class Book extends BaseController
{
    //作品列表
    public function index()
    {
        $uid = get_login_author('id');
        $param = get_params();
        $where = ['authorid' => $uid];
        if (isset($param['finish']) && $param['finish']) {
            $where['isfinish'] = $param['finish'];
        }
        if (isset($param['signing']) && $param['signing']) {
            $where['issign'] = ($param['signing'] - 1);
        }
        if (isset($param['status']) && $param['status']) {
            $where['status'] = ($param['status'] - 1);
        }
        $list = Db::name('book')
            ->field('IF(update_time = 0, create_time, update_time) AS order_time,id,update_time,create_time,title,author,authorid,cover,remark,style,ending,genre,subgenre,isfinish,label,label_custom,words,status,editorid,editor,issign,finishtime,outline,chapters')
            ->where($where)
            ->order('order_time desc')
            ->select()
            ->toArray();
        foreach ($list as $k => $v) {
            $words = intval($v['words']);
            if ($words > 1000 && $words < 10000) {
                $words = sprintf("%.1f", $words / 1000) . '千';
            } else if ($words > 10000) {
                $words = sprintf("%.1f", $words / 10000) . '万';
            } else {
                $words = $words . '字';
            }
            $list[$k]['words'] = $words;
        }
        View::assign('list', $list);
        return view();
    }

    //添加作品
    public function add()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $uid = get_login_author('id');
            $user = Db::name('author')->where(['id' => $uid])->find();
            if (empty($user)) {
                to_assign(1, '作者不存在！');
            }
            $param['title'] = strip_tags(trim($param['title']));
            if (empty($param['title'])) {
                to_assign(1, '作品名称未填写！');
            }
            // if (!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9！，。：“”（）—— ]+$/u", $param['title'])) {
            //     to_assign(1, '作品名称有禁用字符！');
            // }
            if (empty($param['genre'])) {
                to_assign(1, '作品大类未选择！');
            }
            if (empty($param['subgenre'])) {
                to_assign(1, '作品小类未选择！');
            }
            $bookname = mb_str_split($param['title']);
            if (count($bookname) > 20) {
                to_assign(1, '作品名称不得大于20个字！');
            }
            $tmp = Db::name('book')->where(['title' => trim($param['title'])])->find();
            if (!empty($tmp)) {
                to_assign(1, '作品名称已被使用！');
            }
            $remark = $param['remark'];
            if (empty($remark)) {
                to_assign(1, '作品简介为空！');
            }
            list($jjnum, $jjstr) = countWordsAndContent($remark, true);
            if (intval($jjnum) < 20) {
                to_assign(1, '作品简介不能低于20字');
            }
            $param['author'] = $user['nickname'];
            $param['authorid'] = $uid;
            if (!isset($param['style'])) {
                $param['style'] = '';
            }
            if (!isset($param['ending'])) {
                $param['ending'] = '';
            }
            if (!isset($param['identity'])) {
                $param['identity'] = '';
            }
            if (!isset($param['image'])) {
                $param['image'] = '';
            }
            if (!isset($param['schools'])) {
                $param['schools'] = '';
            }
            if (!isset($param['element'])) {
                $param['element'] = '';
            }
            if (empty($param['style'])) {
                to_assign(1, '作品风格未选择');
            }
            if (empty($param['ending'])) {
                to_assign(1, '作品结局未选择');
            }
            if (empty($param['identity'])) {
                to_assign(1, '主角身份未选择');
            }
            if (empty($param['image'])) {
                to_assign(1, '主角形象未选择');
            }
            if (empty($param['schools'])) {
                to_assign(1, '作品流派未选择');
            }
            if (empty($param['element'])) {
                to_assign(1, '故事元素未选择');
            }
            $param['status'] = 1;
            $param['isfinish'] = 1;
            // 标签
            $param['label'] = $param['style'] . ',' . $param['ending'] . ',' . ($param['identity'] ? $param['identity'] . ',' : '') . ($param['image'] ? $param['image'] . ',' : '') . ($param['schools'] ? $param['schools'] . ',' : '') . ($param['element'] ? $param['element'] : '');
            unset($param['identity'], $param['image'], $param['schools'], $param['element']);
            $param['create_time'] = time();
            $filename = Pinyin::permalink($param['title'], '');
            $book = Db::name('book')->where(['filename' => $filename])->find();
            $param['filename'] = $filename;
            $insertId = Db::name('book')->strict(false)->field(true)->insertGetId($param);
            if ($insertId !== false) {
                if (!empty($book)) {
                    $filename = $filename . $insertId;
                    Db::name('book')->where('id', $insertId)->strict(false)->field(true)->update(['filename' => $filename]);
                }
                to_assign(0, '添加成功');
            } else {
                to_assign(1, '添加失败');
            }
            // $res = Db::name('book')->strict(false)->field(true)->insertGetId($param);
            // if ($res !== false) {
            //     // $cover = makecover($res);
            //     // if ($cover) {
            //     //     $res = Db::name('book')->where(['id' => $res])->strict(false)->field(true)->update(['cover' => $cover]);
            //     // } else {
            //     //     Db::name('book')->where(['id' => $res])->delete();
            //     //     //删除作品
            //     //     to_assign(1, '封面生成失败');
            //     // }
            //     to_assign(0, '添加成功');
            // } else {
            //     to_assign(1, '添加失败');
            // }
        } else {
            $result = hook("bookTagHook");
            $tags = json_decode($result, true);
            View::assign('tags', $tags['data']);
            $genre = Db::name('category')->where(['pid' => 0, 'status' => 1])->order('ordernum asc')->select()->toArray();
            View::assign('genre', $genre);
            return view();
        }
    }

    //作品详情
    public function detail()
    {
        $param = get_params();
        $id = $param['id'];
        $uid = get_login_author('id');
        $book = Db::name('book')->where(['id' => $id])->find();
        if (empty($book)) {
            to_assign(1, '作品不存在');
        }
        if ($book['authorid'] != $uid) {
            to_assign(1, '作品不存在');
        }
        $chapters = Db::name('chapter')->field('id,title,create_time,wordnum,status')->where(array('bookid' => $book['id']))->order('create_time desc')->limit(10)->select()->toArray();
        $result = hook("bookTagHook");
        $result = json_decode($result, true);
        $tags = $result['data'];
        View::assign('tags', $tags);
        $genre = Db::name('category')->where(['pid' => 0, 'status' => 1])->order('ordernum asc')->select()->toArray();
        View::assign('genre', $genre);
        $subgenre = Db::name('category')->where(['pid' => $book['genre'], 'status' => 1])->order('ordernum asc')->select()->toArray();
        if (empty($book['editor']) && empty($book['editorid'])) {
            $book['editor'] = Db::name('admin')->where(['id' => $book['editorid']])->value('nickname');
        }
        $labels = explode(',', $book['label']);
        $book['labe_identity'] = $book['labe_image'] = $book['labe_schools'] = $book['labe_element'] = '';
        if (empty($book['style'])) {
            foreach ($tags['style']['data'] as $v) {
                if (in_array($v, $labels)) {
                    $book['style'] = $v;
                    unset($tags['style']);
                    break;
                }
            }
        }
        if (empty($book['ending'])) {
            foreach ($tags['ending']['data'] as $v) {
                if (in_array($v, $labels)) {
                    $book['ending'] = $v;
                    unset($tags['ending']);
                    break;
                }
            }
        }
        foreach ($tags['identity']['data'] as $v) {
            if (in_array($v, $labels)) {
                $book['labe_identity'] = $v;
                break;
            }
        }
        foreach ($tags['image']['data'] as $v) {
            if (in_array($v, $labels)) {
                $book['labe_image'] = $v;
                break;
            }
        }
        foreach ($tags['schools']['data'] as $v) {
            if (in_array($v, $labels)) {
                $book['labe_schools'] = $v;
                break;
            }
        }
        foreach ($tags['element']['data'] as $v) {
            if (in_array($v, $labels)) {
                $book['labe_element'] = $v;
                break;
            }
        }
        View::assign('subgenre', $subgenre);
        View::assign('chapters', $chapters);
        View::assign('book', $book);
        return view();
    }

    public function getcate()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $pid = intval($param['pid']);
            $list = [];
            if (empty($pid)) {
                return to_assign(1, '参数错误');
            }
            $list = Db::name('category')->where(['pid' => 0, 'status' => 1])->order('ordernum asc')->select()->toArray();
            return table_assign(0, '', ['data' => $list]);
        } else {
            return to_assign(1, '错误');
        }
    }

    public function getsmallcate()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $pid = intval($param['pid']);
            $list = [];
            if (empty($pid)) {
                return to_assign(1, '参数错误');
            }
            $list = Db::name('category')->where(['pid' => $pid, 'status' => 1])->order('ordernum asc')->select()->toArray();
            return table_assign(0, '', ['data' => $list]);
        } else {
            return to_assign(1, '错误');
        }
    }

    //作品编辑
    public function edit()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $uid = get_login_author('id');
            $id = $param['id'];
            if (empty($id)) {
                to_assign(1, '参数错误');
            }
            $book = Db::name('book')->where(['id' => $id])->find();
            if (empty($book)) {
                to_assign(1, '作品不存在');
            }
            if ($book['authorid'] != $uid) {
                to_assign(1, '作品不存在');
            }
            if ($book['isfinish'] == 2) {
                to_assign(1, '作品已完结，禁止修改！');
            }
            $user = Db::name('author')->where(['id' => $uid])->find();
            if (empty($user)) {
                to_assign(1, '作者不存在！');
            }
            //更新作者笔名
            if ($book['author'] != $user['nickname']) {
                $param['author'] = $user['nickname'];
            }
            $param['title'] = strip_tags(trim($param['title']));
            if (empty($param['title'])) {
                to_assign(1, '作品名称未填写！');
            }
            // if (!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9！，。：“”（）—— ]+$/u", $param['title'])) {
            //     to_assign(1, '作品名称有禁用字符！');
            // }
            if (empty($param['genre'])) {
                to_assign(1, '作品大类未选择！');
            }
            if (empty($param['subgenre'])) {
                to_assign(1, '作品小类未选择！');
            }
            $bookname = mb_str_split($param['title']);
            if (count($bookname) > 20) {
                to_assign(1, '作品名称不得大于20个字！');
            }
            $remark = $param['remark'];
            if (empty($remark)) {
                to_assign(1, '作品简介为空！');
            }
            list($jjnum, $jjstr) = countWordsAndContent($remark, true);
            if (intval($jjnum) < 20) {
                to_assign(1, '作品简介不能低于20字');
            }
            //如果作品名称改了则验证作品名称是否可用
            if ($book['title'] != $param['title']) {
                $tmp = Db::name('book')->where(['title' => trim($param['title']), ['authorid', '<>', $uid]])->find();
                if (!empty($tmp)) {
                    to_assign(1, '作品名称已被使用！');
                }
                if (intval($book['issign']) >= 1) {
                    to_assign(1, '签约作品改名请联系编辑！');
                }
            }
            if (!isset($param['style'])) {
                $param['style'] = '';
            }
            if (!isset($param['ending'])) {
                $param['ending'] = '';
            }
            if (!isset($param['identity'])) {
                $param['identity'] = '';
            }
            if (!isset($param['image'])) {
                $param['image'] = '';
            }
            if (!isset($param['schools'])) {
                $param['schools'] = '';
            }
            if (!isset($param['element'])) {
                $param['element'] = '';
            }
            if (empty($param['style'])) {
                to_assign(1, '作品风格未选择');
            }
            if (empty($param['ending'])) {
                to_assign(1, '作品结局未选择');
            }
            if (empty($param['identity'])) {
                to_assign(1, '主角身份未选择');
            }
            if (empty($param['image'])) {
                to_assign(1, '主角形象未选择');
            }
            if (empty($param['schools'])) {
                to_assign(1, '作品流派未选择');
            }
            if (empty($param['element'])) {
                to_assign(1, '故事元素未选择');
            }
            // 标签
            $param['label'] = $param['style'] . ',' . $param['ending'] . ',' . ($param['identity'] ? $param['identity'] . ',' : '') . ($param['image'] ? $param['image'] . ',' : '') . ($param['schools'] ? $param['schools'] . ',' : '') . ($param['element'] ? $param['element'] : '');
            unset($param['identity'], $param['image'], $param['schools'], $param['element']);
            $param['update_time'] = time();
            if ($param['title'] != $book['title']) {
                $filename = Pinyin::permalink($param['title'], '');
                $filenamebook = Db::name('book')->where(['filename' => $filename, ['id', '<>', $book['id']]])->find();
                if (!empty($filenamebook)) {
                    $filename = $filename . $book['id'];
                }
                $param['filename'] = $filename;    
            }
            $res = Db::name('book')->where(['id' => $book['id']])->strict(false)->field(true)->update($param);
            if ($res !== false) {
                to_assign(0, '编辑成功');
            } else {
                to_assign(1, '编辑失败');
            }
        } else {
            return to_assign(1, '错误');
        }
    }
}
