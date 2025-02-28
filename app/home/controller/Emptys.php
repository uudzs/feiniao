<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Request;
use think\facade\View;

class Emptys extends BaseController
{
    public function miss()
    {
        if (strpos(Request::url(), 'sitemap.xml') !== false) {
            $sitemappath = app()->getRootPath() . 'runtime/html/sitemap.xml';
            if (is_file($sitemappath)) {
                $file_time =  filectime($sitemappath);
                if ($file_time !== false) {
                    if ((time() - $file_time) < 86400) {
                        echo file_get_contents($sitemappath);
                        exit;
                    }
                }
            }
            $content = View::fetch('sitemap');
            $File = new \think\template\driver\File();
            $File->write($sitemappath, $content);
            echo $content;
            exit;
        }
        return view('404');
    }
}
