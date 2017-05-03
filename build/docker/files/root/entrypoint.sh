#!/bin/bash

set -e

### Verify container started with all required configuration

if [ -z "$MARTI_SMTP_HOST" ]; then
  echo >&2 '[ERROR]: specify the MARTI_SMTP_HOST environment variable (e.g. "smtp.gmail.com")'
  exit 1
fi
if [ -z "$MARTI_SMTP_PORT" ]; then
  echo >&2 '[ERROR]: specify the MARTI_SMTP_PORT environment variable (e.g. "587")'
  exit 1
fi
if [ -z "$MARTI_SMTP_USERNAME" ]; then
  echo >&2 '[ERROR]: specify the MARTI_SMTP_USERNAME environment variable (e.g. "first.last@gmail.com")'
  exit 1
fi
if [ -z "$MARTI_SMTP_PASSWORD" ]; then
  echo >&2 '[ERROR]: specify the MARTI_SMTP_PASSWORD environment variable (e.g. "my_password")'
  exit 1
fi

### Initialize web server

chown -R www-data:www-data /var/www /var/log/php

### Initialize database

if [ ! -d /var/lib/mysql/mysql ]; then
  mysqld --initialize-insecure --user=root --datadir=/var/lib/mysql
fi

chown -R mysql:mysql /var/lib/mysql

if [ ! -d "/var/lib/mysql/$MARTI_DB_NAME" ]; then
  service mysql start
  mysqladmin create "$MARTI_DB_NAME"
  mysql marti < /docker-entrypoint-initdb.d/marti-init.sql
  service mysql stop
fi

### Initialize mail relay

postconf relayhost="[$MARTI_SMTP_HOST]:$MARTI_SMTP_PORT"
printf "$MARTI_SMTP_HOST $MARTI_SMTP_USERNAME:$MARTI_SMTP_PASSWORD\n" > /etc/postfix/sasl/sasl_passwd
postmap /etc/postfix/sasl/sasl_passwd
rm /etc/postfix/sasl/sasl_passwd
chown -R root:postfix /etc/postfix/sasl
chmod 750 /etc/postfix/sasl
chmod 640 /etc/postfix/sasl/sasl_passwd*

### Start container services

exec /usr/bin/supervisord --nodaemon -c /etc/supervisor/supervisord.conf
