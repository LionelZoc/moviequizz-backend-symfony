<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;


use App\Entity\Movie;
use App\Entity\Actor;
use App\Entity\Question;
use App\Utils\RedisHelper;


class CreateQuestionsCommand extends Command
{
    protected static $defaultName = 'app:create:questions';
    private $imdbToken;
    private $imdbHost;
    private $redisHelper;

    private $httpClient;
    private $popularActors= null;
    private $baseUrl;

    protected function configure()
    {
        $this
            ->setDescription('command that populate redis database with some questions')
        ;
    }

    public function __construct($imdbToken, $imdbHost, RedisHelper $helper) {

        parent::__construct();
        $this->imdbHost = $imdbHost;
        $this->imdbToken = $imdbToken;
        $this->redisHelper = $helper;
        $this->httpClient = HttpClient::create();
    }

    //get an actor that played in the movie, you can specify the index to choose a specific actor
    private function getCastMember($movie){

        if (array_key_exists("credits", $movie) &&  array_key_exists("cast", $movie["credits"])){

                $cast = $movie["credits"]["cast"];
                $length = count($cast);
                if ($length < 1){
                    throw new \Exception("Movie with no cast member");
                    
                }
                $index = rand(0, $length-1);
                return $cast[$index];

            }
    }
    //function that checks if an actor is in a movie
    private function isActorInMovieCast($actor, $movie){

        if (array_key_exists("credits", $movie) &&  array_key_exists("cast", $movie["credits"])){

                $cast = $movie["credits"]["cast"];

                if (is_array($cast)){
                    foreach ($cast as $key => $castData) {
                        # code...
                        if($castData["id"] == $actor["id"]){
                            return true;
                        }
                    }
                    return false;
                }
            }

    }


    /*
    * get some popular actor
    * return array[]
    */
    private function getPopularActors(){

        if(!empty($this->popularActors)) return $this->popularActors;

        $popularActorRoute = '/person/popular';
        $response = $this->httpClient->request('GET', $this->imdbHost.$popularActorRoute, ['auth_bearer' => $this->imdbToken]);
        $statusCode = $response->getStatusCode();

        if($statusCode == 200){

            $content = $response->toArray();
            $actors =  $content['results'];
            $this->popularActors = $actors;
            return $actors;
        }
        return null;
    }


    /*
    * function to get a movie details and credits from movie id
    */
    private function getMovideDetails($movie){
        if(empty($movie)) {
            throw new \Exception("provide a valid movie");

        }

        $movieDetailsRoute = '/movie/'.$movie.'?append_to_response=credits';

        $response = $this->httpClient->request('GET', $this->imdbHost.$movieDetailsRoute, ['auth_bearer' => $this->imdbToken]);

        $statusCode = $response->getStatusCode();
        if ($statusCode == 200){


            $content = $response->toArray();
            //dump($content["credits"]);
            return $content;
        }
        return null;
    }

    /*
    * create a question from a movie, and popular actors
    * you can also specify the index from which to start looking into the popular actor list
    * the function also take the $expectedAnswer to create a question with an expected response
    */
    private function createQuestion($movie,$questionIndex=1, $expectedAnswer=true, SymfonyStyle $io){
        try {

            $popularActors = $this->getPopularActors();
            $popularActorsSize = count($popularActors);
            if (!$popularActors){
                //should not happen
                return null;
            }

            $question = new Question();
            $question->setMovieTitle($movie["title"]);
            $question->setMoviePoster($this->baseUrl.'original'.$movie["poster_path"]);
            $question->setResponse($expectedAnswer ? "true" : "false");
            $question->setId($questionIndex);

            //take one popular person

            $castMember  = $this->getCastMember($movie);

            if ($expectedAnswer == true){
                $question->setActorName($castMember['name']);
                $question->setActorPoster($this->baseUrl.'original'.$castMember['profile_path']);

            }else{
                //find a popular actor that is not in the movie cast
                do {

                    //choose randomly a popular actor that is not in the movie cast
                    $actorIndex = rand(1, $popularActorsSize-1);
                    $actor = $popularActors[$actorIndex];
                    //$actorIndex++;
                } while ($this->isActorInMovieCast($actor, $movie) == true);

                $question->setActorName($actor['name']);
                $question->setActorPoster($this->baseUrl.'original'.$actor['profile_path']);
            }

            //save question to redis
            //chose id
            $this->redisHelper->saveQuestion($question, "question".$questionIndex);
            return $question;
            
        } catch (\Exception $e) {

            $io->error($e->getMessage());
            return null;
        }


    }

    //get imdb configuration for image absolute path configuration
    private function getConfiguration(){

        $configurationRoute = "/configuration";


        $response = $this->httpClient->request('GET', $this->imdbHost.$configurationRoute, ['auth_bearer' => $this->imdbToken]);

        $statusCode = $response->getStatusCode();

        $content = $response->toArray();

        if ($statusCode == 200){

            $this->baseUrl = isset($content["images"]["secure_base_url"]) ? $content["images"]["secure_base_url"] : "https://image.tmdb.org/t/p/";

        }else{

            $this->baseUrl = "https://image.tmdb.org/t/p/";

        }


    }

    //create a list of question
    //$nbPage is the number of page to go through to get popular movies
    private function batchCreateQuestion($nbPage=1, SymfonyStyle $io){


      $questionIndex = 1;

      for ($i=1; $i < $nbPage; $i++) {
        $discoverMovieRoute = '/discover/movie?sort_by=popularity.desc&page='.$i;
        
        //fetch some popular movies
        $response = $this->httpClient->request('GET', $this->imdbHost.$discoverMovieRoute, ['auth_bearer' => $this->imdbToken]);

        $statusCode = $response->getStatusCode();

        $content = $response->toArray();


        $expectedAnswer = true;

        if($statusCode == 200){
            $result = $content['results'];
            //create 3 quizz on each movie
            foreach ($result as $key => $movieData) {

                $movie = $this->getMovideDetails($movieData['id']);

                //create a question so that the anwser will be expectedAnswer
                $createdQuestion = $this->createQuestion($movie, $questionIndex, $expectedAnswer, $io);
                //$redisQuestion = $this->redisHelper->get('question'.$questionIndex);
                if ($createdQuestion){
                    $questionIndex++;
                    $expectedAnswer = !$expectedAnswer;
                }
                

            }
            $io->success(sprintf('You have created %d questions', $questionIndex - 1));
        }else{
            $io->error("unable to fetch some movies");
        }
      }

    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        //number of page to go through when fetching popular movies
        $maxPage = 20;
        //get image path configuration
        $this->getConfiguration($io);

        //create some questions 
        $this->batchCreateQuestion($maxPage, $io);

        return 0;
    }
}
