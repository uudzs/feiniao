<?php

return array (
  'secretid' => 
  array (
    'title' => 'SecretId',
    'type' => 'string',
    'value' => '',
    'tips' => 'SecretId',
  ),
  'secretkey' => 
  array (
    'title' => 'SecretKey',
    'type' => 'string',
    'value' => '',
    'tips' => 'SecretKey',
  ),
  'bucket' => 
  array (
    'title' => 'bucket',
    'type' => 'string',
    'value' => '',
    'tips' => 'bucket',
  ),
  'region' => 
  array (
    'title' => 'region',
    'type' => 'string',
    'value' => 'ap-guangzhou',
    'tips' => 'region',
  ),
  'domain' => 
  array (
    'title' => '绑定域名',
    'type' => 'string',
    'value' => '',
    'tips' => '完整域名：如 https://***.cos.***.myqcloud.com',
  ),
  'keep_file' => 
  array (
    'title' => '保留本地文件',
    'type' => 'radio',
    'options' => 
    array (
      1 => '是',
      0 => '否',
    ),
    'value' => '1',
    'tips' => '选择否将在上传完毕后删除服务器对应目录里的文件',
  ),
);
