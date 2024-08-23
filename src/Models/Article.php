<?php

namespace App\Models;

class Article
{
    private ?string $titleTr;
    private ?string $titleEn;
    private ?string $abstractTr;
    private ?string $abstractEn;
    private ?string $keywordsTr;
    private ?string $keywordsEn;
    private ?string $firstPage;
    private ?string $lastPage;
    private ?string $pdfUrl;
    private ?string $primaryLanguage;
    private array $authors;
    private array $citations;

    public function __construct()
    {
        $this->titleTr = null;
        $this->titleEn = null;
        $this->abstractTr = null;
        $this->abstractEn = null;
        $this->keywordsTr = null;
        $this->keywordsEn = null;
        $this->firstPage = null;
        $this->lastPage = null;
        $this->pdfUrl = null;
        $this->primaryLanguage = null;
        $this->authors = [];
        $this->citations = [];


    }

    public function getTitleTr(): ?string
    {
        return $this->titleTr;
    }

    public function setTitleTr(?string $titleTr): void
    {
        $this->titleTr = $titleTr;
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

    public function getKeywordsTr(): ?string
    {
        return $this->keywordsTr;
    }

    public function setKeywordsTr(?string $keywordsTr): void
    {
        $this->keywordsTr = $keywordsTr;
    }

    public function getKeywordsEn(): ?string
    {
        return $this->keywordsEn;
    }

    public function setKeywordsEn(?string $keywordsEn): void
    {
        $this->keywordsEn = $keywordsEn;
    }

    public function getFirstPage(): ?string
    {
        return $this->firstPage;
    }

    public function setFirstPage(?string $firstPage): void
    {
        $this->firstPage = $firstPage;
    }

    public function getLastPage(): ?string
    {
        return $this->lastPage;
    }

    public function setLastPage(?string $lastPage): void
    {
        $this->lastPage = $lastPage;
    }

    public function getPdfUrl(): ?string
    {
        return $this->pdfUrl;
    }

    public function setPdfUrl(?string $pdfUrl): void
    {
        $this->pdfUrl = $pdfUrl;
    }

    public function getPrimaryLanguage(): ?string
    {
        return $this->primaryLanguage;
    }

    public function setPrimaryLanguage(?string $primaryLanguage): void
    {
        $this->primaryLanguage = $primaryLanguage;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function setAuthors(array $authors): void
    {
        $this->authors = $authors;
    }

    public function getCitations(): array
    {
        return $this->citations;
    }

    public function setCitations(array $citations): void
    {
        $this->citations = $citations;
    }
}