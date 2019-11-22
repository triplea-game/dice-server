This folder contains the Nginx configurations used by the MARTI production and staging environments.

## Update Nginx configuration

### Production

The following process is used to update the Nginx configuration for the `dice.marti.triplea-game.org` subdomain:

1. Submit a PR to this repo with the proposed change to _config/nginx/dice.tripleawarclub.org_.
1. Review and merge the PR.
1. Login to `dice.marti.triplea-game.org`.
1. Change to the directory where you have cloned the `triplea-game/dice-server` repo and checkout the appropriate tag/branch.
1. Review the changes to be applied to ensure the deployed configuration has not been modified outside of this process.
    ```bash
    $ git diff /etc/nginx/sites-available/dice.tripleawarclub.org ./config/nginx/dice.tripleawarclub.org
    ```
1. Copy the new configuration.
    ```bash
    $ sudo cp ./config/nginx/dice.tripleawarclub.org /etc/nginx/sites-available/
    ```
1. Enable the configuration if needed.
    ```bash
    $ sudo ln -s /etc/nginx/sites-available/dice.tripleawarclub.org /etc/nginx/sites-enabled/dice.tripleawarclub.org
    ```
1. Reload the Nginx configuration.
    ```bash
    $ sudo systemctl reload nginx
    ```
1. Smoke test the new configuration as needed.

### Staging

The following process is used to update the Nginx configuration for the `dice-staging.marti.triplea-game.org` subdomain:

1. Submit a PR to this repo with the proposed change to _config/nginx/dice-staging.tripleawarclub.org_.
1. Review and merge the PR.
1. Login to `dice-staging.marti.triplea-game.org`.
1. Change to the directory where you have cloned the `triplea-game/dice-server` repo and checkout the appropriate tag/branch.
1. Review the changes to be applied to ensure the deployed configuration has not been modified outside of this process.
    ```bash
    $ git diff /etc/nginx/sites-available/dice-staging.tripleawarclub.org ./config/nginx/dice-staging.tripleawarclub.org
    ```
1. Copy the new configuration.
    ```bash
    $ sudo cp ./config/nginx/dice-staging.tripleawarclub.org /etc/nginx/sites-available/
    ```
1. Enable the configuration if needed.
    ```bash
    $ sudo ln -s /etc/nginx/sites-available/dice-staging.tripleawarclub.org /etc/nginx/sites-enabled/dice-staging.tripleawarclub.org
    ```
1. Reload the Nginx configuration.
    ```bash
    $ sudo systemctl reload nginx
    ```
1. Smoke test the new configuration as needed.
