<?php

use think\facade\Route;
use think\facade\Db;

try {
    if (get_addons_is_enable('sitegroup')) {
        $result = hook('siteGroupRouteHook');
        if ($result && isJson($result)) {
            $result = json_decode($result, true);
            if ($result && is_array($result)) {
                $rule = $result;
            }
        }
        if (empty($rule)) {
            $rule = Db::name('route')->field('id,rule,name,group')->where(['status' => 1])->order('id asc')->select()->toArray();
        }
    } else {
        $rule = Db::name('route')->field('id,rule,name,group')->where(['status' => 1])->order('id asc')->select()->toArray();
    }
    $data = array_column($rule, null, 'name');
    if (isset($data['/']) && $data['/']['rule']) Route::rule($data['/']['rule'], 'index/index', 'GET')->name('/');
    if (isset($data['book_cates']) && $data['book_cates']['rule']) Route::rule($data['book_cates']['rule'], 'book/cate', 'GET')->name('book_cates');
    if (isset($data['book_detail']) && $data['book_detail']['rule']) Route::rule($data['book_detail']['rule'], 'book/detail', 'GET')->name('book_detail');
    if (isset($data['rank']) && $data['rank']['rule']) Route::rule($data['rank']['rule'], 'book/rank', 'GET')->name('rank');
    Route::rule('app', 'index/app', 'GET')->name('app');
    //if (isset($data['app']) && $data['app']['rule']) Route::rule($data['app']['rule'], 'index/app', 'GET')->name('app');
    if (isset($data['shuku']) && $data['shuku']['rule']) Route::rule($data['shuku']['rule'], 'book/list', 'GET')->name('shuku');
    if (isset($data['quanben']) && $data['quanben']['rule']) Route::rule($data['quanben']['rule'], 'book/quanben', 'GET')->name('quanben');
    if (isset($data['author_detail']) && $data['author_detail']['rule']) Route::rule($data['author_detail']['rule'], 'author/detail', 'GET')->pattern(['id' => '\d+'])->name('author_detail');
    if (isset($data['chapter_detail']) && $data['chapter_detail']['rule']) Route::rule($data['chapter_detail']['rule'], 'chapter/detail', 'GET')->pattern(['bookid' => '[^/]+', 'id' => '[a-zA-Z0-9]+'])->name('chapter_detail');
    if (isset($data['notice'])) {
        Route::group('notice', function () use ($data) {
            if ($data['notice']['rule']) Route::rule($data['notice']['rule'], 'info/index', 'GET')->name('notice');
            if (isset($data['notice_page']) && $data['notice_page']['rule']) Route::rule($data['notice_page']['rule'], 'info/index', 'GET')->pattern(['page' => '\d+'])->name('notice_page');
        });
    }
    if (isset($data['notice_detail']) && $data['notice_detail']['rule']) Route::rule($data['notice_detail']['rule'], 'info/detail', 'GET')->pattern(['id' => '\d+'])->name('notice_detail');
    if (isset($data['pages']) && $data['pages']['rule']) Route::rule($data['pages']['rule'], 'pages/detail', 'GET')->pattern(['name' => '[a-z-]+'])->name('pages');
    if (isset($data['news_detail']) && $data['news_detail']['rule']) Route::rule($data['news_detail']['rule'], 'article/detail', 'GET')->pattern(['id' => '\d+'])->name('news_detail');
    if (isset($data['login']) && $data['login']['rule']) Route::rule($data['login']['rule'], 'login/index', 'GET')->name('login');
    if (isset($data['register']) && $data['register']['rule']) Route::rule($data['register']['rule'], 'login/register', 'GET')->name('register');
    if (isset($data['search']) && $data['search']['rule']) Route::rule($data['search']['rule'], 'search/index', 'GET')->name('search');
    if (isset($data['my']) && $data['my']['rule']) Route::rule($data['my']['rule'], 'user/index', 'GET')->name('my');
    if (isset($data['message']) && $data['message']['rule']) Route::rule($data['message']['rule'], 'message/index', 'GET')->name('message');
    if (isset($data['profile']) && $data['profile']['rule']) Route::rule($data['profile']['rule'], 'user/profile', 'GET')->name('profile');
    if (isset($data['realnameauth']) && $data['realnameauth']['rule']) Route::rule($data['realnameauth']['rule'], 'user/realnameauth', 'GET')->name('realnameauth');
    if (isset($data['invite']) && $data['invite']['rule']) Route::rule($data['invite']['rule'], 'invite/index', 'GET')->name('invite');
    if (isset($data['myinvite']) && $data['myinvite']['rule']) Route::rule($data['myinvite']['rule'], 'user/invite', 'GET')->name('myinvite');
    if (isset($data['inviteurl']) && $data['inviteurl']['rule']) Route::rule($data['inviteurl']['rule'], 'invite/index', 'GET')->pattern(['name' => '\w+'])->name('inviteurl');
    if (isset($data['task']) && $data['task']['rule']) Route::rule($data['task']['rule'], 'task/index', 'GET')->name('task');
    if (isset($data['bookshelf']) && $data['bookshelf']['rule']) Route::rule($data['bookshelf']['rule'], 'bookshelf/index', 'GET')->name('bookshelf');
    if (isset($data['readlog']) && $data['readlog']['rule']) Route::rule($data['readlog']['rule'], 'user/readlog', 'GET')->name('readlog');
    if (isset($data['nickname']) && $data['nickname']['rule']) Route::rule($data['nickname']['rule'], 'user/nickname', 'GET')->name('nickname');
    if (isset($data['phone']) && $data['phone']['rule']) Route::rule($data['phone']['rule'], 'user/phone', 'GET')->name('phone');
    if (isset($data['security']) && $data['security']['rule']) Route::rule($data['security']['rule'], 'user/security', 'GET')->name('security');
    if (isset($data['service']) && $data['service']['rule']) Route::rule($data['service']['rule'], 'user/service', 'GET')->name('service');
    if (isset($data['about']) && $data['about']['rule']) Route::rule($data['about']['rule'], 'user/about', 'GET')->name('about');
    if (isset($data['agreement']) && $data['agreement']['rule']) Route::rule($data['agreement']['rule'], 'user/agreement', 'GET')->name('agreement');
    if (isset($data['privacy']) && $data['privacy']['rule']) Route::rule($data['privacy']['rule'], 'user/privacy', 'GET')->name('privacy');
    if (isset($data['vip']) && $data['vip']['rule']) Route::rule($data['vip']['rule'], 'vip/index', 'GET')->name('vip');
    if (isset($data['viplog']) && $data['viplog']['rule']) Route::rule($data['viplog']['rule'], 'vip/log', 'GET')->name('viplog');
    if (isset($data['coinlog']) && $data['coinlog']['rule']) Route::rule($data['coinlog']['rule'], 'coin/index', 'GET')->name('coinlog');
    if (isset($data['withdraw']) && $data['withdraw']['rule']) Route::rule($data['withdraw']['rule'], 'withdraw/index', 'GET')->name('withdraw');
    if (isset($data['withdrawlog']) && $data['withdrawlog']['rule']) Route::rule($data['withdrawlog']['rule'], 'withdraw/log', 'GET')->name('withdrawlog');
    if (isset($data['bankcard']) && $data['bankcard']['rule']) Route::rule($data['bankcard']['rule'], 'user/bankcard', 'GET')->name('bankcard');
    if (isset($data['order']) && $data['order']['rule']) Route::rule($data['order']['rule'], 'order/index', 'GET')->name('order');
    if (isset($data['wechatpay']) && $data['wechatpay']['rule']) Route::rule($data['wechatpay']['rule'], 'pay/wechat', 'GET|POST')->name('wechatpay');
    if (isset($data['alipaypay']) && $data['alipaypay']['rule']) Route::rule($data['alipaypay']['rule'], 'pay/alipay', 'GET|POST')->name('alipaypay');
    if (isset($data['becomeauthor']) && $data['becomeauthor']['rule']) Route::rule($data['becomeauthor']['rule'], 'user/author', 'GET|POST')->name('becomeauthor');
    if (isset($data['report']) && $data['report']['rule']) Route::rule($data['report']['rule'], 'user/report', 'GET|POST')->name('report');    
    if (isset($data['wechat_pay_callback']) && $data['wechat_pay_callback']['rule']) Route::rule($data['wechat_pay_callback']['rule'], 'pay/wechat_pay_callback', 'GET|POST')->name('wechat_pay_callback');
    if (isset($data['alipay_h5_pay_callback']) && $data['alipay_h5_pay_callback']['rule']) Route::rule($data['alipay_h5_pay_callback']['rule'], 'pay/alipay_h5_pay_callback', 'GET|POST')->name('alipay_h5_pay_callback');
    if (isset($data['comments']) && $data['comments']['rule']) Route::rule($data['comments']['rule'], 'user/comments', 'GET')->name('comments');
    Route::rule('follow', 'user/follow', 'GET')->name('follow');
    if (isset($data['novelfilter']) && $data['novelfilter']['rule']) Route::rule($data['novelfilter']['rule'], 'novel/index', 'GET')
        ->pattern([
            'channel' => '\d+',
            'status'  => '[a-z0-9]+',
            'cat'     => '\d+',
            'word'    => '\d+',
            'order'   => '[a-z]+',
            'page'    => '\d+',
            'cid'     => '\d+',
            'mode'    => '\d+'
        ])->name('novelfilter');
    if (isset($data['novel']) && $data['novel']['rule']) Route::get($data['novel']['rule'], 'novel/index')->name('novel');
    if (isset($data['girls']) && $data['girls']['rule']) Route::get($data['girls']['rule'], 'novel/girls')->name('girls');
    if (isset($data['top_main']) && $data['top_main']['rule']) Route::rule($data['top_main']['rule'], 'rank/index', 'GET')
        ->ext('html')
        ->pattern(['channel' => 'male|female', 'cid' => '\w+',])
        ->name('top_main');
    if (isset($data['top_detail']) && $data['top_detail']['rule']) Route::rule($data['top_detail']['rule'], 'rank/detail', 'GET')
        ->ext('html')
        ->pattern([
            'channel' => 'male|female',
            'type'    => 'hits|new|comments|chapters|finish|words',
            'cid'     => '\w+',
            'page'    => '\d+'
        ])
        ->default(['page' => 1])
        ->name('top_detail');
} catch (Exception $e) {
}
Route::miss('\app\home\controller\Emptys::miss');
