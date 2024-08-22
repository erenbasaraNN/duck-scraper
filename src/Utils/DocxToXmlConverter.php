<?php

namespace Utils;

use PhpOffice\PhpWord\IOFactory;

class DocxToXmlConverter
{
    public function convert($docxFilePath, $outputDir)
    {
        // Load the .docx file
        $phpWord = IOFactory::load($docxFilePath);

        // Convert to XML
        $xmlContent = $phpWord->saveXML();

        // Define the output XML file path
        $xmlFilePath = $outputDir . '/' . basename($docxFilePath, '.docx') . '.xml';

        // Save the XML content to a file
        file_put_contents($xmlFilePath, $xmlContent);

        return $xmlFilePath;
    }
}

// Example usage
$converter = new DocxToXmlConverter();
$converter->convert('path/to/your/docx/file.docx', 'path/to/output/directory');
