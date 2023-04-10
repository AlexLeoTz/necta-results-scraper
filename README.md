# Necta Results Scraper

This package provides a PHP script that allows you to scrape students' results from the National Examinations Council of Tanzania (NECTA) website.

## Installation

You can install this package using Composer:

```composer require alexleotz/necta-results-scraper```

## Usage

To use this package, simply call the static result method of the NectaResultScraper class with the student form four index number string as the argument:

```<?php
require_once('vendor/autoload.php');

use NectaResultScraper\NectaResultScraper;

// Call the static result method of the NectaResultScraper class with the index number string as the argument.
$result = NectaResultScraper::result('S1187/0142/2022');

// Output the result.
echo $result;
```

## Output

The output is student's result in json format

```{
"gender": "M",
"division": "I",
"points": "15",
"subjects": {
"CIV": "'C'",
"HIST": "'C'",
"GEO": "'C'",
"B/KNOWL": "'C'",
"KISW": "'C'",
"ENGL": "'B'",
"PHY": "'C'",
"CHEM": "'B'",
"BIO": "'A'",
"B/MATH": "'A'"
}
"source":"https:\/\/matokeo.necta.go.tz\/csee2022\/results\/s1187.htm"
}
```

## Error

If the student is not found, a JSON response with a status code of 404 will be returned. For any other error, a code of 500 will be returned.

## Contributing

If you’d like to contribute to this project, please fork the repository and use a feature branch. Pull requests are welcome!

## License

This package is released under the MIT license. See LICENSE for details.
