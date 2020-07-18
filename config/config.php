<?php  
$config = [
    'application' => [
        'title'    => 'Amazon Services',
        'logo'     => 'public/images/icons/logo.jpg',
        'favicon'  => 'public/images/icons/favicon.jpg',
        'timezone' => date_default_timezone_set('ASIA/DHAKA')
    ],

	'key'     => '',
	'secret'  => '',
	'version' => 'latest',
	'region'  => 'us-east-1',
    'bucket'  => '', 
    'upload_acl'    => 'public-read',
    'output_bucket' => '',
	's3_url'        => 'https://s3.amazonaws.com',
    'upload_dir'    => 'public/videos/', 

	'cloudfront'    => 'https://xxx.cloudfront.net',
    'pipeline_id'   => '',
    'preset_id'     => '',
    'output_key'    => 'hls/',
    'playlist_name' => 'start', 
    'supported_ext' => array('mp4', 'avi', 'mpeg'),
    'clear_localstorage'  => true, // after upload to s3 delete local video
    'expires'       => 30, // after upload to s3 delete local video

    'private_key'   => dirname(__DIR__) . '/public/files/aws-s3-cloudfront-private-key.pem', 
    'key_pair_id'   => '', 

    'mail'          => [
        'charset'   => 'UTF-8',
        'mail_type' => 'Html', // Text
        'sender_email' => ''
    ],

    'google'        => [
        'client_id'     => '',
        'client_secret' => '',
        'redirect_url'  => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'], // full path
    ]
]; 
  