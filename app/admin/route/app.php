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
    Route::rule('verify', 'verify/verify', 'GET|POST')->name('verify');
    Route::rule('login$', 'login/index', 'GET|POST')->name('login');
    $data = array_column($rule, null, 'name');
    if (isset($data['book_detail']) && $data['book_detail']['rule']) Route::rule($data['book_detail']['rule'], 'book/detail', 'GET')->name('book_detail');
    if (isset($data['author_detail']) && $data['author_detail']['rule']) Route::rule($data['author_detail']['rule'], 'author/detail', 'GET')->pattern(['id' => '\d+'])->name('author_detail');
    if (isset($data['chapter_detail']) && $data['chapter_detail']['rule']) Route::rule($data['chapter_detail']['rule'], 'chapter/detail', 'GET')->pattern(['bookid' => '\d+', 'id' => '\d+'])->name('chapter_detail');
} catch (Exception $e) {
}
