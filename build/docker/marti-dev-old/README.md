This folder provides a Dockerfile for building a MARTI container that can be used for development/testing.

### Build

Build the image using the following command (run from the root of your Git repository):

```
$ docker build --tag triplea/marti:latest -f build/docker/marti-dev-old/Dockerfile .
```

### Run

Start a new container using the following command:

```
$ docker run -d --name=marti -p 3000:80 \
  --env MARTI_SMTP_HOST=<smtp_host> \
  --env MARTI_SMTP_PORT=<smtp_port> \
  --env MARTI_SMTP_USERNAME=<smtp_username> \
  --env MARTI_SMTP_PASSWORD=<smtp_password> \
  triplea/marti
```

where the `smtp_*` variables are specific to your ISP or email provider.  **The container currently only supports SMTP over TLS.**  Therefore, if your ISP or email provider supports multiple SMTP connection options (e.g. TLS, SSL, or raw TCP), you can only use the TLS option.

Once the container is started, navigate to `http://localhost:3000` from the host to bring up the MARTI home page.

If you wish to edit the MARTI source code live while the container is running, mount a volume that maps the MARTI source code folder to `/var/www`:

```
-v <path/to/repo>/dice/:/var/www/
```

where `<path/to/repo>` is the path to the root of your Git repository.

Note that the container will change ownership of files mounted to this volume to `www-data:www-data` (UID 33, GID 33).  Therefore, you will have to change ownership back to your user/group on the host in order to edit the files (or create the `www-data` group on the host with the same GID and add your user to that group).  Regardless, you should restore original ownership once the container is stopped.

Users running SELinux on the host may have to append `:Z` to the `-v` flag value above for the context labels to be adjusted such that the container can access those files.  To restore the original labels, use the `chcon` command.  For example:

```
$ chcon -R --reference=README.md dice
```

will restore the context labels for all files under and including the `dice` folder to their original values (any sibling entry can be used as the reference; `README.md` was picked arbitrarily).

### Usage

The base Docker image does not support SSH.  Therefore, to use the container interactively (e.g. if you want to run the MySQL shell), you must execute a shell in the context of the container:

```
$ docker exec -it marti bash
```
