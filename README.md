# Test Cases

## Service for send notifications about expiring subscriptions

## Requirements

- php
- postgresql
- liquibase


## Configs

```shell
$ cp .env.sample .env
// and edit .env (fill actual DB credentials)

$ cp liquibase.properties.sample liquibase.properties
// and edit liquibase.properties (fill actual credentials)
```

## Production/Staging database preparation and migrations

NOTE: do not use angle brackets in entity names, substitute by the actual names instead!

1. Create a liquibase user under which the migrations will be applied:
   ```sql
   CREATE USER <karma8_liquibase_user> CREATEROLE ENCRYPTED PASSWORD 'aPassword';
   ```
   NOTE: be sure you have the same `<karma8_liquibase_user>` and `aPassword` in the `liquibase.properties`.
1. Create a database for the service (owner must be the liquibase user):
   ```sql
   CREATE DATABASE <db_name> OWNER <karma8_liquibase_user>;
   ```
1. Now you can run `liquibase` to migrate initial schema (be sure you are in the project's root and the `liquibase.properties` file is placed here):

   ```shell
   $ liquibase update
   ```

   NOTE: you might have to add the `classpath: /path/to/the/postgresql-jdbc-driver.jar` into the `liquibase.properties` file
   unless your liquibase installation contains the postgresql JDBC driver
   (you also can place the driver jar file manually into the `lib` directory of the liquibase installation).

   NOTE: in the case of remote database you have to put the correct jdbc connection string into your `liquibase.properties` like this:

   ```ini
   url: jdbc:postgresql://<host>[:<port>]/<db_name>?ssl=true&prepareThreshold=0
   ```

   See more options here: https://jdbc.postgresql.org/documentation/head/connect.html

## What's next?

Do not forget to create an user under which the service will operate, and grant the `karma8_owner` role to the service user:

```sql
CREATE USER <service_user> ENCRYPTED PASSWORD 'aPassword';
GRANT karma8_owner TO <service_user>;
```

## Run scripts

### Start producer for jobs (add new tasks to send email queue or check email queue)

```shell
$ php index.php
```

### Start send notifications job

```shell
$ php send.php
```

### Start check emails job

```shell
$ php check.php
```

## Run scripts as daemons

To run script as daemon you can use http://supervisord.org/

Just install supervisor service and create a file at `/etc/supervisor/conf.d/karma8-program.ini` with text like this:

```shell
[program:karma8-sender]
user = www-data
command = /usr/local/bin/php send.php
directory = /var/www/karma8-test
process_name=%(program_name)s_%(process_num)02d
numprocs = 4
autorestart = true
autostart = true
stdout_logfile = /var/log/karma8-sender.log
stderr_logfile = /var/log/karma8-sender-error.log
stopwaitsecs = 90

[program:karma8-checker]
user = www-data
command = /usr/local/bin/php check.php
directory = /var/www/karma8-test
process_name=%(program_name)s_%(process_num)02d
numprocs = 4
autorestart = true
autostart = true
stdout_logfile = /var/log/karma8-checker.log
stderr_logfile = /var/log/karma8-checker-error.log
stopwaitsecs = 90
```
