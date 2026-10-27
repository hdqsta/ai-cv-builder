<?php

namespace App\Services;

use Exception;
use DOMDocument;
use ZipArchive;
use Smalot\PdfParser\Parser as PdfParser;

class TextExtractor
{
    /**
     * Extract raw text from a PDF or DOCX file.
     *
     * @param string $filePath
     * @param string $extension
     * @return string
     * @throws Exception
     */
    public function extract(string $filePath, string $extension): string
    {
        $extension = strtolower($extension);

        if ($extension === 'pdf') {
            return $this->extractPdf($filePath);
        } elseif ($extension === 'docx') {
            return $this->extractDocx($filePath);
        }

        throw new Exception("Unsupported file extension: .{$extension}");
    }

    /**
     * Extract text from a PDF file using Smalot PDF Parser.
     *
     * @param string $filePath
     * @return string
     */
    protected function extractPdf(string $filePath): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (Exception $e) {
            // Fallback or log error
            return '';
        }
    }

    /**
     * Extract text from a DOCX file natively by opening its XML.
     *
     * @param string $filePath
     * @return string
     */
    protected function extractDocx(string $filePath): string
    {
        $text = '';
        $zip = new ZipArchive();

        if ($zip->open($filePath) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $xmlContent = $zip->getFromIndex($index);
                $zip->close();

                $dom = new DOMDocument();
                // Prevent errors on parsing standard word XML
                if (@$dom->loadXML($xmlContent, LIBXML_NOENT | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                    $paragraphs = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p');
                    foreach ($paragraphs as $p) {
                        $pText = '';
                        $wts = $p->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't');
                        foreach ($wts as $wt) {
                            $pText .= $wt->nodeValue;
                        }
                        if (trim($pText) !== '') {
                            $text .= $pText . "\n";
                        }
                    }
                }
            } else {
                $zip->close();
            }
        }

        return $text;
    }
}
