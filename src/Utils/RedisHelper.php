<?php

namespace App\Utils;

use Predis\Client;

class RedisHelper
{
	private $redisClient;

	public function __construct(Client $client)
	{
		$this->redisClient = $client;
	}

	// public function set($key, $value){
	// 	$this->redisClient->hmset($key, $value);
	// }

	public function set($key,$field, $value){
		$this->redisClient->hset($key, $field, $value);
	}

	public function get($key){
		return $this->redisClient->hgetall($key);
	}

	public function getField($key, $field){
		return $this->redisClient->hget($key, $field);
	}
}
