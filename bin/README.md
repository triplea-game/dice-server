This folder contains scripts for performing various tasks related to the MARTI dice server.

## Create and initialize the staging database

1. Login to `dice.tripleawarclub.org`.
1. Change to the directory where you have cloned the `triplea-game/dice-server` repo.
1. Create the staging database:
    ```bash
    $ ./bin/create_staging_db
    ```
1. Initialize the staging database schema:
    ```bash
    $ ./bin/migrate_db ./config/db 0 marti_staging marti_dice
    ```
