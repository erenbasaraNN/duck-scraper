<?php

namespace App\Utils;

use App\Models\Article;
use App\Models\Issue;
use SimpleXMLElement;
use Exception;

class Generator
{
    /**
     * Generate the XML output for all issues in a directory.
     *
     * @param Issue[] $issues Array of Issue objects containing articles and metadata.
     * @param string $categoryName The name of the magazine category (used for the output file name).
     */
    public function generate(array $issues, string $categoryName): void
    {
        try {
            // Create the root element for the XML output
            $xml = new SimpleXMLElement('<issues/>');

            // Loop through each issue and add it to the XML
            foreach ($issues as $issue) {
                $issueElement = $xml->addChild('issue');
                $issueElement->addChild('volume', $issue->getVolume() ?? '');
                $issueElement->addChild('number', $issue->getNumber() ?? '');
                $issueElement->addChild('year', $issue->getYear() ?? '');

                // Add articles to the issue
                $articlesElement = $issueElement->addChild('articles');
                foreach ($issue->getArticles() as $article) {
                    /** @var Article $article */
                    $articleElement = $articlesElement->addChild('article');
                    $articleElement->addChild('title_en', htmlspecialchars($article->getTitleEn() ?? ''));       // English title
                    $articleElement->addChild('title_tr', htmlspecialchars($article->getTitleTr() ?? ''));     // Turkish title
                    $articleElement->addChild('abstract_en', htmlspecialchars($article->getAbstractEn() ?? '')); // English abstract
                    $articleElement->addChild('abstract_tr', htmlspecialchars($article->getAbstractTr() ?? '')); // Turkish abstract

                    // You can add more fields as necessary, such as authors, keywords, etc.
                }
            }

            // Determine the output file path based on the category name
            $outputFilePath = __DIR__ . "/../output/{$categoryName}_output.xml";
            echo "Saving XML to: {$outputFilePath}<br>";

            // Save the XML file
            $this->saveXml($xml, $outputFilePath);

            echo "XML file successfully saved to {$outputFilePath}.<br>";

        } catch (Exception $e) {
            echo "Error generating XML: " . $e->getMessage() . "<br>";
        }
    }

    /**
     * Save the generated XML to a file.
     *
     * @param SimpleXMLElement $xml The XML data.
     * @param string $filePath The path where the XML file should be saved.
     * @throws Exception if file saving fails
     */
    private function saveXml(SimpleXMLElement $xml, string $filePath): void
    {
        try {
            // Create the directory if it doesn't exist
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                echo "Creating directory: {$directory}<br>";
                if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                    throw new Exception("Failed to create directory: {$directory}");
                } else {
                    echo "Directory created: {$directory}<br>";
                }
            } else {
                echo "Directory already exists: {$directory}<br>";
            }

            // Save the XML to the file
            echo "Attempting to save XML to: {$filePath}<br>";
            $result = $xml->asXML($filePath);
            if ($result === false) {
                throw new Exception("Failed to save XML to {$filePath}");
            } else {
                echo "XML successfully saved to {$filePath}<br>";
            }

        } catch (Exception $e) {
            throw new Exception("Error saving XML: " . $e->getMessage());
        }
    }
}
