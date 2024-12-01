<?php

use think\facade\Route;
Route::rule('cate-<id>', 'book/cate', 'GET|POST')->name('book_cates');
Route::rule('book-:id', 'book/detail', 'GET|POST')->name('book_detail');
Route::miss('\app\home\controller\Emptys::miss');