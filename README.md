# aiml-converter
[![Version](https://img.shields.io/packagist/v/patrickschur/aiml-converter.svg?style=flat-square)](https://packagist.org/packages/patrickschur/aiml-converter)
[![Maintenance](https://img.shields.io/maintenance/yes/2017.svg?style=flat-square)](https://github.com/patrickschur/aiml-converter)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.4-4AC51C.svg?style=flat-square)](http://php.net/)
[![License](https://img.shields.io/packagist/l/patrickschur/aiml-converter.svg?style=flat-square)](https://opensource.org/licenses/MIT)

Converts AIML to CSV and CSV to AIML.

## How to use
```bash
$ composer require patrickschur/aiml-converter
```

```php
require 'vendor/autoload.php';
 
$conv = new AIMLConverter;
 
$conv->aiml2csv('default.aiml'); // Creates a CSV file from an AIML file
// or
$conv->csv2aiml('default.csv'); // Creates a various amount of AIML files from a CSV file
```