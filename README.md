## civistrings

civistrings is the string extractor for CiviCRM core and CiviCRM extensions. 
It scans PHP, Smarty, JS, and partial HTML files for references to the ts()
function -- and generates a list of strings using gettext's POT file format.

## Usage

```bash
## Scan all recognizable files under "myfolder/"
civistrings -o myfile.pot myfolder

## Scan all *.js files
find -name '*.js' | civistrings - -o myfile.pot
```

## Development and Testing

The "examples" folder includes a series of example input files and expected
output files.  To see if the examples are correctly processed, simply run
"phpunit".

If you need to add new examples or change the behavior of the test, update
tests/Command/ExtractCommandTest.php.
