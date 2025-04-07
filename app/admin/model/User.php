<?php

namespace app\admin\model;

use think\Model;
use think\model\relation\HasMany;

class User extends Model
{
    protected $pk = 'id';
    protected $schema = [
        'id' => 'int',
        'nickname' => 'string',
        'headimgurl' => 'string',
        'username' => 'string',
        'status' => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
        'mobile' => 'string',
        'email' => 'string'
    ];
    // 自动时间戳转换（Unix时间戳）
    protected $autoWriteTimestamp = false;
    protected $createTime = 'register_time';
    protected $updateTime = 'update_time';
    // 隐藏敏感字段
    protected $hidden = ['password', 'salt', 'securitypwd'];
    // 用户状态常量
    const STATUS_DELETED = -1;
    const STATUS_DISABLED = 0;
    const STATUS_NORMAL = 1;

    public function setPasswordAttr($value)
    {
        if (!empty($value)) {
            // 生成20位盐值
            $salt = set_salt(20);
            // 设置盐值并返回加密后的密码
            $this->set('salt', $salt);
            return set_password($value, $salt);
        }
        return $value;
    }

    // 最后登录时间格式化
    public function getLastLoginTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    // 注册时间格式化
    public function getRegisterTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    // 头像地址处理
    public function getHeadimgurlAttr($value)
    {
        return $value ? get_file($value) : '/static/images/default_avatar.png';
    }

    // 性别状态转文本
    public function getSexTextAttr($value, $data)
    {
        return match ($data['sex'] ?? 0) {
            1 => '女',
            2 => '男',
            default => '未知'
        };
    }

    // 保持与 Third 模型的关联
    public function third(): HasMany
    {
        return $this->hasMany(Third::class, 'user_id');
    }

    // 允许批量修改的字段
    protected $allowedFields = [
        'nickname',
        'username',
        'password',
        'mobile',
        'email',
        'headimgurl',
        'status'
    ];
}
