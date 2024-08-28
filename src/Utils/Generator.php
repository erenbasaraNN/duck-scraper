<?php

namespace App\Utils;

use App\Models\Article;
use App\Models\Issue;
use DOMDocument;
use DOMException;
use Exception;

class Generator
{
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

                    // Retrieve the PDF URL
                    $pdfUrl = $articleObj->getPdfUrl() ?? '';

                    // Debugging: Check if PDF URL is being retrieved correctly
                    if (empty($pdfUrl)) {
                        echo "Warning: PDF URL is empty for article titled: " . $articleObj->getTitleEn() . "<br>";
                    } else {
                        echo "Writing PDF URL to XML for article titled '" . $articleObj->getTitleEn() . "': " . $pdfUrl . "<br>";
                    }

                    // Write PDF URL to XML
                    try {
                        // Write the fulltext-file element
                        $fulltextFileElement = $xmlDoc->createElement('fulltext-file', $pdfUrl);  // Removed htmlspecialchars() for testing
                        $articleElement->appendChild($fulltextFileElement);
                    } catch (DOMException $e) {
                        echo "Error writing fulltext-file for article titled: " . $articleObj->getTitleEn() . " - " . $e->getMessage() . "<br>";
                    }

                    // Page numbers
                    $firstPageElement = $xmlDoc->createElement('firstpage', htmlspecialchars($articleObj->getFirstPage() ?? ''));
                    $articleElement->appendChild($firstPageElement);

                    $lastPageElement = $xmlDoc->createElement('lastpage', htmlspecialchars($articleObj->getLastPage() ?? ''));
                    $articleElement->appendChild($lastPageElement);

                    // Primary language determination
                    $primaryLanguage = $this->determinePrimaryLanguage($articleObj);
                    $primaryLanguageElement = $xmlDoc->createElement('primary-language', htmlspecialchars($primaryLanguage));
                    $articleElement->appendChild($primaryLanguageElement);

                    // Translations
                    $this->appendTranslations($xmlDoc, $articleElement, $articleObj);

                    // Authors
                    $this->appendAuthors($xmlDoc, $articleElement, $articleObj);

                    // Citations
                    $this->appendCitations($xmlDoc, $articleElement, $articleObj);
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

    private function determinePrimaryLanguage(Article $articleObj): string
    {
        if (!empty($articleObj->getTitleTr()) && !empty($articleObj->getTitleEn())) {
            return 'tr';
        } elseif (!empty($articleObj->getTitleTr())) {
            return 'tr';
        } elseif (!empty($articleObj->getTitleEn())) {
            return 'en';
        }
        return '';
    }

    private function appendTranslations(DOMDocument $xmlDoc, $articleElement, Article $articleObj): void
    {
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

        // English translation
        if (!empty($articleObj->getTitleEn())) {
            $translationEnElement = $xmlDoc->createElement('translation');
            $translationsElement->appendChild($translationEnElement);
            $translationEnElement->appendChild($xmlDoc->createElement('locale', 'en'));
            $translationEnElement->appendChild($xmlDoc->createElement('title', htmlspecialchars($articleObj->getTitleEn())));
            $translationEnElement->appendChild($xmlDoc->createElement('abstract', htmlspecialchars($articleObj->getAbstractEn() ?? '')));
            $translationEnElement->appendChild($xmlDoc->createElement('keywords', htmlspecialchars($articleObj->getKeywordsEn() ?? '')));
        }
    }

    private function appendAuthors(DOMDocument $xmlDoc, $articleElement, Article $articleObj): void
    {
        $authorsElement = $xmlDoc->createElement('authors');
        $articleElement->appendChild($authorsElement);

        foreach ($articleObj->getAuthors() as $author) {
            $authorElement = $xmlDoc->createElement('author');
            $authorsElement->appendChild($authorElement);
            $authorElement->appendChild($xmlDoc->createElement('firstname', htmlspecialchars($author['firstName'] ?? '')));
            $authorElement->appendChild($xmlDoc->createElement('lastname', htmlspecialchars($author['lastName'] ?? '')));
        }
    }

    private function appendCitations(DOMDocument $xmlDoc, $articleElement, Article $articleObj): void
    {
        $citationsElement = $xmlDoc->createElement('citations');
        $articleElement->appendChild($citationsElement);

        foreach ($articleObj->getCitations() as $citation) {
            $citationElement = $xmlDoc->createElement('citation');
            $citationsElement->appendChild($citationElement);
            $citationElement->appendChild($xmlDoc->createElement('row', htmlspecialchars($citation['row'] ?? '')));
            $citationElement->appendChild($xmlDoc->createElement('value', htmlspecialchars($citation['value'] ?? '')));
        }
    }

    private function saveXml(DOMDocument $xmlDoc, string $filePath): void
    {
        try {
            // Create the output directory if it doesn't exist
            $outputDirectory = dirname($filePath);
            if (!is_dir($outputDirectory)) {
                mkdir($outputDirectory, 0777, true);
            }

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
