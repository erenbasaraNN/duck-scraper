<?php

namespace App\Models;

class Issue
{
    private ?string $volume;
    private ?string $number;
    private ?string $year;
    private array $articles = [];

    public function __construct()
    {
        $this->volume = null;
        $this->number = null;
        $this->year = null;
    }

    public function setVolume($volume): void
    {
        $this->volume = $volume;
    }

    public function getVolume(): ?string
    {
        return $this->volume;
    }

    public function setNumber($number): void
    {
        $this->number = $number;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setYear($year): void
    {
        $this->year = $year;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function addArticle(Article $article): void
    {
        $this->articles[] = $article;
    }

    public function getArticles(): array
    {
        return $this->articles;
    }
}