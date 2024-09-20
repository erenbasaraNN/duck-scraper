<?php

require 'vendor/autoload.php';

use App\Crawler\DocCrawler;

$generatedFiles = [];
$indexHtml = file_get_contents('Templates/index.html');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Define the base path to the Resources directories for Documents and PDFs
    $baseResourcesDirectory = __DIR__ . '/Resources';
    $documentsDirectory = $baseResourcesDirectory . '/Documents';
    $pdfsDirectory = $baseResourcesDirectory . '/PDFS';
    $outputDir = '/var/tmp/web_crawler/xml/';

    // Get all directories inside the Documents folder
    $directories = glob($documentsDirectory . '/*', GLOB_ONLYDIR);

    // Iterate through each directory and start the crawling process
    foreach ($directories as $resourcesDirectory) {
        $directoryName = basename($resourcesDirectory);  // Get the name of the current directory (e.g., Aquatic)

        // Initialize the DocCrawler with the current directory, documentsDirectory, and pdfsDirectory
        $docCrawler = new DocCrawler($resourcesDirectory, $pdfsDirectory);

        // Start the crawling process for this directory
        try {
            $docCrawler->crawl();
        } catch (Exception $e) {
        }

        // Add the generated XML file to the list
        $generatedFiles[] = (string)$outputDir . "{$directoryName}_output.xml";
    }

    $liGroup = '';
    foreach ($generatedFiles as $file) {
        $liTag = sprintf('<li><a href="%s" target="_blank">%s</a></li>', htmlspecialchars($file), htmlspecialchars($file));
        $liGroup .= $liTag;
    }
    $indexHtml = str_replace('<!-- LIGROUP -->', $liGroup, $indexHtml);
    echo "Crawling completed!<br>";
}
echo $indexHtml;

