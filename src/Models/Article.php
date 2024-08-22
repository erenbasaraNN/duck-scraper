<?php

namespace App\Models;

class Article
{
    private ?string $titleTr;
    private ?string $titleEn;
    private ?string $abstractTr;
    private ?string $abstractEn;

    public function __construct($titleTr, $titleEn, $abstractTr, $abstractEn)
    {
        $this->titleTr = null;
        $this->titleEn = null;
        $this->abstractTr = null;
        $this->abstractEn = null;
    }

    public function getTitleEn(): ?string
    {
        return $this->titleEn;
    }

    public function setTitleEn(?string $titleEn): void
    {
        $this->titleEn = $titleEn;
    }

    public function getAbstractTr(): ?string
    {
        return $this->abstractTr;
    }

    public function setAbstractTr(?string $abstractTr): void
    {
        $this->abstractTr = $abstractTr;
    }

    public function getAbstractEn(): ?string
    {
        return $this->abstractEn;
    }

    public function setAbstractEn(?string $abstractEn): void
    {
        $this->abstractEn = $abstractEn;
    }

    public function getTitleTr(): ?string
    {
        return $this->titleTr;
    }

    public function setTitleTr(?string $titleTr): void
    {
        $this->titleTr = $titleTr;
    }

}