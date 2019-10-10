<?php
return [
    'accessKey' => Env::get('qiniu.accessKey'),
    'secretKey' => Env::get('qiniu.secretKey'),
    'bucket'    => Env::get('qiniu.bucket'),
    'domain'    => Env::get('qiniu.domain'),
];