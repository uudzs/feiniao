<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Book as BookModel;
use app\admin\validate\BookValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;
use Overtrue\Pinyin\Pinyin;

class Book extends BaseController
{

    var $uid;
    var $model;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new BookModel();
        $this->uid = get_login_admin('id');
    }
    /**
     * 数据列表
     */
    public function datalist()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = [];
            if (!empty($param['keywords'])) {
                $where[] = ['title|author|style|ending|label|label_custom', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['subgenre'])) {
                $where[] = ['subgenre', '=', $param['subgenre']];
            }
            if (!empty($param['cate_id'])) {
                $where[] = ['genre|subgenre', '=', $param['cate_id']];
            }
            $param['order'] = 'id desc';
            $list = $this->model->getBookList($where, $param);
            $list = $list->toArray();
            $starttime = strtotime(date('Y-m-01', strtotime('-1 month', time()))); // 获取上个月的第一天
            $endtime = strtotime(date('Y-m-01', time())) - 1; // 获取上个月的最后一天
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['lastmonthwords'] = Db::name('chapter')->where(['bookid' => $v['id'], 'status' => 1, ['verify', 'in', '0,1'], ['create_time', '>=', $starttime], ['create_time', '<=', $endtime]])->sum('wordnum');
            }
            return table_assign(0, '', $list);
        } else {
            return view();
        }
    }

    public function savefield()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $id = $param['id'];
            if (!$id) {
                return to_assign(1, '参数错误');
            }
            unset($param['id']);
            $this->model->where('id', $id)->strict(false)->field(true)->update($param);
            return to_assign();
        } else {
            return to_assign(1, '请求失败');
        }
    }

    /**
     * 添加
     */
    public function add()
    {
        if (request()->isAjax()) {
            $param = get_params();

            // 检验完整性
            try {
                validate(BookValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $param['label'] = implode(',', [$param['identity'], $param['image'], $param['schools'], $param['element']]);
            unset($param['id'], $param['label_custom_ids'], $param['identity'], $param['image'], $param['schools'], $param['element']);
            $label = strtr($param['label'], ',', '');
            if (empty(trim($label))) {
                $param['label'] = '';
            }
            if (isset($param["outlinetime"])) {
                $param["outlinetime"] = $param["outlinetime"] ? strtotime($param["outlinetime"]) : 0;
            }
            if (isset($param["finishtime"])) {
                $param["finishtime"] = $param["finishtime"] ? strtotime($param["finishtime"]) : 0;
            }
            if (empty($param['title'])) {
                return to_assign(1, '作品名称不能为空');
            }
            if (empty($param['authorid'])) {
                return to_assign(1, '作者不能为空');
            }
            if (empty($param['genre'])) {
                return to_assign(1, '作品大类不能为空');
            }
            if (empty($param['subgenre'])) {
                return to_assign(1, '作品小类不能为空');
            }
            $param['create_time'] = time();
            $filename = Pinyin::permalink($param['title'], '');
            $book = Db::name('book')->where(['filename' => $filename])->find();
            $param['filename'] = $filename;
            $insertId = Db::name('book')->strict(false)->field(true)->insertGetId($param);
            if ($insertId !== false) {
                if (!empty($book)) {
                    $filename = $filename . $insertId;
                    Db::name('book')->where('id', $insertId)->strict(false)->field(true)->update(['filename' => $filename]);
                }
            }
            add_log('add', $insertId, $param);
            return to_assign(0, '操作成功', ['aid' => $insertId]);
            //$this->model->addBook($param);
        } else {
            if (get_addons_is_enable('booktag')) {
                $result = hook("bookTagHook");
                $result = json_decode($result, true);
                $tags = $result['data'];
                View::assign('tags', $tags);
            } else {
                View::assign('tags', []);
            }
            $genres = Db::name('category')->where(['pid' => 0, 'status' => 1])->order('ordernum asc')->select()->toArray();
            View::assign('genres', $genres);
            return view();
        }
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $param = get_params();
        if (request()->isAjax()) {
            // 检验完整性
            try {
                validate(BookValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            if (empty($param['id'])) {
                return to_assign(1, '作品ID为空');
            }
            $book = $this->model->getBookById($param['id']);
            if (empty($book)) {
                return to_assign(1, '作品不存在');
            }
            if (isset($param["outlinetime"])) {
                $param["outlinetime"] = $param["outlinetime"] ? strtotime($param["outlinetime"]) : 0;
            }
            if (isset($param["finishtime"])) {
                $param["finishtime"] = $param["finishtime"] ? strtotime($param["finishtime"]) : 0;
            }
            unset($param['file']);
            if (empty($param['title'])) {
                return to_assign(1, '作品名称不能为空');
            }
            if (empty($param['authorid'])) {
                return to_assign(1, '作者不能为空');
            }
            if (empty($param['genre'])) {
                return to_assign(1, '作品大类不能为空');
            }
            if (empty($param['subgenre'])) {
                return to_assign(1, '作品小类不能为空');
            }
            // if (empty($param['style'])) {
            //     return to_assign(1, '作品风格不能为空');
            // }
            // if (empty($param['ending'])) {
            //     return to_assign(1, '作品结局不能为空');
            // }
            if (!isset($param['identity'])) {
                $param['identity'] = '';
            }
            if (!isset($param['image'])) {
                $param['image'] = '';
            }
            if (!isset($param['schools'])) {
                $param['schools'] = '';
            }
            if (!isset($param['element'])) {
                $param['element'] = '';
            }
            $param['label'] = implode(',', [$param['identity'], $param['image'], $param['schools'], $param['element']]);
            unset($param['identity'], $param['image'], $param['schools'], $param['element']);
            $label = strtr($param['label'], ',', '');
            if (empty(trim($label))) {
                $param['label'] = '';
            }
            if ($param['title'] != $book['title']) {
                $filename = Pinyin::permalink($param['title'], '');
                $filenamebook = Db::name('book')->where(['filename' => $filename, ['id', '<>', $book['id']]])->find();
                if (!empty($filenamebook)) {
                    $filename = $filename . $book['id'];
                }
                $param['filename'] = $filename;
                $param['update_time'] = time();
                Db::name('book')->where('id', $book['id'])->strict(false)->field(true)->update($param);
                add_log('edit', $param['id'], $param);
                return to_assign();
            } else {
                $this->model->editBook($param);
            }
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $detail = $this->model->getBookById($id);
            if (!empty($detail)) {
                $identity = $image = $schools = $element = '';
                if (get_addons_is_enable('booktag')) {
                    $result = hook("bookTagHook");
                    $result = json_decode($result, true);
                    $tags = $result['data'];
                } else {
                    $tags = [];
                }
                if (!empty($detail['label'])) {
                    $labels = explode(',', $detail['label']);
                    foreach ($tags['identity']['data'] as $v) {
                        if (in_array($v, $labels)) {
                            $identity = $v;
                            break;
                        }
                    }
                    foreach ($tags['image']['data'] as $v) {
                        if (in_array($v, $labels)) {
                            $image = $v;
                            break;
                        }
                    }
                    foreach ($tags['schools']['data'] as $v) {
                        if (in_array($v, $labels)) {
                            $schools = $v;
                            break;
                        }
                    }
                    foreach ($tags['element']['data'] as $v) {
                        if (in_array($v, $labels)) {
                            $element = $v;
                            break;
                        }
                    }
                }
                $subgenre = Db::name('category')->where(['pid' => $detail['genre'], 'status' => 1])->order('ordernum asc')->select()->toArray();
                $genres = Db::name('category')->where(['pid' => 0, 'status' => 1])->order('ordernum asc')->select()->toArray();
                if (empty($detail['editor'])) {
                    $detail['editor'] = '';
                }
                if (empty($detail['label_custom'])) {
                    $detail['label_custom'] = '';
                }
                if (empty($detail['outline'])) {
                    $detail['outline'] = '';
                }
                View::assign('genres', $genres);
                View::assign('subgenre', $subgenre);
                View::assign('identity', $identity);
                View::assign('image', $image);
                View::assign('schools', $schools);
                View::assign('element', $element);
                View::assign('tags', $tags);
                View::assign('detail', $detail);
                return view();
            } else {
                throw new \think\exception\HttpException(404, '找不到页面');
            }
        }
    }

    public function booklist()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = ['status' => 1];
            if (!empty($param['keywords'])) {
                $where[] = ['title|author', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['cate_id'])) {
                $where[] = ['genre|subgenre', '=', $param['cate_id']];
            }
            $param['order'] = 'create_time Asc';
            $list = $this->model->getBookList($where, $param);
            return table_assign(0, '', $list);
        } else {
            return view();
        }
    }

    //导入
    public function import()
    {
        if (request()->isPost()) {
            set_time_limit(0);
            $param = get_params();
            if (request()->file('txtfile')) {
                $file = request()->file('txtfile');
            } else {
                return to_assign(1, '没有选择上传文件');
            }
            $md5 = $file->hash('md5');
            $fileExt = ['txt'];
            //1M=1024*1024=1048576字节
            $fileSize = 100000 * 1024 * 1024;
            $validate = \think\facade\Validate::rule([
                'image' => 'require|fileSize:' . $fileSize . '|fileExt:' . implode(',', $fileExt),
            ]);
            $file_check['image'] = $file;
            if (!$validate->check($file_check)) {
                return to_assign(1, $validate->getError());
            }
            // 日期前綴
            $dataPath = date('Ymd');
            $filename = \think\facade\Filesystem::disk('public')->putFile($dataPath, $file, function () use ($md5) {
                return $md5;
            });
            if ($filename) {
                $path = get_config('filesystem.disks.public.url');
                $realPath = CMS_ROOT . "public" . $path . '/' . $filename;
                $chapter = [];
                $data_link = [];
                $str = $title = $author = $genre = '';
                $big_cate_id = 0; //大类
                try {
                    //逐行读取文件内容
                    $handle = fopen($realPath, 'r');
                    while (($line = fgets($handle)) !== false) {
                        $e = mb_detect_encoding($line, array('UTF-8', 'ASCII', 'GB2312', 'GBK', 'BIG5'));
                        if ($e != 'UTF-8') {
                            $line = mb_convert_encoding($line, 'UTF-8', $e);
                            //$line = iconv($e, 'UTF-8', $line);
                        }
                        //作者：作者: 
                        if (empty($author)) {
                            if (strpos($line, ' 著') !== false) {
                                $author = explode(' 著', $line)[0];
                            }
                            if (strpos($line, '作者:') !== false) {
                                $a = explode('作者:', $line);
                                $author = isset($a[1]) ? $a[1] : '';
                            }
                            if (strpos($line, '作者：') !== false) {
                                $a = explode('作者：', $line);
                                $author = isset($a[1]) ? $a[1] : '';
                            }
                        }
                        if (empty($genre)) {
                            if (strpos($line, '大类：') !== false) {
                                $a = explode('大类：', $line);
                                $genre = isset($a[1]) ? $a[1] : '';
                            }
                            if (strpos($line, '大类:') !== false) {
                                $a = explode('大类:', $line);
                                $genre = isset($a[1]) ? $a[1] : '';
                            }
                        }
                        $line = preg_replace('/^[\p{C}\s]+|[\p{C}\s]+$/u', '', $line);
                        if (preg_match_all("/^([序|尾]+[章|声])([^\r\n]+)?/u", $line, $arr)) {
                            unset($arr);
                            if (mb_strlen($line) > 50) {
                                $str .= $line . "\n";
                            } else {
                                if ($title) {
                                    $chapter[] = [
                                        'title' => $title,
                                        'content' => $str
                                    ];
                                    $str = '';
                                }
                                $title = $line;
                            }
                        } else {
                            if (preg_match_all("/^([第][\d一二两三四五六七八九零十百千万、-]+[章|章节|卷|集|回])([^\r\n]+)?/u", $line, $arr)) {
                                unset($arr);
                                if (mb_strlen($line) > 50) {
                                    $str .= $line . "\n";
                                } else {
                                    if ($title) {
                                        $chapter[] = [
                                            'title' => $title,
                                            'content' => $str
                                        ];
                                        $str = '';
                                    }
                                    $title = $line;
                                }
                            } else {
                                $str .= $line . "\n";
                            }
                        }
                        unset($line);
                    }
                    fclose($handle);
                } catch (\Exception $e) {
                    return to_assign(1, '导入失败:' . $e->getMessage());
                }
                $chapter[] = [
                    'title' => $title,
                    'content' => $str
                ];
                $title = $file->getOriginalName();
                if (!empty($title)) {
                    $title = explode('.', trim($title))[0];
                }
                unlink($realPath);
                if (empty($title)) {
                    return to_assign(1, '文件名称获取失败');
                }
                if (strpos($title, '《') !== false) {
                    $parts = explode('《', $title);
                    if (isset($parts[1]) && strpos($parts[1], '》') !== false) {
                        $title = explode('》', $parts[1])[0];
                    }
                }
                if (empty($title)) {
                    return to_assign(1, '作品名称识别失败');
                }
                if (empty($author)) {
                    return to_assign(1, '作者获取失败');
                }
                if (!empty($chapter)) {
                    $book = Db::name('book')->where(['title' => $title])->find();
                    $user = Db::name('author')->where(['nickname' => trim($author)])->find();
                    if (empty($user)) {
                        $time = (string) time();
                        $salt = substr(MD5($time), 0, 6);
                        $password = set_salt(20);
                        $data = array(
                            'nickname' => trim($author),
                            'salt' => $salt,
                            'password' => sha1(MD5($password) . $salt),
                            'ip' => request()->ip(),
                            'create_time' => time(),
                            'status' => 1,
                        );
                        $authorid = Db::name('author')->strict(false)->field(true)->insertGetId($data);
                        if ($authorid !== false) {
                            $data_link[] = furl('author_detail', ['id' => $authorid], true, 'home');
                        }
                    } else {
                        $authorid = $user['id'];
                        $author = $user['nickname'];
                    }
                    $category = Db::name('category')->where(['name' => trim($genre)])->find();
                    if (!empty($category)) {
                        $big_cate_id = $category['id'];
                    }
                    if (empty($book)) {
                        $bookdata = [
                            'title' => $title,
                            'author' => $author,
                            'authorid' => $authorid,
                            'status' => 1,
                            'genre' => $big_cate_id,
                            'filename' => Pinyin::permalink($title, ''),
                            'create_time' => time(),
                        ];
                        $bookid = Db::name('book')->strict(false)->field(true)->insertGetId($bookdata);
                        if ($bookid !== false) {
                            $data_link[] = furl('book_detail', ['id' => $bookdata['filename'] ? $bookdata['filename'] : $bookid], true, 'home');
                        }
                    } else {
                        $bookid = $book['id'];
                    }
                    $chaptertable = calc_hash_db($bookid); //章节内容表名
                    $skip = $success = $fail = 0; //跳过、成功、失败
                    foreach ($chapter as $k => $v) {
                        $istitle = Db::name('chapter')->where(['bookid' => $bookid, 'title' => $v['title']])->find();
                        if (!empty($istitle)) {
                            $skip++;
                            continue;
                        }
                        list($wordnum, $content) = countWordsAndContent($v['content'], true);
                        $data = [
                            'bookid' => $bookid,
                            'title' => $v['title'],
                            'chaps' => $k + 1,
                            'status' => 1,
                            'verify' => 1,
                            'trial_time' => 0,
                            'verifyresult' => '',
                            'verifytime' => time(),
                            'wordnum' => $wordnum,
                            'create_time' => time()
                        ];
                        $sid = Db::name('chapter')->strict(false)->field(true)->insertGetId($data);
                        if ($sid !== false) {
                            $data_link[] = furl('chapter_detail', ['id' => $sid], true, 'home');
                            Db::name($chaptertable)->strict(false)->field(true)->insertGetId(['sid' => $sid, 'info' => $content]);
                            $success++;
                        } else {
                            $fail++;
                        }
                    }
                    $words = Db::name('chapter')->where(array('bookid' => $bookid, ['verify', 'in', '0,1']))->sum('wordnum');
                    $booksave = [];
                    $booksave['words'] = $words;
                    $booksave['chapters'] = Db::name('chapter')->where(array('bookid' => $bookid, ['verify', 'in', '0,1']))->count();
                    $booksave['update_time'] = time();
                    $res = Db::name('book')->where(['id' => $bookid])->strict(false)->field(true)->update($booksave);
                    if (get_addons_is_enable('baidupush') && $data_link) {
                        hook('baiduPushHook', ['type' => 'add', 'data' => $data_link]);
                    }
                    return to_assign(0, '导入成功' . $success . '章，跳过重复章节' . $skip . '章。');
                } else {
                    return to_assign(1, '未识别到章节');
                }
            } else {
                return to_assign(1, '上传失败，请重试');
            }
        } else {
            $param = get_params();
            return view();
        }
    }

    /**
     * 查看信息
     */
    public function read()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $detail = $this->model->getBookById($id);
        if (!empty($detail)) {
            View::assign('detail', $detail);
            return view();
        } else {
            throw new \think\exception\HttpException(404, '找不到页面');
        }
    }

    /**
     * 删除
     * type=0,逻辑删除，默认
     * type=1,物理删除
     */
    public function del()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $i = 0;
        if (strpos($id, ',') !== false) {
            $list = explode(',', $id);
            foreach ($list as $key => $value) {
                $book = Db::name('book')->where(['id' => $value])->find();
                if (!empty($book)) {
                    $chaptertable = calc_hash_db($book['id']); //章节内容表名
                    $chapter = Db::name('chapter')->where(['bookid' => $book['id']])->select();
                    $chapter = $chapter ? $chapter->toArray() : [];
                    foreach ($chapter as $k => $v) {
                        Db::name('chapter')->where(['id' => $v['id']])->delete();
                        Db::name('chapter_draft')->where(['cid' => $v['id']])->delete(); //草稿箱
                        Db::name('chapter_verify')->where(['cid' => $v['id']])->delete(); //审核库
                        Db::name($chaptertable)->where(['sid' => $v['id']])->delete();
                    }
                    Db::name('book')->where(['id' => $book['id']])->delete(); //作品
                    $i++;
                }
            }
        } else {
            $book = Db::name('book')->where(['id' => $id])->find();
            if (empty($book)) {
                return to_assign(1, '作品不存在');
            }
            $chaptertable = calc_hash_db($book['id']); //章节内容表名
            $chapter = Db::name('chapter')->where(['bookid' => $book['id']])->select();
            $chapter = $chapter ? $chapter->toArray() : [];
            foreach ($chapter as $k => $v) {
                Db::name('chapter')->where(['id' => $v['id']])->delete();
                Db::name('chapter_draft')->where(['cid' => $v['id']])->delete(); //草稿箱
                Db::name('chapter_verify')->where(['cid' => $v['id']])->delete(); //审核库
                Db::name($chaptertable)->where(['sid' => $v['id']])->delete();
            }
            Db::name('book')->where(['id' => $book['id']])->delete(); //作品
            $i++;
        }
        return to_assign(0, '删除' . $i . '个作品！');
    }
}
