<?php

namespace App\Utils;

use App\Models\Article;
use App\Models\Issue;
use DOMDocument;
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
            $xmlDoc = new DOMDocument('1.0', 'UTF-8');
            $xmlDoc->formatOutput = true;  // Enable formatting with indentation and line breaks

            $issuesElement = $xmlDoc->createElement('issues');
            $xmlDoc->appendChild($issuesElement);

            // Loop through each issue and add it to the XML
            foreach ($issues as $issueObj) {
                $issueElement = $xmlDoc->createElement('issue');
                $issuesElement->appendChild($issueElement);

                $volumeElement = $xmlDoc->createElement('volume', htmlspecialchars($issueObj->getVolume() ?? ''));
                $issueElement->appendChild($volumeElement);

                $yearElement = $xmlDoc->createElement('year', htmlspecialchars($issueObj->getYear() ?? ''));
                $issueElement->appendChild($yearElement);

                $numberElement = $xmlDoc->createElement('number', htmlspecialchars($issueObj->getNumber() ?? ''));
                $issueElement->appendChild($numberElement);

                // Add articles to the issue
                $articlesElement = $xmlDoc->createElement('articles');
                $issueElement->appendChild($articlesElement);

                foreach ($issueObj->getArticles() as $articleObj) {
                    /** @var Article $articleObj */
                    $articleElement = $xmlDoc->createElement('article');
                    $articlesElement->appendChild($articleElement);

                    // Fulltext URL
                    $fulltextFileElement = $xmlDoc->createElement('fulltext-file', htmlspecialchars($articleObj->getPdfUrl() ?? ''));
                    $articleElement->appendChild($fulltextFileElement);

                    // Page numbers
                    $firstPageElement = $xmlDoc->createElement('firstpage', htmlspecialchars($articleObj->getFirstPage() ?? ''));
                    $articleElement->appendChild($firstPageElement);

                    $lastPageElement = $xmlDoc->createElement('lastpage', htmlspecialchars($articleObj->getLastPage() ?? ''));
                    $articleElement->appendChild($lastPageElement);

                    $primaryLanguage = ''; // Default to 'en'
                    if (!empty($articleObj->getTitleTr()) && !empty($articleObj->getTitleEn())) {
                        $primaryLanguage = 'tr';
                    } elseif (!empty($articleObj->getTitleTr())) {
                        $primaryLanguage = 'tr';
                    } elseif (!empty($articleObj->getTitleEn())) {
                        $primaryLanguage = 'en';
                    }

                    $primaryLanguageElement = $xmlDoc->createElement('primary-language', htmlspecialchars($primaryLanguage));
                    $articleElement->appendChild($primaryLanguageElement);
                    // Translations
                    $translationsElement = $xmlDoc->createElement('translations');
                    $articleElement->appendChild($translationsElement);

                    // Turkish translation
                    if (!empty($articleObj->getTitleTr())) {
                        $translationTrElement = $xmlDoc->createElement('translation');
                        $translationsElement->appendChild($translationTrElement);
                        $translationTrElement->appendChild($xmlDoc->createElement('locale', 'tr'));
                        $translationTrElement->appendChild($xmlDoc->createElement('title', htmlspecialchars($articleObj->getTitleTr())));
                        $translationTrElement->appendChild($xmlDoc->createElement('abstract', htmlspecialchars($articleObj->getAbstractTr() ?? '')));
                        $translationTrElement->appendChild($xmlDoc->createElement('keywords', htmlspecialchars($articleObj->getKeywordsTr() ?? '')));
                    }
                    // English translation (if available)
                    if (!empty($articleObj->getTitleEn())) {
                        $translationEnElement = $xmlDoc->createElement('translation');
                        $translationsElement->appendChild($translationEnElement);
                        $translationEnElement->appendChild($xmlDoc->createElement('locale', 'en'));
                        $translationEnElement->appendChild($xmlDoc->createElement('title', htmlspecialchars($articleObj->getTitleEn())));
                        $translationEnElement->appendChild($xmlDoc->createElement('abstract', htmlspecialchars($articleObj->getAbstractEn() ?? '')));
                        $translationEnElement->appendChild($xmlDoc->createElement('keywords', htmlspecialchars($articleObj->getKeywordsEn() ?? '')));
                    }

                    // Authors
                    $authorsElement = $xmlDoc->createElement('authors');
                    $articleElement->appendChild($authorsElement);

                    foreach ($articleObj->getAuthors() as $author) {
                        $authorElement = $xmlDoc->createElement('author');
                        $authorsElement->appendChild($authorElement);
                        $authorElement->appendChild($xmlDoc->createElement('firstname', htmlspecialchars($author['firstName'] ?? '')));
                        $authorElement->appendChild($xmlDoc->createElement('lastname', htmlspecialchars($author['lastName'] ?? '')));
                    }

                    // Citations
                    $citationsElement = $xmlDoc->createElement('citations');
                    $articleElement->appendChild($citationsElement);

                    foreach ($articleObj->getCitations() as $citation) {
                        $citationElement = $xmlDoc->createElement('citation');
                        $citationsElement->appendChild($citationElement);

                        // Add the row and value for each citation
                        $citationElement->appendChild($xmlDoc->createElement('row', htmlspecialchars($citation['row'] ?? '')));
                        $citationElement->appendChild($xmlDoc->createElement('value', htmlspecialchars($citation['value'] ?? '')));
                    }

                }
            }

            // Determine the output file path based on the category name
            $outputFilePath = __DIR__ . "/../output/{$categoryName}_output.xml";

            // Save the XML file
            $this->saveXml($xmlDoc, $outputFilePath);
        } catch (Exception $e) {
            echo "Error generating XML: " . $e->getMessage() . "<br>";
        }
    }

    /**
     * Save the generated XML to a file with formatting.
     *
     * @param DOMDocument $xmlDoc The XML document.
     * @param string $filePath The path where the XML file should be saved.
     * @throws Exception if file saving fails
     */
    private function saveXml(DOMDocument $xmlDoc, string $filePath): void
    {
        try {
            // Save the formatted XML to the file
            $result = $xmlDoc->save($filePath);
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
