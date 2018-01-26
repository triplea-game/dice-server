This folder provides a Docker Compose file for building containers that can be used for MARTI development/testing.  Separate containers are used for Nginx, PHP, and MySQL.

### Build

Build the containers using the following command (run from the root of your Git repository):

```
$ docker-compose -f build/docker/marti-dev/docker-compose.yml build
```

### Run

Start the containers using the following command (run from the root of your Git repository):

```
$ MARTI_SMTP_HOST=<smtp_host> \
  MARTI_SMTP_PORT=<smtp_port> \
  MARTI_SMTP_USERNAME=<smtp_username> \
  MARTI_SMTP_PASSWORD=<smtp_password> \
  docker-compose -f build/docker/marti-dev/docker-compose.yml up
```

where the `smtp_*` variables are specific to your ISP or email provider.  **The container currently only supports SMTP over TLS.**  Therefore, if your ISP or email provider supports multiple SMTP connection options (e.g. TLS, SSL, or raw TCP), you can only use the TLS option.

Note that the `up` command will also build the images if this is the first run.

Once the container is started, navigate to `http://localhost:4000` from the host to bring up the MARTI home page.

### Development

If you wish to edit the MARTI source code live while the containers are running, uncomment the commented-out lines for the `src-volume` volume in _docker-compose.yml_.  You must update the `device` driver option to be the absolute path to the _src/_ folder in your MARTI Git repository.

#### Linux hosts running SELinux or AppArmor

When binding your host development folder to the `src-volume` volume, the ownership and permissions of the _src/_ folder will be changed to allow access by the containers.  To restore the original ownership, run the following command from the root of your Git repository:

```
$ sudo chown -R <user>:<group> src
```

where _<user>_ is your username and _<group>_ is your primary group.

To restore the original context labels, run the following command from the root of your Git repository:

```
$ sudo chcon -R --reference=README.md src
```

This will restore the context labels for all files under and including the _src/_ folder to their original values (any sibling entry can be used as the reference; _README.md_ was picked arbitrarily).
