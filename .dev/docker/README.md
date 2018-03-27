# Pterodactyl Panel - Docker Image
This is a ready to use docker image for the panel.

## Requirements
This docker image requires some additional software to function. The software can either be provided in other containers (see the [docker-compose.yml](docker-compose.yml) as an example) or as existing instances.

A mysql database is required. We recommend [this](quay.io/parkervcp/mariadb-alpine) image if you prefer to run it in a docker container. As a non-containerized option we recommend mariadb.

A caching software is required as well. You can choose any of the [supported options](#cache-drivers).

You can provide additional settings using a custom `.env` file or by setting the appropriate environment variables.

## Build
execute the following command in project root
```docker build -t <repo>/<image>:<tag> .```

## Setup

Start the docker container and the required dependencies (either provide existing ones or start containers as well, see the [docker-compose.yml](docker-compose.yml) file as an example).

```
docker run \
-p [Ports](#ports) \
-e [Environment Variables](#environment-variables) \
-v [Volumes](#volumes) \
-ti <repo>/<image>:<tag>
```

First start command with config saving (just a basic example)
```
docker run \
-p 80:80 \
-e APP_URL=http://mydomain.com \
-e DB_HOST=mydomain.com \
-e DB_PORT=3306 \
-e DB_DATABASE=ptero \
-e DB_USERNAME=root \
-e DB_PASSWORD=root \
-e MAIL_DRIVER=mail \
-e APP_TIMEZONE=Europe/Helsinki \
-v /etc/pterodactyl/:/app/var/ \
-d <repo>/<image>:<tag>
```

For next start, you can remove environment variables if you have saved config
```
docker run \
-p 80:80 \
-v /etc/pterodactyl/:/app/var/ \
-d <repo>/<image>:<tag>
```

After the startup is complete you'll need to create a user.
If you are running the docker container without docker-compose, use:
```
docker exec -it <container id> php artisan p:user:make
```
If you are using docker compose use
```
docker-compose exec panel php artisan p:user:make
```

## Ports
If you want to use https for your panel, you have two options :
 - you want to autogenerate certificates : you need to bind both ports 80 and 443 (`-p 80:80 -p 443:443`)
 - you already have certificates : you need to bind ports 443 (`-p 443:443`)
If you prefer using unsecured http, you just bind 80 (`-p 80:80`)

## Volumes
If you want to use data stored on disk, such as domain certificates or save the .env outside of the container
 - /etc/letsencrypt/, provided certificates repositories (`-v /etc/letsencrypt/:/etc/letsencrypt/`)
 - /app/var/, panel configuration (`-v /etc/pterodactyl/:/app/var/`)

## Environment Variables
There are multiple environment variables to configure the panel when not providing your own `.env` file, see the following table for details on each available option.

Note: If your `APP_URL` starts with `https://` you need to provide an `LETSENCRYPT_EMAIL` as well so Certificates can be generated.

| Variable            | Description                                                                    | Required |
| ------------------- | ------------------------------------------------------------------------------ | -------- |
| `APP_URL`           | The URL the panel will be reachable with (including protocol)                  | yes      |
| `LETSENCRYPT_EMAIL` | The email used for automatic letsencrypt certificate generation(only for https)| no       |
| `DB_HOST`           | The host of the mysql instance                                                 | yes      |
| `DB_PORT`           | The port of the mysql instance                                                 | yes      |
| `DB_DATABASE`       | The name of the mysql database                                                 | yes      |
| `DB_USERNAME`       | The mysql user                                                                 | yes      |
| `DB_PASSWORD`       | The mysql password for the specified user                                      | yes      |
| `CACHE_DRIVER`      | The cache driver (see [Cache drivers](#cache-drivers) for detais)              | no       |
| `SESSION_DRIVER`    | The session driver (see [Session drivers](#session-drivers) for detais)        | no       |
| `QUEUE_DRIVER`      | The queue driver (see [Queue drivers](#queue-drivers) for detais)              | no       |
| `MAIL_DRIVER`       | The email driver (see [Mail drivers](#mail-drivers) for details)               | yes      |
| `APP_TIMEZONE`      | The timezone to use for the panel                                              | yes      |


### Cache drivers
You can choose between different cache drivers depending on what you prefer.
We recommend redis when using docker as it can be started in a container easily.

| Driver     | Description          | Required variables                                                      |
| ---------- | -------------------- | ----------------------------------------------------------------------- |
| redis      | Redis (recommended)  | `REDIS_HOST`, (optional)`REDIS_PORT`, (optional)`REDIS_PASS`            |
| memcached  | Memcached            |                                                                         |
| file       | Filesystem (default) |                                                                         |

### session drivers
You can choose between different cache drivers depending on what you prefer.
We recommend redis when using docker as it can be started in a container easily.

| Driver     | Description          | Required variables                                                      |
| ---------- | -------------------- | ----------------------------------------------------------------------- |
| redis      | Redis (recommended)  | `REDIS_HOST`, (optional)`REDIS_PORT`, (optional)`REDIS_PASS`            |
| memcached  | Memcached            |                                                                         |
| database   | MySQL Database       |                                                                         |
| file       | Filesystem (default) |                                                                         |
| cookie     | Cookie               |                                                                         |

### Queue drivers
You can choose between different cache drivers depending on what you prefer.
We recommend redis when using docker as it can be started in a container easily.

| Driver     | Description              | Required variables                                                      |
| ---------- | ------------------------ | ----------------------------------------------------------------------- |
| redis      | Redis (recommended)      | `REDIS_HOST`, (optional)`REDIS_PORT`, (optional)`REDIS_PASS`            |
| database   | MySQL Database (default) |                                                                         |
| sync       | Sync                     |                                                                         |

### Mail drivers
You can choose between different mail drivers according to your needs.
Every driver requires `MAIL_FROM` to be set.

| Driver   | Description                          | Required variables                                            |
| -------- | ------------------------------------ | ------------------------------------------------------------- |
| mail     | uses the installed php mail          |                                                               |
| mandrill | [Mandrill](http://www.mandrill.com/) | `MAIL_USERNAME`                                               |
| postmark | [Postmark](https://postmarkapp.com/) | `MAIL_USERNAME`                                               |
| mailgun  | [Mailgun](https://www.mailgun.com/)  | `MAIL_USERNAME`, `MAIL_HOST`                                  |
| smtp     | Any SMTP server can be configured    | `MAIL_USERNAME`, `MAIL_HOST`, `MAIL_PASSWORD`, `MAIL_PORT`    |
