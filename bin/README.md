This folder contains scripts for performing various tasks related to the MARTI dice server.

## Create and initialize the staging database

1. Login to `dice-staging.marti.triplea-game.org`.
1. Change to the directory where you have cloned the `triplea-game/dice-server` repo.
1. Create the staging database:
    ```bash
    $ ./bin/create_staging_db
    ```
1. Initialize the staging database schema:
    ```bash
    $ ./bin/migrate_db ./config/db 0 marti_staging marti_dice
    ```

## Deploy application

### Production

1. Login to `dice.marti.triplea-game.org`.
1. Create the deployment directory if necessary.
    ```bash
    $ sudo mkdir /usr/share/nginx/html/dice.tripleawarclub.org
    $ sudo chmod 2755 /usr/share/nginx/html/dice.tripleawarclub.org/
    $ sudo chown www-data:www-data /usr/share/nginx/html/dice.tripleawarclub.org/
    $ sudo -u www-data mkdir /usr/share/nginx/html/dice.tripleawarclub.org/public_html
    ```
1. Change to the directory where you have cloned the `triplea-game/dice-server` repo.
1. Deploy the application:
    ```bash
    $ sudo -u www-data ./bin/deploy ./src/ /usr/share/nginx/html/dice.tripleawarclub.org/public_html/
    ```
1. Smoke test the application as needed.
    * Load home page at https://dice.marti.triplea-game.org .
    * Use the TripleA client to send a test email.

### Staging

1. Login to `dice-staging.marti.triplea-game.org`.
1. Create the deployment directory if necessary.
    ```bash
    $ sudo mkdir /usr/share/nginx/html/dice-staging.tripleawarclub.org
    $ sudo chmod 2755 /usr/share/nginx/html/dice-staging.tripleawarclub.org/
    $ sudo chown www-data:www-data /usr/share/nginx/html/dice-staging.tripleawarclub.org/
    $ sudo -u www-data mkdir /usr/share/nginx/html/dice-staging.tripleawarclub.org/public_html
    ```
1. Change to the directory where you have cloned the `triplea-game/dice-server` repo.
1. Deploy the application:
    ```bash
    $ sudo -u www-data ./bin/deploy ./src/ /usr/share/nginx/html/dice-staging.tripleawarclub.org/public_html/
    ```
1. Smoke test the application as needed.
    * Load home page at https://dice-staging.marti.triplea-game.org .
    * Use the TripleA client to send a test email.
