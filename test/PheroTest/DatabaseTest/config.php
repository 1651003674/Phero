<?php

use Symfony\Component\Cache\Simple\RedisCache;
/**
 * @Author: lerko
 * @Date:   2017-05-27 18:12:52
 * @Last Modified by:   lerko
 * @Last Modified time: 2017-07-26 17:56:28
 */
return [
	"database" => [
		"master" => [
			"dsn" => "mysql:dbname=phero;host=127.0.0.1",
			"user" => "root",
			"password" => "lerko",
		],
		// "slave" => [
		// 	[
		// 		"dsn" => "mysql:dbname=kn_erp_db;host=172.17.0.3",
		// 		"user" => "admin",
		// 		"password" => "password",
		// 	],
		// 	[
		// 		"dsn" => "mysql:dbname=kn_erp_db;host=172.17.0.4",
		// 		"user" => "admin",
		// 		"password" => "password",
		// 	],
		// ],
	],
	"swoole"=>[
		"worker_num"=>100,
		"pool_num"=>20
	],
	"cache"=>'redis://127.0.0.1',
	"debug"=>false
];
