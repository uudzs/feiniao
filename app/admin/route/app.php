<?php
use think\facade\Route;

Route::rule('verify', 'verify/verify', 'GET|POST')->name('verify');
Route::rule('login$', 'login/index', 'GET|POST')->name('login');
Route::rule('book-:id', 'book/detail', 'GET|POST')->name('book_detail');
Route::rule('author-:id', 'author/detail', 'GET|POST')->name('author_detail');
Route::rule('chapter-:id', 'chapter/detail', 'GET|POST')->name('chapter_detail');