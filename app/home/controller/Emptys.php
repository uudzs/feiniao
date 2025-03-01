<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Request;
use think\facade\View;
use think\facade\Db;

class Emptys extends BaseController
{
    public function miss()
    {
        $url = Request::url();
        if (preg_match('/sitemap(-.*)?\.xml$/', $url)) {
            $path = app()->getRootPath() . 'runtime/html/sitemap/';
            if (!createDirectory($path)) {
                return false;
            }
            $File = new \think\template\driver\File();
            $filename = basename($url);
            $limit = 5000;
            $total = (int) Db::name('book')->where(['status' => 1])->count();
            if (preg_match('/sitemap-book-(\d+)\.xml/', $url, $matches)) {
                $page = intval($matches[1]);
                if ($total > $limit) {                    
                    $this->booksitemap($page, $total, $limit, $path, $File);
                    $sitemappath = $path . 'sitemap-book-' . $page . '.xml';
                    if (is_file($sitemappath)) {
                        $file_time =  filectime($sitemappath);
                        $filesize = (int) filesize($sitemappath);
                        if ($file_time !== false && $filesize > 0) {
                            if ((time() - $file_time) < 86400) {
                                $content =  file_get_contents($sitemappath);
                                echo $content;
                                exit;
                            }
                        }
                    }
                    $start = $limit * $page;
                    $list = Db::name('book')->field('id,filename')->where(['status' => 1])->order('create_time desc')->limit($start, $limit)->select()->toArray();
                    $content = View::fetch('sitemapbook', ['list' => $list]);
                    if (!empty($content)) {
                        $File->write($sitemappath, $content);
                        echo $content;
                        exit;
                    }
                } else {
                    $sitemappath = $path . 'sitemap.xml';
                    if (is_file($sitemappath)) {
                        $file_time =  filectime($sitemappath);
                        $filesize = (int) filesize($sitemappath);
                        if ($file_time !== false && $filesize > 0) {
                            if ((time() - $file_time) < 86400) {
                                $content =  file_get_contents($sitemappath);
                                echo $content;
                                exit;
                            }
                        }
                    }
                    $content = View::fetch('sitemap');
                    if (!empty($content)) {
                        $File->write($sitemappath, $content);
                        echo $content;
                        exit;
                    }
                }
            } else {
                if ($filename == 'sitemap-other.xml') {
                    $sitemappath = $path . 'sitemap-other.xml';
                    if (is_file($sitemappath)) {
                        $file_time =  filectime($sitemappath);
                        $filesize = (int) filesize($sitemappath);
                        if ($file_time !== false && $filesize > 0) {
                            if ((time() - $file_time) < 86400) {
                                $content =  file_get_contents($sitemappath);
                                echo $content;
                                exit;
                            }
                        }
                    }
                    $content = View::fetch('sitemapother');
                    if (!empty($content)) {
                        $File->write($sitemappath, $content);
                        echo $content;
                        exit;
                    }
                }
                if ($total > $limit) {                    
                    $this->booksitemap(0, $total, $limit, $path, $File);
                }
                $sitemappath = $path . 'sitemap.xml';
                if (is_file($sitemappath)) {
                    $file_time =  filectime($sitemappath);
                    $filesize = (int) filesize($sitemappath);
                    if ($file_time !== false && $filesize > 0) {
                        if ((time() - $file_time) < 86400) {
                            $content =  file_get_contents($sitemappath);
                            echo $content;
                            exit;
                        }
                    }
                }
                $content = View::fetch('sitemap');
                if (!empty($content)) {
                    $File->write($sitemappath, $content);
                    echo $content;
                    exit;
                }
            }
        }
        return view('404');
    }

    private function booksitemap($page, $total, $limit, $path, $File)
    {
        $totalpage = ceil($total / $limit);
        if ($totalpage > 0 && $page >= $totalpage) return false;
        $files = [];
        $content = View::fetch('sitemapother');
        if (!empty($content)) {
            $sitemappath = $path . 'sitemap-other.xml';
            $File->write($sitemappath, $content);
            $files[] = $sitemappath;
        }
        $i = 0;
        while ($i < $totalpage) {
            $sitemappath = $path . 'sitemap-book-' . $i . '.xml';
            if (is_file($sitemappath)) {
                $file_time =  filectime($sitemappath);
                $filesize = (int) filesize($sitemappath);
                if ($file_time !== false && $filesize > 0) {
                    if ((time() - $file_time) < 86400) {
                        $i++;
                        $files[] = $sitemappath;
                        continue;
                    }
                }
            }
            $files[] = $sitemappath;
            $i++;
        }
        $content = View::fetch('sitemappage', ['files' => $files, 'domain' => Request::domain()]);
        if (!empty($content)) {
            $sitemappath = $path . 'sitemap.xml';
            $File->write($sitemappath, $content);
        }
    }
}
