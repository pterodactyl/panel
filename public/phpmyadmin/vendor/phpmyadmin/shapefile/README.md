# shapefile
ShapeFile library for PHP

![Test-suite](https://github.com/phpmyadmin/shapefile/workflows/Run%20tests/badge.svg?branch=master)
[![codecov.io](https://codecov.io/github/phpmyadmin/shapefile/coverage.svg?branch=master)](https://codecov.io/github/phpmyadmin/shapefile?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phpmyadmin/shapefile/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phpmyadmin/shapefile/?branch=master)
[![Packagist](https://img.shields.io/packagist/dt/phpmyadmin/shapefile.svg)](https://packagist.org/packages/phpmyadmin/shapefile)

## Features

Currently the 2D and 3D variants except MultiPatch of the ShapeFile format as
defined in https://www.esri.com/library/whitepapers/pdfs/shapefile.pdf. The
library currently supports reading and editing of ShapeFiles and the Associated
information (DBF file). There are a lot of things that can be improved in the
code, if you are interested in developing, helping with the documentation,
making translations or offering new ideas please contact us.

## Installation

Please use [Composer][1] to install:

```sh
composer require phpmyadmin/shapefile
```

To be able to read and write the associated DBF file, you need ``dbase``
extension:

```sh
pecl install dbase
echo "extension=dbase.so" > /etc/php7/conf.d/dbase.ini
```

## Documentation

The API documentation is available at
<https://develdocs.phpmyadmin.net/shapefile/>.

## Usage

To read shape file:

```php
$shp = new \PhpMyAdmin\ShapeFile\ShapeFile(0);
$shp->loadFromFile('path/file.*');
```

## History

This library is based on BytesFall ShapeFiles library written by Ovidio (ovidio
AT users.sourceforge.net). The library has been embedded in phpMyAdmin for
years and slowly developed there. At one point people started to use our
version rather than the original library and that was the point we decided to
make it separate package.

[1]:https://getcomposer.org/

