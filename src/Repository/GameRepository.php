<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Utils\RedisHelper;


/**
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository
{
    private $redisHelper;
    public function __construct(RedisHelper $helper)
    {
        $this->redisHelper = $helper;
    }

    // /**
    //  * @return Game Returns a game object from key
    //  */

    public function findGame($id)
    {
        if(empty($id) || !$this->redisHelper->exists($id)){
            return null;
        }
        //create a repository for game that will return a game object
        $data  = $this->redisHelper->get($id);

        $game = new Game();
        $game->setScore(isset($data['score'])? (int)$data["score"] : 0);
        $game->setUid($data['id']);
        $game->setFinished(strcmp($data['finished'], "yes") == 0 ? true: false);
        $game->setNextStep(isset($data['nextStep']) ? (int)$data['nextStep'] : 1);

        return $game;
    }


    /*
    public function findOneBySomeField($value): ?Game
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
