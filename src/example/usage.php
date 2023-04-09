<?php
require_once('vendor/autoload.php');

use NectaResultScraper\NectaResultScraper;

// Call the static result method of the NectaResultScraper class with the index number string as the argument.
$result = NectaResultScraper::result('S1187/0142/2022');

// Output the result.
echo $result;
