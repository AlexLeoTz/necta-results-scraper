<?php

namespace NectaResultScraper;

require_once 'vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

class NectaResultScraper
{
    public static function results($index_number, $level = 'csee')
    {
        $level = strtolower($level);
        // Check if the provided level is valid (csee or acsee)
        if (!in_array($level, ['csee', 'acsee'])) {
            return json_encode([
                'error' => 'Invalid level. Only "csee" and "acsee" are supported.',
            ]);
        }
        // Create an object of the class
        $result = new NectaResultScraper();
        // Call the scrape method and return the result
        return $result->scrape($index_number, $level);
    }

    public function scrape($index_number, $level)
    {
        // Implementation of scrape method

        try {
            // Check if the index number is not valid
            if (!$this->is_index_number_valid($index_number)) {
                return json_encode([
                    'error' => 'Invalid index number',
                ]);
            } else {
                if (strpos($index_number, '.') !== false) {
                    $substrings = explode('.', $index_number);
                } elseif (strpos($index_number, '/') !== false) {
                    $substrings = explode('/', $index_number);
                }
                // Exam year
                $year = $substrings[2];
                // School number
                $school_number = $substrings[0];
                // Convert school number to lowercase
                $school_number = strtolower($school_number);
                // Student number
                $student_number = $substrings[1];

                $url = $this->url($year, $school_number, $level);
                if ($year > 2018) {
                    $index = 2;
                } else {
                    $index = 0;
                }
                $found = false;
                $result = [];
                // Create a new instance of the HttpBrowser with a timeout of 30 seconds.
                $browser = new HttpBrowser(HttpClient::create(['timeout' => 30]));

                // Use the HTTP browser to retrieve a crawler object for the specified URL
                $crawler = $browser->request('GET', $url);

                $tables = $crawler->filter("table")->eq($index);

                $examination_number = $school_number . "/" . $student_number;

                $tables->filter('tr')->each(function ($tr) use ($examination_number, &$found, &$result, $url) {
                    $row = array();
                    $tr->filter('td')->each(function ($td) use (&$row) {
                        $row[] = trim($td->text());
                    });

                    if (strtolower($row[0]) == $examination_number) {
                        $found = true;
                        $gender = $row[1];
                        $division = $row[3];
                        $points = $row[2];
                        $subjects = $row[4];
                        preg_match_all("/([A-Z]+)\s-\s'([A-Z])'/", $subjects, $matches);
                        $subjects = $matches[1];
                        $grades = $matches[2];
                        $subjects_grades = array_combine($subjects, $grades);
                        // If the examination number was found, return the relevant student result in JSON format
                        $result = [
                            'gender' => $gender,
                            'division' => $division,
                            'points' => $points,
                            'subjects' => $subjects,
                            'subjects_grades' => $subjects_grades,
                            'source' => $url,
                        ];
                    }
                });
                // If the examination number was not found in the table, return an error message and HTTP status code
                if (!$found) {
                    return [
                        'error' => 'Result not found',
                        'status' => 404,
                        'source' => $url,
                        'examination_number' => $examination_number,
                    ];
                }
                return $result;
            }
        } catch (\Throwable $th) {
            return [
                'error' => $th->getMessage(),
            ];
        }
    }

    private function url($year, $school_number, $level)
    {
        // Determine the current year based on the level. For acsee, results are released in the same year.
        $currentYear = ($level == 'acsee') ? date("Y") : date("Y") - 1;
        // Generate the array of supported years from 2003 to the current year (or year before the current year for non-acsee levels)
        $supported_years = range(2003, $currentYear);
        if ($level == 'csee') {
            // For the years from 2003 up to 2015, results are located at this domain (https://maktaba.tetea.org)
            if (in_array($year, range(2003, 2015))) {
                $level = strtoupper($level);
                return "https://maktaba.tetea.org/exam-results/{$level}{$year}/${school_number}.htm";
            }
            // For the years from 2016 up to the current year (or year before the current year for non-acsee levels), results are located at this domain (https://onlinesys.necta.go.tz)
            if (in_array($year, range(2016, $currentYear - 1))) {
                return "https://onlinesys.necta.go.tz/results/{$year}/{$level}/results/{$school_number}.htm";
            }
            // For the current year (or year before the current year for non-acsee levels), results are initially located at this domain (https://matokeo.necta.go.tz),
            // and later moved back to onlinesys after 3 to 5 months
            if ($year == $currentYear) {
                return "https://matokeo.necta.go.tz/results/{$year}/{$level}/CSEE2023/results/{$school_number}.htm";
            }
        } else {
            // For the years from 2016 up to the current year (or year before the current year for non-acsee levels), results are located at this domain (https://onlinesys.necta.go.tz)
            if (in_array($year, range(2016, $currentYear - 1))) {
                return "https://onlinesys.necta.go.tz/results/{$year}/{$level}/results/{$school_number}.htm";
            }
            // For the current year (or year before the current year for non-acsee levels), results are initially located at this domain (https://matokeo.necta.go.tz),
            // and later moved back to onlinesys after 3 to 5 months
            if ($year == $currentYear) {
                return "https://matokeo.necta.go.tz/results/{$year}/{$level}/results/{$school_number}.htm";
            }
        }
    }


    private function acseeUrl($year, $school_number) {}


    private function is_index_number_valid($index_number)
    {
        # We use REGEX to verify if the index number follows one of the specified formats:
        # - S0596.0010.2022
        # - S0596/0010/2022
        $regex = '/^S\d{4}\.\d{4}\.\d{4}$|^S\d{4}\/\d{4}\/\d{4}$/';
        if (preg_match($regex, $index_number)) {
            return true;
        }
        return false;
    }
}
