<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use app\api\middleware\Auth;
use think\facade\Db;
use think\exception\ValidateException;
use app\admin\validate\CommentValidator;
use app\admin\validate\CommentReplyValidator;
use app\admin\validate\CommentLikeValidator;
use app\admin\model\{Comment as CommentModel, CommentLike, CommentReply, Book};

class Comment extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => ['list']]
    ];

    public function list()
    {
        if (!get_system_config('content', 'comment_open')) $this->apiError('407');
        $param = get_params();
        $target_id = isset($param['target_id']) ? intval($param['target_id']) : 0;
        $target_type = isset($param['target_type']) ? intval($param['target_type']) : 0;
        $page = isset($param['page']) ? intval($param['page']) : 1;
        $limit = isset($param['limit']) ? intval($param['limit']) : 0;
        if (empty($target_id) || empty($target_type)) {
            $this->apiError('paramerror');
        }
        $query = CommentModel::with([
            'user',
            'replies' => function ($query) {
                $query->with([
                    'user',
                    'parentReply.user',
                    'childReplies' => function ($q) {
                        $q->with(['user', 'childReplies' => function ($subQ) {
                            $subQ->with(['user', 'childReplies']);
                        }]);
                    }
                ]);
            }
        ])->where('target_type', $target_type)
            ->where('target_id', $target_id)
            ->where('status', CommentModel::STATUS_APPROVED);
        $pageSize = empty($limit) ? get_config('app.page_size') : $limit;
        $total = $query->count();
        $list = $query->page($page, $pageSize)
            ->order('create_time', 'desc')
            ->select();

        $pageSize = empty($limit) ? get_config('app.page_size') : $limit;
        $total = $query->count();
        $list = $query->page($page, $pageSize)
            ->order('create_time', 'desc')
            ->select();

        $this->apiSuccess('success', [
            'data' => $list,
            'total' => $total,
            'page_size' => $pageSize
        ]);
    }

    public function create()
    {
        if (!get_system_config('content', 'comment_open')) $this->apiError('407');
        $data = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        $data['user_id'] = JWT_UID;
        try {
            validate(CommentValidator::class)->check($data);
        } catch (ValidateException $e) {
            return to_assign(1, $e->getError());
        }
        Db::startTrans();
        try {
            $isVerifyEnabled = get_system_config('content', 'comment_verify_open') ?? 0;
            $comment = CommentModel::create([
                'user_id' => JWT_UID,
                'target_type' => $data['target_type'],
                'target_id' => $data['target_id'],
                'content' => $data['content'],
                'status' => $isVerifyEnabled ? 0 : 1,
                'like_count' => 0
            ]);
            if ($data['target_type'] == 1 && !$isVerifyEnabled) Book::where('id', $data['target_id'])->inc('comments')->update();
            // 提交事务
            Db::commit();
            $message = $isVerifyEnabled ? 'examineing' : 'success';
            return json(['code' => 0, 'msg' => lang($message)]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    public function reply()
    {
        if (!get_system_config('content', 'comment_open')) $this->apiError('407');
        $data = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        $is_verify = intval(get_system_config('content', 'comment_verify_open'));
        $data['user_id'] = JWT_UID;
        $data['status'] = $is_verify ? 0 : 1;
        try {
            validate(CommentReplyValidator::class)->check($data);
        } catch (ValidateException $e) {
            return to_assign(1, $e->getError());
        }
        // 创建回复
        $reply = CommentReply::create($data);
        // 更新主评论回复数
        CommentModel::where('id', $data['comment_id'])
            ->inc('reply_count')
            ->update();
        $this->apiSuccess($is_verify ? 'examineing' : 'success');
    }

    public function like()
    {
        if (!get_system_config('content', 'comment_open')) $this->apiError('407');
        // 验证用户登录状态
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }

        // 参数获取与预处理
        $data = array_merge(get_params(), ['user_id' => JWT_UID]);

        try {
            validate(CommentLikeValidator::class)->check($data);
        } catch (ValidateException $e) {
            return to_assign(1, $e->getError());
        }

        // 开启事务
        Db::startTrans();

        // 检查重复点赞（使用锁防止并发）
        $exists = CommentLike::where([
            'user_id' => $data['user_id'],
            'target_id' => $data['target_id'],
            'target_type' => $data['target_type']
        ])
            ->lock(true)
            ->find();

        if ($exists) {
            Db::rollback();
            $this->apiError('repeat');
        }

        // 获取目标模型
        $modelMap = [
            1 => CommentModel::class,
            2 => CommentReply::class
        ];

        if (!isset($modelMap[$data['target_type']])) {
            $this->apiError('paramerror');
        }
        $modelClass = $modelMap[$data['target_type']];
        $target = $modelClass::where('id', $data['target_id'])
            ->lock(true)
            ->find();

        if (!$target) {
            $this->apiError('404');
        }

        // 创建点赞记录      
        CommentLike::create([
            'user_id' => $data['user_id'],
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
            'create_time' => date('Y-m-d H:i:s')
        ]);

        // 更新点赞数
        $modelClass::where('id', $data['target_id'])
            ->inc('like_count')
            ->update();

        // 提交事务
        Db::commit();

        $this->apiSuccess('success');
    }
}
