<?php

declare(strict_types=1);

namespace app\author\controller;

use app\author\BaseController;
use think\facade\Db;
use think\Image;

class Api extends BaseController
{
    //上传文件
    public function upload()
    {
        $param = get_params();
        $sourse = 'file';
        if (isset($param['sourse'])) {
            $sourse = $param['sourse'];
        }
        if ($sourse == 'file' || $sourse == 'tinymce') {
            if (request()->file('file')) {
                $file = request()->file('file');
            } else {
                return to_assign(1, '没有选择上传文件');
            }
        } else {
            if (request()->file('editormd-image-file')) {
                $file = request()->file('editormd-image-file');
            } else {
                return to_assign(1, '没有选择上传文件');
            }
        }
        // 获取上传文件的hash散列值
        $sha1 = $file->hash('sha1');
        $md5 = $file->hash('md5');
        $rule = [
            'image' => 'jpg,png,jpeg,gif',
            'doc' => 'doc,docx,ppt,pptx,xls,xlsx,pdf',
            'file' => 'zip,gz,7z,rar,tar',
            'video' => 'mpg,mp4,mpeg,avi,wmv,mov,flv,m4v',
        ];
        $fileExt = $rule['image'] . ',' . $rule['doc'] . ',' . $rule['file'] . ',' . $rule['video'];
        //1M=1024*1024=1048576字节
        $fileSize = 100 * 1024 * 1024;
        if (isset($param['type']) && $param['type']) {
            $fileExt = $rule[$param['type']];
        } else {
            $fileExt = $rule['image'];
        }
        if (isset($param['size']) && $param['size']) {
            $fileSize = $param['size'];
        }
        $validate = \think\facade\Validate::rule([
            'image' => 'require|fileSize:' . $fileSize . '|fileExt:' . $fileExt,
        ]);
        $file_check['image'] = $file;
        if (!$validate->check($file_check)) {
            return to_assign(1, $validate->getError());
        }
        // 日期前綴
        $dataPath = date('Ym');
        $use = 'thumb';
        $filename = \think\facade\Filesystem::disk('public')->putFile($dataPath, $file, function () use ($md5) {
            return $md5;
        });
        if ($filename) {
            $path = get_config('filesystem.disks.public.url');
            $filepath = $path . '/' . $filename;
            if (isset($param['thumb'])) {
                $realPath = CMS_ROOT . "public" . $path . '/' . $filename;
                $image = Image::open($realPath);
                // 按照原图的比例生成一个最大为500*500的缩略图并保存为thumb.png
                $image->thumb(500, 500, Image::THUMB_CENTER)->save($realPath . '_thumb.' . $file->extension());
                $filepath = $filepath . '_thumb.' . $file->extension();
            } else {
                $realPath = CMS_ROOT . "public" . $path . '/' . $filename;
            }
            $obj = auto_run_addons('storage', ['url' => $filename]);
            if ($obj) {
                $result = isset($obj[0]) ? $obj[0] : $obj;
                if (!isJson($result)) return to_assign(1, '上传失败');
                $result = json_decode($result, true);
                if (isset($result['code']) && intval($result['code']) == 0) {
                    $filepath = $result['data'] ?: $filepath;
                } else {
                    return to_assign(1, $result['msg']);
                }
            }
            //写入到附件表
            $data = [];
            $data['filepath'] = $filepath;
            $data['name'] = $file->getOriginalName();
            $data['mimetype'] = $file->getOriginalMime();
            $data['fileext'] = $file->extension();
            $data['filesize'] = $file->getSize();
            $data['filename'] = $filename;
            $data['sha1'] = $sha1;
            $data['md5'] = $md5;
            $data['module'] = \think\facade\App::initialize()->http->getName();
            $data['action'] = app('request')->action();
            $data['uploadip'] = app('request')->ip();
            $data['create_time'] = time();
            $data['user_id'] = get_login_author('id') ? get_login_author('id') : 0;
            if ($data['module'] = 'admin') {
                //通过后台上传的文件直接审核通过
                $data['status'] = 1;
                $data['admin_id'] = $data['user_id'];
                $data['audit_time'] = time();
            }
            $data['use'] = request()->has('use') ? request()->param('use') : $use; //附件用处
            $res = [];
            $res['id'] = Db::name('file')->insertGetId($data);
            $res['filepath'] = $data['filepath'];
            $res['name'] = $data['name'];
            $res['filename'] = $data['filename'];
            //add_log('upload', $data['user_id'], $data, '文件');
            if ($sourse == 'editormd') {
                //editormd编辑器上传返回
                return json(['success' => 1, 'message' => '上传成功', 'url' => $data['filepath']]);
            } else if ($sourse == 'tinymce') {
                //tinymce编辑器上传返回
                return json(['success' => 1, 'message' => '上传成功', 'location' => $data['filepath']]);
            } else {
                //普通上传返回
                return to_assign(0, '上传成功', $res);
            }
        } else {
            return to_assign(1, '上传失败，请重试');
        }
    }
}
