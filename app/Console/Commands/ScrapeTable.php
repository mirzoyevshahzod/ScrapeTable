<?php

namespace App\Console\Commands;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Illuminate\Console\Command;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ScrapeTable extends Command
{
    protected $signature = 'scrape:table';
    protected $description = 'Scrape dynamic table data using Selenium and export to Excel';

    public function handle()
    {
        $this->info('Starting Selenium-based scraping...');

        // Selenium server URL
        $serverUrl = 'http://localhost:4444/wd/hub'; // Adjust if needed

        // Configure headless Chrome
        $options = new ChromeOptions();
        $options->addArguments(['--headless', '--disable-gpu', '--window-size=1920,1080']);
        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);

        $driver = RemoteWebDriver::create($serverUrl, $capabilities);
        $data = []; // To store all scraped data

        try {
            // Navigate to the target website
            $url = 'https://tarif.customs.uz/spravochnik/viewDatatable.jsp?lang=uz_UZ';
            $driver->get($url);
            $pageCount = 0; // Initialize page counter

            // Wait for the table to load
            $driver->wait(20)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(
                    WebDriverBy::cssSelector('table#tiftn tbody tr')
                )
            );

            do {
                $this->info("Scraping page " . ($pageCount + 1) . "...");

                // Scrape table rows
                $rows = $driver->findElements(WebDriverBy::cssSelector('table#tiftn tbody tr'));
                foreach ($rows as $row) {
                    $cells = $row->findElements(WebDriverBy::cssSelector('td'));
                    $rowData = [];
                    foreach ($cells as $cell) {
                        $rowData[] = $cell->getText(); // Extract cell text
                    }
                    $data[] = $rowData;
                }

                // Check for the "Next" button
                $nextButton = $driver->findElements(WebDriverBy::cssSelector('a#tiftn_next'));
                if (count($nextButton) > 0 && $nextButton[0]->isDisplayed() && $pageCount < 1291) { // Stop after 1291 pages
                    $this->info('Moving to the next page...');
                    $nextButton[0]->click(); // Click the "Next" button
                    sleep(2); // Wait for the next page to load
                    $pageCount++;
                } else {
                    $this->info('No more pages or reached the last page.');
                    break;
                }
            } while (true);

            // Export scraped data to an Excel file
            $this->exportToExcel($data);

            $this->info('Scraping completed successfully and data exported to Excel.');
        } catch (\Exception $e) {
            $this->error('An error occurred during scraping: ' . $e->getMessage());
        } finally {
            // Quit the WebDriver
            $driver->quit();
        }
    }

    /**
     * Export data to an Excel file.
     *
     * @param array $data
     */
    private function exportToExcel(array $data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write data to the spreadsheet
        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $cellValue) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $cellAddress = $columnLetter . ($rowIndex + 1);
                $sheet->setCellValue($cellAddress, $cellValue);
            }
        }

        // Save the file
        $filePath = storage_path('app/scraped_dynamic_table.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $this->info("Data saved to: $filePath");
    }
}
