<?php

namespace App\Controller;


use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\GameRepository;

class GameController extends ResourceController
{
    /**
     * @Route("/games/{id}", name="get_game", methods={"GET"})
     */
    public function getAction(string $id, GameRepository $repository)
    {
    	if(empty($id) || !$this->redisHelper->exists('game'.$id)){
    		return $this->error("this game does not exist");
    	}
    	//create a repository for game that will return a game object
        
        $game = $repository->findGame($id);
        if(!$game){
        	return $this->error("you provided a bad game id");
        }
        return $this->success($game);
    }
    /**
     * @Route("/games", name="create_game", methods={"POST"})
     */
    public function createAction(Request $request)
    {
    	try {

    		$id = uniqid('game');
	    	$game = new Game();
	    	$game->setUid($id);
	    	$game->setScore(0);
	    	$game->setFinished(false);

    		$this->redisHelper->createGame($game);
    		
    	} catch (\Exception $e) {
    		return $this->error("unable to create the game try again later");
    		
    	}
    	
        return $this->success($game);
    }
}
