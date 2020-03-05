<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\QuestionRepository")
 */
class Question
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;


    /**
     * @ORM\Column(type="boolean")
     */
    private $response;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $moviePoster;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $movieTitle;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $actorPoster;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $actorName;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }




    public function getResponse(): ?bool
    {
        return $this->response;
    }

    public function setResponse(bool $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getMoviePoster(): ?string
    {
        return $this->moviePoster;
    }

    public function setMoviePoster(string $moviePoster): self
    {
        $this->moviePoster = $moviePoster;

        return $this;
    }

    public function getMovieTitle(): ?string
    {
        return $this->movieTitle;
    }

    public function setMovieTitle(string $movieTitle): self
    {
        $this->movieTitle = $movieTitle;

        return $this;
    }

    public function getActorPoster(): ?string
    {
        return $this->actorPoster;
    }

    public function setActorPoster(string $actorPoster): self
    {
        $this->actorPoster = $actorPoster;

        return $this;
    }

    public function getActorName(): ?string
    {
        return $this->actorName;
    }

    public function setActorName(string $actorName): self
    {
        $this->actorName = $actorName;

        return $this;
    }
}
