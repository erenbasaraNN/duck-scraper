<?php

namespace Utils;

use App\Models\Issue;
use App\Models\Article;
use SimpleXMLElement;

class Generator
{
    /**
     * Generate the XML output based on the provided Issue data.
     *
     * @param Issue $issue The issue object containing articles and metadata.
     * @param string $categoryName The name of the magazine category (used for the output file name).
     */
    public function generate(Issue $issue, string $categoryName): void
    {
        // Create the root element for the XML output
        $xml = new SimpleXMLElement('<issues/>');
        $issueElement = $xml->addChild('issue');
        $issueElement->addChild('volume', $issue->getVolume());
        $issueElement->addChild('year', $issue->getYear());
        $issueElement->addChild('number', $issue->getNumber());

        // Add articles to the XML
        $articlesElement = $issueElement->addChild('articles');
        foreach ($issue->getArticles() as $article) {
            $articleElement = $articlesElement->addChild('article');

            // Populate the article details
            $articleElement->addChild('fulltext-file', htmlspecialchars($article->getFulltextFile()));
            $articleElement->addChild('firstpage', $article->getFirstPage());
            $articleElement->addChild('lastpage', $article->getLastPage());
            $articleElement->addChild('primary-language', $article->getPrimaryLanguage());

            // Add translations
            $translationsElement = $articleElement->addChild('translations');
            foreach ($article->getTranslations() as $translation) {
                $translationElement = $translationsElement->addChild('translation');
                $translationElement->addChild('locale', $translation['locale']);
                $translationElement->addChild('title', htmlspecialchars($translation['title']));
                $translationElement->addChild('abstract', htmlspecialchars($translation['abstract']));
                $translationElement->addChild('keywords', htmlspecialchars($translation['keywords']));
            }

            // Add authors
            $authorsElement = $articleElement->addChild('authors');
            foreach ($article->getAuthors() as $author) {
                $authorElement = $authorsElement->addChild('author');
                $authorElement->addChild('firstname', htmlspecialchars($author['firstname']));
                $authorElement->addChild('lastname', htmlspecialchars($author['lastname']));
            }

            // Add citations
            $citationsElement = $articleElement->addChild('citations');
            foreach ($article->getCitations() as $citation) {
                $citationElement = $citationsElement->addChild('citation');
                $citationElement->addChild('row', $citation['row']);
                $citationElement->addChild('value', htmlspecialchars($citation['value']));
            }
        }

        // Determine the output file path based on the category name
        $outputFilePath = __DIR__ . "/../output/{$categoryName}_output.xml";
        $this->saveXml($xml, $outputFilePath);
    }

    /**
     * Save the generated XML to a file.
     *
     * @param SimpleXMLElement $xml The XML data.
     * @param string $filePath The path where the XML file should be saved.
     */
    private function saveXml(SimpleXMLElement $xml, string $filePath): void
    {
        // Create the directory if it doesn't exist
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        // Save the XML to the file
        $xml->asXML($filePath);
    }
}
