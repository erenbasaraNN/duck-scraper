<?php

namespace App\Crawler;

use App\Models\Article;
use App\Models\Issue;
use App\Utils\Generator;
use Exception;

class DocCrawler
{
    private string $directory;
    private string $documentsDirectory;
    private string $pdfsDirectory;

    public function __construct(string $directory, string $documentsDirectory, string $pdfsDirectory)
    {
        $this->directory = $directory;
        $this->documentsDirectory = $documentsDirectory;
        $this->pdfsDirectory = $pdfsDirectory;
    }

    /**
     * @throws Exception
     */
    public function crawl(): void
    {
        $xmlFiles = $this->getXmlFiles($this->directory);

        if (empty($xmlFiles)) {
            return;
        }

        $issues = [];

        foreach ($xmlFiles as $xmlFile) {
            $issue = new Issue();
            $this->processXmlFile($xmlFile, $issue);
            $this->fetchPdfsForIssue($issue);
            $issues[] = $issue;
        }

        $categoryName = basename($this->directory);
        $this->exportIssues($issues, $categoryName);

    }

    private function getXmlFiles(string $directory): array
    {
        return glob("$directory/*.xml");
    }

    /**
     * @throws Exception
     */
    private function processXmlFile(string $xmlFilePath, Issue $issue): void
    {
        $xmlContent = file_get_contents($xmlFilePath);
        $issue->setYear(2024);
        $this->extractVolumeAndNumber($xmlContent, $issue);

        $pdfPaths = $this->fetchPdfsForIssue($issue);

        preg_match_all('/Makale (\d+)/', $xmlContent, $matches, PREG_OFFSET_CAPTURE);
        $articleCount = count($matches[0]);

        for ($i = 0; $i < $articleCount; $i++) {
            $startPos = $matches[0][$i][1];
            $endPos = ($i < $articleCount - 1) ? $matches[0][$i + 1][1] : strlen($xmlContent);
            $articleContent = substr($xmlContent, $startPos, $endPos - $startPos);

            $article = new Article();
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

            if (isset($pdfPaths[$i])) {
                $article->setPdfUrl($pdfPaths[$i]);
            }

            $issue->addArticle($article);
        }
    }


    private function extractVolumeAndNumber($xmlContent, Issue $issue): void
    {
        $pattern = '/<w:t>C(\d+)S(\d+)/';
        if (preg_match($pattern, $xmlContent, $matches)) {
            $volume = $matches[1];
            $number = $matches[2];
            $issue->setVolume($volume);
            $issue->setNumber($number);
        }
    }

    /**
     * @throws Exception
     */
    private function fetchPdfsForIssue(Issue $issue): array
    {
        $logFilePath = __DIR__ . '/../logs/missing_pdfs.log';

        $categoryName = basename($this->directory);
        $pdfDirectory = $this->getIssuePdfDirectory($categoryName, $issue);

        if (!is_dir($pdfDirectory)) {
            $this->logMissingPdfDirectory($pdfDirectory, $logFilePath);
            return [];
        }

        $pdfFiles = $this->getPdfFiles($pdfDirectory);

        return array_map(function ($file) use ($pdfDirectory) {
            return $pdfDirectory . '/' . $file;
        }, $pdfFiles);
    }


    private function logMissingPdfDirectory(string $pdfDirectory, string $logFilePath): void
    {
        $logDirectory = dirname($logFilePath);
        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0777, true);
        }

        $message = "Missing PDF directory: $pdfDirectory - " . date('Y-m-d H:i:s') . PHP_EOL;
        file_put_contents($logFilePath, $message, FILE_APPEND);
    }

    /**
     * @throws Exception
     */
    private function getIssuePdfDirectory(string $categoryName, Issue $issue): string
    {
        $volume = $issue->getVolume();
        $number = $issue->getNumber();

        $patternFull = "/Cilt\s*$volume.*Sayı\s*$number/i";
        $patternShort = "/C{$volume}S$number/i";
        $patternNoSpace = "/Cilt{$volume}Sayı$number/i";

        $categoryDirectory = "$this->pdfsDirectory/$categoryName";

        $matchedDirectory = $this->findMatchingDirectory($categoryDirectory, $patternFull);

        if (!$matchedDirectory) {
            $matchedDirectory = $this->findMatchingDirectory($categoryDirectory, $patternShort);
        }

        if (!$matchedDirectory) {
            $matchedDirectory = $this->findMatchingDirectory($categoryDirectory, $patternNoSpace);
        }

        if ($matchedDirectory) {
            return $matchedDirectory;
        } else {
            throw new Exception("Cilt $volume Sayı $number içeren klasör bulunamadı: $categoryDirectory");
        }
    }
    /**
     * @param string $directory
     * @param string $pattern
     * @return string|null
     * @throws Exception
     */
    private function findMatchingDirectory(string $directory, string $pattern): ?string
    {
        if (!is_dir($directory)) {
            throw new Exception("Dizin mevcut değil: $directory");
        }

        $folders = scandir($directory);

        foreach ($folders as $folder) {
            if ($folder === '.' || $folder === '..') {
                continue;
            }

            if (preg_match($pattern, $folder)) {
                return "$directory/$folder";
            }
        }

        return null;
    }


    private function getPdfFiles(string $pdfDirectory): array
    {
        $pdfFiles = scandir($pdfDirectory);
        if ($pdfFiles === false) {
            return [];
        }

        return array_values(array_filter($pdfFiles, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'pdf';
        }));
    }
    private function crawlTitleEn($xmlContent): ?string
    {
        $pattern = '/<w:t>\s*(?:BAŞLIK:|Başlık:)\s*<\/w:t>.*?<w:t>EN:\s*(.*?)<\/w:t>/si';
        return $this->extractWithRegex($pattern, $xmlContent);
    }

    private function crawlTitleTr($xmlContent): ?string
    {
        $pattern = '/<w:t>\s*(?:BAŞLIK:|Başlık:)\s*<\/w:t>.*?<w:t>TR:\s*(.*?)<\/w:t>/si';
        return $this->extractWithRegex($pattern, $xmlContent);
    }

    private function crawlAbstractEn($xmlContent): ?string
    {
        $pattern = '/<w:t>\s*(?:ABSTRACT:|Abstract)\s*<\/w:t>.*?<w:t>(.*?)<\/w:t>/si';
        $abstract = $this->extractWithRegex($pattern, $xmlContent);

        if ($abstract === null || str_word_count($abstract) <= 5) {
            $pattern2 = '/<w:t>(?:Abstract:|ABSTRACT:)\s*(.*?)<\/w:t>/si';
            $abstract = $this->extractWithRegex($pattern2, $xmlContent);
        }

        return $abstract;
    }

    private function crawlAbstractTr($xmlContent): ?string
    {
        $pattern = '/<w:t>\s*(?:ÖZET:|Özet:|ÖZET|Özet|Öz|ÖZ:|ÖZET )\s*<\/w:t>.*?<w:t>(.*?)<\/w:t>/si';
        $abstract = $this->extractWithRegex($pattern, $xmlContent);

        if ($abstract === null || str_word_count($abstract) <= 5) {
            $pattern2 = '/<w:t>(?:Özet:|ÖZET:)\s*(.*?)<\/w:t>/si';
            $abstract = $this->extractWithRegex($pattern2, $xmlContent);
        }

        return $abstract;
    }

    private function crawlKeywordsTr($xmlContent): ?string
    {
        $pattern = '/<w:t>\s*(?:Anahtar Kelimeler:|Anahtar Sözcükler:|Anahtar Kelimeler :|Anahtar Sözcükler :|Anahtar kelimeler:|Anahtar sözcükler:|Anahtar sözcükler :|Anahatar sözcükler :)\s*(.*?)<\/w:t>/si';
        return $this->extractWithRegex($pattern, $xmlContent);
    }

    private function crawlKeywordsEn($xmlContent): ?string
    {
        $pattern = '/<w:t>\s*(?:Key words:|Key Words:|Keywords :|Keywords:)\s*(.*?)<\/w:t>/si';
        return $this->extractWithRegex($pattern, $xmlContent);
    }

    private function crawlPageNumbers($xmlContent): ?array
    {
        $pattern = '/<w:t>\s*(?:Sayfa No:|SayfaNo:|Sayfa no:|Sayfa No :)\s*(\d+)\s*-\s*(\d+)\s*<\/w:t>/i';

        if (preg_match($pattern, $xmlContent, $matches)) {
            return [
                'firstPage' => $matches[1],
                'lastPage' => $matches[2],
            ];
        }

        return null;
    }

    private function crawlCitations($xmlContent): array
    {
        $citationPattern = '/<w:t>\s*(?:KAYNAKLAR|KAYNAKÇA|KAYNAKÇA:|KAYNAKLAR:|KAYNAKLAR\s*:|REFERENCES|REFERENCES:|REFERENCES\s*:)\s*<\/w:t>.*?(<w:p>.*?<\/w:p>)+/si';

        $citations = [];
        $row = 1;

        if (preg_match($citationPattern, $xmlContent, $matches)) {
            preg_match_all('/<w:t>(.*?)<\/w:t>/', $matches[0], $textMatches);
            $textMatches[1] = array_slice($textMatches[1], 1);

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
            return $matches[1];
        }
        return null;
    }

    private function exportIssues(array $issues, $categoryName): void
    {
        $generator = new Generator($this->documentsDirectory);
        $generator->generate($issues, $categoryName);
    }
}
