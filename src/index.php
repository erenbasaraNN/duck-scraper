<?php

require 'vendor/autoload.php';

use Crawler\DocCrawler;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Define the path to the Resources directory (adjust the path as needed)
    $resourcesDirectory = __DIR__ . '/Resources/AquaticSciences';  // Adjust this path as needed

    // Initialize the DocCrawler with the directory path
    $docCrawler = new DocCrawler($resourcesDirectory);

    // Start the crawling process
    $docCrawler->crawl();

    echo "Crawling completed!";
    exit;  // End script after running the crawler
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doc Crawler</title>
</head>
<body>
<h1>Doc Crawler</h1>
<form method="post">
    <button type="submit">Start Crawling</button>
</form>
</body>
</html>
