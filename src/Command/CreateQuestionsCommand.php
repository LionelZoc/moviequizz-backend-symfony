<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use JMS\Serializer\SerializationContext;

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
    private $serializer;
    private $httpClient;
    private $popularActors= null;
    private $baseUrl;

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    public function __construct($imdbToken, $imdbHost, RedisHelper $helper, $serializer) {

        parent::__construct();
        $this->imdbHost = $imdbHost;
        $this->imdbToken = $imdbToken;
        $this->redisHelper = $helper;
        $this->serializer = $serializer;
        $this->httpClient = HttpClient::create();
    }

    //get an actor that played in the movie, you can specify the index to choose a specific actor
    private function getCastMember($movie, $index = 0){

        if (array_key_exists("credits", $movie) &&  array_key_exists("cast", $movie["credits"])){

                $cast = $movie["credits"]["cast"];
                $length = count($cast);
                if ($index < $length){
                    return $cast[$index];
                }
                return $cast[0];
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

            $content = $response->getContent();
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

            $content = $response->getContent();
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
    private function createQuestion($movie,$questionIndex=1, $expectedAnswer=true, $actorIndex = 0){

        $popularActors = $this->getPopularActors();
        $popularActorsSize = count($popularActors);
        if (!$popularActors){
            //should not happen
            return null;
        }

        $question = new Question();
        $question->setMovieTitle($movie["title"]);
        $question->setMoviePoster($this->baseUrl.'original'.$movie["poster_path"]);
        $question->setResponse($expectedAnswer);
        $question->setId($questionIndex);

        //take one popular person 

        $castMember  = $this->getCastMember($movie, 1);

        if ($expectedAnswer == true){
            $question->setActorName($castMember['name']);
            $question->setActorPoster($this->baseUrl.'original'.$castMember['profile_path']);

        }else{

            do {
                # code...
                //reset index if sup to actors array length
                if ($actorIndex >= $popularActorsSize ){
                    $actorIndex = 0;
                }
                
                $actor = $popularActors[$actorIndex];
                $actorIndex++;
            } while ($this->isActorInMovieCast($actor, $movie) == true);

            $question->setActorName($actor['name']);
            $question->setActorPoster($this->baseUrl.'original'.$actor['profile_path']);
        }

        //save question to redis
        //chose id
        $this->redisHelper->saveQuestion($question, "question".$questionIndex);
        return $question;

        //if yes set the response to the question to true, if not , set to false
        //if i want the answer to be true, i just have to take one actor from the cast team 
    }

    private function getConfiguration(){

        $configurationRoute = "/configuration";

        $response = $this->httpClient->request('GET', $this->imdbHost.$configurationRoute, ['auth_bearer' => $this->imdbToken]);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
        $content = $response->toArray();
        
        if ($statusCode == 200){

            $this->baseUrl = isset($content["images"]["secure_base_url"]) ? $content["images"]["secure_base_url"] : "https://image.tmdb.org/t/p/";

        }else{

            $this->baseUrl = "https://image.tmdb.org/t/p/";

        }

    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        //get image path configuration
        $this->getConfiguration();

        $discoverMovieRoute = "/discover/movie?sort_by=popularity.desc&page=1";
        
        //fetch some popular movies
        $response = $this->httpClient->request('GET', $this->imdbHost.$discoverMovieRoute, ['auth_bearer' => $this->imdbToken]);

        $statusCode = $response->getStatusCode();
        $io->note(sprintf('status code: %s', $statusCode));

        $content = $response->getContent();
        $content = $response->toArray();
        $questionIndex = 1;
        $expectedAnswer = true;
        if($statusCode == 200){
            $result = $content['results'];

            foreach ($result as $key => $movieData) {

                $movie = $this->getMovideDetails($movieData['id']);
                dump("created question for index");
                dump($questionIndex);
                dump($expectedAnswer);
                $this->createQuestion($movie, $questionIndex, $expectedAnswer, $actorIndex = 0 );
                $redisQuestion = $this->redisHelper->get('question'.$questionIndex);
                dump($redisQuestion);
                $questionIndex++;
                $expectedAnswer = !$expectedAnswer;
                $io->note(sprintf('created question for index: %s', $questionIndex, $expectedAnswer));
                
                
                break;
            }
            $io->success(sprintf('You have created %d questions', $questionIndex - 1));
        }else{
            $io->error("unable to fetch some movies");
        }

        ///dump($content);
// $content = ['id' => 521583, 'name' => 'symfony-docs', ...]
        /*
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');*/

        return 0;
    }
}
