# LitCal Anniversary Calculator

Similarly to the [Liturgical Calendar Project](https://github.com/JohnRDOrazio/LiturgicalCalendar 'https://github.com/JohnRDOrazio/LiturgicalCalendar') and to the [BibleGet Project](https://github.com/BibleGet-I-O/endpoint 'https://github.com/BibleGet-I-O/endpoint'), this project is designed as an API. The API translates data from the Database to a data exchange format such as JSON.

Then any kind of data visualization frontend interface can be created to interact with the API and display the data. An example of this can be found at [opera-romana-pellegrinaggi/litcal-anniversari-frontend](https://github.com/opera-romana-pellegrinaggi/litcal-anniversari-frontend 'https://github.com/opera-romana-pellegrinaggi/litcal-anniversari-frontend').

The API takes the following parameters:

* **`YEAR`**: the year for which anniversaries will be calculated. For example, if Saint Ignatius was canonized in 1622, and the value `2022` is supplied to the **`YEAR`** parameter, then a `CENTENARY` anniversary of 400 years will be returned.
