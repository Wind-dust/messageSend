<?php
return [
    'accessKey' => Env::get('qiniu.accessKey'),
    'secretKey' => Env::get('qiniu.secretKey'),
    'bucket'    => Env::get('qiniu.bucket'),
    'excelbucket'    => Env::get('qiniu.excelbucket'),
    'domain'    => Env::get('qiniu.domain'),
    'exceldomain'    => Env::get('qiniu.exceldomain'),
];