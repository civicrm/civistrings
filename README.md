## civistrings

civistrings is the string extractor for CiviCRM core and CiviCRM extensions.
It scans PHP, Smarty, JS, and partial HTML files for references to the ts()
function -- and generates a list of strings using gettext's POT file format.

## Requirements

* PHP 7.4+
* Composer (http://getcomposer.org)

### Download: Single Executable (PHAR)

`civistrings` is distributed in PHAR format, which is a portable executable file (for PHP). It should run on most Unix-like systems where PHP 5.3+ is installed.

Simply download [`civistrings`](https://download.civicrm.org/civistrings/civistrings.phar) and put it somewhere in the PATH, eg

```bash
sudo curl -LsS https://download.civicrm.org/civistrings/civistrings.phar -o /usr/local/bin/civistrings
sudo chmod +x /usr/local/bin/civistrings
```

To upgrade an existing installation, re-download the latest `civistrings.phar`.

### Download: Git + Composer

To download the source tree and all dependencies, use [`git`](https://git-scm.com) and [`composer`](https://getcomposer.org/), e.g.

```
git clone git://github.com/civicrm/civistrings.git
cd civistrings
composer install
```

The main executable is `bin/civistrings`. You may execute that file directly, 
or add the `bin/` folder to `PATH`, e.g. 

```
export PATH=/home/myuser/civistrings/bin:$PATH
```

## Usage

```bash
## Scan all recognizable files under "myfolder/"
civistrings -o myfile.pot myfolder

## Scan all *.js files
find -name '*.js' | civistrings - -o myfile.pot
```

## Development

For a full set of development and testing activities, you will need:

* [`git`](https://git-scm.com)
* [`composer`](https://getcomposer.org/)
* [`box`](http://box-project.github.io/box2/)
* [`phpunit`](https://phpunit.de/)

> __TIP__: If you use `nix-shell`, it will provide `php`, `git`, `composer`,
> `box`, `phpunit8`, and `phpunit9`.

### Testing

Tests are based on a series of example files (e.g. `./examples/ex1.php` and
the corresponding `./examples/ex1.pot`).

To run the tests, simply call your favorite instance of `phpunit`.

### Build

To build a new copy of `civistrings.phar` from source, install [`git`](https://git-scm.com), [`composer`](https://getcomposer.org/), and
[`box`](http://box-project.github.io/box2/) and run:

```
git clone git://github.com/civicrm/civistrings.git
cd civistrings
composer install
php -dphar.readonly=0 `which box` build
```
