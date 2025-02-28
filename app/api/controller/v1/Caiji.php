<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\View;

class Caiji extends BaseController
{

    var $token = '123caiji321';

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => ['category', 'book', 'chapter']]
    ];

    private function Auth()
    {
        $param = get_params();
        if (empty($param)) {
            self::return_msg('参数错误');
        }
        if (empty($param['token'])) {
            self::return_msg('参数错误');
        }
        if (trim($param['token']) != $this->token) {
            self::return_msg('参数错误');
        }
        unset($param['token']);
        return $param;
    }

    /**
     * @api {get} /caiji/category 获取分类
     */
    public function category()
    {
        return View::fetch('v1/category/bigcate');
    }

    /**
     * @api {post} /caiji/book 采集入库
     */
    public function book()
    {
        $param = $this->Auth();
        if (empty($param['title'])) {
            self::return_msg('标题为空');
        }
        if (empty($param['author'])) {
            self::return_msg('作者为空');
        }
        $title = trim($param['title']);
        $author = trim($param['author']);
        $book = Db::name('book')->field('id')->where(['title' => $title])->find();
        $user = Db::name('author')->field('id')->where(['nickname' => $author])->find();
        $authorid = 0;
        if (empty($user)) {
            $time = (string) time();
            $salt = substr(MD5($time), 0, 6);
            $data = array(
                'email' => set_salt(10) . '@qq.com',
                'salt' => $salt,
                'password' => sha1(MD5(set_salt(10)) . $salt),
                'ip' => request()->ip(),
                'create_time' => time(),
                'status' => 1,
                'nickname' => $author,
            );
            $authorid = Db::name('author')->strict(false)->field(true)->insertGetId($data);
        } else {
            $authorid = $user['id'];
        }
        if (empty($authorid)) {
            self::return_msg('作者错误');
        }
        if (empty($book)) {
            $remark = isset($param['intro']) ? str_replace("'", "\'", trim($param['intro'])) : '';
            list($num, $remark) = countWordsAndContent($remark, true);
            $catid = isset($param['catid']) ? intval($param['catid']) : 0;
            $genre = 0;
            $subgenre = 0;
            if ($catid > 0) {
                $cate = Db::name('category')->where(['id' => $catid])->find();
                if (!empty($cate)) {
                    if (intval($cate['pid']) > 0) {
                        $genre = $cate['pid'];
                        $subgenre = $cate['id'];
                    } else {
                        $genre = $cate['id'];
                    }
                }
            }
            $category = isset($param['category']) ? trim($param['category']) : '';
            if (empty($genre) && !empty($category)) {
                $len = mb_strlen($category);
                $cate = Db::name('category')->where(['name' => $category])->find();
                if (empty($cate)) {
                    if ($len > 2) {
                        $name = mb_substr($category, 0, 2);
                        $cate = Db::name('category')->where(['name' => $name])->find();
                        if (empty($cate)) {
                            $name = mb_substr($category, 2);
                            $cate = Db::name('category')->where(['name' => $name])->find();
                        }
                        if (empty($cate)) {
                            $cate = Db::name('category')->where(['name' => '其他'])->find();
                        }
                    } else {
                        $cate = Db::name('category')->where(['name' => '其他'])->find();
                    }
                    if (!empty($cate)) {
                        if (intval($cate['pid']) > 0) {
                            $genre = $cate['pid'];
                            $subgenre = $cate['id'];
                        } else {
                            $genre = $cate['id'];
                        }
                    }
                } else {
                    if (intval($cate['pid']) > 0) {
                        $genre = $cate['pid'];
                        $subgenre = $cate['id'];
                    } else {
                        $genre = $cate['id'];
                    }
                }
            }
            $isfinish = isset($param['isfinish']) ? trim($param['isfinish']) : '';
            $finish_arr = ['完结' => 2, '连载' => 1];
            if (!empty($isfinish) && isset($finish_arr[$isfinish])) {
                $isfinish = $finish_arr[$isfinish];
            } else {
                $isfinish = 1;
            }
            $data = [
                'title' => $title,
                'author' => $author,
                'authorid' => $authorid,
                'remark' => $remark,
                'words' => 0,
                'status' => 1,
                'genre' => $genre,
                'subgenre' => $subgenre,
                'chapters' => 0,
                'isfinish' => $isfinish,
                'create_time' => isset($param['addtime']) ? strtotime(trim($param['addtime'])) : 0,
                'update_time' => isset($param['edittime']) ? strtotime(trim($param['edittime'])) : 0,
                'cover' => isset($param['cover']) && trim($param['cover']) ? trim($param['cover']) : '',
            ];
            $res = Db::name('book')->strict(false)->field(true)->insertGetId($data);
            if ($res !== false) {
                self::return_msg('发布成功');
            } else {
                self::return_msg('作品错误');
            }
        }
        self::return_msg('采集错误');
    }

    /**
     * @api {post} /caiji/chapter 章节入库
     */
    public function chapter()
    {
        $param = $this->Auth();
        $title = isset($param['title']) ? trim($param['title']) : '';
        $booktitle = isset($param['booktitle']) ? trim($param['booktitle']) : '';
        $content = isset($param['content']) ? trim($param['content']) : '';
        if (empty($title)) {
            self::return_msg('章节名称为空');
        }
        if (empty($booktitle)) {
            self::return_msg('作品名称为空');
        }
        if (empty($content)) {
            self::return_msg('章节内容为空');
        }
        $addtime = isset($param['addtime']) ? strtotime(trim($param['addtime'])) : 0;
        $edittime = isset($param['edittime']) ? strtotime(trim($param['edittime'])) : 0;
        $bookid = 0;
        $book = Db::name('book')->field('id,authorid')->where(['title' => $booktitle])->find();
        if (empty($book)) {
            self::return_msg('作品不存在-跳过');
        }
        $bookid = $book['id'];
        $authorid = $book['authorid'];
        $chaptertable = calc_hash_db($bookid); //章节内容表名
        $chapterData = Db::name('chapter')->field('id')->where(['bookid' => $bookid, 'title' => $title])->find();
        list($wordnum, $str) = countWordsAndContent($content, true);
        if (empty($chapterData)) {
            $chaps = 1;
            $serial = Db::name('chapter')->where(array('bookid' => $bookid))->order('chaps desc')->value('chaps');
            if (!empty($serial)) {
                $chaps = intval($serial) + 1;
            } else {
                $chaps = 1;
            }
            $chapter = [];
            $chapter['draft'] = 0; //非草稿       
            $chapter['wordnum'] = $wordnum;
            $chapter['verify'] = 1;
            $chapter['bookid'] = $bookid;
            $chapter['authorid'] = $authorid;
            $chapter['status'] = 1;
            $chapter['chaps'] = $chaps;
            $chapter['title'] = $title;
            $chapter['create_time'] = $addtime;
            $chapter['update_time'] = $edittime;
            $sid = Db::name('chapter')->strict(false)->field(true)->insertGetId($chapter);
            if ($sid !== false) {
                $cid = Db::name($chaptertable)->strict(false)->field(true)->insertGetId(['sid' => $sid, 'info' => $str]);
                if ($cid !== false) {
                    self::return_msg('ok');
                } else {
                    Db::name('chapter')->where('id', $sid)->delete(); //删除章节
                    self::return_msg('入库失败');
                }
            } else {
                self::return_msg('发布失败');
            }
        } else {
            $chapterContent = Db::name($chaptertable)->where(['sid' => $chapterData['id']])->find();
            if (empty($chapterContent)) {
                $cid = Db::name($chaptertable)->strict(false)->field(true)->insertGetId(['sid' => $chapterData['id'], 'info' => $str]);
                if ($cid !== false) {
                    self::return_msg('ok');
                } else {
                    Db::name('chapter')->where('id', $chapterData['id'])->delete(); //删除章节
                    self::return_msg('入库失败');
                }
            } else {
                self::return_msg('重复跳过');
            }
        }
    }

    /**
     * 统一错误返回
     * Summary of return_msg
     * @param mixed $msg
     * @return never
     */
    protected static function return_msg($msg)
    {
        echo $msg;
        exit;
    }
}
