<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Utils\RedisHelper;

/**
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository
{
    private $redisHelper;

    public function __construct(RedisHelper $helper)
    {
        $this->redisHelper = $helper;
    }

    // /**
    //  * @return Game Returns a game object from key
    //  */

    public function findQuestionById($id)
    {
        if(empty($id) || !$this->redisHelper->exists('question'.$id)){
            return null;
        }
        //create a repository for game that will return a game object
        $data  = $this->redisHelper->get('question'.$id);
        $question = new Question();
        $question->setMoviePoster(isset($data['moviePoster'])? $data["moviePoster"] : 0);
        $question->setMovieTitle(isset($data['movieTitle']) ? $data['movieTitle']: "no title" );
        $question->setActorName(isset($data['actorName']) ? $data['actorName'] : "");
        $question->setActorPoster(isset($data['actorProfile']) ? $data['actorProfile'] : "" );
        $question->setResponse(isset($data['response']) ? $data['response'] : "false");
        $question->setId((int)$data['id']);

        return $question;
    }

}
