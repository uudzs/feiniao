<?php

declare(strict_types=1);

namespace app\author\controller;

use app\author\BaseController;
use think\facade\Db;
use think\facade\View;
use esign\CommonHelper;
use esign\SignHelper;

class Sign extends BaseController
{


    /**
     * [状态说明][流程走向]
     * 0 作者审核了签约待后台审核
     * 2 后台已同意签约并设置了签约方式、类型、等级、价格
     * 3 作者确认了签约信息
     * 4 驳回
     * 5 后台审核并成生合同文件
     * 6 作者答名成功等待后台盖章
     * 1 后台盖章成功完合签署完成
     */
    /**
     * [分支]
     * outlinestate 大纲状态 0待审1通过2拒绝
     * 实名状态 统计用作者的实名状态
     */

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
        $sign_succes = $sign_not = $sign_progress = $sign_refuse = 0;//签约成功|未签约|签约进行中|拒绝
        foreach ($list as $k => $v) {
            $issign = intval($v['issign']);
            if ($issign != 1) {
                $sign = Db::name('author_sign')->where(['genre' => 1, 'pid' => $v['id'], 'uid' => $uid])->find();
                if (!empty($sign)) {
                    //状态0待审1完成2已审3已确认4拒绝5后台全通过
                    $status = intval($sign['status']);
                    $list[$k]['issign'] = $issign == 1 ? 1 : $status + 2;
                    if ($status == 1) {
                        $sign_succes++;
                        continue;
                    }
                    if ($status == 4) {
                        $sign_refuse++;
                        continue;
                    }
                    $sign_progress++;
                } else {
                    $sign_not++;
                }
            }
            if ($issign == 1) {
                $sign_succes++;
            }
        }
        //银行卡修改单独取出来
        $bank_list = Db::name('author_sign')->where(['genre' => 2, 'rtype' => 2, 'uid' => $uid])->select();
        $bank_list = $bank_list ? $bank_list->toArray() : [];
        foreach ($bank_list as $k => $v) {
            $log = Db::name('author_log')->where(['id' => $v['lid']])->find();
            //进行反序列操作
            if (!empty($log)) {
                $log['front'] = unserialize($log['front']);
                $log['after'] = unserialize($log['after']);
            }
            $bank_list[$k]['log'] = $log;
            $status = intval($v['status']);
            if ($status == 1) {
                $sign_succes++;
                continue;
            }
            if ($status == 4) {
                $sign_refuse++;
                continue;
            }
            $sign_progress++;
        }
        View::assign('webtitle', get_system_config('web', 'title'));
        View::assign('list', $list);
        View::assign('bank_list', $bank_list);
        View::assign('sign_succes', $sign_succes);
        View::assign('sign_not', $sign_not);
        View::assign('sign_progress', $sign_progress);
        View::assign('sign_refuse', $sign_refuse);
        return view();
    }

    //申请签约
    public function apply()
    {
        $uid = get_login_author('id');
        $param = get_params();
        $bid = intval($param['id']);
        if (empty($bid)) {
            $this->error('关键参数为空！');
        }
        $member = Db::name('author')->where(['id' => $uid])->find();
        if (empty($member)) {
            $this->error('作者不存在！');
        }
        if (intval($member['status']) != 1) {
            $this->error('用户被禁止！');
        }
        $book = Db::name('book')->where(['id' => $bid, 'authorid' => $member['id']])->find();
        if (empty($book)) {
            $this->error('作品不存在');
        }
        if ($book['status'] != 1) {
            $this->error('作品被禁止');
        }
        if ($book['issign'] == 1) {
            $this->error('作品已签约');
        }
        if (empty($member['true_name']) || empty($member['idcard']) || empty($member['address']) || empty($member['idcard'])) {
            $this->error('用户信息未完善，请先完善信息！');
        }
        //主协议
        $sign = Db::name('author_sign')->where(['genre' => 1, 'pid' => $book['id'], 'uid' => $member['id']])->find();
        // if (!empty($sign) && $sign['status'] == 1) {
        //     $this->error('此作品已申请过签约了');
        // }
        //如果有未完成的补充协议也禁止申请签约的
        $supplysign = Db::name('author_sign')->where(['genre' => 2, 'rtype' => 2, 'uid' => $member['id'], ['status', '<>', 1]])->find();
        if (!empty($supplysign)) {
            $this->error('请先完成未签署的银行卡协议');
        }
        if ($sign && !empty($sign['editor'])) {
            $sign['editorname'] = Db::name('admin')->where(['id' => $sign['editor']])->value('nickname');
        }
        $book['sign'] = $sign;
        $book['wordsstatus'] = $book['words'] >= 10000 ? 1 : 0; //字数状态
        //补充协议
        $signlist = Db::name('author_sign')->where(['genre' => 2, 'pid' => $book['id']])->order('applytme desc')->select();
        $signlist = $signlist ? $signlist->toArray() : [];
        //dd_author_sign表rtype补充协议类型1笔名修改2银行卡修改3作品名修改4买断转分成修改
        //dd_author_log表action操作行为| 1笔名2银行卡3作品名4作品简介5手机号		
        foreach ($signlist as $key => &$val) {
            //查操作日志
            if ($val['lid']) {
                $val['log'] = Db::name('author_log')->where(['id' => $val['lid']])->find();
            } else {
                $val['log'] = [];
            }
            if (!empty($val['editor'])) {
                $val['editorname'] = Db::name('admin')->where(['id' => $val['editor']])->value('nickname');
            } else {
                $val['editorname'] = '无';
            }
        }
        View::assign('book', $book);
        View::assign('signlist', $signlist);
        View::assign('webtitle', get_system_config('web', 'title'));
        return view();
    }

    //申请保存
    public function applysave()
    {
        $uid = get_login_author('id');
        $param = get_params();
        $bid = intval($param['id']);
        if (request()->isAjax()) {
            if (empty($bid)) {
                to_assign(1, '关键参数为空！');
            }
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
            if ($book['status'] != 1) {
                to_assign(1, '作品被禁止');
            }
            if ($book['issign'] == 1) {
                to_assign(1, '作品已签约');
            }
            if (empty($member['true_name']) || empty($member['idcard']) || empty($member['address']) || empty($member['idcard'])) {
                to_assign(1, '用户信息未完善，请先完善信息！');
            }
            $sign = Db::name('author_sign')->where(['genre' => 1, 'pid' => $book['id'], 'uid' => $member['id']])->find();
            if (!empty($sign) && $sign['status'] != 4) {
                to_assign(1, '此作品已申请过签约了');
            }
            //如果有未完成的补充协议也禁止申请签约的
            $supplysign = Db::name('author_sign')->where(['genre' => 2, 'uid' => $member['id'], ['status', '<>', 1]])->find();
            if (!empty($supplysign)) {
                to_assign(1, '请先完成未签署的补充协议');
            }
            //如果是重新申请
            if ($sign && $sign['status'] == 4) {
                Db::name('book')->where(['id' => $book['id']])->strict(false)->field(true)->update(['outline' => '']); //清空大纲
                $data = [
                    'status' => 0,
                    'mode' => 0,
                    'type' => 0,
                    'level' => 0,
                    'price' => 0,
                    'words' => $book['words'],
                    'age' => $member['idcard'] ? getAgeByIDCard($member['idcard']) : 0,
                    'outlinestate' => 0,
                    'contract' => '',
                    'rcontract' => '',
                    'parentauth' => '',
                    'applytme' => time(),
                    'verifytime' => 0,
                    'completetime' => 0,
                    'contracttime' => 0,
                    'reason' => '',
                    'realnameauth' => 0,
                    'flowid' => '',
                    'confirmstate' => 0,
                    'confirmtime' => 0,
                ];
                $result = Db::name('author_sign')->where(['id' => $sign['id']])->strict(false)->field(true)->update($data); //重置
                if (false !== $result) {
                    to_assign(0, '申请成功');
                } else {
                    to_assign(1, '申请失败');
                }
            }
            $data = [
                'genre' => 1,
                'uid' => $member['id'],
                'pid' => $bid,
                'words' => $book['words'],
                'age' => $member['idcard'] ? getAgeByIDCard($member['idcard']) : 0,
                'qq' => '',
                'postcode' => '',
                'address' => '',
                'mobile' => '',
                'status' => 0,
                'applytme' => time(),
            ];
            $res = Db::name('author_sign')->strict(false)->field(true)->insertGetId($data);
            if (false !== $res) {
                to_assign(0, '申请成功');
            } else {
                to_assign(1, '申请失败');
            }
        } else {
            to_assign(1, '错误提交');
        }
    }

    //保存大纲
    public function saveoutline()
    {
        $uid = get_login_author('id');
        $param = get_params();
        if (request()->isAjax()) {
            $bid = intval($param['id']);
            $outline = $param['outline'];
            if (empty($bid) || empty($outline)) {
                to_assign(1, '关键参数为空！');
            }
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
            if ($book['status'] != 1) {
                to_assign(1, '作品被禁止');
            }
            $sign = Db::name('author_sign')->where(['genre' => 1, 'pid' => $book['id'], 'uid' => $member['id']])->find();
            if (empty($sign)) {
                to_assign(1, '请先申请签约');
            }
            if ($sign['outlinestate'] == 1) {
                to_assign(1, '大纲已审核不可重复保存');
            }
            if ($sign['outlinestate'] == 0 && !empty($book['outline'])) {
                to_assign(1, '大纲审核中，不可重复提交。');
            }
            list($wordnum, $str) = countWordsAndContent($outline);
            if ($wordnum < 200 || $wordnum > 1000) {
                to_assign(1, '大纲字数在200-1000字');
            }
            $result = Db::name('author_sign')->where(['id' => $sign['id']])->strict(false)->field(true)->update(['outlinestate' => 0]); //重置大纲审核状态
            if (false !== $result) {
                $res = Db::name('book')->where(['id' => $book['id']])->strict(false)->field(true)->update(['outline' => $outline, 'outlinetime' => time()]); //更新作品大纲
                if (false !== $res) {
                    to_assign(0, '大纲保存成功');
                } else {
                    to_assign(1, '大纲保存失败');
                }
            } else {
                to_assign(1, '大纲保存失败');
            }
        } else {
            to_assign(1, '错误提交');
        }
    }

    /**
     * 确认签约
     */
    public function saveconfirm()
    {
        $uid = get_login_author('id');
        $param = get_params();
        if (request()->isAjax()) {
            $bid = intval($param['bid']);
            $sid = intval($param['sid']);
            $address = $param['address'];
            $mobile = $param['mobile'];
            $postcode = $param['postcode'];
            if (empty($bid) || empty($sid)) {
                to_assign(1, '关键参数为空！');
            }
            $member = Db::name('author')->where(['id' => $uid])->find();
            if (empty($member)) {
                to_assign(1, '作者不存在！');
            }
            if (intval($member['status']) != 1) {
                to_assign(1, '用户被禁止！');
            }
            if (empty($member['address']) && empty($address)) {
                to_assign(1, '详细地址不可为空！');
            }
            $data = [];
            //如果没有认证实名则收集作者信息
            if (intval($member['authstate']) != 1) {
                if ($param['idcard'] && $param['true_name']) {
                    $data['true_name'] = $param['true_name']; //真实姓名
                    $data['idcard'] = $param['idcard']; //身份证号
                    $res = peopleAuthState(['idNo' => $param['idcard'], 'name' => $param['true_name']]);
                    if (intval($res['code']) != 0) {
                        to_assign(1, $res['message']);
                    }
                    $data['authstate'] = 1;
                    $member['authstate'] = 1;
                }
            }
            if (empty($member['address']) && !empty($address)) {
                $data['address'] = $address;
            }
            if (empty($member['postcode']) && !empty($postcode)) {
                $data['postcode'] = $postcode;
            }
            if (!empty($data)) {
                $result = Db::name('author')->where(['id' => $member['id']])->strict(false)->field(true)->update($data); //更新作者表信息
            }
            $book = Db::name('book')->where(['id' => $bid, 'authorid' => $uid])->find();
            if (empty($book)) {
                to_assign(1, '作品不存在');
            }
            if ($book['status'] != 1) {
                to_assign(1, '作品被禁止');
            }
            $sign = Db::name('author_sign')->where(['id' => $sid])->find();
            if (empty($sign)) {
                to_assign(1, '请先申请签约');
            }
            if ($sign['uid'] != $uid || $sign['pid'] != $bid) {
                to_assign(1, '签约错误');
            }
            if ($sign['status'] == 3) {
                to_assign(1, '请不要重复确认');
            }
            if ($sign['status'] != 2) {
                to_assign(1, '状态不允许操作');
            }
            $update = ['address' => $address, 'mobile' => $mobile, 'postcode' => $postcode, 'status' => 3, 'realnameauth' => $member['authstate']];
            $res = Db::name('author_sign')->where(['id' => $sign['id']])->strict(false)->field(true)->update($update);
            if ($res !== false) {
                to_assign(0, '确认成功');
            } else {
                to_assign(1, '确认失败');
            }
        } else {
            to_assign(1, '错误提交');
        }
    }

    //发送合同签署短信
    public function sendsignsms()
    {
        $uid = get_login_author('id');
        if (request()->isAjax()) {
            $param = get_params();
            $mobile = intval($param['mobile']);//手机号
            if (empty($mobile)) {
                to_assign(1, '必要参数为空！');
            }
            $member = Db::name('author')->where(['id' => $uid])->find();
            if (empty($member)) {
                to_assign(1, '作者不存在！');
            }
            if (intval($member['status']) != 1) {
                to_assign(1, '用户被禁止！');
            }
            $accountid = CommonHelper::getPersonAccount($member);
            if (!$accountid) {
                to_assign(1, '获取accountId失败！');
            }
            $res = CommonHelper::send3rdCode($accountid, $mobile);
            if ($res && $res['errCode'] == 0) {
                to_assign(0, '发送成功');
            } else {
                to_assign(1, (isset($res['msg']) && $res['msg']) ? $res['msg'] : '发送失败');
            }
        } else {
            to_assign(1, '错误提交');
        }
    }

    //签署合同|签名
    public function signcontract()
    {
        $uid = get_login_author('id');
        if (request()->isAjax()) {
            $param = get_params();
            $bid = intval($param['bid']);
            $sid = intval($param['sid']);
            $code = intval($param['captcha']);
            $mobile = intval($param['mobile']);
            if (empty($sid) || empty($code) || empty($mobile)) {
                to_assign(1, '必要参数为空！');
            }
            $member = Db::name('author')->where(['id' => $uid])->find();
            if (empty($member)) {
                to_assign(1, '作者不存在！');
            }
            if (intval($member['status']) != 1) {
                to_assign(1, '用户被禁止！');
            }
            $sign = Db::name('author_sign')->where(['id' => $sid])->find();
            if (empty($sign)) {
                to_assign(1, '请先申请签约');
            }
            if ($sign['uid'] != $uid || $sign['pid'] != $bid) {
                to_assign(1, '签约错误');
            }
            if ($sign['mobile'] != $mobile) {
                to_assign(1, '手机号码有误');
            }
            if ($member['authstate'] != 1) {
                to_assign(1, '请先完成实名认证！');
            }
            if ($sign['genre'] != 1 || $sign['status'] != 5) {
                to_assign(1, '签约状态错误！');
            }
            //更新年龄
            if (empty($sign['age'])) {
                Db::name('author_sign')->where(['id' => $sign['id']])->strict(false)->field(true)->update(['age' => getAgeByIDCard($member['idcard'])]);
            }
            $book = Db::name('book')->where(['id' => $bid, 'authorid' => $uid])->find();
            if (empty($book)) {
                to_assign(1, '作品不存在');
            }
            $status = intval($book['status']);
            if ($status != 1) {
                to_assign(1, '作品状态已下架或被禁止');
            }
            CommonHelper::signinit($member, $book); //初始化签约信息
            $tempData = CommonHelper::enterOutTemplate($sign, $member, $book); //获取填充模板的内容
            if (empty($tempData)) {
                to_assign(1, '模板内容不存在！');
            }
            $srcPdfPath = $tempData['formal']['fill']; //源
            if (!file_exists($srcPdfPath)) {
                to_assign(1, '合同文件不存在！');
            }
            $rootpath = CMS_ROOT; //根目录            
            $dstPdfFile = $tempData['formal']['official']; //目标
            $accountId = $member['esignopenid']; //签署账号标识
            $sealData = CommonHelper::imgToBase64($rootpath . "/sign/" . $member['id'] . '/sign.png'); //印章图片base64
            $signType = 'Multi'; //签章类型，Single（单页签章）、Multi（多页签章）、Edges（签骑缝章）、Key（关键字签章）
            $signPosList = [$tempData['formal']['signPos'][0], $tempData['formal']['signPos'][1]]; //签署位置信息列表
            $result = SignHelper::mobileUserMultiPositionSign($srcPdfPath, $dstPdfFile, $accountId, $sealData, $signPosList, $code, $mobile, $signType);
            if (intval($result['errCode']) == 0 && file_exists($dstPdfFile)) {
                $update = [];
                $update['contract'] = '/sign/' . explode('/sign/', $dstPdfFile)[1]; //主合同待签名地址
                //授权书
                $srcPdfPath = $tempData['auth']['fill']; //源
                $dstPdfFile = $tempData['auth']['save']; //目标
                $signType = 'Single'; //签章类型，Single（单页签章）、Multi（多页签章）、Edges（签骑缝章）、Key（关键字签章）
                //开始签名
                $result = SignHelper::userSign($srcPdfPath, $dstPdfFile, $accountId, $sealData, $tempData['auth']['signPos'], $signType);
                if ($result['errCode'] == 0 && file_exists($dstPdfFile)) {
                    $update['rcontract'] = '/sign/' . explode('/sign/', $dstPdfFile)[1]; //授权书
                    $update['status'] = 6; //状态
                    $update['contracttime'] = time(); //合同时间
                    $res = Db::name('author_sign')->where(['id' => $sign['id']])->strict(false)->field(true)->update($update); //更新签约状态
                    if ($res !== false) {
                        to_assign(0, '签名成功');
                    } else {
                        to_assign(1, '签名失败');
                    }
                } else {
                    to_assign(1, ($result['msg'] ? $result['msg'] : '签名失败'));
                }
            } else {
                to_assign(1, ($result['msg'] ? $result['msg'] : '签名失败'));
            }
        } else {
            to_assign(1, '错误提交');
        }
    }

    //获取合同PDF文件
    public function signshowpdf()
    {
        $uid = get_login_author('id');
        $param = get_params();
        $sid = intval($param['sid']);
        $type = intval($param['type']);
        if (empty($sid)) {
            $this->error('必要参数为空！');
        }
        $member = Db::name('author')->where(['id' => $uid])->find();
        if (empty($member)) {
            $this->error('作者不存在！');
        }
        if (intval($member['status']) != 1) {
            $this->error('用户被禁止！');
        }
        $sign = Db::name('author_sign')->where(['id' => $sid])->find();
        if (empty($sign)) {
            $this->error('请先申请签约');
        }
        if ($sign['uid'] != $uid) {
            $this->error('信息错误');
        }
        $file = '';
        if ($type == 0) {
            $file = realpath(CMS_ROOT) . $sign['contract'];
        }
        if ($type == 1) {
            $file = realpath(CMS_ROOT) . $sign['rcontract'];
        }
        if ($type == 2) {
            $file = realpath(CMS_ROOT) . $sign['parentauth'];
        }
        if (!is_file($file)) {
            $this->error('文件不存在');
        }
        //header('Content-Disposition: inline; filename="aaa.pdf"');
        //inline 表示设置文件内嵌于浏览器中
        //attachment 表示将文件以附件形式直接下载
        header("Pragma: public"); // required 
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false); // required for certain browsers
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($file));
        header('Accept-Ranges: bytes');
        ob_clean();
        flush();
        readfile($file);
    }

    //下载合同PDF文件
    public function signdown()
    {
        $uid = get_login_author('id');
        $param = get_params();
        $sid = intval($param['sid']);
        $type = intval($param['type']);
        if (empty($sid)) {
            $this->error('必要参数为空！');
        }
        $member = Db::name('author')->where(['id' => $uid])->find();
        if (empty($member)) {
            $this->error('作者不存在！');
        }
        if (intval($member['status']) != 1) {
            $this->error('用户被禁止！');
        }
        $sign = Db::name('author_sign')->where(['id' => $sid])->find();
        if (empty($sign)) {
            $this->error('请先申请签约');
        }
        if ($sign['uid'] != $uid) {
            $this->error('信息错误');
        }
        $file = '';
        if ($type == 0) {
            $file = realpath(CMS_ROOT) . $sign['contract'];
        }
        if ($type == 1) {
            $file = realpath(CMS_ROOT) . $sign['rcontract'];
        }
        if ($type == 2) {
            $file = realpath(CMS_ROOT) . $sign['parentauth'];
        }
        if (!is_file($file)) {
            $this->error('文件不存在');
        }
        //header('Content-Disposition: inline; filename="aaa.pdf"');
        //inline 表示设置文件内嵌于浏览器中
        //attachment 表示将文件以附件形式直接下载
        header("Pragma: public"); // required 
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false); // required for certain browsers
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($file));
        header('Accept-Ranges: bytes');
        ob_clean();
        flush();
        readfile($file);
    }
}