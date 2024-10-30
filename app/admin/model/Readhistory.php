<?php
namespace app\admin\model;
use think\model;
class Readhistory extends Model
{
  /**
   * 获取分页列表
   * @param $where
   * @param $param
   */
  public function getReadhistoryList($where, $param)
  {
    $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
    $order = empty($param['order']) ? 'a.id desc' : $param['order'];
    $list = self::where($where)
      ->field('a.*,b.title as booktitle,b.cover,b.author,b.authorid,b.genre,b.subgenre,b.remark,u.nickname,u.mobile')
      ->alias('a')
      ->join('book b', 'b.id = a.book_id')
      ->join('user u', 'u.id = a.user_id')
      ->order($order)
      ->paginate($rows, false, ['query' => $param])
      ->each(function ($item, $key) {
        $item->cover = get_file($item->cover);
      });
    return $list;
  }

  public function getReadhistoryBook($where, $param)
  {
    $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
    $order = empty($param['order']) ? 'id desc' : $param['order'];
    $list = self::where($where)
      ->field('*')
      ->group('book_id')
      ->order($order)
      ->paginate($rows, false, ['query' => $param]);
    return $list;
  }

  /**
   * 添加数据
   * @param $param
   */
  public function addReadhistory($param)
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
  public function editReadhistory($param)
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
  public function getReadhistoryById($id)
  {
    $info = self::where('id', $id)->find();
    return $info;
  }

  /**
   * 删除信息
   * @param $id
   * @return array
   */
  public function delReadhistoryById($id, $type = 0)
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

