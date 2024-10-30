<?php

namespace app\admin\model;

use think\model;

class Book extends Model
{
  /**
   * 获取分页列表
   * @param $where
   * @param $param
   */
  public function getBookList($where, $param)
  {
    $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
    $order = empty($param['order']) ? 'id desc' : $param['order'];
    $list = self::where($where)
      ->field('id,title,author,authorid,cover,style,ending,genre,subgenre,isfinish,finishtime,chapters,label,label_custom,hits,words,status,editor,editorid,issign,create_time,update_time,remark')
      ->order($order)
      ->paginate($rows, false, ['query' => $param])
      ->each(function ($item, $key) {
        $item->cover = get_file($item->cover);
        $item->bigcatetitle = self::name('category')->where(['id' => $item->genre])->value('name');
        $item->sellcatetitle = self::name('category')->where(['id' => $item->subgenre])->value('name');
        if (!$item->editor) {
          if ($item->editorid) {
            $item->editor = self::name('admin')->where(['id' => $item->editorid])->value('nickname');
          } else {
            $item->editor = '--';
          }
        }
      });
    return $list;
  }

  /**
   * 添加数据
   * @param $param
   */
  public function addBook($param)
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
  public function editBook($param)
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
  public function getBookById($id)
  {
    $info = self::where('id', $id)->find();
    return $info;
  }

  /**
   * 删除信息
   * @param $id
   * @return array
   */
  public function delBookById($id, $type = 0)
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

