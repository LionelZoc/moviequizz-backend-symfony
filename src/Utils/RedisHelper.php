<?php

namespace App\Utils;

use Predis\Client;
use App\Entity\Game;
use App\Entity\Question;

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
		if(!$this->exists($key)) return null;
		return $this->redisClient->hgetall($key);
	}


	public function exists($key){
		return $this->redisClient->exists($key);
	}
	public function getField($key, $field){
		return $this->redisClient->hget($key, $field);
	}

	//should move this to some manager class
	public function saveGame(Game $game, $id){

		$this->redisHelper->set($id , "id", $game->getUid());
    	$this->redisHelper->set($id , "score", $game->getScore());
    	$this->redisHelper->set($id , "finished", $game->getFinished() ? 1 : 0);
	}

	public function saveQuestion(Question $question, $id){

		$this->set($id , "id", $question->getId());
    	$this->set($id , "moviePoster", $question->getMoviePoster());
    	$this->set($id , "movieTitle", $question->getMovieTitle());
    	$this->set($id , "actorName", $question->getActorName());
    	$this->set($id , "actorTitle", $question->getActorPoster());
	}

}
