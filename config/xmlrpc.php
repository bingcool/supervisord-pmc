<?php

// Dashboard columns. 2 or 3
$config['supervisor_cols'] = 2;

// Refresh Dashboard every x seconds. 0 to disable
$config['refresh'] = 0;

// Enable or disable Alarm Sound
$config['enable_alarm'] = false;

// Show hostname after server name
$config['show_host'] = false;

$config['supervisor_servers'] = array(
	'server01' => array(
		'url' => 'http://192.168.99.103/RPC2',
		'port' => '9001',
		'username' => 'bingcool',
		'password' => '123456',
		'process_title' => [
			'DLiveUser' => "统计直播用户",
			'DRejectOrder'=>"拒绝订单数",
			'DReshOrder'=>"刷单数",
			'DSendSms'=>"发送短信",
			'DUserNotify'=>"用户通知"
		]
	)
);

// Set timeout connecting to remote supervisord RPC2 interface
$config['timeout'] = 3;

// Path to Redmine new issue url
$config['redmine_url'] = 'http://redmine.url/path_to_new_issue_url';

// Default Redmine assigne ID
$config['redmine_assigne_id'] = '69';

return $config;