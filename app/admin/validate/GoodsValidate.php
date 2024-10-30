<?php

namespace app\admin\validate;
use think\Validate;

class GoodsValidate extends Validate
{
    protected $rule = [
    'cate_id' => 'require',
    'title' => 'require',
    'thumb' => 'require',
    'content' => 'require',
    'base_price' => 'require',
    'price' => 'require',
    'stocks' => 'require',
];

    protected $message = [
    'cate_id.require' => '所属分类不能为空',
    'title.require' => '商品名称不能为空',
    'thumb.require' => '缩略图不能为空',
    'content.require' => '商品描述不能为空',
    'base_price.require' => '市场价格不能为空',
    'price.require' => '实际价格不能为空',
    'stocks.require' => '商品库存不能为空',
];
}