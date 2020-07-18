<?php
// Require the Composer autoloader.
require 'vendor/autoload.php';
require 'config/config.php';

use Aws\Exception\AwsException;
use Aws\ElasticTranscoder\ElasticTranscoderClient;

$result = [];
 
### ElasticTranscoderClient 
$elasticTranscoder = new ElasticTranscoderClient([
    'version' => $config['version'],
    'region'  => $config['region'],
    'credentials' => [ 
        'key'    => $config['key'],
        'secret' => $config['secret'],
    ]
]);
  

$result = $elasticTranscoder->readJob([
    'Id' => '<string>', // REQUIRED
]);