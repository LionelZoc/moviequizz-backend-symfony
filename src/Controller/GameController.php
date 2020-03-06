<?php

namespace App\Controller;


use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\GameRepository;
use App\Repository\QuestionRepository;
use App\Entity\Game;

/**
 * @Route("/api/games")
 */
class GameController extends ResourceController
{
    /**
     * @Route("/{id}", name="get_game", methods={"GET"})
     */
    public function getAction(string $id, GameRepository $repository)
    {
    	if(empty($id) || !$this->redisHelper->exists($id)){
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
     * @Route("/{id}/play", name="get_game_quizz", methods={"GET"})
     */
    public function playAction(string $id, GameRepository $repository, QuestionRepository $questionRepository)
    {
      if(empty($id) || !$this->redisHelper->exists($id)){
        return $this->error("this game does not exist");
      }
      //create a repository for game that will return a game object

        $game = $repository->findGame($id);
        if ($game->getFinished()){
          return $this->error("this game is finished", 403);
        }
        $question = $questionRepository->findQuestionById($game->getNextStep());
        return $this->success($question);
    }

    /**
     * @Route("/{id}/play", name="respond_game_quizz", methods={"POST"})
     */
    public function respondQuizzAction(string $id,Request $request, GameRepository $repository, QuestionRepository $questionRepository)
    {
      if(empty($id) || !$this->redisHelper->exists($id)){
        return $this->error("this game does not exist");
      }
      //create a repository for game that will return a game object
      $content  = $request->request->all();

        $game = $repository->findGame($id);
        if ($game->getFinished()){
          return $this->error("this game is finished", 403);
        }

        $step = $game->getNextStep();
        if ($step > $content['question']){
          return $this->error("you've already respond to that quizz", 403);
        }
        $question = $questionRepository->findQuestionById($content['question']);
        if(!$question){
          return $this->error("this question id does not exist");
        }

        if($content['response'] === $question->getResponse()){
          $game->setNextStep($step + 1);
          $game->setScore($game->getScore() + 1);
          $this->redisHelper->saveGame($game);
        }else{
          $game->setFinished(true);
          $this->redisHelper->saveGame($game);
        }

        return $this->success($game);
    }

    /**
     * @Route("/", name="create_game", methods={"POST"})
     */
    public function createAction(Request $request)
    {
    	try {

    		$id = uniqid('game');
	    	$game = new Game();
	    	$game->setUid($id);
	    	$game->setScore(0);
	    	$game->setFinished(false);
        $game->setNextStep(1);

    		$this->redisHelper->saveGame($game);

    	} catch (\Exception $e) {
    		return $this->error($e->getMessage());

    	}

        return $this->success($game);
    }
}
