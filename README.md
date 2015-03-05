## civistrings

civistrings is the string extractor for CiviCRM core and CiviCRM extensions. 
It scans PHP, Smarty, JS, and partial HTML files for references to the ts()
function -- and generates a list of strings using gettext's POT file format.

## Requirements

* PHP 5.3+
* Composer (http://getcomposer.org)

## Installation

```bash
cd $HOME

# If you haven't already, install the PHP tool "composer"
curl -s http://getcomposer.org/installer | php

git clone git://github.com/civicrm/civistrings.git
cd civistrings
php $HOME/composer.phar install

# Add civistrings to the PATH; consider updating ~/.bashrc or ~/.profile
export PATH=$HOME/civistrings/bin:$PATH

# or symlink to a ~/bin directory (add to your $PATH if necessary)
mkdir ~/bin/
ln -s $HOME/civistrings/bin/civistrings ~/bin/civistrings
```

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
