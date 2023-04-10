<?php

namespace NectaResultScraper;

require_once 'vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

class NectaResultScraper
{
    public static function result($index_number)
    {
        // Create an object of the class
        $result = new NectaResultScraper();

        // Call the scrape method and return the result
        return $result->scrape($index_number);
    }

    public function scrape($index_number)
    {
        // Implementation of scrape method
        try {
            // Initialize variables for student gender, division, points,and subjects.
            $gender = "";
            $division = "";
            $points = "";
            $subjects = "";
            // Split the index number string into an array using '/' as the delimiter.
            $substrings = explode('/', $index_number);
            // Exam year
            $year = $substrings[2];
            // School number
            $school_number = $substrings[0];

            $school_number = strtolower($school_number);
            // Student number
            $student_number = $substrings[1];
            // Examination number
            $examination_number = $school_number . "/" . $student_number;
            /**
             *  NECTA may hosts student results in different domains depending on the examination year. 
             * In order to appropriately handle this variation, the code checks the examination year of the student
             *  and chooses the relevant domain to crawl
             */

            // If student graduated in 2021
            if (intval($year) == 2021) {
                $url = "https://onlinesys.necta.go.tz/results/2021/csee/results/" . $school_number . ".htm";
            }
            // If student graduated in 2022
            elseif (intval($year) == 2022) {
                $url = "https://matokeo.necta.go.tz/csee2022/results/" . $school_number . ".htm";
            }
            // If student graduated after 2014
            elseif (intval($year) > 2014) {
                $url = "https://onlinesys.necta.go.tz/results/" . $year . "/csee/results/" . $school_number . ".htm";
            }

            // If none of the previous conditions are met
            else {
                $url = "https://onlinesys.necta.go.tz/results/" . $year . "/csee/" . $school_number . ".htm";
            }

            // This line sets the `$index` variable based on the value of `$year`.
            // If `$year` is greater than 2018, `$index` is set to 2. Otherwise, `$index` is set to 0.
            // This is done using a ternary operator.
            // The `$index` variable is used later in the code.
            // The purpose of the `$index` variable is not clear from this code snippet alone.
            $index = ($year > 2018) ? 2 : 0;
            // Assuming the student has not been found yet.
            $found = false;
            // Create a new instance of the HttpBrowser with a timeout of 30 seconds.
            $browser = new HttpBrowser(HttpClient::create(['timeout' => 30]));

            // Use the HTTP browser to retrieve a crawler object for the specified URL
            $crawler = $browser->request('GET', $url);

            // Filter the resulting HTML content to extract tables based on an index value
            $tables = $crawler->filter("table")->eq($index);

            // Use a callback function to process each row in the filtered tables
            $tables->filter('tr')->each(function ($tr) use ($examination_number, $url, &$found, &$gender, &$division, &$points, &$subjects) {

                // Extract the data from each row and store it in an array
                $row = array();
                $tr->filter('td')->each(function ($td) use (&$row) {
                    $row[] = trim($td->text());
                });

                // Check if the row contains the examination number we are looking for
                if (strtolower($row[0]) == $examination_number) {
                    // Store the relevant information in variables
                    $gender = $row[1];
                    $division = $row[3];
                    $points = $row[2];
                    $subjects = $row[4];
                    $found = true;
                }
            });

            // If the examination number was not found in the table, return an error message and HTTP status code
            if (!$found) {
                return json_encode([
                    'error' => 'Student is not found',
                    'status_code' => 404
                ]);
            }

            // If the examination number was found, return the relevant student result in JSON format
            return json_encode([
                'gender' => $gender,
                'division' => $division,
                'points' => $points,
                'subjects' => $subjects,
                'source' => $url
            ]);
        }
        /**
         * // Catch any throwable errors that might occur during the execution of the try block 
         * and return a JSON-encoded error message with a status code of 500.
         */
        catch (Throwable $th) {
            return json_encode([
                'error' => $th->getMessage(),
                'status_code' => 500
            ]);
        }
    }
}
