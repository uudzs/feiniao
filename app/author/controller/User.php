<?php

declare(strict_types=1);

namespace app\author\controller;

use app\author\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Session;

class User extends BaseController
{
    public function index()
    {
        $uid = get_login_author('id');
        $allbook = Db::name('book')->where(['authorid' => $uid])->count(); //全部作品
        $livebook = Db::name('book')->where(['authorid' => $uid, 'status' => 1])->count(); //上架作品
        $finishbook = Db::name('book')->where(['authorid' => $uid, 'isfinish' => 2])->count(); //完结作品
        $signingbook = Db::name('book')->where(['authorid' => $uid, 'issign' => 1])->count(); //签约作品
        $bookupdate = $allbook - $finishbook; //更新作品
        View::assign('allbook', $allbook);
        View::assign('finishbook', $finishbook);
        View::assign('bookupdate', $bookupdate);
        View::assign('signingbook', $signingbook);
        View::assign('livebook', $livebook);
        return view();
    }

    //基本设置
    public function basic()
    {
        $uid = get_login_author('id');
        $userInfo = Db::name('author')->where(['id' => $uid])->find();
        if (strpos($userInfo['nickname'], (get_system_config('web', 'title') . '_')) !== false) {
            $userInfo['nicknamemodify'] = 1;
        } else {
            $userInfo['nicknamemodify'] = 0;
        }
        // 查询未签协的银行卡更改协议
        $sign = Db::name('author_sign')->where(['uid' => $uid, 'rtype' => 2, ['status', '<>', 1]])->count();
        View::assign('sign', $sign);
        View::assign('userInfo', $userInfo);
        return view();
    }

    //保存修改
    public function save()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $uid = get_login_author('id');
            if (empty($param)) {
                to_assign(1, '没有要修改的信息！');
            }
            $member = Db::name('author')->where(['id' => $uid])->find();
            if (empty($member)) {
                to_assign(1, '用户不存在！');
            }
            if ($member['status'] != 1) {
                to_assign(1, '用户被禁止！');
            }
            if (strpos($member['nickname'], (get_system_config('web', 'title') . '_')) !== false && empty($param['nickname'])) {
                to_assign(1, '请设置笔名！');
            }
            //如果还未认证过实名
            if (intval($member['authstate']) != 1) {
                if (!isset($param['true_name']) || empty($param['true_name'])) {
                    to_assign(1, '真实姓名不能为空！');
                }
                if (!isset($param['idcard']) || empty($param['idcard'])) {
                    to_assign(1, '请填写身份证号！');
                }
                if (empty($member['idcardpos']) && empty($param['idcardpos'])) {
                    to_assign(1, '请上传身份证正面照！');
                }
                if (empty($member['idcardside']) && empty($param['idcardside'])) {
                    to_assign(1, '请上传身份证反面照！');
                }
                if (!isset($param['sex']) || empty($param['sex'])) {
                    to_assign(1, '请选择性别！');
                }
                unset($param['authstate']);
            }
            //如果还未认证过银行卡
            if (intval($member['bankstate']) != 1) {
                if (empty($param['bankcard'])) {
                    to_assign(1, '请填写银行卡号！');
                }
                if (empty($param['bankprovince'])) {
                    to_assign(1, '请选择开户行所在省份！');
                }
                if (empty($param['bankcity'])) {
                    to_assign(1, '请选择开户行所在市！');
                }
                if (empty($param['bankdeposit'])) {
                    to_assign(1, '请填写开户银行信息！');
                }
                if (empty($member['bankcardphoto']) && empty($param['bankcardphoto'])) {
                    to_assign(1, '请上传银行卡照！');
                }
                if (!isset($param['true_name']) || empty($param['true_name'])) {
                    to_assign(1, '真实姓名不能为空！');
                }
                if (!isset($param['idcard']) || empty($param['idcard'])) {
                    to_assign(1, '请填写身份证号！');
                }
                unset($param['authstate'], $param['bankstate']);
            } else {
                unset($param['true_name'], $param['idcard'], $param['bankcard'], $param['bankprovince'], $param['bankcity'], $param['bankdeposit'], $param['bankcardphoto']);
            }
            //详细地址
            if (!isset($param['address']) || empty($param['address'])) {
                unset($param['address']);
            }
            if (empty($member['email']) && empty($param['email'])) {
                to_assign(1, '电子邮箱不能为空！');
            }
            if ($member['email'] && $param['email'] != $member['email']) {
                $email = Db::name('author')->where(['email' => $param['email'], ['id', '<>', $member['id']]])->find();
                if (!empty($email)) {
                    to_assign(1, '此邮箱已被使用！');
                }
            }
            if (empty($member['qq']) && empty($param['qq'])) {
                to_assign(1, 'QQ不能为空！');
            }
            if (empty($member['province']) && empty($param['province'])) {
                to_assign(1, '请选择省份或地区！');
            }
            if (empty($member['city']) && empty($param['city'])) {
                to_assign(1, '请选择市！');
            }
            if (empty($member['county']) && empty($param['county'])) {
                to_assign(1, '请选择城市或县！');
            }
            //是否修改笔名
            $ismodnickname = false;
            //修改笔名
            if (strpos($member['nickname'], (get_system_config('web', 'title') . '_')) !== false && !empty($param['nickname'])) {
                if ($param['nickname'] != $member['nickname']) {
                    $exist = Db::name('author')->where(['nickname' => $param['nickname']])->find(); //检测是否有重复
                    if (!empty($exist)) {
                        to_assign(1, '笔名已被使用');
                    }
                    $sign = Db::name('author_sign')->where(['genre' => 1, 'uid' => $member['id'], 'status' => 1])->select(); //查所有已成功签约的主协议
                    $sign = $sign ? $sign->toArray() : [];
                    $pennamesign = Db::name('author_sign')->where(['genre' => 2, 'uid' => $member['id'], 'rtype' => 1, ['status', '<>', 1]])->select(); //查询是否有改笔名审核中的记录
                    $pennamesign = $pennamesign ? $pennamesign->toArray() : [];
                    if (!empty($sign) && empty($pennamesign)) {
                        //写操作日志
                        $data = [
                            'type' => 1,
                            'pid' => $member['id'],
                            'front' => $member['nickname'],
                            'after' => $param['nickname'],
                            'action' => 1,
                            'status' => 0,
                            'addtime' => time(),
                        ];
                        $lid = Db::name('author_log')->strict(false)->field(true)->insertGetId($data);
                        //addmessage(98, 0, $member['id'], $lid);
                        //每个作品都要重新签约
                        foreach ($sign as $k => $v) {
                            $data = [
                                'genre' => 2,
                                'mode' => $v['mode'],
                                'type' => $v['type'],
                                'level' => $v['level'],
                                'rtype' => 1, //改笔名
                                'uid' => $v['uid'],
                                'pid' => $v['pid'],
                                'words' => $v['words'],
                                'age' => $v['age'],
                                'qq' => $v['qq'],
                                'mobile' => $v['mobile'],
                                'divideratio' => $v['divideratio'],
                                'attendance' => $v['attendance'],
                                'price' => $v['price'],
                                'status' => 0,
                                'applytme' => time(),
                                'lid' => $lid,
                            ];
                            Db::name('author_sign')->strict(false)->field(true)->insertGetId($data);
                        }
                        unset($param['nickname']);
                    }
                    //如果两个都有说明还在审核中，也要禁止更改笔名操作
                    if (!empty($sign) && !empty($pennamesign)) {
                        unset($param['nickname']);
                    }
                    //如果没有签约直接改笔名
                    if (empty($sign) && empty($pennamesign)) {
                        $ismodnickname = true;
                    }
                } else {
                    unset($param['nickname']);
                }
            }
            //银行卡更新
            if (intval($member['bankstate']) != 1) {
                //如果更换了银行卡需要重新签署协议|只有已签约的作者有效
                if ($param['bankcard'] != $member['bankcard'] && intval($member['issign']) == 1) {
                    $banksign = Db::name('author_sign')->where(['genre' => 2, 'uid' => $member['id'], 'rtype' => 2, ['status', '<>', 1]])->select()->toArray(); //查询是否有改银行卡审核中的记录
                    //银行卡更改补充协议
                    if (empty($banksign)) {
                        //判断银行卡照片
                        if ($member['bankcardphoto'] == $param['bankcardphoto']) {
                            to_assign(1, '请上传银行卡照片！');
                        }
                        //写操作日志
                        $front = serialize(['bankdeposit' => $member['bankdeposit'], 'bankcard' => $member['bankcard'], 'bankcardphoto' => $member['bankcardphoto']]);
                        $after = serialize(['bankdeposit' => $param['bankdeposit'], 'bankcard' => $param['bankcard'], 'bankcardphoto' => $param['bankcardphoto']]);
                        $data = [
                            'type' => 1,
                            'pid' => $member['id'],
                            'front' => $front,
                            'after' => $after,
                            'action' => 2,
                            'status' => 0,
                            'addtime' => time(),
                        ];
                        $lid = Db::name('author_log')->strict(false)->field(true)->insertGetId($data);
                        //addmessage(97, 0, $member['id'], $lid);
                        //写签约记录
                        $data = [
                            'genre' => 2,
                            'mode' => 1,
                            'type' => 0,
                            'level' => 0,
                            'rtype' => 2, //银行卡更改
                            'uid' => $member['id'],
                            'pid' => 0,
                            'words' => 0,
                            'age' => 0,
                            'qq' => 0,
                            'mobile' => $member['mobile'],
                            'divideratio' => 0,
                            'price' => 0,
                            'attendance' => 0,
                            'status' => 0,
                            'applytme' => time(),
                            'lid' => $lid,
                        ];
                        Db::name('author_sign')->strict(false)->field(true)->insertGetId($data);
                        unset($param['bankdeposit'], $param['bankcard']);
                    }
                    //如果两个都有说明还在审核中，也要禁止更改银行卡操作
                    if (!empty($banksign)) {
                        unset($param['bankdeposit'], $param['bankcard']);
                    }
                }
            }
            //非必填的如果为空的要删除
            //头像
            if (!isset($param['headimg']) || empty($param['headimg'])) {
                unset($param['headimg']);
            }
            //工作单位
            if (!isset($param['workunit']) || empty($param['workunit'])) {
                unset($param['workunit']);
            }
            //固定电话
            if (!isset($param['telephone']) || empty($param['telephone'])) {
                unset($param['telephone']);
            }
            $param['update_time'] = time();
            $res = Db::name('author')->where(['id' => $uid])->strict(false)->field(true)->update($param);
            if ($res !== false) {
                if ($ismodnickname) {
                    //批量更改笔名
                    $result = Db::name('book')->where(['authorid' => $member['id']])->strict(false)->field(true)->update(['author' => $param['nickname']]); //批量更新作品表的作者名                
                    $this->restMakeDefaultCover();
                    //修改头像
                    if (empty($param['headimg']) && empty($member['headimg'])) {
                        $char = mb_substr($param['nickname'], 0, 1, 'utf-8');
                        $avatar = make_avatars($char);
                        if ($avatar) {
                            $res = Db::name('author')->where(['id' => $uid])->strict(false)->field(true)->update(['headimg' => $avatar]);
                        }
                    }
                }
                to_assign(0, '设置成功');
            } else {
                to_assign(1, '设置失败');
            }
        } else {
            to_assign(1, '没有权限');
        }
    }

    //重新生成默认封面
    public function restMakeDefaultCover()
    {
        $uid = get_login_author('id');
        $list = Db::name('book')->field('id,cover')->where(['authorid' => $uid])->select();
        $book = $list ? $list->toArray() : [];
        foreach ($book as $k => $v) {
            $cover = '/storage/cover/' . $uid . '/' . $v['id'] . '.png';
            if ($v['cover'] == $cover) {
                makecover($v['id']);
            }
        }
    }

    public function service()
    {
        return view();
    }

    //更新
    public function renew()
    {
        $uid = get_login_author('id');
        if (request()->isAjax()) {
            $param = get_params();
            $date = $param['date'];
            if (empty($date)) {
                $date = date("Y-m");
            }
            $days = date('t', strtotime($date)); //天数
            $start = strtotime($date . '-1');
            $end = strtotime($date . '-' . $days) + 86399;
            $book = Db::name('book')->field('id')->where(['authorid' => $uid])->select();
            $book = $book ? $book->toArray() : [];
            $res = $data = [];
            if (!empty($book)) {
                $bids = implode(',', array_column($book, 'id'));
                $list = Db::name('chapter')->field('wordnum,create_time,trial_time')->where('bookid', 'in', $bids)->whereBetween('create_time', $start . ',' . $end)->select();
                $list = $list ? $list->toArray() : [];
                for ($i = 1; $i <= $days; $i++) {
                    $s = strtotime($date . '-' . $i);
                    $e = strtotime($date . '-' . $i) + 86399;
                    $res[$date . '-' . $i]['title'] = $date . '-' . $i;
                    $res[$date . '-' . $i]['value'] = 0;
                    $res[$date . '-' . $i]['start'] = date('r', $s);
                    $res[$date . '-' . $i]['end'] = date('r', $e);
                    foreach ($list as $k => $val) {
                        if (intval($val['trial_time']) > 0) {
                            continue;
                        }
                        if ($val['create_time'] >= $s && $val['create_time'] <= $e) {
                            $res[$date . '-' . $i]['value'] += $val['wordnum'];
                            unset($list[$k]);
                        }
                    }
                    if ($res[$date . '-' . $i]['value'] > 0) {
                        $res[$date . '-' . $i]['title'] = '更新：' . $res[$date . '-' . $i]['value'] . '字';
                        $res[$date . '-' . $i]['className'] = 'bg-info';
                    } else {
                        $res[$date . '-' . $i]['title'] = '未更新';
                        $res[$date . '-' . $i]['className'] = 'bg-secondary';
                    }
                    unset($res[$date . '-' . $i]['value']);
                    $data[] = $res[$date . '-' . $i];
                }
            }
            to_assign(0, '请求成功', $data);
        } else {
            to_assign(1, '请求错误');
        }
    }

    //修改密码
    public function modifypass()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $uid = get_login_author('id');
            $oldpass = $param['oldpass'];
            $newpass = $param['newpass'];
            $confirmnewpass = $param['confirmnewpass'];
            if (empty($oldpass) || empty($newpass) || empty($confirmnewpass)) {
                return to_assign(1, '全为必填项，请填写。');
            }
            if ($newpass !== $confirmnewpass) {
                return to_assign(1, '新密码与确认新密码不一致。');
            }
            $user = Db::name('author')->where(['id' => $uid])->find();
            if (empty($user)) {
                to_assign(1, '用户不存在！');
            }
            if ($user['status'] != 1) {
                to_assign(1, '用户被禁止！');
            }
            if (sha1(MD5(trim($param['oldpass'])) . $user['salt']) !== $user['password']) {
                return to_assign(1, '旧密码错误');
            }
            $data = array(
                'password' => sha1(MD5($param['newpass']) . $user['salt']),
                'ip' => request()->ip(),
                'update_time' => time(),
            );
            $res = Db::name('author')->where(['id' => $user['id']])->strict(false)->field(true)->update($data);
            if ($res) {
                $session_user = get_config('app.session_author');
                Session::delete($session_user);
                return to_assign(0, '修改成功，请重新登录');
            } else {
                return to_assign(1, '修改失败');
            }
        } else {
            return view();
        }
    }
}