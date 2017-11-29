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
