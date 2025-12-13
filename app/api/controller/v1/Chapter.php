<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;
use content\Content;

class Chapter extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];

    /**
     * 章节详情
     * Summary of detail
     * @return void
     */
    public function detail()
    {
        $param = get_params();
        $id = intval($param['id']);
        $uid = JWT_UID;
        if (empty($id)) {
            $this->apiError('empty');
        }
        $chapter = \app\common\model\Chapter::getChapterDetail($id);
        if (empty($chapter)) {
            $this->apiError('404');
        }
        if (intval($chapter['status']) !== 1) {
            $this->apiError('407');
        }
        if (intval($chapter['verify']) === 2) {
            $this->apiError('407');
        }
        $book = \app\common\model\Novel::getBookDetail($chapter['bookid']);
        if (empty($book)) {
            $this->apiError('404');
        }
        if (intval($book['status']) !== 1) {
            $this->apiError('407');
        }
        $chapter['chapteraccess'] = chapterCheckAccess($id);
        $hide_content = $chapter['chapteraccess'] !== 1;
        $book['cover'] = get_file($book['cover']);
        $chapter['title'] = get_full_chapter($chapter['title'], $chapter['chaps']);
        $chapter['book'] = $book;
        $chapter['hide_content'] = $hide_content;
        if (!$hide_content) {
            if (app('request')->isMobile() || isWeChat() || !get_system_config('content', 'chapter_pages_content_open')) {
                $content = Content::get($book['id'], $chapter['id']);
                if (empty($content)) {
                    $obj = auto_run_addons('collect', [
                        'type' => 'single_chapter',
                        'chapter_id' => $id
                    ]);
                    $content = current(array_filter($obj));
                }
                if ($content && mb_strlen($content) > 0) {
                    list($wordnum, $content) = countWordsAndContent($content, true);
                    $chapter['wordnum'] = $wordnum;
                    $chapter['content'] = htmlspecialchars_decode($content);
                    $replace = array("", "<br>", "<br>");
                    $search = array(" ", "\n", '\n');
                    $chapter['content'] = str_replace($search, $replace, $chapter['content']);
                }
            } else {
                $chapter['content'] = '';
            }
        } else {
            $chapter['content'] = lang('common.nopermission');
        }
        $bookid = $book['id'];
        $chapter_id = $id;
        $ip = request()->ip();
        $model_name = \think\facade\App::initialize()->http->getName();
        if (!empty($uid)) {
            $member = Db::name('user')->where(array('id' => $uid))->find();
            if (!empty($member)) {
                //查询是否有该章节记录
                $readhistory = Db::name('readhistory')->where(array('book_id' => $bookid, 'chapter_id' => $chapter_id, 'user_id' => $member['id']))->find();
                //增加阅读记录
                if (empty($readhistory)) {
                    Db::name('readhistory')->strict(false)->field(true)->insertGetId([
                        "user_id" => $member['id'],
                        "book_id" => $bookid,
                        "chapter_id" => $chapter_id,
                        'read_date' => date('Y-m-d'),
                        'ip' => $ip,
                        "title" => $chapter['title'],
                        "position" => '',
                        "readnum" => 1,
                        "create_time" => time()
                    ]);
                } else {
                    Db::name('readhistory')->where(['id' => $readhistory['id']])->update([
                        'update_time' => time(),
                        "position" => '',
                        'read_date' => date('Y-m-d'),
                        'ip' => $ip,
                        "title" => $chapter['title'],
                        'readnum' => $readhistory['readnum'] + 1,
                    ]);
                }
                //每日阅读章节奖励
                $conf = get_system_config('reward');
                $read_number = intval($conf['chapter_number']);
                $reward = intval($conf['chapter_reward']);
                $name = trim($conf['chapter_id']);
                if ($read_number > 0 && $reward > 0) {
                    $readcount = Db::name('readhistory')->where(['user_id' => $member['id'], 'read_date' => date('Y-m-d')])->count();
                    $already = Db::name('task')->where(['user_id' => $member['id'], 'taskid' => $name, 'task_date' => date('Y-m-d'), 'status' => 0])->find();
                    if (!empty($already) && intval($readcount) >= $read_number) {
                        $vip = Db::name('vip_log')->where(['status' => 1, 'user_id' => $uid, ['expire_time', '>', time()]])->find();
                        if (!empty($vip)) {
                            if (floatval($conf['vip_reward']) > 1) {
                                $reward = floor($reward * floatval($conf['vip_reward']));
                            }
                        }
                        // 开启事务
                        Db::startTrans();
                        try {
                            // 执行数据库操作
                            Db::name('user')->where('id', $member['id'])->inc('coin', $reward)->update();
                            add_coin_log($member['id'], $reward, 1, lang('reward.dayreadchapter'));
                            Db::name('task')->where('id', $already['id'])->update(['status' => 1, 'update_time' => time()]);
                            // 提交事务
                            Db::commit();
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                        }
                    }
                }
                //如果有邀请人
                if (intval($member['inviter']) > 0) {
                    $senior = Db::name('user')->where(array('id' => $member['inviter']))->find();
                    if (!empty($senior)) {
                        $one_level = $two_level = $three_level = false;
                        $starttime = intval($member['register_time']);
                        $count = 0;
                        for ($i = 0; $i < 7; $i++) {
                            $starttime = $starttime + ($i * 86400);
                            $endtime = $starttime + 86399;
                            $readcount = Db::name('readhistory')->where(['user_id' => $member['id'], ['create_time', '>=', $starttime], ['create_time', '<=', $endtime]])->count();
                            if (intval($readcount) > 0) {
                                $count++;
                                if ($i == 0) {
                                    $one_level = true;
                                }
                                if ($i == 2 && $count == 3) {
                                    $two_level = true;
                                }
                                if ($i == 6 && $count == 7) {
                                    $three_level = true;
                                }
                            }
                        }
                        $level_1 = Db::name('task')->where(['user_id' => $senior['id'], 'taskid' => $uid, 'type' => 4, 'status' => 0])->find();
                        if (!empty($level_1) && $one_level) {
                            $reward = intval($conf['invite_1_level']);
                            $vip = Db::name('vip_log')->where(['status' => 1, 'user_id' => $senior['id'], ['expire_time', '>', time()]])->find();
                            if (!empty($vip)) {
                                if (floatval($conf['vip_reward']) > 1) {
                                    $reward = floor($reward * floatval($conf['vip_reward']));
                                }
                            }
                            // 开启事务
                            Db::startTrans();
                            try {
                                // 执行数据库操作
                                Db::name('user')->where('id', $senior['id'])->inc('coin', $reward)->update();
                                add_coin_log($senior['id'], $reward, 1, lang('login.firstread'));
                                Db::name('task')->where('id', $level_1['id'])->update(['status' => 1, 'update_time' => time()]);
                                // 提交事务
                                Db::commit();
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                            }
                        }
                        $level_2 = Db::name('task')->where(['user_id' => $senior['id'], 'taskid' => $uid, 'type' => 5, 'status' => 0])->find();
                        if (!empty($level_2) && $two_level) {
                            $reward = intval($conf['invite_2_level']);
                            $vip = Db::name('vip_log')->where(['status' => 1, 'user_id' => $senior['id'], ['expire_time', '>', time()]])->find();
                            if (!empty($vip)) {
                                if (floatval($conf['vip_reward']) > 1) {
                                    $reward = floor($reward * floatval($conf['vip_reward']));
                                }
                            }
                            // 开启事务
                            Db::startTrans();
                            try {
                                // 执行数据库操作
                                Db::name('user')->where('id', $senior['id'])->inc('coin', $reward)->update();
                                add_coin_log($senior['id'], $reward, 1, lang('login.day3read'));
                                Db::name('task')->where('id', $level_2['id'])->update(['status' => 1, 'update_time' => time()]);
                                // 提交事务
                                Db::commit();
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                            }
                        }
                        $level_3 = Db::name('task')->where(['user_id' => $senior['id'], 'taskid' => $uid, 'type' => 6, 'status' => 0])->find();
                        if (!empty($level_3) && $three_level) {
                            $reward = intval($conf['invite_3_level']);
                            $vip = Db::name('vip_log')->where(['status' => 1, 'user_id' => $senior['id'], ['expire_time', '>', time()]])->find();
                            if (!empty($vip)) {
                                if (floatval($conf['vip_reward']) > 1) {
                                    $reward = floor($reward * floatval($conf['vip_reward']));
                                }
                            }
                            // 开启事务
                            Db::startTrans();
                            try {
                                // 执行数据库操作
                                Db::name('user')->where('id', $senior['id'])->inc('coin', $reward)->update();
                                add_coin_log($senior['id'], $reward, 1, lang('login.day7read'));
                                Db::name('task')->where('id', $level_3['id'])->update(['status' => 1, 'update_time' => time()]);
                                // 提交事务
                                Db::commit();
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                            }
                        }
                    }
                }
            }
        } else {
            //查询是否有该章节记录
            $readhistory = Db::name('readhistory')->where(array('book_id' => $bookid, 'chapter_id' => $chapter_id, 'ip' => $ip))->find();
            //增加阅读记录
            if (empty($readhistory)) {
                Db::name('readhistory')->strict(false)->field(true)->insertGetId([
                    "user_id" => 0,
                    "book_id" => $bookid,
                    "chapter_id" => $chapter_id,
                    'read_date' => date('Y-m-d'),
                    'ip' => $ip,
                    "title" => $chapter['title'],
                    "position" => '',
                    "readnum" => 1,
                    "create_time" => time()
                ]);
            } else {
                Db::name('readhistory')->where(['id' => $readhistory['id']])->update([
                    'update_time' => time(),
                    "position" => '',
                    'read_date' => date('Y-m-d'),
                    'ip' => $ip,
                    "title" => $chapter['title'],
                    'readnum' => $readhistory['readnum'] + 1,
                ]);
            }
        }
        //前一章
        $front = Db::name('chapter')->field('id,bookid,title')->where(['bookid' => $bookid, 'status' => 1, ['verify', 'in', '0,1'], ['chaps', '<', $chapter['chaps']]])->order('chaps DESC')->find();
        if (!empty($front)) {
            $chapter['front_chapter'] = $front['id'];
            $chapter['front_url'] = str_replace($model_name, 'home', (string)furl('chapter_detail', ['id' => $front['id'], 'bookid' => $book['filename'] ? $book['filename'] : $front['bookid']]));
        } else {
            $chapter['front_chapter'] = 0;
            $chapter['front_url'] = '';
        }
        //后一章
        $after = Db::name('chapter')->field('id,bookid,title')->where(['bookid' => $bookid, 'status' => 1, ['verify', 'in', '0,1'], ['chaps', '>', $chapter['chaps']]])->order('chaps ASC')->find();
        if (!empty($after)) {
            $chapter['after_chapter'] = $after['id'];
            $chapter['after_url'] = str_replace($model_name, 'home', (string)furl('chapter_detail', ['id' => $after['id'], 'bookid' => $book['filename'] ? $book['filename'] : $after['bookid']]));
        } else {
            if (get_addons_is_enable('caijipro')) {
                $isNewChapter = hook('caijiproUpgradeHook', ['bookid' => $bookid]);
                if ($isNewChapter) {
                    $after = Db::name('chapter')->field('id,bookid,title')->where(['bookid' => $bookid, 'status' => 1, ['verify', 'in', '0,1'], ['chaps', '>', $chapter['chaps']]])->order('chaps ASC')->find();
                    if (!empty($after)) {
                        $chapter['after_chapter'] = $after['id'];
                        $chapter['after_url'] = str_replace($model_name, 'home', (string)furl('chapter_detail', ['id' => $after['id'], 'bookid' => $book['filename'] ? $book['filename'] : $after['bookid']]));
                    } else {
                        $chapter['after_chapter'] = 0;
                        $chapter['after_url'] = '';
                    }
                } else {
                    $chapter['after_chapter'] = 0;
                    $chapter['after_url'] = '';
                }
            } else {
                $chapter['after_chapter'] = 0;
                $chapter['after_url'] = '';
            }
        }
        $chapter['chaps'] = lang('common.numbers') . numConvertWord($chapter['chaps']) . lang('common.chapter');
        $total = Db::name('chapter')->where(['bookid' => $bookid, 'status' => 1, ['verify', 'in', '0,1']])->count();
        if ($uid) {
            $reads = Db::name('readhistory')->where(['user_id' => $uid, 'book_id' => $bookid])->count();
            if ($total == 0 || $reads < 0) {
                $chapter['speed'] = 0;
            } else {
                $chapter['speed'] = round(($reads / $total) * 100, 2);
            }
        } else {
            $reads = Db::name('readhistory')->where(['ip' => $ip, 'book_id' => $bookid])->count();
            if ($total == 0 || $reads < 0) {
                $chapter['speed'] = 0;
            } else {
                $chapter['speed'] = round(($reads / $total) * 100, 2);
            }
        }
        $chapter['fav'] = Db::name('favorites')->where(['user_id' => $uid, 'pid' => $book['id']])->count();
        $today = date('Y-m-d'); // 当天日期
        $chapter['like'] = Db::name('like_log')->where(['user_id' => $uid, 'book_id' => $book['id'], 'chapter_id' => $chapter_id, 'like_date' => $today])->count();
        $chapter['create_time'] = time_format($chapter['create_time']);
        $result = [
            'data' => [$chapter],
            'total' => $total,
            'current_page' => 1,
            'last_page' => 0,
            'per_page' => isset($param['limit']) ? $param['limit'] : 0
        ];
        $this->apiSuccess('success', $result);
    }
}
