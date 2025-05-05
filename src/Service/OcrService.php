<?php

namespace App\Service;

use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    private string $tesseractPath;
    private string $tessdataDir;

    public function __construct(
        string $tesseractPath = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
        string $tessdataDir = 'C:\\Program Files\\Tesseract-OCR\\tessdata'
    ) {
        $this->tesseractPath = $tesseractPath;
        $this->tessdataDir = $tessdataDir;
    }

    public function extractText(string $imagePath): ?string
    {
        try {
            return (new TesseractOCR($imagePath))
                ->executable($this->tesseractPath)
                ->tessdataDir($this->tessdataDir)
                ->lang('fra') // change to 'eng' if needed
                ->config('preserve_interword_spaces', '1')
                ->run();
        } catch (\Exception $e) {
            // Log or return null if Tesseract fails
            return null;
        }
    }
}
