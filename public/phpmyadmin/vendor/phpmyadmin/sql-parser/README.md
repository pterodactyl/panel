# SQL Parser

A validating SQL lexer and parser with a focus on MySQL dialect.

## Code status

![Tests](https://github.com/phpmyadmin/sql-parser/workflows/Run%20tests/badge.svg?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/phpmyadmin/sql-parser/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/phpmyadmin/sql-parser/?branch=master)
[![codecov.io](https://codecov.io/github/phpmyadmin/sql-parser/coverage.svg?branch=master)](https://codecov.io/github/phpmyadmin/sql-parser?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phpmyadmin/sql-parser/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phpmyadmin/sql-parser/?branch=master)
[![Translation status](https://hosted.weblate.org/widgets/phpmyadmin/-/svg-badge.svg)](https://hosted.weblate.org/engage/phpmyadmin/?utm_source=widget)
[![Packagist](https://img.shields.io/packagist/dt/phpmyadmin/sql-parser.svg)](https://packagist.org/packages/phpmyadmin/sql-parser)
[![Open Source Helpers](https://www.codetriage.com/phpmyadmin/sql-parser/badges/users.svg)](https://www.codetriage.com/phpmyadmin/sql-parser)
[![Type coverage](https://shepherd.dev/github/phpmyadmin/sql-parser/coverage.svg)](https://shepherd.dev/github/phpmyadmin/sql-parser)
[![Infection MSI](https://badge.stryker-mutator.io/github.com/phpmyadmin/sql-parser/master)](https://infection.github.io)

## Installation

Please use [Composer][1] to install:

```sh
composer require phpmyadmin/sql-parser
```

## Documentation

The API documentation is available at
<https://develdocs.phpmyadmin.net/sql-parser/>.

## Usage

### Command line utilities

Command line utility to syntax highlight SQL query:

```sh
./vendor/bin/highlight-query --query "SELECT 1"
```

Command line utility to lint SQL query:

```sh
./vendor/bin/lint-query --query "SELECT 1"
```

Command line utility to tokenize SQL query:

```sh
./vendor/bin/tokenize-query --query "SELECT 1"
```

All commands are able to parse input from stdin (standard in), such as:

```sh
echo "SELECT 1" | ./vendor/bin/highlight-query
cat example.sql | ./vendor/bin/lint-query
```

### Formatting SQL query

```php
echo PhpMyAdmin\SqlParser\Utils\Formatter::format($query, ['type' => 'html']);
```

### Discoverying query type

```php
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Utils\Query;

$query = 'OPTIMIZE TABLE tbl';
$parser = new Parser($query);
$flags = Query::getFlags($parser->statements[0]);

echo $flags['querytype'];
```

### Parsing and building SQL query

```php
require __DIR__ . '/vendor/autoload.php';

$query1 = 'select * from a';
$parser = new PhpMyAdmin\SqlParser\Parser($query1);

// inspect query
var_dump($parser->statements[0]); // outputs object(PhpMyAdmin\SqlParser\Statements\SelectStatement)

// modify query by replacing table a with table b
$table2 = new \PhpMyAdmin\SqlParser\Components\Expression('', 'b', '', '');
$parser->statements[0]->from[0] = $table2;

// build query again from an array of object(PhpMyAdmin\SqlParser\Statements\SelectStatement) to a string
$statement = $parser->statements[0];
$query2 = $statement->build();
var_dump($query2); // outputs string(19) 'SELECT  * FROM `b` '

// Change SQL mode
PhpMyAdmin\SqlParser\Context::setMode(PhpMyAdmin\SqlParser\Context::SQL_MODE_ANSI_QUOTES);

// build the query again using different quotes
$query2 = $statement->build();
var_dump($query2); // outputs string(19) 'SELECT  * FROM "b" '
```

## Localization

You can localize error messages installing `phpmyadmin/motranslator` version `5.0` or newer:

```sh
composer require phpmyadmin/motranslator:^5.0
```

The locale is automatically detected from your environment, you can also set a different locale

**From cli**:

```sh
LC_ALL=pl ./vendor/bin/lint-query --query "SELECT 1"
```

**From php**:

```php
require __DIR__ . '/vendor/autoload.php';

$GLOBALS['lang'] = 'pl';

$query1 = 'select * from a';
$parser = new PhpMyAdmin\SqlParser\Parser($query1);
```

## More information

This library was originally created during the Google Summer of Code 2015 and has been used by phpMyAdmin since version 4.5.

[1]:https://getcomposer.org/
