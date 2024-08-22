<?php

namespace App\Crawler;

use App\Models\Article;
use App\Models\Issue;
use App\Utils\Generator;

class DocCrawler
{
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function crawl()
    {
        // Recursively search for .xml files in the directory
        $xmlFiles = $this->getXmlFiles($this->directory);

        if (empty($xmlFiles)) {
            echo "No XML files found in {$this->directory}.<br>";
            return;
        }

        // Array to hold all issues for this directory
        $issues = [];

        // Process each XML file as a separate issue
        foreach ($xmlFiles as $xmlFile) {
            echo "Processing file: {$xmlFile}<br>";

            // Create a new Issue object for each file
            $issue = new Issue();
            $this->processXmlFile($xmlFile, $issue);

            // Add the issue to the list of issues for this directory
            $issues[] = $issue;
        }

        // Generate the output XML for the directory
        $categoryName = basename($this->directory);
        $this->exportIssues($issues, $categoryName);
    }

    private function getXmlFiles($directory)
    {
        // Search for .xml files directly in the given directory
        return glob("{$directory}/*.xml");
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
        return $this->extractWithRegex('/<volume>(\d+)<\/volume>/', $xmlContent);
    }

    private function crawlNumber($xmlContent): ?string
    {
        return $this->extractWithRegex('/<number>(\d+)<\/number>/', $xmlContent);
    }

    private function crawlTitleEn($xmlContent): ?string
    {
        return $this->extractWithRegex('/<title_en>(.*)<\/title_en>/', $xmlContent);
    }

    private function crawlTitleTr($xmlContent): ?string
    {
        return $this->extractWithRegex('/<title_tr>(.*)<\/title_tr>/', $xmlContent);
    }

    private function crawlAbstractEn($xmlContent): ?string
    {
        return $this->extractWithRegex('/<abstract_en>(.*)<\/abstract_en>/', $xmlContent);
    }

    private function crawlAbstractTr($xmlContent): ?string
    {
        return $this->extractWithRegex('/<abstract_tr>(.*)<\/abstract_tr>/', $xmlContent);
    }

    private function extractWithRegex($pattern, $content): ?string
    {
        if (preg_match($pattern, $content, $matches)) {
            return $matches[1];  // Return the first captured group
        }
        return null;
    }

    private function exportIssues(array $issues, $categoryName): void
    {
        // Call the Generator to export the issues as XML
        $generator = new Generator();
        $generator->generate($issues, $categoryName);
    }
}
