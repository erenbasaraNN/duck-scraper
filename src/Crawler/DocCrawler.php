<?php

namespace Crawler;

use App\Models\Article;
use App\Models\Issue;
use SimpleXMLElement;

class DocCrawler
{
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function crawl()
    {
        // Scan the directory for XML files
        $xmlFiles = glob($this->directory . '/*.xml');

        foreach ($xmlFiles as $xmlFile) {
            $this->processXmlFile($xmlFile);
        }
    }

    private function processXmlFile($xmlFilePath)
    {
        // Load the XML file
        $xmlContent = simplexml_load_file($xmlFilePath);

        // Extract data from the XML
        $issue = new Issue();
        $issue->setYear(2024); // Year is fixed

        $issue->setVolume($this->crawlVolume($xmlContent));
        $issue->setNumber($this->crawlNumber($xmlContent));

        // Extract articles and map them to Article objects
        foreach ($xmlContent->xpath('//ArticleTag') as $articleNode) { // Replace with actual tag
            $article = new Article();
            $article->setTitle($this->crawlTitleEn($articleNode));
            $article->setAbstract($this->crawlAbstractEn($articleNode));
            // Add more mappings as needed

            $issue->addArticle($article);
        }

        // For now, we can print the extracted data
        $this->printIssue($issue);
    }

    // Individual crawling methods for each data point
    private function crawlVolume(SimpleXMLElement $xml)
    {
        // Example: Replace with actual XPath or tag
        return (int)$xml->xpath('//VolumeTag')[0];
    }

    private function crawlNumber(SimpleXMLElement $xml)
    {
        // Example: Replace with actual XPath or tag
        return (int)$xml->xpath('//NumberTag')[0];
    }

    private function crawlTitleEn(SimpleXMLElement $articleNode)
    {
        // Example: Replace with actual XPath or tag for English title
        return (string)$articleNode->xpath('TitleEn')[0];
    }

    private function crawlTitleTr(SimpleXMLElement $articleNode)
    {
        // Example: Replace with actual XPath or tag for Turkish title
        return (string)$articleNode->xpath('TitleTr')[0];
    }

    private function crawlAbstractEn(SimpleXMLElement $articleNode)
    {
        // Example: Replace with actual XPath or tag for English abstract
        return (string)$articleNode->xpath('AbstractEn')[0];
    }

    private function crawlAbstractTr(SimpleXMLElement $articleNode)
    {
        // Example: Replace with actual XPath or tag for Turkish abstract
        return (string)$articleNode->xpath('AbstractTr')[0];
    }

    private function printIssue(Issue $issue)
    {
        echo "Issue: Volume " . $issue->getVolume() . ", Number " . $issue->getNumber() . ", Year " . $issue->getYear() . "\n";
        foreach ($issue->getArticles() as $article) {
            echo "Article: " . $article->getTitle() . "\n";
        }
    }
}
