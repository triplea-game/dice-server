#
# Dockerfile for building a MARTI container that can be used for development/testing.
#
# This image MUST NOT be used for production!
#

FROM stenote/docker-lemp@sha256:9bf317e08b614f7a6097cf1b7a32a49377c872478f59a19d657d71e27e40af3c
MAINTAINER "tripleabuilderbot@gmail.com"

ENV \
  DEBIAN_FRONTEND=noninteractive \
  MARTI_DB_HOST=localhost \
  MARTI_DB_NAME=marti \
  MARTI_DB_PASSWORD= \
  MARTI_DB_USERNAME=root

RUN apt-get -y update \
  && apt-get -y --no-install-recommends install postfix mailutils libsasl2-2 libsasl2-modules \
  && postconf \
      smtp_sasl_auth_enable=yes \
      smtp_sasl_password_maps=hash:/etc/postfix/sasl/sasl_passwd \
      smtp_sasl_security_options=noanonymous \
      smtp_sasl_tls_security_options=noanonymous \
      smtp_use_tls=yes \
  && printf "fastcgi_param MARTI_DB_HOST $MARTI_DB_HOST;\n" >> /etc/nginx/fastcgi_params \
  && printf "fastcgi_param MARTI_DB_USERNAME $MARTI_DB_USERNAME;\n" >> /etc/nginx/fastcgi_params \
  && if [ -n "$MARTI_DB_PASSWORD" ]; then printf "fastcgi_param MARTI_DB_PASSWORD $MARTI_DB_PASSWORD;\n" >> /etc/nginx/fastcgi_params; fi \
  && printf "fastcgi_param MARTI_DB_NAME $MARTI_DB_NAME;" >> /etc/nginx/fastcgi_params

COPY ["./build/docker/marti-dev-old/files/root", "/"]
COPY ["./dice", "/var/www"]
