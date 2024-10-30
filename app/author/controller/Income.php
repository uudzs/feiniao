<?php
declare(strict_types=1);

namespace app\author\controller;

use app\author\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Route;

class Income extends BaseController
{
    //作品列表
    public function index()
    {
        $uid = get_login_author('id');
        $param = get_params();
        $where = ['authorid' => $uid, 'status' => 1, 'issign' => 1];
        $list = Db::name('book')
            ->field('IF(update_time = 0, create_time, update_time) AS order_time,id,update_time,create_time,title,words,status,editorid,editor,issign,finishtime,outline,chapters')
            ->where($where)
            ->order('order_time desc')
            ->select()
            ->toArray();
        foreach ($list as $key => $value) {
            $list[$key]['url'] = (string) Route::buildUrl('income/index', ['id' => $value['id']]);
        }
        if (isset($param['id']) && $param['id']) {
            $id = $param['id'];
        } else {
            if ($list) {
                $id = $list[0]['id'];
            } else {
                $id = 0;
            }
        }
        $income = [];
        $total_money = 0;
        $sign_type = '';
        if ($id) {
            $nowDay = date('d');
            if ($nowDay < 6) {
                $starttime = strtotime(date('Y-m-01', strtotime('-1 month', time())));// 获取上个月的第一天
                $month = date('Ym', $starttime);
                $total_money = Db::name('book_monthly_salary')->where(['book_id' => $id, 'user_id' => $uid, ['month', '<', $month]])->sum('money');
                $income = Db::name('book_monthly_salary')->where(['book_id' => $id, 'user_id' => $uid, ['month', '<', $month]])->select()->toArray();
            } else {
                $total_money = Db::name('book_monthly_salary')->where(['book_id' => $id, 'user_id' => $uid])->sum('money');
                $income = Db::name('book_monthly_salary')->where(['book_id' => $id, 'user_id' => $uid])->select()->toArray();
            }
            foreach ($income as $key => $value) {
                $income[$key]['month'] = date('Y-m', strtotime($value['month'] . '01'));
                $income[$key]['minimum_amount'] = floatval($value['minimum_amount']) <= 0 ? '-' : $value['minimum_amount'];
                $income[$key]['total_minimum_amount'] = floatval($value['total_minimum_amount']) <= 0 ? '-' : $value['total_minimum_amount'];
                $income[$key]['super_guaranteed_money'] = floatval($value['super_guaranteed_money']) <= 0 ? '-' : $value['super_guaranteed_money'];
                $income[$key]['other_income'] = floatval($value['other_income']) <= 0 ? '-' : $value['other_income'];
                $income[$key]['channel_income'] = floatval($value['channel_income']) <= 0 ? '-' : $value['channel_income'];
                $income[$key]['copyright_income'] = floatval($value['copyright_income']) <= 0 ? '-' : $value['copyright_income'];
                $income[$key]['rewards_attendance'] = floatval($value['rewards_attendance']) <= 0 ? '-' : $value['rewards_attendance'];
                $income[$key]['money'] = floatval($value['money']) <= 0 ? '-' : $value['money'];
            }
            if ($income) {
                $sign_type = $income[0]['sign_type'];
            }
        }
        //$total_moneys = Db::name('author_month_income')->where(['user_id' => $uid, 'is_pay' => 1])->sum('real_money'); //已到账总收益
        //$total_tax = Db::name('author_month_income')->where(['user_id' => $uid, 'is_pay' => 1])->sum('tax'); //总交税
        //$total_notmoney = Db::name('author_month_income')->where(['user_id' => $uid, 'is_pay' => 0])->sum('real_money'); //未到账收益
        //$total_word_count = Db::name('book_monthly_salary')->where(['user_id' => $uid])->sum('word_count'); //已加入收益总字数
        //$total_book = Db::name('book')->where(['authorid' => $uid, 'status' => 1])->count(); //作品总数
        //$total_book_words = Db::name('book')->where(['authorid' => $uid, 'status' => 1])->sum('words'); //作品总字数
        //View::assign('total_moneys', $total_moneys);
        //View::assign('total_tax', $total_tax);
        //View::assign('total_notmoney', $total_notmoney);
        //View::assign('total_word_count', $total_word_count);
        //View::assign('total_book', $total_book);
        //View::assign('total_book_words', $total_book_words);

        View::assign('id', $id);
        View::assign('income', $income);
        View::assign('total_money', $total_money);
        View::assign('sign_type', $sign_type);
        View::assign('list', $list);
        return view();
    }
}