<?php
return [
    'accessKey' => Env::get('qiniu.accessKey'),
    'secretKey' => Env::get('qiniu.secretKey'),
    'bucket'    => Env::get('qiniu.bucket'),
    'excelbucket'    => Env::get('qiniu.excelbucket'),
    'videobucket'    => Env::get('qiniu.videobucket'),
    'domain'    => Env::get('qiniu.domain'),
    'exceldomain'    => Env::get('qiniu.exceldomain'),
    'videodomain'    => Env::get('qiniu.videodomain'),
];
