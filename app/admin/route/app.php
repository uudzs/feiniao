<?php
use think\facade\Route;

Route::rule('verify', 'verify/verify', 'GET|POST')->name('verify');