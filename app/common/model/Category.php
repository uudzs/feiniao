<?php

namespace app\common\model;

use think\Model;
use think\facade\Cache;

class Category extends Model
{

    // 男频分类ID（根据数据库结构，女频ID为13）
    const FEMALE_CATEGORY_ID = 13;

    /**
     * 获取男频分类列表
     * @return array
     */
    public static function getMaleCategories()
    {
        $cacheKey = "male_categories";
        $data = Cache::get($cacheKey);
        if (!$data) {
            $data = self::where('pid', 0)
                ->where('id', '<>', self::FEMALE_CATEGORY_ID)
                ->where('status', 1)
                ->order('ordernum ASC')
                ->column('id,name,key', 'id');
            Cache::set($cacheKey, $data, 86400);
        }
        return $data;
    }

    /**
     * 获取子分类列表
     * @return array
     */
    public static function getCategorySubclass($pid = 0)
    {
        $cacheKey = "categories_subclass_" . $pid;
        $data = Cache::get($cacheKey);
        if (!$data) {
            $data = self::where('pid', $pid)
                ->where('status', 1)
                ->order('ordernum ASC')
                ->column('id,name,key', 'id');
            Cache::set($cacheKey, $data, 86400);
        }
        return $data;
    }

    /**
     * 获取女频子分类列表
     * @return array
     */
    public static function getFemaleSubCategories()
    {
        $cacheKey = "female_subclass";
        $data = Cache::get($cacheKey);
        if (!$data) {
            $data = self::where('pid', self::FEMALE_CATEGORY_ID)
                ->where('status', 1)
                ->order('ordernum ASC')
                ->column('id,name,key', 'id');
            Cache::set($cacheKey, $data, 86400);
        }
        return $data;
    }

    /**
     * 根据频道类型获取分类列表
     * @param int $channelType 1-男频 2-女频
     * @return array
     */
    public static function getCategoriesByChannel($channelType)
    {
        return $channelType == 2
            ? self::getFemaleSubCategories()
            : self::getMaleCategories();
    }
}
