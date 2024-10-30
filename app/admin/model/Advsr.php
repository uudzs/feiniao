<?php

namespace app\admin\model;

use think\model;

class Advsr extends Model
{

  /**
   * 获取分页列表
   * @param $where
   * @param $param
   */
  public function getAdvsrList($where, $param)
  {
    $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
    $order = empty($param['order']) ? 'a.id desc' : $param['order'];
    $list = self::name('advsr')->where($where)
      ->field('a.*,u.title as adver_title,u.viewnum,u.type as adver_type,u.width,u.height,u.channel,u.status as adver_status')
      ->alias('a')
      ->join('adver u', 'u.id = a.adver_id')
      ->order($order)
      ->paginate($rows, false, ['query' => $param]);
    return $list;
  }

  /**
   * 添加数据
   * @param $param
   */
  public function addAdvsr($param)
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
  public function editAdvsr($param)
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
  public function getAdvsrById($id)
  {
    $info = self::where('id', $id)->find();
    return $info;
  }

  /**
   * 删除信息
   * @param $id
   * @return array
   */
  public function delAdvsrById($id, $type = 0)
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

