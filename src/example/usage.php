<?php
// Require the NectaResultScraper.php file.
require_once('../NectaResultScraper.php');

// Import the NectaResultScraper class.
use NectaResultScraper\NectaResultScraper;

// Call the static result method of the NectaResultScraper class with the index number string as the argument.
$result = NectaResultScraper::result('S1187/0142/2022');

// Output the result.
echo $result;
