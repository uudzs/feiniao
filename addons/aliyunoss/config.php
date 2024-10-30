<?php

return array(
  'access_key_id' =>
    array(
      'title' => 'AccessKeyId',
      'type' => 'string',
      'value' => '',
      'tips' => 'AccessKeyId',
    ),
  'access_key_secret' =>
    array(
      'title' => 'AccessKeySecret',
      'type' => 'string',
      'value' => '',
      'tips' => 'AccessKeySecret',
    ),
  'bucket' =>
    array(
      'title' => 'Bucket',
      'type' => 'string',
      'value' => '',
      'tips' => 'Bucket',
    ),
  'endpoint' =>
    array(
      'title' => 'Endpoint',
      'type' => 'string',
      'value' => '',
      'tips' => 'Endpoint',
    ),
  'domain' =>
    array(
      'title' => '绑定域名',
      'type' => 'string',
      'value' => '',
      'tips' => '完整域名：如 https://img.aliyun.com',
    ),
  'keep_file' =>
    array(
      'title' => '保留本地文件',
      'type' => 'radio',
      'options' =>
        array(
          1 => '是',
          0 => '否',
        ),
      'value' => '1',
      'tips' => '选择否将在上传完毕后删除服务器对应目录里的文件',
    ),
);
