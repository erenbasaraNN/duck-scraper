<?php

require 'vendor/autoload.php';

use App\Crawler\DocCrawler;

$generatedFiles = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Define the base path to the Resources directory
    $baseResourcesDirectory = __DIR__ . '/Resources';

    // Get all directories inside the Resources folder
    $directories = glob($baseResourcesDirectory . '/*', GLOB_ONLYDIR);

    // Iterate through each directory and start the crawling process
    foreach ($directories as $resourcesDirectory) {
        $directoryName = basename($resourcesDirectory);  // Get the name of the current directory (e.g., AquaticSciences)

        // Initialize the DocCrawler with the current directory path
        echo "Starting to crawl directory: {$resourcesDirectory}<br>";
        $docCrawler = new DocCrawler($resourcesDirectory);

        // Start the crawling process for this directory
        $docCrawler->crawl();
        echo "Finished crawling directory: {$resourcesDirectory}<br>";

        // Add the generated XML file to the list
        $generatedFiles[] = "output/{$directoryName}_output.xml";
    }

    echo "Crawling completed!<br>";
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

<?php if (!empty($generatedFiles)): ?>
    <h2>Generated XML Files</h2>
    <ul>
        <?php foreach ($generatedFiles as $file): ?>
            <li><a href="<?= $file ?>" target="_blank"><?= basename($file) ?></a></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

</body>
</html>
