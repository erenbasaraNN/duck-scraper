<?php

namespace App\Utils;

use App\Models\Article;
use App\Models\Issue;
use DOMDocument;
use DOMException;
use Exception;
use RuntimeException;

class Generator
{
    public function generate(array $issues, string $categoryName): void
    {
        try {
            $xmlDoc = new DOMDocument('1.0', 'UTF-8');
            $xmlDoc->formatOutput = true;

            $issuesElement = $xmlDoc->createElement('issues');
            $xmlDoc->appendChild($issuesElement);

            foreach ($issues as $issueObj) {
                /** @var Issue $issueObj */
                $issueElement = $xmlDoc->createElement('issue');
                $issuesElement->appendChild($issueElement);

                $volumeElement = $xmlDoc->createElement('volume', htmlspecialchars($issueObj->getVolume() ?? ''));
                $issueElement->appendChild($volumeElement);

                $yearElement = $xmlDoc->createElement('year', htmlspecialchars($issueObj->getYear() ?? ''));
                $issueElement->appendChild($yearElement);

                $numberElement = $xmlDoc->createElement('number', htmlspecialchars($issueObj->getNumber() ?? ''));
                $issueElement->appendChild($numberElement);

                $articlesElement = $xmlDoc->createElement('articles');
                $issueElement->appendChild($articlesElement);

                foreach ($issueObj->getArticles() as $articleObj) {
                    /** @var Article $articleObj */
                    $articleElement = $xmlDoc->createElement('article');
                    $articlesElement->appendChild($articleElement);

                    $pdfUrl = $articleObj->getPdfUrl() ?? '';

                    try {
                        $fulltextFileElement = $xmlDoc->createElement('fulltext-file', $pdfUrl);
                        $articleElement->appendChild($fulltextFileElement);
                    } catch (DOMException $e) {
                        echo "Error writing fulltext-file for article titled: " . $articleObj->getTitleEn() . " - " . $e->getMessage() . "<br>";
                    }

                    $firstPageElement = $xmlDoc->createElement('firstpage', htmlspecialchars($articleObj->getFirstPage() ?? ''));
                    $articleElement->appendChild($firstPageElement);

                    $lastPageElement = $xmlDoc->createElement('lastpage', htmlspecialchars($articleObj->getLastPage() ?? ''));
                    $articleElement->appendChild($lastPageElement);

                    $primaryLanguage = $this->determinePrimaryLanguage($articleObj);
                    $primaryLanguageElement = $xmlDoc->createElement('primary-language', htmlspecialchars($primaryLanguage));
                    $articleElement->appendChild($primaryLanguageElement);

                    $this->appendTranslations($xmlDoc, $articleElement, $articleObj);

                    $this->appendAuthors($xmlDoc, $articleElement, $articleObj);

                    $this->appendCitations($xmlDoc, $articleElement, $articleObj);
                }
            }

            $outputDir = '/var/tmp/web_crawler/xml/';
            $outputFilePath = $outputDir . "{$categoryName}_output.xml";

            $this->saveXml($xmlDoc, $outputFilePath);
        } catch (Exception $e) {
            echo "Error generating XML: " . $e->getMessage() . "<br>";
        }
    }

    private function determinePrimaryLanguage(Article $articleObj): string
    {
        if (!empty($articleObj->getTitleTr()) && !empty($articleObj->getTitleEn())) {
            return 'tr';
        }

        if (!empty($articleObj->getTitleTr())) {
            return 'tr';
        }

        if (!empty($articleObj->getTitleEn())) {
            return 'en';
        }
        return '';
    }

    /**
     * @throws DOMException
     */
    private function appendTranslations(DOMDocument $xmlDoc, $articleElement, Article $articleObj): void
    {
        $translationsElement = $xmlDoc->createElement('translations');
        $articleElement->appendChild($translationsElement);

        if (!empty($articleObj->getTitleTr())) {
            $translationTrElement = $xmlDoc->createElement('translation');
            $translationsElement->appendChild($translationTrElement);
            $translationTrElement->appendChild($xmlDoc->createElement('locale', 'tr'));
            $translationTrElement->appendChild($xmlDoc->createElement('title', htmlspecialchars($articleObj->getTitleTr())));
            $translationTrElement->appendChild($xmlDoc->createElement('abstract', htmlspecialchars($articleObj->getAbstractTr() ?? '')));
            $translationTrElement->appendChild($xmlDoc->createElement('keywords', htmlspecialchars($articleObj->getKeywordsTr() ?? '')));
        }

        if (!empty($articleObj->getTitleEn())) {
            $translationEnElement = $xmlDoc->createElement('translation');
            $translationsElement->appendChild($translationEnElement);
            $translationEnElement->appendChild($xmlDoc->createElement('locale', 'en'));
            $translationEnElement->appendChild($xmlDoc->createElement('title', htmlspecialchars($articleObj->getTitleEn())));
            $translationEnElement->appendChild($xmlDoc->createElement('abstract', htmlspecialchars($articleObj->getAbstractEn() ?? '')));
            $translationEnElement->appendChild($xmlDoc->createElement('keywords', htmlspecialchars($articleObj->getKeywordsEn() ?? '')));
        }
    }

    /**
     * @throws DOMException
     */
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

    /**
     * @throws DOMException
     */
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

    /**
     * @throws Exception
     */
    private function saveXml(DOMDocument $xmlDoc, string $filePath): void
    {
        try {
            $outputDir = dirname($filePath);
            if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $outputDir));
            }

            $xmlDoc->save($filePath);
        } catch (Exception $e) {
            throw new RuntimeException("Error saving XML: " . $e->getMessage());
        }
    }
}