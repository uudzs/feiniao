<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
//use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;

class Author extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];


    /**
     * 作者详情
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
        $detail = Db::name('author')->field('id,nickname,headimg,true_name,mobile,email,create_time,update_time,authstate,bankstate,birth,qq,issign')->where(['id' => $id])->find();
        $model_name = \think\facade\App::initialize()->http->getName();
        if ($detail) {
            if ($uid) {
                $detail['isfollow'] = Db::name('follow')->where(['user_id' => $uid, 'from_id' => $detail['id']])->count();
            } else {
                $detail['isfollow'] = 0;
            }
            $detail['headimg'] = get_file($detail['headimg']);
            $detail['books'] = Db::name('book')->field('id,title,author,authorid,cover,style,ending,genre,subgenre,isfinish,finishtime,chapters,label,label_custom,hits,words,status,editor,editorid,issign,create_time,update_time,remark,filename')->where(['authorid' => $id, 'status' => 1])->order('create_time desc')->select()->toArray(); //所有作品
            $detail['follow_count'] = Db::name('follow')->where(['from_id' => $detail['id']])->count();
            $detail['words_count'] = Db::name('book')->where(['status' => 1, 'authorid' => $detail['id']])->sum('words');
            $detail['hits_count'] = Db::name('book')->where(['status' => 1, 'authorid' => $detail['id']])->sum('hits');
            $detail['authorurl'] = str_replace($model_name, 'home', (string) Route::buildUrl('author_detail', ['id' => $detail['id']]));
            foreach ($detail['books'] as $key => $value) {
                $detail['books'][$key]['cover'] = get_file($value['cover']);
                $detail['books'][$key]['words'] = wordCount($value['words']);
                $detail['books'][$key]['bigclassname'] = Db::name('category')->where(['id' => $value['genre']])->value('name');
                $detail['books'][$key]['smallclassname'] = Db::name('category')->where(['id' => $value['subgenre']])->value('name');
                if (!empty($value['remark'])) {
                    $value['remark'] = htmlspecialchars_decode($value['remark']);
                    $replace = array("&nbsp;", "<br>", "<br>");
                    $search = array(" ", "\n", '\n');
                    $detail['books'][$key]['remark'] = str_replace($search, $replace, $value['remark']);
                }
                if (!empty($detail['label']) && strpos($detail['label'], ',') !== false) {
                    $detail['label'] = explode(',', $detail['label']);
                } else {
                    $detail['label'] = [];
                }
                $detail['books'][$key]['url'] = str_replace($model_name, 'home', (string) Route::buildUrl('book_detail', ['id' => $value['filename'] ? $value['filename'] : $value['id']]));
            }
        } else {
            $this->apiError('作者不存在');
        }
        $this->apiSuccess('请求成功', $detail);
    }
}
