<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\QuestionRepository")
 * @ExclusionPolicy("all")
 */
class Question
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Expose
     */
    private $id;


    /**
     * @ORM\Column(type="string", length=255)
     */
    private $response;

    /**
     * @ORM\Column(type="string", length=255)
     * @Expose
     */
    private $moviePoster;

    /**
     * @ORM\Column(type="string", length=255)
     * @Expose
     */
    private $movieTitle;

    /**
     * @ORM\Column(type="string", length=255)
     * @Expose
     */
    private $actorPoster;

    /**
     * @ORM\Column(type="string", length=255)
     * @Expose
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




    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(string $response): self
    {
        $this->response = strcmp($response, "true") == 0 ? "true" : "false";

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
