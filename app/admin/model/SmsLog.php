<?php

namespace app\admin\model;

use think\model;

class SmsLog extends Model
{

  public function getAccountAttribute($value)
  {
    if (preg_match('/^1[3-9]\d{9}$/', $value)) {
      return substr($value, 0, 3) . '****' . substr($value, -4);
    } elseif (preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $value)) {
      list($username, $domain) = explode('@', $value);
      $length = strlen($username);
      if ($length <= 3) {
        $maskedUsername = $username[0] . str_repeat('*', $length - 1);
      } else {
        $maskedUsername = substr($username, 0, 3) . str_repeat('*', $length - 3);
      }
      return $maskedUsername . '@' . $domain;
    }
    return $value;
  }

  /**
   * 获取分页列表
   * @param $where
   * @param $param
   */


  public function getSmsLogList($where, $param)
  {
    $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
    $order = empty($param['order']) ? 'id desc' : $param['order'];
    // 移除多余的each回调，访问器会自动生效
    $list = self::where($where)
      ->field('id,count,send_time,expire_time,code,account')
      ->order($order)
      ->paginate($rows, false, ['query' => $param])->each(function ($item) {
        // 直接调用访问器方法
        $item->account = $this->getAccountAttribute($item->account);
        return $item->toArray();
    });
    return $list;
  }

  /**
   * 添加数据
   * @param $param
   */
  public function addSmsLog($param)
  {
    $insertId = 0;
    try {
      $param['create_time'] = time();
      $insertId = self::strict(false)->field(true)->insertGetId($param);
      add_log('add', $insertId, $param);
    } catch (\Exception $e) {
      return to_assign(1, '操作失败，原因：' . $e->getMessage());
    }
    return to_assign(0, '操作成功', ['aid' => $insertId]);
  }

  /**
   * 编辑信息
   * @param $param
   */
  public function editSmsLog($param)
  {
    try {
      $param['update_time'] = time();
      self::where('id', $param['id'])->strict(false)->field(true)->update($param);
      add_log('edit', $param['id'], $param);
    } catch (\Exception $e) {
      return to_assign(1, '操作失败，原因：' . $e->getMessage());
    }
    return to_assign();
  }


  /**
   * 根据id获取信息
   * @param $id
   */
  public function getSmsLogById($id)
  {
    $info = self::where('id', $id)->find();
    return $info;
  }

  /**
   * 删除信息
   * @param $id
   * @return array
   */
  public function delSmsLogById($id, $type = 0)
  {
    if ($type == 0) {
      //逻辑删除
      try {
        $param['delete_time'] = time();
        self::where('id', $id)->update(['delete_time' => time()]);
        add_log('delete', $id);
      } catch (\Exception $e) {
        return to_assign(1, '操作失败，原因：' . $e->getMessage());
      }
    } else {
      //物理删除
      try {
        self::where('id', $id)->delete();
        add_log('delete', $id);
      } catch (\Exception $e) {
        return to_assign(1, '操作失败，原因：' . $e->getMessage());
      }
    }
    return to_assign();
  }
}
