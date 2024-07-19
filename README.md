# LitCal Anniversary Calculator

Similarly to the [Liturgical Calendar Project](https://github.com/JohnRDOrazio/LiturgicalCalendar 'https://github.com/JohnRDOrazio/LiturgicalCalendar') and to the [BibleGet Project](https://github.com/BibleGet-I-O/endpoint 'https://github.com/BibleGet-I-O/endpoint'), this project is designed as an API. The API calculates data from a data file and produces a response in a data exchange format such as JSON.

Then any kind of data visualization frontend interface can be created to interact with the API and display the data. An example of this can be found at [opera-romana-pellegrinaggi/litcal-anniversari-frontend](https://github.com/opera-romana-pellegrinaggi/litcal-anniversari-frontend 'https://github.com/opera-romana-pellegrinaggi/litcal-anniversari-frontend').

The API takes the following parameters:

* **`YEAR`**: the year for which anniversaries will be calculated. For example, if Saint Ignatius was canonized in 1622, and the value `2022` is supplied to the **`YEAR`** parameter, then a `CENTENARY` anniversary of 400 years will be returned.
* **`LOCALE`**: the language in which strings should be translated / localized.

# Development

To test the API locally, simply launch using PHP's built-in server: `php -S localhost:8000`. You can choose to serve over a different port if you wish.

# Locales

In order to obtain localized results in development, you must have the correct locales installed on your local machine.

Run `locale -a` and check to see if <kbd>de_DE.utf8</kbd>, <kbd>es_ES.utf8</kbd>, <kbd>fr_FR.utf8</kbd>, <kbd>it_IT.utf8</kbd>, <kbd>nl_NL.utf8</kbd>, <kbd>pt_PT.utf8</kbd> are among the results.

For any locales that are not installed, you will not get translation results.

In order to install a locale on Ubuntu, run `sudo apt-get install language-pack-{two-letter-iso-code}`. For example `sudo apt-get install language-pack-es` will install the Spanish locale on your machine. You will then be able to get translation results for Spanish.

# Translation into other languages

<a href="https://translate.johnromanodorazio.com/engage/liturgical-calendar/">
<img src="https://translate.johnromanodorazio.com/widgets/liturgical-calendar/-/liturgical-anniversary-calculator-data/open-graph.png" alt="Translation status" />
</a>
