<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
//use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;

set_time_limit(0);
ini_set('memory_limit', '-1');

class Book extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => ['download']]
    ];


    /**
     * 作品详情
     * Summary of detail
     */
    public function detail()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        if (empty($id)) {
            $this->apiError('参数错误');
        }
        $uid = JWT_UID;
        $detail = Db::name('book')->where(['id' => $id])->find();
        $model_name = \think\facade\App::initialize()->http->getName();
        if ($detail) {
            if (intval($detail['status']) != 1) {
                $this->apiError('作品被禁止');
            }
            $detail['bigclassname'] = Db::name('category')->where(['id' => $detail['genre']])->value('name');
            $detail['smallclassname'] = Db::name('category')->where(['id' => $detail['subgenre']])->value('name');
            $detail['bigclassname'] = $detail['bigclassname'] ?: '-';
            $detail['smallclassname'] = $detail['smallclassname'] ?: '-';
            $detail['cover'] = get_file($detail['cover']);
            $detail['issign'] = Db::name('author')->where(['id' => $detail['authorid']])->value('issign');
            $detail['words'] = wordCount($detail['words']);
            $detail['uptime'] = $detail['update_time'] ? date('Y-m-d H:i:s', $detail['update_time']) : date('Y-m-d H:i:s', $detail['create_time']);
            $detail['chapter'] = Db::name('chapter')->field('id,title,chaps,bookid,create_time')->where(['bookid' => $id, 'status' => 1, ['verify', 'in', '0,1']])->order('chaps asc')->select()->toArray(); //所有章节
            $first_chapter = $last_chapter = [];
            if (!empty($detail['chapter'])) {
                $first_chapter = $detail['chapter'][0]; //第一章
                $last_chapter = end($detail['chapter']); //最后一章
                foreach ($detail['chapter'] as $k => $v) {
                    $url = (string) Route::buildUrl('chapter_detail', ['id' => $v['id']]);
                    $detail['chapter'][$k]['chapter_url'] = str_replace($model_name, 'home', $url);
                    $detail['chapter'][$k]['title'] = get_full_chapter($v['title'], $v['chaps']);
                }
            }
            $detail['first_chapter'] = $first_chapter;
            $detail['last_chapter'] = $last_chapter;
            $ip = app('request')->ip();
            $where = ['book_id' => $id];
            if ($uid) {
                $where['user_id'] = $uid;
                $detail['fav'] = Db::name('favorites')->where(['user_id' => $uid, 'pid' => $detail['id']])->find();
                $detail['fav'] = $detail['fav'] ?: '';
                $detail['follow'] = Db::name('follow')->where(['user_id' => $uid, 'from_id' => $detail['authorid']])->find();
                $detail['follow'] = $detail['follow'] ?: '';
            } else {
                $where['ip'] = $ip;
                $detail['fav'] = '';
                $detail['follow'] = '';
            }
            $detail['continueread'] = 0;
            $detail['fav_count'] = Db::name('favorites')->where(['pid' => $detail['id']])->count();
            //查询是否有该书记录
            $reads = Db::name('readhistory')->field('IF(update_time = 0, create_time, update_time) AS order_time,id,update_time,create_time,title,chapter_id,book_id')->where($where)->order('order_time desc')->find();
            //查询是否有该章节记录
            if (!empty($reads)) {
                $detail['continueread'] = 1;
                $detail['chapter_url'] = (string) Route::buildUrl('chapter_detail', ['id' => $reads['chapter_id']]);
            } else {
                if ($first_chapter) {
                    $detail['chapter_url'] = (string) Route::buildUrl('chapter_detail', ['id' => $first_chapter['id']]);
                } else {
                    $detail['chapter_url'] = 'javascript:;';
                }
            }
            $detail['chapter_url'] = str_replace($model_name, 'home', $detail['chapter_url']);
            $detail['authorurl'] = str_replace($model_name, 'home', (string) Route::buildUrl('author_detail', ['id' => $detail['authorid']]));
            if (!empty($last_chapter)) {
                $detail['chaptertime'] = time_tran($last_chapter['create_time']);
            } else {
                $detail['chaptertime'] = '';
            }
            if (!empty($detail['remark'])) {
                $detail['remark'] = htmlspecialchars_decode($detail['remark']);
                $replace = array("&nbsp;", "<br>", "<br>");
                $search = array(" ", "\n", '\n');
                $detail['remark'] = str_replace($search, $replace, $detail['remark']);
            }
            if (!empty($detail['label']) && strpos($detail['label'], ',') !== false) {
                $detail['label'] = explode(',', $detail['label']);
            } else {
                $detail['label'] = [];
            }
        } else {
            $this->apiError('作品不存在');
        }
        $this->apiSuccess('请求成功', $detail);
    }

    /**
     * 获取作品列表
     * Summary of booklist
     * @return void
     */
    public function booklist()
    {
        $param = get_params();
        $where = ['status' => 1];
        if (isset($param['keywords']) && !empty($param['keywords'])) {
            $where[] = ['title|author', 'like', '%' . $param['keywords'] . '%'];
        }
        $page = isset($param['page']) ? intval($param['page']) : 1;
        $areaid = isset($param['areaid']) ? intval($param['areaid']) : 0;
        //最多可以载加多少页
        if ($page > 10) {
            $page = 10;
        }
        if (!empty($areaid)) {
            $big = [];
            $category = Db::name('category')->field('id,name,pid')->where(['status' => 1])->order('ordernum asc')->select()->toArray();
            if ($areaid == 2) {
                foreach ($category as $key => $value) {
                    if (intval($value['pid']) == 0 && strpos($value['name'], '女生') !== false) {
                        $big = $value['id'];
                        break;
                    }
                }
                if ($big) {
                    $param['genre'] = $big;
                }
            } else {
                foreach ($category as $key => $value) {
                    if (intval($value['pid']) == 0 && strpos($value['name'], '女生') === false) {
                        $big[] = $value['id'];
                    }
                }
                if (count($big) > 0) {
                    $param['genre'] = implode(',', $big);
                }
            }
        }
        if (isset($param['genre']) && !empty($param['genre'])) {
            if (strpos((string)$param['genre'], ',') !== false) {
                $where[] = ['genre', 'in', $param['genre']];
            } else {
                $where[] = ['genre', '=', $param['genre']];
            }
        }
        if (isset($param['subgenre']) && !empty($param['subgenre'])) {
            if (strpos((string)$param['subgenre'], ',') !== false) {
                $where[] = ['subgenre', 'in', $param['subgenre']];
            } else {
                $where[] = ['subgenre', '=', $param['subgenre']];
            }
        }
        if (isset($param['bookid']) && !empty($param['bookid'])) {
            $where[] = ['id', '<>', $param['bookid']];
        }
        if (isset($param['authorid']) && !empty($param['authorid'])) {
            $where[] = ['authorid', '=', $param['authorid']];
        }
        if (isset($param['isfinish']) && !empty($param['isfinish'])) {
            $where[] = ['isfinish', '=', $param['isfinish']];
        }
        if (isset($param['rand']) && !empty($param['rand']) && isset($param['limit']) && !empty($param['limit'])) {
            $count = Db::name('book')->where(['status' => 1])->count();
            $limit = intval($param['limit']);
            if ($count > $limit) {
                $count_page = ceil($count / $limit);
                if ($count_page > 1) {
                    $page = mt_rand(1, (int)$count_page);
                }
            }
        }
        if (isset($param['words']) && !empty($param['words'])) {
            $words = intval($param['words']);
            //300万字以上
            if ($words == 1) {
                $where[] = ['words', '>=', 10000 * 300];
            }
            //100万字以上
            if ($words == 2) {
                $where[] = ['words', '>=', 10000 * 100];
            }
            //50万字以上
            if ($words == 3) {
                $where[] = ['words', '>=', 10000 * 50];
            }
            //30万字以下
            if ($words == 4) {
                $where[] = ['words', '<=', 10000 * 30];
            }
            //30-50万字
            if ($words == 5) {
                $where[] = ['words', '>=', 10000 * 30];
                $where[] = ['words', '<=', 10000 * 50];
            }
            //50-100万字
            if ($words == 6) {
                $where[] = ['words', '>=', 10000 * 50];
                $where[] = ['words', '<=', 10000 * 100];
            }
            //100-300万字
            if ($words == 7) {
                $where[] = ['words', '>=', 10000 * 100];
                $where[] = ['words', '<=', 10000 * 300];
            }
        }
        if (!isset($param['order']) || empty($param['order'])) {
            $param['order'] = 'sort DESC';
        }
        if (isset($param['limit'])) {
            $param['limit'] = intval($param['limit']);
        }
        $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
        $order = empty($param['order']) ? 'id desc' : $param['order'];
        $list =  Db::name('book')->where($where)
            ->field('id,title,author,authorid,cover,style,ending,genre,subgenre,isfinish,finishtime,chapters,label,label_custom,hits,words,status,editor,editorid,issign,create_time,update_time,remark,filename')
            ->order($order)
            ->paginate(['list_rows' => $rows, 'var_page' => 'page', 'page' => $page, 'query' => $param]);
        $result = $list->toArray();
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $author = Db::name('author')->where(['id' => $v['authorid']])->find();
                if (empty($author)) {
                    unset($result['data'][$k]);
                    continue;
                }
                $result['data'][$k]['cover']  = get_file($v['cover']);
                $result['data'][$k]['bigcatetitle'] = Db::name('category')->where(['id' => $v['genre']])->value('name');
                $result['data'][$k]['sellcatetitle'] = Db::name('category')->where(['id' => $v['subgenre']])->value('name');
                $result['data'][$k]['headpic'] = get_file($author['headimg']);
                $result['data'][$k]['cover_str'] = get_file($v['cover']);
                $result['data'][$k]['isfinish_str'] = intval($v['isfinish']) == 2 ? '完结' : '连载';
                $result['data'][$k]['words_str'] = intval($v['words']) > 0 ? wordCount($v['words']) : 0;
                $result['data'][$k]['authorurl'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('author_detail', ['id' => $v['authorid']]));
                $result['data'][$k]['url'] = str_replace(\think\facade\App::initialize()->http->getName(), 'home', (string) Route::buildUrl('book_detail', ['id' => $v['filename'] ? $v['filename'] : $v['id']]));
            }
        } else {
            $result = [
                'data' => [],
                'total' => 0,
                'current_page' => 1,
                'last_page' => 0,
                'per_page' => 0
            ];
        }
        $this->apiSuccess('请求成功', $result);
    }

    /**
     * 获取下载
     * Summary of getdown
     * @return void
     */
    public function getdown()
    {
        $param = get_params();
        $bookid = isset($param['bookid']) ? $param['bookid'] : 0;
        $type = isset($param['type']) ? $param['type'] : 'txt';
        if (empty($bookid)) {
            $this->apiError('参数错误');
        }
        $uid = JWT_UID;
        $power_config = get_system_config('power');
        if (isset($power_config['txt_download_open']) && intval($power_config['txt_download_open']) == 1) {
            $txt_download_islogin = isset($power_config['txt_download_islogin']) ? intval($power_config['txt_download_islogin']) : 0;
            if ($txt_download_islogin == 1 && empty($uid)) {
                $this->apiError('请先登录');
            }
            $book = Db::name('book')->where(['id' => $bookid])->find();
            if (empty($book)) {
                $this->apiError('作品不存在');
            }
            if (intval($book['status']) != 1) {
                $this->apiError('作品被禁止');
            }
            $relativepath = 'runtime' . DIRECTORY_SEPARATOR . 'down' . DIRECTORY_SEPARATOR . $book['id'] . DIRECTORY_SEPARATOR;
            $path = app()->getRootPath() . $relativepath;
            if (!createDirectory($path)) {
                return to_assign(1, '创建' . $path . '目录失败');
            }
            $token = uuid('down_' . $book['id'] . '_' . $type . '_');
            $down_path = get_cache($token);
            if (!empty($down_path)) {
                $this->apiSuccess('请求成功', ['url' => (string) Route::buildUrl('download', ['token' => $token])]);
            }
            $file = $path . $book['id'] . '.' . $type;
            if (is_file($file)) {
                $newChapter = Db::name('chapter')->field('IF(update_time = 0, create_time, update_time) AS order_time,create_time,update_time')->where(['bookid' => $bookid, 'status' => 1, ['verify', 'in', '0,1']])->order('order_time desc')->find();
                if (!empty($newChapter)) {
                    $file_time =  filectime($file);
                    if ($file_time > intval($newChapter['order_time'])) {
                        set_cache($token, $file, 60);
                        $this->apiSuccess('请求成功', ['url' => (string) Route::buildUrl('download', ['token' => $token])]);
                    }
                }
            }
            $chaptertable = calc_hash_db($book['id']);
            $chapters = Db::name('chapter')->field('id,title,chaps,wordnum')->where(['bookid' => $bookid, 'status' => 1, ['verify', 'in', '0,1']])->order('chaps asc')->select()->toArray(); //所有章节
            if (empty($chapters)) {
                $this->apiError('章节为空');
            }
            $txt_download_num = isset($power_config['txt_download_num']) ? intval($power_config['txt_download_num']) : 0;
            $txt_download_promotion_type = isset($power_config['txt_download_promotion_type']) ? intval($power_config['txt_download_promotion_type']) : 0;
            $txt_download_promotion_content = isset($power_config['txt_download_promotion_content']) ? trim($power_config['txt_download_promotion_content']) : '';
            if ($txt_download_num > 0) {
                $chapters = array_slice($chapters, 0, $txt_download_num);
            }
            $novelContent = '';
            foreach ($chapters as $key => $value) {
                if (intval($value['wordnum']) <= 0) {
                    if (get_addons_is_enable('caijipro')) {
                        $content = hook('caijiproChapterHook', ['chapterid' => $value['id']]);
                        if ($content && mb_strlen($content) > 0) {
                            list($wordnum, $content) = countWordsAndContent($content);
                            $novelContent .= "\r\n\r\n" . get_full_chapter($value['title'], $value['chaps']) . "\r\n";
                            $novelContent .= $content;
                        }
                    }
                } else {
                    $content = Db::name($chaptertable)->where(['sid' => $value['id']])->value('info');
                    if (empty($content)) {
                        if (get_addons_is_enable('caijipro')) {
                            $content = hook('caijiproChapterHook', ['chapterid' => $value['id']]);
                            if ($content && mb_strlen($content) > 0) {
                                list($wordnum, $content) = countWordsAndContent($content);
                                $novelContent .= "\r\n\r\n" . get_full_chapter($value['title'], $value['chaps']) . "\r\n";
                                $novelContent .= $content;
                            }
                        }
                    } else {
                        $novelContent .= "\r\n\r\n" . get_full_chapter($value['title'], $value['chaps']) . "\r\n";
                        $novelContent .= $content;
                    }
                }
                if (mb_strlen($txt_download_promotion_content) > 0 && $txt_download_promotion_type > 0) {
                    //头部添加
                    if ($txt_download_promotion_type == 1 && $key == 0) {
                        $novelContent = $txt_download_promotion_content . "\r\n\r\n" . $novelContent;
                        //尾部添加
                    } else if ($txt_download_promotion_type == 2 && (count($chapters) - 1) == $key) {
                        $novelContent .= "\r\n\r\n" . $txt_download_promotion_content;
                        //头尾添加
                    } else if ($txt_download_promotion_type == 3 && $key == 0) {
                        $novelContent = $txt_download_promotion_content . "\r\n\r\n" . $novelContent;
                        //头尾添加
                    } else if ($txt_download_promotion_type == 3 && (count($chapters) - 1) == $key) {
                        $novelContent .= "\r\n\r\n" . $txt_download_promotion_content;
                        //每章添加
                    } else if ($txt_download_promotion_type == 4) {
                        $novelContent .= "\r\n\r\n" . $txt_download_promotion_content;
                    }
                }
            }
            try {
                $stream = fopen($file, "w");
                if ($stream === false) {
                    $this->apiError('生成文件失败');
                }
                fwrite($stream, $novelContent); // 写入内容
                fclose($stream); // 关闭文件
            } catch (\Exception $e) {
                $this->apiError('生成文件失败');
            }
            if (!is_file($file)) {
                $this->apiError('生成文件失败');
            }
            set_cache($token, $file, 60);
            $this->apiSuccess('请求成功', ['url' => (string) Route::buildUrl('download', ['token' => $token])]);
        } else {
            $this->apiError('禁止下载');
        }
    }

    public function download()
    {
        $param = get_params();
        $token = isset($param['token']) ? $param['token'] : '';
        if (empty($token)) {
            $this->apiError('参数错误');
        }
        list($action, $bookid, $type) = explode('_', $token);
        if (empty($bookid)) {
            $this->apiError('token错误');
        }
        if (empty($type)) {
            $this->apiError('token错误');
        }
        $book = Db::name('book')->where(['id' => $bookid])->find();
        if (empty($book)) {
            $this->apiError('作品不存在');
        }
        if (intval($book['status']) != 1) {
            $this->apiError('作品被禁止');
        }
        $down_path = get_cache($token);
        if (empty($down_path)) {
            $this->apiError('参数错误');
        }
        if (!is_file($down_path)) {
            $this->apiError('下载文件不存在');
        }
        if ($type == 'txt') {
            $filename = $book['title'] . '.txt';
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($down_path));
            readfile($down_path);
            exit;
        }
    }
}
