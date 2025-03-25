<?php

namespace NectaResultScraper;

require_once 'vendor/autoload.php';


use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

/**
 * Necta Result Scraper
 *
 * @author Alex Leo <loealex175@gmail.com>
 * @contributor Issa Hakimu <issahakimuakida365@gmail.com>
 */
class NectaResultScraper
{
    public static function results(string $indexNumber)
    {
        $result = new NectaResultScraper();
        return $result->scrape($indexNumber);
    }

    public function scrape(string $indexNumber): array
    {
        try {
            if (!$this->isIndexNumberValid($indexNumber)) {
                return ['error' => 'Invalid index number'];
            }
            if(!$this->isValidYearInIndexNumber($indexNumber)){
                return ['error' => 'This package supports result scraping for years from 2015 to 2024'];
            } else {
                if (strpos($indexNumber, '.') !== false) {
                    $substrings = explode('.', $indexNumber);
                } elseif (strpos($indexNumber, '/') !== false) {
                    $substrings = explode('/', $indexNumber);
                }
                $year = $substrings[2];
                $schoolNumber = strtolower($substrings[0]);
                $studentNumber = $substrings[1];

                $url = $this->getUrl($year, $schoolNumber);
                $index = ($year > 2018) ? 2 : 0;
                $found = false;
                $result = [];
                $browser = new HttpBrowser(HttpClient::create(['timeout' => 30]));
                $crawler = $browser->request('GET', $url);

                $schoolName = $this->getSchoolName($crawler, $schoolNumber); 

                $tables = $crawler->filter("table")->eq($index);
                $examinationNumber = $schoolNumber . "/" . $studentNumber;


                $tables->filter('tr')->each(function ($tr) use ($examinationNumber, &$found, &$result, $url, $schoolName, $year) {
                    $row = [];
                    $tr->filter('td')->each(function ($td) use (&$row) {
                        $row[] = trim($td->text());
                    });

                    if (strtolower($row[0]) == $examinationNumber) {
                        $found = true;
                        $indexNumber = $row[0] . '/' . $year;
                        $gender = $row[1];
                        $division = $row[3];
                        $points = $row[2];
                        $subjects = $row[4];
                        preg_match_all("/([A-Z]+)\s-\s'([A-Z])'/", $subjects, $matches);
                        $subjects = $matches[1];
                        $grades = $matches[2];
                        $subjectsGrades = array_combine($subjects, $grades);
                        $result = [
                            'index_number' => $indexNumber,
                            'secondary_school' => $schoolName,
                            'gender' => $gender,
                            'division' => $division,
                            'points' => $points,
                            'subjects' => $subjects,
                            'subjects_grades' => $subjectsGrades,
                            'source' => $url,
                        ];
                    }
                });

                if (!$found) {
                    return [
                        'error' => 'Result not found',
                        'status' => 404,
                        'source' => $url,
                        'examination_number' => $indexNumber,
                    ];
                }
                return $result;
            }
        } catch (Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    private function getUrl(string $year, string $schoolNumber)
    {
        $supportedYears = ['2015', '2017', '2018', '2019', '2020', '2021', '2022', '2023'];
        if (in_array($year, $supportedYears)) {
            return "https://onlinesys.necta.go.tz/results/{$year}/csee/results/{$schoolNumber}.htm";
        }
        if ($year == 2024) {
            return "https://matokeo.necta.go.tz/results/{$year}/csee/CSEE2024/CSEE2024/results/{$schoolNumber}.htm";
        }
        if ($year == 2016) {
            return "https://onlinesys.necta.go.tz/results/2016/csee/results/{$schoolNumber}.htm";
        }
    }

    private function getSchoolName($crawler, $schoolNumber): string
    {
        $schoolNumberForName = strtoupper($schoolNumber);
    
        if ($crawler->filter('p')->eq(2)->count() === 0) {
            return '';
        }
    
        $schoolName = trim($crawler->filter('p')->eq(2)->text());
        $schoolName = str_ireplace($schoolNumberForName, '', $schoolName);
    
        return trim($schoolName);
    }
    


    private function isIndexNumberValid(string $indexNumber): bool
    {
        $pattern = "/^[QPS]\d{4}\/\d{4}\/\d{4}$/";
        return preg_match($pattern, $indexNumber) === 1;
    }

    private function isValidYearInIndexNumber(string $indexNumber) : bool {
        $pattern = "/^[QPS]\d{4}\/\d{4}\/(\d{4})$/";
        
        if (preg_match($pattern, $indexNumber, $matches)) {
            $year = (int) $matches[1];
            return $year >= 2015;
        }
        
        return false;
    }
}
