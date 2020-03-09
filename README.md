#set up .env.local

create a .env.local file in which you will pour you env variable.

for a quick start you can put those values in it:

REDIS_URL=redis://localhost
APP_ENV=dev
IMDB_TOKEN=eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhMmY3ZGUxYTRkYTQzOTNhNjcyMWQwNDVhMWZmOWU2MyIsInN1YiI6IjVlNWVkMmEzODdlNjNlMDAxNTc2MWVlMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.2zKfQwiO7m0kk9r2qt8gAMszNWelvfa4r5ZX7sroL54
IMDB_HOST="https://api.themoviedb.org/3"


#install packages

composer install

#populate redis with questions ! Mandatory

create some questions with: php bin/console app:create:questions

#endpoints exposed

create game with: POST api/games
get game with : GET api/games/{id}

get random question : GET api/games/{game_id}/play
send response to question : POST api/{game_id}/play //body: {response:"true",question:1}
