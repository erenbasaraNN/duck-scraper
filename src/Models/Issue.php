<?php

namespace App\Models;

use App\Models\Article;

class Issue
{
    private $volume;
    private $number;
    private $year;
    private $articles = [];

    public function setVolume($volume)
    {
        $this->volume = $volume;
    }

    public function getVolume()
    {
        return $this->volume;
    }

    public function setNumber($number)
    {
        $this->number = $number;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setYear($year)
    {
        $this->year = $year;
    }

    public function getYear()
    {
        return $this->year;
    }

    public function addArticle(Article $article)
    {
        $this->articles[] = $article;
    }

    public function getArticles()
    {
        return $this->articles;
    }
}
