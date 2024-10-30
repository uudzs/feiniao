<?php

declare(strict_types=1);

namespace app\author\controller;

use app\author\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Route;
use PhpOffice\PhpWord\IOFactory;
use think\exception\ValidateException;
use think\facade\App;

class Chapter extends BaseController
{
    //作品列表
    public function index()
    {
        $uid = get_login_author('id');
        $param = get_params();
        $bid = intval($param['bid']);
        if (empty($bid)) {
            return to_assign(1, '参数为空');
        }
        $book = Db::name('book')->where(['id' => $bid])->find();
        if (empty($book)) {
            return to_assign(1, '作品不存在');
        }
        if ($book['authorid'] != $uid) {
            return to_assign(1, '作品不存在');
        }
        if (request()->isAjax()) {
            $search = isset($param['search']) ? trim($param['search']['value']) : ''; //搜索     
            $draw = isset($param['draw']) ? intval($param['draw']) : ''; //这个值直接返回给前台
            $start = isset($param['start']) ? intval($param['start']) : 0; //分页下标值
            $length = isset($param['length']) ? intval($param['length']) : 10; //分页显示条数
            $searchData = isset($param['searchData']) ? intval($param['searchData']) : ''; //条件1：已上架2：定时发布3：草稿箱4：被拒绝
            $where = ['bookid' => $bid];
            $order = 'chaps desc';
            //已上架
            if ($searchData == 1) {
                $where['status'] = 1;
            }
            //定时发布
            if ($searchData == 2) {
                $where['status'] = 0;
                $where[] = ['trial_time', '>', 0];
                $order = 'trial_time desc';
            }
            //草稿箱
            if ($searchData == 3) {
                $where = [
                    'bid' => $bid,
                    'aid' => $uid
                ];
                //搜索  
                if (!empty($search)) {
                    $where[] = ['title', 'like', '%' . $search . '%'];
                }
                $total = Db::name('chapter_draft')->where($where)->count();
                $list = Db::name('chapter_draft')
                    ->field('id,bid,chaps,title,wordnum,create_time,update_time')
                    ->where($where)
                    ->order($order)
                    ->limit($start, $length)
                    ->select();
                $list = $list->toArray();
                foreach ($list as $k => $v) {
                    $list[$k]['editurl'] = (string) Route::buildUrl('chapter/edit', ['tid' => $v['id']]);
                    $list[$k]['trial_time'] = 0;
                    $list[$k]['status'] = 0;
                    $list[$k]['verify'] = 0;
                }
                echo json_encode([
                    "draw" => $draw,
                    'data' => $list,
                    'recordsTotal' => $total,
                    'recordsFiltered' => $total,
                    'code' => 0,
                    'msg' => $search
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            //被拒绝
            if ($searchData == 4) {
                $where['verify'] = 2;
            }
            //搜索  
            if (!empty($search)) {
                $where[] = ['title', 'like', '%' . $search . '%'];
            }
            $total = Db::name('chapter')->where($where)->count();
            $list = Db::name('chapter')
                ->field('id,bookid as bid,chaps,title,status,draft,verify,wordnum,create_time,update_time,verifyresult,verifypeople,verifytime,trial_time')
                ->where($where)
                ->order($order)
                ->limit($start, $length)
                ->select();
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['editurl'] = (string) Route::buildUrl('chapter/edit', ['id' => $v['id']]);
                if ($v['verify'] != 1) {
                    $verify = Db::name('chapter_verify')->where('cid', $v['id'])->find();
                    if (!empty($verify)) {
                        $list[$k]['chaps'] = $verify['chaps'];
                        $list[$k]['title'] = $verify['title'];
                        $list[$k]['wordnum'] = $verify['wordnum'];
                        $list[$k]['create_time'] = $verify['create_time'];
                    }
                }
            }
            echo json_encode([
                "draw" => $draw,
                'data' => $list,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'code' => 0,
                'msg' => $search
            ], JSON_UNESCAPED_UNICODE);
            exit;
            //return table_assign(0, '', ['data' => $list]);
        } else {
            $refuse = ['bookid' => $bid, 'verify' => 2]; //被拒绝章节
            $refusecount = Db::name('chapter')->where($refuse)->count();
            $already = ['bookid' => $bid, 'status' => 1]; //已发布
            $already[] = ['verify', '<>', 2];
            $alreadycount = Db::name('chapter')->where($already)->count();
            $timing = ['bookid' => $bid, 'status' => 0]; //定时发布
            $timing[] = ['trial_time', '>', 0];
            $timingcount = Db::name('chapter')->where($timing)->count();
            $draftcount = Db::name('chapter_draft')->where(['aid' => $uid, 'bid' => $bid])->count();
            $allcount = Db::name('chapter')->where(['bookid' => $bid])->count();
            View::assign('refusecount', $refusecount);
            View::assign('alreadycount', $alreadycount);
            View::assign('timingcount', $timingcount);
            View::assign('draftcount', $draftcount);
            View::assign('allcount', $allcount);
            View::assign('book', $book);
            return view();
        }
    }

    //添加章节
    public function add()
    {
        $param = get_params();
        $uid = get_login_author('id');
        $bid = intval($param['bid']);
        $user = Db::name('author')->where(['id' => $uid])->find();
        if (empty($user)) {
            to_assign(1, '作者不存在！');
        }
        $book = Db::name('book')->where(['id' => $bid])->find();
        if (empty($book)) {
            to_assign(1, '作品不存在');
        }
        if ($book['status'] != 1) {
            to_assign(1, '作品被禁止');
        }
        if ($book['isfinish'] == 2) {
            to_assign(1, '作品已完结');
        }
        if (request()->isAjax()) {
        } else {
            $info = [];
            $chapter = Db::name('chapter')->where(array('bookid' => $book['id']))->order('chaps desc')->value('chaps');
            if (!empty($chapter)) {
                $serial = intval($chapter) + 1;
            } else {
                $serial = 1;
            }
            $info['chaps'] = $serial;
            $info['cid'] = 0;
            $info['draftid'] = 0; //草稿箱ID
            $info['title'] = '';
            $info['wordnum'] = 0;
            $info['content'] = '';
            $info['chapstitle'] = '第' . numConvertWord($serial) . '章 '; //章节序号名称
            //取草稿箱
            $draftlist = Db::name('chapter_draft')->where(['aid' => $uid, 'bid' => $book['id']])->select();
            $draftlist = $draftlist->toArray();
            $draftchapter = [];
            $search = array("&nbsp;", "<br>");
            $replace = array(" ", "\n");
            if (!empty($draftlist)) {
                foreach ($draftlist as $k => $v) {
                    //当前直接使用
                    if (intval($info['chaps']) == intval($v['chaps'])) {
                        $v['content'] = htmlspecialchars_decode($v['content']);
                        $v['content'] = str_replace($search, $replace, $v['content']);
                        list($info['wordnum'], $info['content']) = countWordsAndContent($v['content'], true);
                        //$info['cid'] = $v['cid'];
                        //$info['chapstitle'] = '第' . numConvertWord($v['chaps']) . '章 '; //章节序号名称                       
                        $info['title'] = $v['title'];
                        $info['draftid'] = $v['id'];
                        continue;
                    }
                    $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                    $v['update_time'] = $v['update_time'] ? date('Y-m-d H:i:s', $v['update_time']) : 0;
                    $draftchapter[] = $v;
                    unset($v['content']);
                }
            }
            unset($draftlist);
            View::assign('draftchapter', $draftchapter);
            View::assign('book', $book);
            View::assign('info', $info);
            return view('chapter');
        }
    }

    public function edit()
    {
        $param = get_params();
        $uid = get_login_author('id');
        $id = isset($param['id']) ? intval($param['id']) : 0;
        $tid = isset($param['tid']) ? intval($param['tid']) : 0;
        if (empty($id) && empty($tid)) {
            to_assign(1, '参数错误！');
        }
        $verify = [];
        if ($id > 0) {
            $chapter = Db::name('chapter_verify')->where(['cid' => $id])->find();
            if (empty($chapter)) {
                $chapter = Db::name('chapter')->where(array('id' => $id))->find();
                if (empty($chapter)) {
                    to_assign(1, '章节不存在！');
                }
                $bid = $chapter['bookid'];
            } else {
                $chapter['id'] = $chapter['cid'];
                $bid = $chapter['bid'];
                $verify = $chapter;
            }
        }
        if ($tid > 0) {
            $chapter = Db::name('chapter_draft')->where(array('id' => $tid))->find();
            if (empty($chapter)) {
                to_assign(1, '章节不存在！');
            }
            $bid = $chapter['bid'];
        }
        $user = Db::name('author')->where(['id' => $uid])->find();
        if (empty($user)) {
            to_assign(1, '作者不存在！');
        }
        $book = Db::name('book')->where(['id' => $bid])->find();
        if (empty($book)) {
            to_assign(1, '作品不存在');
        }
        if ($book['status'] != 1) {
            to_assign(1, '作品被禁止');
        }
        if (request()->isAjax()) {
        } else {
            if ($tid > 0) {
                $info = [
                    'chaps' => $chapter['chaps'],
                    'cid' => $chapter['cid'],
                    'draftid' => $chapter['id'],
                    'title' => $chapter['title'],
                    'wordnum' => $chapter['wordnum'],
                    'chapstitle' => '第' . numConvertWord($chapter['chaps']) . '章 '
                ];
                $info['content'] = htmlspecialchars_decode($chapter['content']);
                $search = array("&nbsp;", "<br>");
                $replace = array(" ", "\n");
                $info['content'] = str_replace($search, $replace, $info['content']);
            } else {
                $info = [
                    'chaps' => $chapter['chaps'],
                    'cid' => $chapter['id'],
                    'draftid' => 0,
                    'title' => $chapter['title'],
                    'wordnum' => $chapter['wordnum'],
                    'chapstitle' => '第' . numConvertWord($chapter['chaps']) . '章 '
                ];
                if (empty($verify)) {
                    $chaptertable = calc_hash_db($book['id']); //章节内容表名
                    $content = Db::name($chaptertable)->where(['sid' => $chapter['id']])->find();
                    if (empty($content)) {
                        to_assign(1, '章节内容不存在');
                    }
                    $info['content'] = $content['info'];
                } else {
                    $info['content'] = $chapter['content'];
                }
                $info['content'] = htmlspecialchars_decode($info['content']);
                $search = array("&nbsp;", "<br>");
                $replace = array(" ", "\n");
                $info['content'] = str_replace($search, $replace, $info['content']);
            }
            if ($tid > 0) {
                //取草稿箱
                $where = ['aid' => $uid, 'bid' => $book['id']];
                $where[] = ['id', '<>', $tid];
                $draftlist = Db::name('chapter_draft')->where($where)->select();
                $draftlist = $draftlist->toArray();
                $draftchapter = [];
                $search = array("&nbsp;", "<br>");
                $replace = array(" ", "\n");
                if (!empty($draftlist)) {
                    foreach ($draftlist as $k => $v) {
                        $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                        $v['update_time'] = $v['update_time'] ? date('Y-m-d H:i:s', $v['update_time']) : 0;
                        $draftchapter[] = $v;
                        unset($v['content']);
                    }
                }
                unset($draftlist);
            } else {
                //取草稿箱
                $draftlist = Db::name('chapter_draft')->where(['aid' => $uid, 'bid' => $book['id']])->select();
                $draftlist = $draftlist->toArray();
                $draftchapter = [];
                $search = array("&nbsp;", "<br>");
                $replace = array(" ", "\n");
                if (!empty($draftlist)) {
                    foreach ($draftlist as $k => $v) {
                        //当前直接使用
                        if (intval($info['chaps']) == intval($v['chaps']) && empty($verify)) {
                            $v['content'] = htmlspecialchars_decode($v['content']);
                            $v['content'] = str_replace($search, $replace, $v['content']);
                            list($info['wordnum'], $info['content']) = countWordsAndContent($v['content'], true);
                            $info['title'] = $v['title'];
                            $info['draftid'] = $v['id'];
                            continue;
                        }
                        $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                        $v['update_time'] = $v['update_time'] ? date('Y-m-d H:i:s', $v['update_time']) : 0;
                        $draftchapter[] = $v;
                        unset($v['content']);
                    }
                }
                unset($draftlist);
            }
            View::assign('draftchapter', $draftchapter);
            View::assign('book', $book);
            View::assign('info', $info);
            return view('chapter');
        }
    }

    //章节入库主体
    private static function chapter($param)
    {
        $uid = get_login_author('id');
        $bid = intval($param['bid']);
        $id = intval($param['id']); //章节ID
        $title = trim($param['title']);
        $member = Db::name('author')->where(['id' => $uid])->find();
        if (empty($member)) {
            to_assign(1, '作者不存在！');
        }
        if (intval($member['status']) != 1) {
            to_assign(1, '用户被禁止！');
        }
        $book = Db::name('book')->where(['id' => $bid, 'authorid' => $member['id']])->find();
        if (empty($book)) {
            to_assign(1, '作品不存在');
        }
        if ($book['isfinish'] == 2) {
            to_assign(1, '作品已完结');
        }
        if ($book['status'] != 1) {
            to_assign(1, '作品被禁止');
        }
        if (empty($title)) {
            to_assign(1, '章节名称为空！');
        }
        // if (!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9！，。：“”（）—— ]+$/u", $title)) {
        //     to_assign(1, '章节名称有禁用字符！');
        // }
        //如果是修改
        if ($id > 0) {
            $chapter = Db::name('chapter')->where(['id' => $id])->find();
            if (empty($chapter)) {
                to_assign(1, '章节不存在');
            }
            if (intval($chapter['bookid']) != $book['id']) {
                to_assign(1, '章节有误');
            }
            $verify = Db::name('chapter_verify')->where(['cid' => $id])->find();
            if (!empty($verify)) {
                to_assign(1, '此章节待审中禁止修改！');
            }
            //有改章节名时，判断是否有重复名称
            if ($chapter['title'] != $title) {
                $tmp = Db::name('chapter')->where(['title' => $title, 'bookid' => $book['id']])->find();
                if (!empty($tmp)) {
                    to_assign(1, '章节名称已被使用！');
                }
            }
        } else {
            $istitle = Db::name('chapter')->where(['bookid' => $book['id'], 'title' => $title])->find();
            if (!empty($istitle)) {
                to_assign(1, '章节名称重复，无法发布。');
            }
        }
        $info = $param['content'];
        list($wordnum, $content) = countWordsAndContent($info, true);
        if (empty($content) || empty($wordnum)) {
            to_assign(1, '章节内容为空，无法发布。');
        }
        if ($wordnum < 1000) {
            to_assign(1, '章节内容不能少于1000字');
        }
        if ($wordnum > 10000) {
            to_assign(1, '章节内容字数大于10000字，无法发布。');
        }
        $param['draft'] = 0; //非草稿       
        $param['wordnum'] = $wordnum;
        $param['verify'] = 0;
        $param['bookid'] = $book['id'];
        $param['chaps'] = intval($param['chaps']);
        $param['create_time'] = time();
        $draftid = intval($param['draftid']); //草稿ID
        if ($draftid > 0) {
            $draft = Db::name('chapter_draft')->where(['id' => $draftid])->find();
            if (empty($draft)) {
                to_assign(1, '草稿不存在');
            }
            if ($draft['bid'] != $book['id'] || $draft['aid'] != $member['id']) {
                to_assign(1, '草稿信息有误');
            }
        }
        //编辑不更新的内容
        if ($id > 0) {
            //$param['trial_time'] = 0;
            $param['update_time'] = time();
            unset($param['thirdchapterid'], $param['create_time'], $param['bookid'], $param['status']);
        } else {
            $chaps = Db::name('chapter')->where(array('bookid' => $book['id']))->order('chaps desc')->value('chaps');
            if (!empty($chaps)) {
                $serial = intval($chaps) + 1;
            } else {
                $serial = 1;
            }
            //以新序号为准
            if ($param['chaps'] != $serial) {
                $param['chaps'] = $serial;
            }
        }
        unset($param['id'], $param['bid'], $param['content'], $param['draftid']); 
        $param['ip'] = app('request')->ip();
        $chaptertable = calc_hash_db($book['id']); //章节内容表名
        //修改
        if ($id > 0) {
            $sid = $id;
            if (isset($chapter) && !empty($chapter) && intval($chapter['verify']) == 1) {
                Db::name('chapter')->where(['id' => $id])->strict(false)->field(true)->update(['verify' => 0, 'verifytime' => 0, 'verifypeople' => '']);
                //入审核库
                $verify = [
                    'cid' => $id,
                    'aid' => $uid,
                    'bid' => $book['id'],
                    'title' => $title,
                    'chaps' => $param['chaps'],
                    'content' => $content,
                    'wordnum' => $wordnum,
                    'create_time' => time()
                ];
                Db::name('chapter_verify')->strict(false)->field(true)->insertGetId($verify);
            } else {
                unset($param['chaps']);
                Db::name('chapter')->where(['id' => $id])->strict(false)->field(true)->update($param);
                Db::name($chaptertable)->where(['sid' => $id])->strict(false)->field(true)->update(['info' => $content]);
            }
            Db::name('chapter_draft')->where('cid', $id)->delete(); //删除草稿
        } else {
            $param['verifytime'] = 9999; //新章节
            $sid = Db::name('chapter')->strict(false)->field(true)->insertGetId($param);
            if ($sid !== false) {
                Db::name($chaptertable)->strict(false)->field(true)->insertGetId(['sid' => $sid, 'info' => $content]);
            } else {
                to_assign(1, '操作失败');
            }
        }
        if ($sid !== false) { 
            //删除草稿箱
            if ($draftid > 0) {
                Db::name('chapter_draft')->where('id', $draftid)->delete();
            }
            to_assign(0, '操作成功', ['data' => $sid]);
        } else {
            to_assign(1, '操作失败');
        }
    }

    //发布章节
    public function release()
    {
        $param = get_params();
        $uid = get_login_author('id');
        $id = intval($param['id']); //章节ID
        if (empty($id)) {
            $param['status'] = 1;
        }
        self::chapter($param);
    }
    //定时发布
    public function timerrelease()
    {
        $param = get_params();
        $uid = get_login_author('id');
        $trial_time = $param['trial_time'];
        if (empty($trial_time)) {
            to_assign(1, '定时发布时间未设置');
        }
        $trial_time = strtotime($trial_time);
        if ($trial_time < time()) {
            to_assign(1, '发布时间发须大于当前时间');
        }
        $param['status'] = 0;
        $param['trial_time'] = $trial_time;
        self::chapter($param);
    }

    //获取草稿箱内容
    public function getdraftcontent()
    {
        $param = get_params();
        $uid = get_login_author('id');
        $id = intval($param['id']);
        if (empty($id)) {
            to_assign(1, '参数为空');
        }
        $chapter = Db::name('chapter_draft')->where(['id' => $id, 'aid' => $uid])->find();
        if (empty($chapter)) {
            to_assign(1, '内容不存在！');
        }
        $search = array("&nbsp;", "<br>");
        $replace = array(" ", "\n");
        $chapter['content'] = htmlspecialchars_decode($chapter['content']);
        $chapter['content'] = str_replace($search, $replace, $chapter['content']);
        list($chapter['wordnum'], $chapter['content']) = countWordsAndContent($chapter['content'], true);
        $chapter['chapstitle'] = '第' . numConvertWord($chapter['chaps']) . '章 '; //章节序号名称
        return table_assign(0, '', ['data' => $chapter]);
    }

    //自动保存草稿箱
    public function autosavedraft()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $uid = get_login_author('id');
            $draftid = intval($param['draftid']);
            $id = intval($param['id']);
            $bid = intval($param['bid']);
            $chaps = intval($param['chaps']);
            $title = trim($param['title']);
            $content = trim($param['content']);
            if (empty($bid) || empty($chaps) || empty($content)) {
                to_assign(1, '必要数据为空');
            }
            $book = Db::name('book')->where(['id' => $bid])->find();
            if (empty($book)) {
                to_assign(1, '作品不存在');
            }
            if ($book['status'] != 1) {
                to_assign(1, '作品被禁止');
            }
            if ($book['isfinish'] == 2) {
                to_assign(1, '作品已完结');
            }
            list($wordnum, $content) = countWordsAndContent($content, true);
            if ($draftid > 0) {
                $draft = Db::name('chapter_draft')->where(['id' => $draftid])->find();
                if (empty($draft)) {
                    to_assign(1, '草稿不存在');
                }
                if ($draft['aid'] != $uid || $draft['bid'] != $bid || $draft['chaps'] != $chaps) {
                    to_assign(1, '没有保存权限');
                }
                Db::name('chapter_draft')->where(['id' => $draftid])->strict(false)->field(true)->update([
                    'cid' => $id,
                    // 'chaps' => $chaps,
                    'title' => $title,
                    'wordnum' => $wordnum,
                    'content' => $content,
                    'update_time' => time()
                ]);
                to_assign(0, '保存成功', ['datetime' => date('Y-m-d H:i:s', time())]);
            } else {
                $draft = Db::name('chapter_draft')->where(['bid' => $bid, 'chaps' => $chaps, 'aid' => $uid])->find();
                if (!empty($draft)) {
                    Db::name('chapter_draft')->where(['id' => $draft['id']])->strict(false)->field(true)->update([
                        'cid' => $id,
                        'chaps' => $chaps,
                        'title' => $title,
                        'wordnum' => $wordnum,
                        'content' => $content,
                        'update_time' => time()
                    ]);
                    to_assign(0, '保存成功', ['tid' => $draft['id'], 'datetime' => date('Y-m-d H:i:s', time())]);
                } else {
                    $tid = Db::name('chapter_draft')->strict(false)->field(true)->insertGetId([
                        'cid' => $id,
                        'aid' => $uid,
                        'bid' => $bid,
                        'chaps' => $chaps,
                        'title' => $title,
                        'wordnum' => $wordnum,
                        'content' => $content,
                        'create_time' => time()
                    ]);
                    if ($tid !== false) {
                        to_assign(0, '保存成功', ['tid' => $tid, 'datetime' => date('Y-m-d H:i:s', time())]);
                    } else {
                        to_assign(1, '保存失败');
                    }
                }
            }
        } else {
            to_assign(1, '访问错误');
        }
    }

    //删除章节
    public function delchapter()
    {
        to_assign(1, '禁止删除');
        $param = get_params();
        $uid = get_login_author('id');
        $id = intval($param['id']);
        if (empty($id)) {
            to_assign(1, '参数为空');
        }
        $chapter = Db::name('chapter')->where(['id' => $id])->find();
        if (empty($chapter)) {
            to_assign(1, '章节不存在！');
        }
        $book = Db::name('book')->where(['id' => $chapter['bookid']])->find();
        if (empty($book)) {
            to_assign(1, '作品不存在');
        }
        if ($uid != $book['authorid']) {
            to_assign(1, '没有权限');
        }
        if ($book['isfinish'] == 2) {
            to_assign(1, '完结作品不可删除');
        }
        //删除
        if (Db::name('chapter')->delete($id) !== false) {
            $words = Db::name('chapter')->where(array('bookid' => $book['id'], ['verify', 'in', '0,1']))->sum('wordnum');
            $booksave['words'] = $words;
            $booksave['chapters'] = Db::name('chapter')->where(array('bookid' => $book['id'], ['verify', 'in', '0,1']))->count();
            $booksave['update_time'] = time();
            //如果是最新章节
            if ($book['newchapter_id'] == $chapter['thirdchapterid']) {
                $newchapter = Db::name('chapter')->where(array('bookid' => $book['id'], 'status' => 1))->order('chaps desc')->find();
                if (!empty($newchapter)) {
                    $booksave['newchaptertime'] = $newchapter['update_time'] ?: $newchapter['create_time'];
                    $booksave['newchapter_id'] = $newchapter['thirdchapterid'] ?: $newchapter['sid'];
                }
            }
            //更新字数，要判断是否需要审核
            $res = Db::name('book')->where(['id' => $book['id']])->strict(false)->field(true)->update($booksave);
            Db::name('chapter_draft')->where(['cid' => $chapter['id']])->delete(); //删除草稿箱
            return to_assign(0, "删除成功");
        } else {
            return to_assign(1, "删除失败");
        }
    }

    //删除草稿相箱内容
    public function deldraft()
    {
        $param = get_params();
        $uid = get_login_author('id');
        $id = intval($param['id']);
        if (empty($id)) {
            to_assign(1, '参数为空');
        }
        $draft = Db::name('chapter_draft')->where(['id' => $id, 'aid' => $uid])->find();
        if (empty($draft)) {
            to_assign(1, '内容不存在！');
        }
        $book = Db::name('book')->where(['id' => $draft['bid']])->find();
        if (empty($book)) {
            to_assign(1, '作品不存在');
        }
        if ($uid != $book['authorid']) {
            to_assign(1, '没有权限');
        }
        if (!empty($draft['cid'])) {
            $chapter = Db::name('chapter')->where(array('id' => $draft['cid']))->find();
            if ($chapter['bookid'] == $book['id']) {
                if (!empty($chapter) && intval($chapter['draft']) == 1 && intval($chapter['status']) != 1 && intval($chapter['verify']) != 1) {
                    if (Db::name('chapter')->delete($chapter['id']) === false) {
                        return to_assign(1, "删除失败");
                    }
                }
            }
        }
        if (Db::name('chapter_draft')->delete($id) !== false) {
            return to_assign(0, "删除成功");
        } else {
            return to_assign(1, "删除失败");
        }
    }

    //字数统计
    public function wordcount()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $content = $param['content'];
            list($wordnum, $str) = countWordsAndContent($content, true);
            return table_assign(0, '', ['data' => $wordnum]);
        } else {
            to_assign(1, '提交错误');
        }
    }

    //导入word
    public function importword()
    {
        if (request()->isAjax()) {
            $param = get_params();
            if (empty($param['bid'])) {
                return to_assign(1, '作品ID不存在！');
            }
            if (empty($param['wordfileid'])) {
                return to_assign(1, '上传文件不存在！');
            }
            $uid = get_login_author('id');
            $book = Db::name('book')->where(['id' => $param['bid']])->find();
            if (empty($book)) {
                return to_assign(1, '作品不存在');
            }
            if ($book['status'] != 1) {
                return to_assign(1, '作品被禁止');
            }
            if ($book['authorid'] != $uid) {
                return to_assign(1, '作品不存在');
            }
            $allcount = Db::name('chapter')->where(['bookid' => $param['bid']])->count();
            if (intval($allcount) >= 1) {
                return to_assign(1, '已发布章节的作品禁止导入，请先删除章节。');
            }
            $file = Db::name('file')->where(['id' => $param['wordfileid']])->find();
            if (empty($file)) {
                return to_assign(1, '上传文件不存在！');
            }
            $chaptertable = calc_hash_db($book['id']); //章节内容表名
            try {
                $filePath = App::getRootPath() . 'public' . $file['filepath'];
                if (!is_file($filePath)) {
                    return to_assign(1, '文件不存在！');
                }
                // 读取Word文档
                $phpWord = IOFactory::load($filePath);
                // 遍历文档内容
                $texts = [];
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $texts[] = $element->getText();
                        }
                    }
                }
                if (empty($texts)) {
                    return to_assign(1, '文件内容为空！');
                }
                $chapter = [];
                $str = $title = '';
                foreach ($texts as $k => $v) {
                    if (preg_match_all("/([第]?[\d一二三四五六七八九零十百千]+[章节])([^\r\n]+)/u", $v, $arr)) {
                        if ($title) {
                            $chapter[] = [
                                'title' => $title,
                                'content' => $str
                            ];
                            $str = '';
                        }
                        $title = $v;
                    } else {
                        $str .= $v . "\n";
                    }
                    if (!isset($texts[$k + 1])) {
                        $chapter[] = [
                            'title' => $title,
                            'content' => $str
                        ];
                    }
                    // if (preg_match("/第[0-9一二两三四五六七八九十百千万]*[章节]/i", $v, $matches)) {
                    //     if ($title) {
                    //         $chapter[] = [
                    //             'title' => $title,
                    //             'content' => $str
                    //         ];
                    //         $str = '';
                    //     }
                    //     $title = $v;
                    // } else {
                    //     $str .= $v . "\n";
                    // }
                    // if (!isset($texts[$k + 1])) {
                    //     $chapter[] = [
                    //         'title' => $title,
                    //         'content' => $str
                    //     ];
                    // }
                }
                if (empty($chapter)) {
                    return to_assign(1, '未匹配到章节！');
                }
                $success = $skip = 0;//成功数|跳过数
                foreach ($chapter as $k => $v) {
                    $istitle = Db::name('chapter')->where(['bookid' => $book['id'], 'title' => $v['title']])->find();
                    if (!empty($istitle)) {
                        $skip++;
                        continue;
                    }
                    list($wordnum, $content) = countWordsAndContent($v['content'], true);
                    $data = [
                        'bookid' => $book['id'],
                        'title' => $v['title'],
                        'chaps' => $k + 1,
                        'status' => 1,
                        'draft' => 0,
                        'verify' => 0,
                        'trial_time' => 0,
                        'verifyresult' => '',
                        'verifytime' => 9999,//新章节
                        'wordnum' => $wordnum,
                        'create_time' => time(),
                    ];
                    $sid = Db::name('chapter')->strict(false)->field(true)->insertGetId($data);
                    if ($sid !== false) {
                        Db::name($chaptertable)->strict(false)->field(true)->insertGetId(['sid' => $sid, 'info' => $content]);
                        $success++;
                    }
                }
                return to_assign(0, '导入成功' . $success . '章，跳过重复章节' . $skip . '章。');
            } catch (ValidateException $e) {
                // 验证失败的异常信息会被捕获并输出
                return to_assign(1, $e->getMessage());
            }
        } else {
            return to_assign(1, '错误访问');
        }
    }
}