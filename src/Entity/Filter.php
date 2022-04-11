<?php

namespace App\Entity;

use App\Repository\FilterRepository;
use Doctrine\ORM\Mapping as ORM;

class Filter
{
    /**
     * @ORM\Column(type="string", length=255, required=false)
     */
    private $genres;

    /**
     * @ORM\Column(type="integer", required=false)
     */
    private $years;

    /**
     * @ORM\Column(type="string", length=255, required=false)
     */
    private $languages;


    public function getGenres(): ?string
    {
        return $this->genres;
    }

    public function setGenres(string $genres): self
    {
        $this->genres = $genres;

        return $this;
    }

    public function getYears(): ?int
    {
        return $this->years;
    }

    public function setYears(int $years): self
    {
        $this->years = $years;

        return $this;
    }

    public function getLanguages(): ?string
    {
        return $this->languages;
    }

    public function setLanguages(string $languages): self
    {
        $this->languages = $languages;

        return $this;
    }
}
