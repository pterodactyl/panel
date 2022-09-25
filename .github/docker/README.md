# Pterodactyl Panel - Docker Image
This is a ready to use docker image for the panel.

## Requirements
This docker image requires some additional software to function. The software can either be provided in other containers (see the [docker-compose.yml](https://github.com/pterodactyl/panel/blob/develop/docker-compose.example.yml) as an example) or as existing instances.

A mysql database is required. We recommend the stock [MariaDB Image](https://hub.docker.com/_/mariadb/) image if you prefer to run it in a docker container. As a non-containerized option we recommend mariadb.

A caching software is required as well. We recommend the stock [Redis Image](https://hub.docker.com/_/redis/) image. You can choose any of the [supported options](#cache-drivers).

You can provide additional settings using a custom `.env` file or by setting the appropriate environment variables in the docker-compose file.

## Setup

Start the docker container and the required dependencies (either provide existing ones or start containers as well, see the [docker-compose.yml](https://github.com/pterodactyl/panel/blob/develop/docker-compose.example.yml) file as an example.

After the startup is complete you'll need to create a user.
If you are running the docker container without docker-compose, use:
```
docker exec -it <container id> php artisan p:user:make
```
If you are using docker compose use
```
docker-compose exec panel php artisan p:user:make
```

## Environment Variables
There are multiple environment variables to configure the panel when not providing your own `.env` file, see the following table for details on each available option.

Note: If your `APP_URL` starts with `https://` you need to provide an `LE_EMAIL` as well so Certificates can be generated.

| Variable            | Description                                                                    | Required |
| ------------------- | ------------------------------------------------------------------------------ | -------- |
| `APP_URL`           | The URL the panel will be reachable with (including protocol)                  | yes      |
| `APP_TIMEZONE`      | The timezone to use for the panel                                              | yes      |
| `LE_EMAIL`          | The email used for letsencrypt certificate generation                          | yes      |
| `DB_HOST`           | The host of the mysql instance                                                 | yes      |
| `DB_PORT`           | The port of the mysql instance                                                 | yes      |
| `DB_DATABASE`       | The name of the mysql database                                                 | yes      |
| `DB_USERNAME`       | The mysql user                                                                 | yes      |
| `DB_PASSWORD`       | The mysql password for the specified user                                      | yes      |
| `CACHE_DRIVER`      | The cache driver        (see [Cache drivers](#cache-drivers) for detais)       | yes      |
| `SESSION_DRIVER`    |                                                                                | yes      |
| `QUEUE_DRIVER`      |                                                                                | yes      |
| `REDIS_HOST`        | The hostname or IP address of the redis database                               | yes      |
| `REDIS_PASSWORD`    | The password used to secure the redis database                                 | maybe    |
| `REDIS_PORT`        | The port the redis database is using on the host                               | maybe    |
| `MAIL_DRIVER`       | The email driver (see [Mail drivers](#mail-drivers) for details)               | yes      |
| `MAIL_FROM`         | The email that should be used as the sender email                              | yes      |
| `MAIL_HOST`         | The host of your mail driver instance                                          | maybe    |
| `MAIL_PORT`         | The port of your mail driver instance                                          | maybe    |
| `MAIL_USERNAME`     | The username for your mail driver                                              | maybe    |
| `MAIL_PASSWORD`     | The password for your mail driver                                              | maybe    |


### Cache drivers
You can choose between different cache drivers depending on what you prefer.
We recommend redis when using docker as it can be started in a container easily.

| Driver   | Description                          | Required variables                                     |
| -------- | ------------------------------------ | ------------------------------------------------------ |
| redis    | host where redis is running          | `REDIS_HOST`                                           |
| redis    | port redis is running on             | `REDIS_PORT`                                           |
| redis    | redis database password              | `REDIS_PASSWORD`                                       |

### Mail drivers
You can choose between different mail drivers according to your needs.
Every driver requires `MAIL_FROM` to be set.

| Driver   | Description                          | Required variables                                            |
| -------- | ------------------------------------ | ------------------------------------------------------------- |
| mail     | uses the installed php mail          |                                                               |
| mandrill | [Mandrill](http://www.mandrill.com/) | `MAIL_USERNAME`                                               |
| postmark | [Postmark](https://postmarkapp.com/) | `MAIL_USERNAME`                                               |
| mailgun  | [Mailgun](https://www.mailgun.com/)  | `MAIL_USERNAME`, `MAIL_HOST`                                  |
| smtp     | Any SMTP server can be configured    | `MAIL_USERNAME`, `MAIL_HOST`, `MAIL_PASSWORD`, `MAIL_PORT`    |
