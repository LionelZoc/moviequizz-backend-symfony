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
use App\Utils\RedisHelper;


class FetchMoviesCommand extends Command
{
    protected static $defaultName = 'app:fetch:movies';
    private $imdbToken;
    private $imdbHost;
    private $redisHelper;
    private $serializer;
    private $httpClient;

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
            if (array_key_exists("credits", $content) &&  array_key_exists("cast", $content["credits"])){

                $cast = $content["credits"]["cast"];
                $slimCastList = [];
                //$actor = new Actor();
                if (is_array($cast)){
                    foreach ($cast as $key => $actorData) {
                        # code...

                        //before adding actor make sure that it does not exists
                        // $actor->setImdId($actorData["id"]);
                        // $actor->setName($actorData["name"]);
                        // $actor->setPoster(isset($actorData["profile_path"])? $actorData["profile_path"] : "");
                        // //$serializedActor = $this->serializer->serialize($actor, 'json');


                        $this->redisHelper->set('actor'.$actorData["id"], "id", $actorData["id"]);
                        $this->redisHelper->set('actor'.$actorData["id"], "name", $actorData["name"]);
                        $this->redisHelper->set('actor'.$actorData["id"],"profile_path", $actorData["profile_path"]);
                        $redisActor = $this->redisHelper->get('actor'.$actorData["id"]);

                        $redisMovieCast = $this->redisHelper->getField("movie".$movie, "cast");

                        $slimCastList[]= ["id"=>$actorData["id"], "name"=>$actorData["name"],"profile_path"=>$actorData["profile_path"]];
                        //$movieCast = "";


                    }

                    $this->redisHelper->set('movie'.$movie, "cast", \json_encode($slimCastList));

                }
            }


        }



    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $discoverMovieRoute = "/discover/movie?sort_by=popularity.desc&page=1";

        //$client = HttpClient::create();

        $response = $this->httpClient->request('GET', $this->imdbHost.$discoverMovieRoute, ['auth_bearer' => $this->imdbToken]);

        $statusCode = $response->getStatusCode();
        $io->note(sprintf('status code: %s', $statusCode));
        // $statusCode = 200
        $contentType = $response->getHeaders()['content-type'][0];
        // $contentType = 'application/json'
        $content = $response->getContent();
        // $content = '{"id":521583, "name":"symfony-docs", ...}'
        $content = $response->toArray();
        if($statusCode == 200){
            $result = $content['results'];
            //$movie = new Movie();
            foreach ($result as $key => $movieData) {
                //improve by making sure that the movie does not exist yet
                $id = $movieData['id'];
                // $title =  $movieData['title'];
                // $poster = $movieData['poster_path'];
                // $movie->setTitle($title);
                // $movie->setId($id);
                // $movie->setPoster($poster);

                // $serializedMovie = $this->serializer->serialize($movie, 'json');
                //
                // $serializedMovie = json_decode($serializedMovie, true);
                //
                // dump($serializedMovie);
                $this->redisHelper->set('movie'.$id, "id", $movieData['id']);
                $this->redisHelper->set('movie'.$id, "title", $movieData['title']);
                $this->redisHelper->set('movie'.$id, "poster_path", $movieData['poster_path']);

                $this->getMovideDetails($id);

                //$redisMovie = $this->redisHelper->get('movie'.$id);
            }
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
