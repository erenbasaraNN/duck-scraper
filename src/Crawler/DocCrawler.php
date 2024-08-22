<?php

namespace Crawler;

use App\Models\Article;
use App\Models\Issue;
use Utils\Generator;

class DocCrawler
{
    private $baseDirectory;

    public function __construct($baseDirectory)
    {
        $this->baseDirectory = $baseDirectory;
    }

    public function crawl(): void
    {
        // Iterate through each magazine directory
        $categories = glob($this->baseDirectory . '/*', GLOB_ONLYDIR);
        foreach ($categories as $categoryDir) {
            $categoryName = basename($categoryDir); // Use directory name as magazine name

            // Process each XML file within the magazine directory
            $xmlFiles = glob($categoryDir . '/*.xml');
            $issue = new Issue();
            $issue->setYear(2024);  // Year is fixed as per your instruction

            foreach ($xmlFiles as $xmlFile) {
                $this->processXmlFile($xmlFile, $issue);
            }

            // Pass the populated Issue object to the Generator
            $this->exportIssue($issue, $categoryName);
        }
    }

    private function processXmlFile($xmlFilePath, Issue $issue): void
    {
        // Load the XML file as a string
        $xmlContent = file_get_contents($xmlFilePath);

        // Extract and set volume and number
        $issue->setVolume($this->crawlVolume($xmlContent));
        $issue->setNumber($this->crawlNumber($xmlContent));

        // Extract and set articles
        preg_match_all('/Makale (\d+)/', $xmlContent, $matches); // Adjust regex to match articles
        foreach ($matches[1] as $match) {
            $article = new Article(titleTr: null, titleEn: null, abstractTr: null, abstractEn: null);
            $article->setTitleEn($this->crawlTitleEn($xmlContent));  // Example for English title extraction
            $article->setAbstractEn($this->crawlAbstractEn($xmlContent));  // Example for English abstract extraction

            // Use additional methods to crawl other article details (e.g., Turkish title and abstract)
            $article->setTitleTr($this->crawlTitleTr($xmlContent));
            $article->setAbstractTr($this->crawlAbstractTr($xmlContent));

            $issue->addArticle($article);
        }
    }

    private function crawlVolume($xmlContent): ?string
    {
        return $this->extractWithRegex('/C(\d+)/', $xmlContent);  // Example regex for volume
    }

    private function crawlNumber($xmlContent): ?string
    {
        return $this->extractWithRegex('/S(\d+)/', $xmlContent);  // Example regex for number
    }

    private function crawlTitleEn($xmlContent): ?string
    {
        // Example regex for extracting English title
        return $this->extractWithRegex('/TitleEn:\s*(.*)/', $xmlContent);
    }

    private function crawlTitleTr($xmlContent): ?string
    {
        // Example regex for extracting Turkish title
        return $this->extractWithRegex('/TitleTr:\s*(.*)/', $xmlContent);
    }

    private function crawlAbstractEn($xmlContent): ?string
    {
        // Example regex for extracting English abstract
        return $this->extractWithRegex('/AbstractEn:\s*(.*)/', $xmlContent);
    }

    private function crawlAbstractTr($xmlContent): ?string
    {
        // Example regex for extracting Turkish abstract
        return $this->extractWithRegex('/AbstractTr:\s*(.*)/', $xmlContent);
    }

    private function extractWithRegex($pattern, $content): ?string
    {
        if (preg_match($pattern, $content, $matches)) {
            return $matches[1];  // Return the first captured group
        }
        return null;
    }

    private function exportIssue(Issue $issue, $categoryName): void
    {
        // This will call the Generator to export the issue as XML
        $generator = new Generator();
        $generator->generate($issue, $categoryName);
    }
}
