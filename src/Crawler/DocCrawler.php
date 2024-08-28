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

    public function crawl(): void
    {
        // Recursively search for .xml files in the directory
        $xmlFiles = $this->getXmlFiles($this->directory);

        if (empty($xmlFiles)) {
            return;
        }

        // Array to hold all issues for this directory
        $issues = [];

        // Process each XML file as a separate issue
        foreach ($xmlFiles as $xmlFile) {
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
        return glob("$directory/*.xml");
    }

    private function processXmlFile($xmlFilePath, Issue $issue): void
    {
        $xmlContent = file_get_contents($xmlFilePath);

        $issue->setYear(2024);

        $this->extractVolumeAndNumber($xmlContent, $issue);

        preg_match_all('/Makale (\d+)/', $xmlContent, $matches, PREG_OFFSET_CAPTURE);

        $articleCount = count($matches[0]);

        for ($i = 0; $i < $articleCount; $i++) {
            // Get the start position of the current article
            $startPos = $matches[0][$i][1];

            // Determine the end position of this article (start of the next article or end of the content)
            $endPos = ($i < $articleCount - 1) ? $matches[0][$i + 1][1] : strlen($xmlContent);

            // Extract the content for this article
            $articleContent = substr($xmlContent, $startPos, $endPos - $startPos);

            // Create a new Article object
            $article = new Article();

            // Extract titles and abstracts for this specific article
            $article->setTitleEn($this->crawlTitleEn($articleContent));
            $article->setAbstractEn($this->crawlAbstractEn($articleContent));
            $article->setTitleTr($this->crawlTitleTr($articleContent));
            $article->setAbstractTr($this->crawlAbstractTr($articleContent));
            $article->setKeywordsTr($this->crawlKeywordsTr($articleContent));
            $article->setKeywordsEn($this->crawlKeywordsEn($articleContent));
            $article->setCitations($this->crawlCitations($articleContent));
            $pageNumbers = $this->crawlPageNumbers($articleContent);
            if ($pageNumbers !== null) {
                $article->setFirstPage($pageNumbers['firstPage']);
                $article->setLastPage($pageNumbers['lastPage']);
            }
            // Add the article to the issue
            $issue->addArticle($article);
        }
    }

    private function extractVolumeAndNumber($xmlContent, Issue $issue): void
    {
        // Regex pattern to extract volume and number from "C27S2 - Makale 1"
        $pattern = '/<w:t>C(\d+)S(\d+)/';
        if (preg_match($pattern, $xmlContent, $matches)) {
            $volume = $matches[1];
            $number = $matches[2];

            // Set volume and number in the Issue object
            $issue->setVolume($volume);
            $issue->setNumber($number);
        }
    }

    private function crawlTitleEn($xmlContent): ?string
    {
        // Regex pattern to match different cases of "Başlık:"
        $pattern = '/<w:t>\s*(?:BAŞLIK:|Başlık:)\s*<\/w:t>.*?<w:t>EN:\s*(.*?)<\/w:t>/si';

        return $this->extractWithRegex($pattern, $xmlContent);
    }

    private function crawlTitleTr($xmlContent): ?string
    {
        // Regex pattern to match different cases of "Başlık:"
        $pattern = '/<w:t>\s*(?:BAŞLIK:|Başlık:)\s*<\/w:t>.*?<w:t>TR:\s*(.*?)<\/w:t>/si';

        return $this->extractWithRegex($pattern, $xmlContent);
    }

    private function crawlAbstractEn($xmlContent): ?string
    {
        $pattern = '/<w:t>\s*(?:ABSTRACT:|Abstract)\s*<\/w:t>.*?<w:t>(.*?)<\/w:t>/si';

        // Extract the abstract content using the primary pattern
        $abstract = $this->extractWithRegex($pattern, $xmlContent);

        // Check if the extracted content is shorter than 5 words
        if ($abstract === null || str_word_count($abstract) <= 5) {
            // Use pattern2 if the content is shorter than 5 words
            $pattern2 = '/<w:t>(?:Abstract:|ABSTRACT:)\s*(.*?)<\/w:t>/si'; // Add your second pattern here
            $abstract = $this->extractWithRegex($pattern2, $xmlContent);
        }

        return $abstract;
    }


    private function crawlAbstractTr($xmlContent): ?string
    {
        // Define a regex pattern to match different forms of the word "ÖZET"
        $pattern = '/<w:t>\s*(?:ÖZET:|Özet:|ÖZET|Özet|Öz|ÖZ:|ÖZET )\s*<\/w:t>.*?<w:t>(.*?)<\/w:t>/si';

        // Extract the abstract content using the primary pattern
        $abstract = $this->extractWithRegex($pattern, $xmlContent);

        // Check if the extracted content is shorter than 5 words
        if ($abstract === null || str_word_count($abstract) <= 5) {
            // Use pattern2 if the content is shorter than 5 words
            $pattern2 = '/<w:t>(?:Özet:|ÖZET:)\s*(.*?)<\/w:t>/si'; // Add your second pattern here
            $abstract = $this->extractWithRegex($pattern2, $xmlContent);
        }

        return $abstract;
    }


    private function crawlKeywordsTr($xmlContent): ?string
    {
        // Define a regex pattern to match different forms of the Turkish keywords
        $pattern = '/<w:t>\s*(?:Anahtar Kelimeler:|Anahtar Sözcükler:|Anahtar Kelimeler :|Anahtar Sözcükler :|Anahtar kelimeler:|Anahtar sözcükler:|Anahtar sözcükler :|Anahatar sözcükler :)\s*(.*?)<\/w:t>/si';

        // Extract the keywords using the regex pattern
        return $this->extractWithRegex($pattern, $xmlContent);
    }

    private function crawlKeywordsEn($xmlContent): ?string
    {
        // Define a regex pattern to match different forms of the English keywords
        $pattern = '/<w:t>\s*(?:Key words:|Key Words:|Keywords :|Keywords:)\s*(.*?)<\/w:t>/si';

        // Extract the keywords using the regex pattern
        return $this->extractWithRegex($pattern, $xmlContent);
    }

    private function crawlPageNumbers($xmlContent): ?array
    {
        // Define a regex pattern to match different forms of "Sayfa No" with optional spaces around the dash
        $pattern = '/<w:t>\s*(?:Sayfa No:|SayfaNo:|Sayfa no:|Sayfa No :)\s*(\d+)\s*-\s*(\d+)\s*<\/w:t>/i';

        // Try to extract the page numbers using the regex pattern
        if (preg_match($pattern, $xmlContent, $matches)) {
            return [
                'firstPage' => $matches[1],  // First page number
                'lastPage' => $matches[2],   // Last page number
            ];
        }

        return null;  // Return null if no match is found
    }


    private function crawlCitations($xmlContent): array
    {
        // Define a regex pattern to match variations of the word "KAYNAKLAR" and "REFERENCES"
        $citationPattern = '/<w:t>\s*(?:KAYNAKLAR|KAYNAKÇA|KAYNAKÇA:|KAYNAKLAR:|KAYNAKLAR\s*:|REFERENCES|REFERENCES:|REFERENCES\s*:)\s*<\/w:t>.*?(<w:p>.*?<\/w:p>)+/si';

        $citations = [];
        $row = 1;

        // Match the group containing the citations
        if (preg_match($citationPattern, $xmlContent, $matches)) {
            // Extract all <w:t> content within the matched group and treat them as citations
            preg_match_all('/<w:t>(.*?)<\/w:t>/', $matches[0], $textMatches);

            // Skip the first match ("KAYNAKLAR" or "REFERENCES")
            $textMatches[1] = array_slice($textMatches[1], 1);

            // Clean up the citations and add to the citations array with row numbers
            foreach ($textMatches[1] as $citation) {
                $cleanedCitation = trim($citation);
                if (!empty($cleanedCitation)) {
                    $citations[] = ['row' => $row++, 'value' => $cleanedCitation];
                }
            }
        }

        return $citations;
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
