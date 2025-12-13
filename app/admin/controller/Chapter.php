<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Chapter as ChapterModel;
use app\admin\validate\ChapterValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\db\exception\DbException;
use think\facade\View;
use content\Content;
use app\admin\model\Volume;

class Chapter extends BaseController
{

    var $uid;
    var $model;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new ChapterModel();
        $this->uid = get_login_admin('id');
    }
    /**
     * 数据列表
     */
    public function datalist()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $where = ['bookid' => $param['bid'], 'trial_time' => 0];
            if (!empty($param['keywords'])) {
                $where[] = ['title', 'like', '%' . $param['keywords'] . '%'];
            }
            $param['order'] = 'chaps asc';
            auto_run_addons('collect', [
                'type' => 'single_book',
                'book_id' => $param['bid'],
            ]);
            $list = $this->model->getChapterList($where, $param);
            return table_assign(0, '', $list);
        } else {
            View::assign('bid', $param['bid']);
            View::assign('title', $param['title']);
            return view();
        }
    }

    public function chapterlist()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $where = ['status' => 1, 'bookid' => $param['bid'], ['verify', '<>', 2]];
            if (!empty($param['keywords'])) {
                $where[] = ['title', 'like', '%' . $param['keywords'] . '%'];
            }
            $param['order'] = 'chaps asc';
            $list = $this->model->getChapterList($where, $param);
            return table_assign(0, '', $list);
        } else {
            View::assign('bid', $param['bid']);
            return view();
        }
    }

    public function caiji()
    {
        if (request()->isAjax()) {
            $param = get_params();
            if (!isset($param['bid']) || empty($param['bid'])) {
                return to_assign(1, '作品ID不能为空');
            }
            try {
                if (!get_addons_is_enable('caijipro')) {
                    return json([
                        'code' => 1,
                        'msg'  => '采集插件未安装或未开启'
                    ])->header(['Content-Type' => 'application/json']);
                }
                $bookid = intval($param['bid']);
                $list = Db::name('chapter')
                    ->field('id')
                    ->where('bookid', $bookid)
                    ->where('wordnum', '<=', 0)
                    ->select()
                    ->toArray();
                if (empty($list)) {
                    return json([
                        'code' => 1,
                        'msg'  => '未找到可更新章节记录'
                    ])->header(['Content-Type' => 'application/json']);
                }
                $success = 0;
                foreach ($list as $key => $value) {
                    $content = hook('caijiproChapterHook', ['chapterid' => $value['id']]);
                    if (!$content || empty($content)) continue;
                    if (mb_strlen($content) > 0) $success++;
                }
                return json([
                    'code' => 0,
                    'msg'  => '采集成功：' . $success . ' 条记录！'
                ])->header(['Content-Type' => 'application/json']);
            } catch (\Exception | \RuntimeException | DbException $e) {
                return json([
                    'code' => 1,
                    'msg'  => $e->getMessage()
                ])->header(['Content-Type' => 'application/json']);
            }
        }
    }

    //审核
    public function verify()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $where = ['verifytime' => 9999, 'trial_time' => 0];
            if (!empty($param['keywords'])) {
                $where[] = ['title', 'like', '%' . $param['keywords'] . '%'];
            }
            $param['order'] = 'create_time desc';
            $list = $this->model->getChapterList($where, $param);
            $list = $list->toArray();
            foreach ($list['data'] as $k => $v) {
                $book = Db::name('book')->where(['id' => $v['bookid']])->find();
                if (!empty($book)) {
                    $list['data'][$k]['author'] = $book['author'];
                    $list['data'][$k]['booktitle'] = $book['title'];
                } else {
                    $list['data'][$k]['author'] = '--';
                    $list['data'][$k]['booktitle'] = '--';
                }
            }
            return table_assign(0, '', $list);
        } else {
            return view();
        }
    }

    public function chaptersort()
    {
        $param = get_params();
        if (request()->isAjax()) {
            if (empty($param['bid'])) {
                to_assign(1, '作品ID为空');
            }
            $book = Db::name('book')->where(array('id' => $param['bid']))->find();
            if (empty($book)) {
                to_assign(1, '作品不存在');
            }
            if (!isset($param['volumes']) || empty($param['volumes'])) {
                to_assign(1, '核心数据为空');
            }
            $chapters = $param['chapters'];
            $volumes = $param['volumes'];
            $bookId = $book['id'];
            if (empty($chapters)) {
                to_assign(1, '请先添加章节');
            }
            try {
                Db::startTrans();

                // 1. 处理分卷数据
                $this->processVolumes($bookId, $volumes);

                // 2. 处理章节数据
                $this->processChapters($bookId, $volumes, $chapters);

                // 3. 同步删除前端不存在的章节
                $this->syncDeletedChapters($bookId, $chapters);

                Db::commit();
                return json([
                    'code' => 0,
                    'msg' => '更新成功'
                ])->header(['Content-Type' => 'application/json']);
            } catch (\Exception $e) {
                Db::rollback();
                return json([
                    'code' => 1,
                    'msg' => $e->getMessage()
                ])->header(['Content-Type' => 'application/json']);
            }
        } else {
            $book = Db::name('book')->where(array('id' => $param['bid']))->find();
            if (empty($book)) {
                to_assign(1, '作品不存在');
            }
            $volumes = (new Volume())::field('id,title as name,sort')->where('bookid', $param['bid'])->order('sort asc')->select()->toArray();
            $chapters = $this->model::field('id,title,chaps as sort,volumeid')->where('bookid', $param['bid'])->order('chaps asc')->select()->toArray();
            if (empty($volumes) && $chapters) {
                $volumes[] = ['id' => 1, 'name' => 'default', 'sort' => 1, 'chapters' => array_column($chapters, 'id')];
            }
            if (!empty($volumes) && $chapters) {
                foreach ($volumes as $key => $value) {
                    foreach ($chapters as $k => &$v) {
                        if (intval($value['id']) === 1 && intval($v['volumeid']) <= 0) {
                            $v['volumeid'] = 1;
                        }
                        if (intval($v['volumeid']) > 0 && $value['id'] == $v['volumeid']) {
                            $volumes[$key]['chapters'][] = $v['id'];
                        }
                    }
                    if (!isset($volumes[$key]['chapters'])) {
                        $volumes[$key]['chapters'] = [];
                    }
                }
            }
            View::assign('bid', $param['bid']);
            View::assign('volumes', $volumes);
            View::assign('chapters', $chapters);
            return view();
        }
    }

    /**
     * 处理分卷数据
     */
    protected function processVolumes($bookId, $volumes)
    {
        // 获取数据库中现有的分卷
        $dbVolumes = Db::name('volume')
            ->where('bookid', $bookId)
            ->column('*', 'id');

        $frontVolumeIds = array_column($volumes, 'id');

        // 1. 处理需要删除的分卷
        $toDelete = array_diff(array_keys($dbVolumes), $frontVolumeIds);
        if (!empty($toDelete)) {
            // 将这些分卷下的章节的volumeid置为0
            Db::name('chapter')
                ->where('bookid', $bookId)
                ->whereIn('volumeid', $toDelete)
                ->update(['volumeid' => 0]);

            // 删除分卷
            Db::name('volume')
                ->where('bookid', $bookId)
                ->whereIn('id', $toDelete)
                ->delete();
        }
        // 2. 处理新增或更新的分卷
        foreach ($volumes as $volume) {
            $volumeData = [
                'bookid' => $bookId,
                'title' => $volume['name'],
                'sort' => $volume['sort'],
                'update_time' => time()
            ];

            if (isset($dbVolumes[$volume['id']])) {
                // 更新现有分卷
                Db::name('volume')
                    ->where('id', $volume['id'])
                    ->update($volumeData);
            } else {
                // 新增分卷
                $volumeData['create_time'] = time();
                Db::name('volume')->insert($volumeData);
            }
        }
    }

    /**
     * 处理章节数据
     */
    protected function processChapters($bookId, $volumes, $chapters)
    {
        // 构建前端章节ID到排序号的映射
        $chapterSortMap = [];
        foreach ($chapters as $chapter) {
            $chapterSortMap[$chapter['id']] = $chapter['sort'];
        }

        // 先重置所有章节为无分卷状态
        Db::name('chapter')
            ->where('bookid', $bookId)
            ->update(['volumeid' => 0, 'update_time' => time()]);

        // 处理每个分卷的章节
        foreach ($volumes as $volume) {
            if (!empty($volume['chapters'])) {
                // 构建章节ID到排序号的映射
                $sortValues = [];
                foreach ($volume['chapters'] as $chapterId) {
                    if (isset($chapterSortMap[$chapterId])) {
                        $sortValues[$chapterId] = $chapterSortMap[$chapterId];
                    }
                }

                // 批量更新这些章节的volumeid和chaps
                Db::name('chapter')
                    ->where('bookid', $bookId)
                    ->whereIn('id', array_keys($sortValues))
                    ->update([
                        'volumeid' => $volume['id'],
                        'chaps' => Db::raw("CASE id " .
                            implode(' ', array_map(function ($id, $sort) {
                                return "WHEN {$id} THEN {$sort} ";
                            }, array_keys($sortValues), $sortValues)) .
                            "END"),
                        'update_time' => time()
                    ]);
            }
        }

        // 确保同一个作品下chaps不重复（按卷分组）
        $this->ensureUniqueChapterSort($bookId);
    }

    /**
     * 同步删除前端不存在的章节
     */
    protected function syncDeletedChapters($bookId, $frontChapters)
    {
        // 获取前端章节ID列表
        $frontChapterIds = array_column($frontChapters, 'id');

        // 获取数据库中该作品的所有章节ID
        $dbChapterIds = Db::name('chapter')
            ->where('bookid', $bookId)
            ->column('id');

        // 找出需要删除的章节ID（存在于数据库但不存在于前端数据）
        $toDelete = array_diff($dbChapterIds, $frontChapterIds);

        if (!empty($toDelete)) {
            // 执行删除操作
            Db::name('chapter')
                ->where('bookid', $bookId)
                ->whereIn('id', $toDelete)
                ->delete();
            if (!is_array($toDelete)) {
                $toDelete = explode(',', $toDelete);
            }
            if (is_array($toDelete)) {
                foreach ($toDelete as $key => $id) {
                    Content::delete($bookId, $id);
                }
            }
        }
    }

    /**
     * 确保同一个作品下章节排序不重复（按卷分组）
     */
    protected function ensureUniqueChapterSort($bookId)
    {
        // 获取所有需要处理的章节
        $chapters = Db::name('chapter')
            ->where('bookid', $bookId)
            ->field('id, volumeid, chaps')
            ->order('volumeid, chaps, id')
            ->select();

        $volumeSortMap = [];
        $toUpdate = [];

        foreach ($chapters as $chapter) {
            $volumeId = $chapter['volumeid'];
            $currentSort = $chapter['chaps'];

            if (!isset($volumeSortMap[$volumeId])) {
                $volumeSortMap[$volumeId] = [];
            }

            // 如果排序号已经存在，需要重新分配
            if (in_array($currentSort, $volumeSortMap[$volumeId])) {
                $newSort = max($volumeSortMap[$volumeId]) + 1;
                $toUpdate[$chapter['id']] = $newSort;
                $volumeSortMap[$volumeId][] = $newSort;
            } else {
                $volumeSortMap[$volumeId][] = $currentSort;
            }
        }

        // 批量更新有冲突的章节排序
        if (!empty($toUpdate)) {
            Db::name('chapter')
                ->whereIn('id', array_keys($toUpdate))
                ->update([
                    'chaps' => Db::raw("CASE id " .
                        implode(' ', array_map(function ($id, $sort) {
                            return "WHEN {$id} THEN {$sort} ";
                        }, array_keys($toUpdate), $toUpdate)) .
                        "END"),
                    'update_time' => time()
                ]);
        }
    }

    /**
     * 添加
     */
    public function add()
    {
        $param = get_params();
        if (request()->isAjax()) {
            // 检验完整性
            try {
                validate(ChapterValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $book = Db::name('book')->where(array('id' => $param['bookid']))->find();
            if (empty($book)) {
                to_assign(1, '作品不存在');
            }
            $serial = intval($param['serial']);
            if (empty($serial)) $serial = 1;
            $title = '第' . numConvertWord($serial) . '章 ' . trim($param['title']);
            $istitle = Db::name('chapter')->where(['bookid' => $book['id'], 'title' => $title])->find();
            if (!empty($istitle)) {
                to_assign(1, '章节名称重复，无法发布。');
            }
            $info = $param['content'];
            list($wordnum, $content) = countWordsAndContent($info, true);
            if (empty($content) || empty($wordnum)) {
                to_assign(1, '章节内容为空，无法发布。');
            }
            $config = get_system_config('content');
            $chapter_min_num = isset($config['chapter_min_num']) ? intval($config['chapter_min_num']) : 0;
            $chapter_max_num = isset($config['chapter_max_num']) ? intval($config['chapter_max_num']) : 0;
            if ($chapter_min_num > 0 && $wordnum < $chapter_min_num) {
                to_assign(1, '章节内容不能少于' . $chapter_min_num . '字');
            }
            if ($chapter_max_num > 0 && $wordnum > $chapter_max_num) {
                to_assign(1, '章节内容字数大于' . $chapter_max_num . '字，无法发布。');
            }
            $data = [
                'title' => $title,
                'bookid' => $book['id'],
                'authorid' => $book['authorid'],
                'title' => $title,
                'chaps' => $serial,
                'wordnum' => $wordnum,
                'firstverifyword' => $wordnum,
                'status' => 1,
                'verify' => 1,
                'draft' => 0,
                'trial_time' => 0,
                'create_time' => time(),
                'firstpasstime' => time()
            ];
            $sid = Db::name('chapter')->strict(false)->field(true)->insertGetId($data);
            if ($sid !== false) {
                Content::add($book['id'], $sid, $content);
                to_assign(0, '添加成功');
            } else {
                to_assign(1, '操作失败');
            }
        } else {
            $book = Db::name('book')->where(array('id' => $param['bid']))->find();
            if (empty($book)) {
                to_assign(1, '作品不存在');
            }
            $chapter = Db::name('chapter')->where(array('bookid' => $param['bid']))->order('chaps desc')->value('chaps');
            if (!empty($chapter)) {
                $serial = intval($chapter) + 1;
            } else {
                $serial = 1;
            }
            $chapstitle = '第' . numConvertWord($serial) . '章 '; //章节序号名称
            View::assign('book', $book);
            View::assign('serial', $serial);
            View::assign('chapstitle', $chapstitle);
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
                validate(ChapterValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $id = $param['id'];
            $content = $param['content'];
            unset($param['content']);
            $chapter = Db::name('chapter')->where(['id' => $id])->find();
            if ($chapter['verify'] != 1) {
                return to_assign(1, '只有已审理核章节才能编辑章节内容');
            }
            $verify = Db::name('chapter_verify')->where('cid', $id)->find();
            if (!empty($verify)) {
                return to_assign(1, '请先审核章节');
            }
            list($wordnum, $content) = countWordsAndContent($content, true);
            if (empty($content) || empty($wordnum)) {
                to_assign(1, '章节内容为空，无法发布。');
            }
            $config = get_system_config('content');
            $chapter_min_num = isset($config['chapter_min_num']) ? intval($config['chapter_min_num']) : 0;
            $chapter_max_num = isset($config['chapter_max_num']) ? intval($config['chapter_max_num']) : 0;
            if ($chapter_min_num > 0 && $wordnum < $chapter_min_num) {
                to_assign(1, '章节内容不能少于' . $chapter_min_num . '字');
            }
            if ($chapter_max_num > 0 && $wordnum > $chapter_max_num) {
                to_assign(1, '章节内容字数大于' . $chapter_max_num . '字，无法发布。');
            }
            $param['update_time'] = time();
            $param['wordnum'] = $wordnum;
            $param['verifypeople'] = get_login_admin('nickname');
            $param['verifytime'] = time();
            $param['verifyresult'] = '编辑修改章节内容';
            Db::name('chapter')->where(['id' => $id])->strict(false)->field(true)->update($param);
            Content::update($chapter['bookid'], $id, $content);
            clear_cache('chapter_' . $id);
            $res = Db::name('book')->where(['id' => $chapter['bookid']])->strict(false)->field(true)->update(['update_time' => time()]);
            return to_assign();
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $chapter = Db::name('chapter')->where(['id' => $id])->find();
            $verify = Db::name('chapter_verify')->where('cid', $id)->find();
            if (!empty($verify)) {
                return to_assign(1, '请先审核章节');
            }
            $content = Content::get($chapter['bookid'], $id);
            if (empty($content)) {
                $obj = auto_run_addons('collect', [
                    'type' => 'single_chapter',
                    'chapter_id' => $id
                ]);
                $content = current(array_filter($obj));
            }
            if (!empty($content)) {
                $chapter['info'] = htmlspecialchars_decode($content);
                View::assign('chapter', $chapter);
                return view();
            } else {
                return to_assign(1, '记录不存在');
            }
        }
    }


    /**
     * 查看信息
     */
    public function read()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $id = isset($param['id']) ? $param['id'] : 0;
            if (empty($id)) {
                return to_assign(1, '章节ID为空');
            }
            $chapter = Db::name('chapter')->where(['id' => $id])->find();
            if (empty($chapter)) {
                return to_assign(1, '章节不存在');
            }
            //如果是拒绝
            if (isset($param['verifyresult'])) {
                if (empty($param['verifyresult'])) {
                    return to_assign(1, '拒绝理由为空');
                }
                $param['verify'] = 2;
                $param['verifytime'] = time();
            } else {
                $param['verify'] = 1;
                $param['verifytime'] = time();
                //首次审核
                if (empty($chapter['firstpasstime'])) {
                    $param['firstpasstime'] = time();
                    $param['firstverifyword'] = $chapter['wordnum'];
                }
            }
            $param['verifypeople'] = get_login_admin('nickname');
            Db::name('chapter')->where(['id' => $id])->strict(false)->field(true)->update($param);
            if ($param['verify'] == 1) {
                $res = Db::name('book')->where(['id' => $chapter['bookid']])->strict(false)->field(true)->update(['update_time' => time()]);
            }
            clear_cache('chapter_' . $id);
            return to_assign();
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $chapter = Db::name('chapter')->where(['id' => $id])->find();
            $verify = Db::name('chapter_verify')->where('cid', $id)->find();
            if (!empty($verify)) {
                return to_assign(1, '请前往【修改章节审核】');
            }
            $content = Content::get($chapter['bookid'], $id);
            if ($content && mb_strlen($content) > 0) {
                $chapter['info'] = htmlspecialchars_decode($content);
                $replace = array("&nbsp;", "<br>");
                $search = array(" ", "\n");
                $chapter['info'] = str_replace($search, $replace, $chapter['info']);
                View::assign('chapter', $chapter);
                return view();
            } else {
                return to_assign(1, '记录不存在');
            }
        }
    }

    /**
     * 删除
     * type=0,逻辑删除，默认
     * type=1,物理删除
     */
    public function del()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        if (empty($id)) {
            return to_assign(1, '章节ID为空');
        }
        $chapter = $this->model->getChapterById($id);
        if (empty($chapter)) {
            return to_assign(1, '章节不存在');
        }
        if (Content::delete($chapter['bookid'], $id)) {
            return to_assign();
        } else {
            return to_assign(1, '删除失败');
        }
    }
}
