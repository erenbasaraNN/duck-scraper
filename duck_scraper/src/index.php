<?php

require 'vendor/autoload.php';

use App\Crawler\DocCrawler;

$generatedFiles = [];

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
        $docCrawler = new DocCrawler($resourcesDirectory, $documentsDirectory, $pdfsDirectory);

        // Start the crawling process for this directory
        try {
            $docCrawler->crawl();
        } catch (Exception $e) {
        }

        // Add the generated XML file to the list
        $generatedFiles[] = "$outputDir . {$directoryName}_output.xml";
    }

    echo "Crawling completed!<br>";
}

include 'Templates/index.html';