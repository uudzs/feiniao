<?php

use think\facade\Route;
Route::rule('cate-<id>', 'book/cate', 'GET|POST')->name('book_cates');
Route::rule('book-:id', 'book/detail', 'GET|POST')->name('book_detail');
Route::rule('login$', 'login/index', 'GET|POST')->name('login');
Route::rule('author-:id', 'author/detail', 'GET|POST')->name('author_detail');
Route::rule('chapter-:id', 'chapter/detail', 'GET|POST')->name('chapter_detail');
Route::miss('\app\home\controller\Emptys::miss');