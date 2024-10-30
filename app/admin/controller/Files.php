<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\File;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Files extends BaseController
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            $fileext = ['', 'jpg,jpeg,png,gif', 'mp4', 'doc,docx,xls,xlsx,ppt,pptx,txt,pdf', 'zip,rar,7z'];
            if (!empty($param['keywords'])) {
                $where[] = ['f.name|g.title', 'like', '%' . $param['keywords'] . '%'];
            }
            if (isset($param['group_id']) && $param['group_id'] != '') {
                $where[] = ['f.group_id', '=', $param['group_id']];
            }
            if (!empty($param['tab'])) {
                $where[] = ['f.fileext', 'in', $fileext[$param['tab']]];
            }
            $where[] = ['f.delete_time', '=', 0];
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $list = DB::name('File')
                ->field("f.*,a.username,g.title as group_title")
                ->alias('f')
                ->join('Admin a', 'f.admin_id = a.id', 'left')
                ->join('FileGroup g', 'f.group_id = g.id', 'left')
                ->order('f.create_time desc')
                ->where($where)
                ->paginate($rows, false, ['query' => $param])
                ->each(function ($item, $key) {
                    $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                    return $item;
                });
            return table_assign(0, '', $list);
        } else {
            return view();
        }
    }
    //编辑
    public function edit()
    {
        if (request()->isAjax()) {
            $param = get_params();
            if (Db::name('File')->where('id', $param['id'])->update(['name' => $param['title']]) !== false) {
                add_log('edit', $param['id'], []);
                return to_assign(0, "操作成功");
            } else {
                return to_assign(1, "操作失败");
            }
        }
    }

    //移动
    public function move()
    {
        if (request()->isAjax()) {
            $group_id = get_params("group_id");
            $ids = get_params("ids");
            $idArray = explode(',', strval($ids));
            $list = [];
            foreach ($idArray as $key => $val) {
                $list[] = [
                    'id' => $val,
                    'group_id' => $group_id,
                    'update_time' => time()
                ];
            }
            $res = (new File())->saveAll($list);
            if ($res !== false) {
                return to_assign(0, "操作成功");
            } else {
                return to_assign(1, "操作失败");
            }
        }
    }
    //删除
    public function delete()
    {
        if (request()->isDelete()) {
            $ids = get_params("ids");
            $idArray = explode(',', strval($ids));
            //$list = [];
            $success = $fail = 0;
            foreach ($idArray as $key => $val) {
                // $list[] = [
                //     'id' => $val,
                //     'delete_time' => time()
                // ];
                $file = Db::name('file')->where('id', $val)->find();
                if ($file) {
                    if (filter_var($file['filepath'], FILTER_VALIDATE_URL) !== false) {
                    } else {
                        $filepath =  app()->getRootPath() . 'public' . $file['filepath'];
                        if (is_file($filepath)) {
                            unlink($filepath);
                        }
                    }
                    $res = Db::name('file')->where('id', $val)->delete();
                    if ($res !== false) {
                        $success++;
                    } else {
                        $fail++;
                    }
                }
            }
            return to_assign(0, "删除成功:" . $success . '，删除失败：' . $fail);
            // $res = (new File())->saveAll($list);
            // if ($res !== false) {
            //     return to_assign(0, "删除成功");
            // } else {
            //     return to_assign(1, "删除失败");
            // }
        } else {
            return to_assign(1, "错误的请求");
        }
    }

    //获取分组
    public function get_group()
    {
        $list = Db::name('FileGroup')->where([['delete_time', '=', 0]])->select()->toArray();
        return to_assign(0, '', $list);
    }
    //添加&编辑
    public function add_group()
    {
        if (request()->isAjax()) {
            $param = get_params();
            if ($param['title'] == '全部' || $param['title'] == '未分组') {
                return to_assign(1, '该分组名称已经存在');
            }
            if (!empty($param['id']) && $param['id'] > 0) {
                $count = Db::name('FileGroup')->where([['id', '<>', $param['id']], ['delete_time', '=', 0], ['title', '=', $param['title']]])->count();
                if ($count > 0) {
                    return to_assign(1, '该分组名称已经存在');
                }
                $res = Db::name('FileGroup')->where(['id' => $param['id']])->strict(false)->field(true)->update($param);
                if ($res != false) {
                    add_log('edit', $param['id'], $param);
                    return to_assign(0, '编辑成功', $param['id']);
                } else {
                    return to_assign(1, '操作失败');
                }
            } else {
                $count = Db::name('FileGroup')->where([['delete_time', '=', 0], ['title', '=', $param['title']]])->count();
                if ($count > 0) {
                    return to_assign(1, '该分组名称已经存在');
                }
                $gid = Db::name('FileGroup')->strict(false)->field(true)->insertGetId($param);
                if ($gid != false) {
                    add_log('add', $gid, $param);
                    return to_assign(0, '添加成功', $gid);
                } else {
                    return to_assign(1, '操作失败');
                }
            }
        }
    }

    //删除
    public function del_group()
    {
        if (request()->isDelete()) {
            $id = get_params("id");
            $count = Db::name('File')->where(["group_id" => $id])->count();
            if ($count > 0) {
                return to_assign(1, "该分组还存在文件，请去除文件或者转移文件后再删除");
            }
            if (Db::name('FileGroup')->delete($id) !== false) {
                add_log('delete', $id, []);
                return to_assign(0, "删除成功");
            } else {
                return to_assign(1, "删除失败");
            }
        } else {
            return to_assign(1, "错误的请求");
        }
    }
}
