<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/4/30 6:30
 * @Email : 30191306465@qq.com
 */

require_once __DIR__.'/vendor/autoload.php';

use app\NetEaseCloudMusic;

header('Content-Type: application/json');

$url = 'http://music.163.com/playlist?id=313050429&userid=250477899';
$data = NetEaseCloudMusic::songList($url,['name','time','url','author','album']);
ob_clean();
echo json_encode($data);