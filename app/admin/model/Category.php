<?php

namespace app\admin\model;

use think\model;

class Category extends Model
{
  /**
   * 获取分页列表
   * @param $where
   * @param $param
   */
  public function getCategoryList($where, $param)
  {
    $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
    $order = empty($param['order']) ? 'id desc' : $param['order'];
    $list = self::where($where)->field('id,name,key,pid,ordernum,status,icons,create_user_id,update_user_id,create_time,update_time')->order($order)->paginate($rows, false, ['query' => $param]);
    return $list;
  }

  /**
   * 添加数据
   * @param $param
   */
  public function addCategory($param)
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
  public function editCategory($param)
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
  public function getCategoryById($id)
  {
    $info = self::where('id', $id)->find();
    return $info;
  }

  /**
   * 删除信息
   * @param $id
   * @return array
   */
  public function delCategoryById($id)
  {
    //物理删除
    try {
      self::where('id', $id)->delete();
      add_log('delete', $id);
    } catch (\Exception $e) {
      return to_assign(1, '操作失败，原因：' . $e->getMessage());
    }
    return to_assign();
  }
}
