# LitCal Anniversary Calculator

Similarly to the [Liturgical Calendar Project](https://github.com/JohnRDOrazio/LiturgicalCalendar 'https://github.com/JohnRDOrazio/LiturgicalCalendar') and to the [BibleGet Project](https://github.com/BibleGet-I-O/endpoint 'https://github.com/BibleGet-I-O/endpoint'), this project is designed as an API. The API calculates data from a data file and produces a response in a data exchange format such as JSON.

Then any kind of data visualization frontend interface can be created to interact with the API and display the data. An example of this can be found at [opera-romana-pellegrinaggi/litcal-anniversari-frontend](https://github.com/opera-romana-pellegrinaggi/litcal-anniversari-frontend 'https://github.com/opera-romana-pellegrinaggi/litcal-anniversari-frontend').

The API takes the following parameters:

* **`YEAR`**: the year for which anniversaries will be calculated. For example, if Saint Ignatius was canonized in 1622, and the value `2022` is supplied to the **`YEAR`** parameter, then a `CENTENARY` anniversary of 400 years will be returned.
* **`LOCALE`**: the language in which strings should be translated / localized.

## Installation

### Composer *(recommended)*


In your project directory issue `composer require liturgical-calendar/anniversarycalculator`. The package will be installed to the `vendor` folder and the requirement added to your `composer.json`.

### Git

You can also clone the repository to your project folder (useful mostly for development of the API and contributing to the codebase):

```console
git clone https://github.com/Liturgical-Calendar/LitCalAnniversaryCalculator.git .
composer install
```

This will clone the repository into the current folder rather than to a folder with the same name as the repository, and then install the package for inclusion in your scripts.

A sample `index.php` script is included.

## Usage

If you installed the package via git, you should already have a sample `index.php` file.

If you installed the package via composer, create a script similar to the following:

```php
// index.php
<?php

use LiturgicalCalendar\AnniversaryCalculator;

require_once 'vendor/autoload.php';

$calculator = new AnniversaryCalculator();
$calculator->init();
```

Then to test the API locally simply launch using PHP's built-in server: `php -S localhost:8000`. You can choose to serve over a different port if you wish.

Now open **localhost:8000** in your browser. You should see a JSON response. You can change the year and the locale using the `YEAR` and `LOCALE` query parameters.

> [!NOTE]
> In order to obtain localized results, you must have the correct locales installed.
>
> Run `locale -a` and check to see if <kbd>de_DE.utf8</kbd>, <kbd>es_ES.utf8</kbd>, <kbd>fr_FR.utf8</kbd>, <kbd>it_IT.utf8</kbd>, <kbd>nl_NL.utf8</kbd>, <kbd>pt_PT.utf8</kbd> are among the results.
>
> For any locales that are not installed, you will not get translation results for certain strings.
>
> In order to install a locale on Ubuntu, run `sudo apt-get install language-pack-{two-letter-iso-code}`. For example `sudo apt-get install language-pack-es` will install the Spanish locale on your machine. You will then be able to get translation results for Spanish.

## Translation into other languages

<a href="https://translate.johnromanodorazio.com/engage/liturgical-calendar/">
<img src="https://translate.johnromanodorazio.com/widgets/liturgical-calendar/-/liturgical-anniversary-calculator-data/open-graph.png" alt="Translation status" />
</a>
