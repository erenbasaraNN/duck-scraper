<?php

namespace App\Models;

class Article
{
    private $title;
    private $abstract;
    // Add other properties as needed

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
    }

    public function getAbstract()
    {
        return $this->abstract;
    }

    // Add more setters and getters for other properties
}
