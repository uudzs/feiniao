<?php

use think\facade\Route;
use think\facade\Db;

try {
    $rule = get_cache('routeRule');
    if (!$rule) {
        $rule = Db::name('route')->field('id,rule,name,group')->where(['status' => 1])->order('id asc')->select()->toArray();
        set_cache('routeRule', $rule);
    }
    $data = array_column($rule, null, 'name');
    if (isset($data['book_cates']) && $data['book_cates']['rule']) Route::rule($data['book_cates']['rule'], 'book/cate', 'GET')->name('book_cates');
    if (isset($data['book_detail']) && $data['book_detail']['rule']) Route::rule($data['book_detail']['rule'], 'book/detail', 'GET')->name('book_detail');
    if (isset($data['author_detail']) && $data['author_detail']['rule']) Route::rule($data['author_detail']['rule'], 'author/detail', 'GET')->pattern(['id' => '\d+'])->name('author_detail');
    if (isset($data['chapter_detail']) && $data['chapter_detail']['rule']) Route::rule($data['chapter_detail']['rule'], 'chapter/detail', 'GET')->pattern(['bookid' => '\d+', 'id' => '\d+'])->name('chapter_detail');
    if (isset($data['login']) && $data['login']['rule']) Route::rule($data['login']['rule'], 'login/index', 'GET')->name('login');
} catch (Exception $e) {
}
Route::miss('\app\home\controller\Emptys::miss');
