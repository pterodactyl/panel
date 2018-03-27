#!/bin/ash
set -e

ENV="/app/var/.env"

# Check if default command is used
if [ "$1" = "/sbin/tini" ]; then
    echo "] Configuring Pterodactyl Panel container"

    if [ -e $ENV ]; then
        echo "] .env file found in /app/var, linking on /app/.env"
        ln -s $ENV /app/.env
        source $ENV
    fi

    if [ -z $(echo "$APP_URL" | sed "/http:\/\//d") ]; then
        echo "] HTTPS is disabled (It's easy to enable!)"
        sed -i "s,<domain>,$APP_URL,g" /etc/caddy/caddy.conf
        sed -i "s,<email>,off,g" /etc/caddy/caddy.conf
    elif [ -z $(echo "$APP_URL" | sed "/https:\/\//d") ]; then
        echo "] HTTPS is enabled"
        sed -i "s,<domain>,$APP_URL,g" /etc/caddy/caddy.conf
        HOSTNAME=$(echo "$APP_URL" | awk -F/ '{print $3}')
        echo "Setup with hostname : $HOSTNAME"
        if [ ! -f /etc/letsencrypt/live/$HOSTNAME/fullchain.pem ] || [ ! -f /etc/letsencrypt/live/$HOSTNAME/privkey.pem ]; then
            echo "] Obtaining certificates for eligible sites from Let's Encrypt"
            sed -i "s,<email>,$LETSENCRYPT_EMAIL,g" /etc/caddy/caddy.conf
        else
            echo "] Using provided certificate and key for tls"
            sed -i "s,<email>,/etc/letsencrypt/live/$HOSTNAME/fullchain.pem /etc/letsencrypt/live/$HOSTNAME/privkey.pem,g" /etc/caddy/caddy.conf
        fi
    else
        echo "] Your APP_URL is missing a protocol."
        exit 1
    fi

    echo "] Waiting for mysql to be ready ($DB_HOST:$DB_PORT)"
    until nc -z -w30 $DB_HOST $DB_PORT; do
        sleep 1
    done
    echo "] Mysql seems to be ready"

    if [ ! -e .env ] || [ ! -s .env ]; then #Didn't find the .env file

        echo "] No .env file found in /app/var. Generating according to environment variables."
        if [ ! -e $ENV ]; then
            mkdir -p var
            cp /app/.env.example $ENV
            chmod 775 $ENV
        else
            echo "] Failed to create $ENV. This should never happen."
        fi
        ln -s $ENV /app/.env

        echo "]   Generating application key"
        php artisan key:generate -n --force

        echo "]   Setting up mysql database connection"
        php artisan p:environment:database -n -q \
            --host="$DB_HOST" --port="$DB_PORT" --database="$DB_DATABASE" --username="$DB_USERNAME" --password="$DB_PASSWORD"

        if [ -z "$CACHE_DRIVER" ]; then #If cache driver not set, set default to file !
            CACHE_DRIVER="file"
        fi

        if [ -z "$SESSION_DRIVER" ]; then #If session driver not set, set default to file !
            SESSION_DRIVER="file"
        fi

        if [ -z "$QUEUE_DRIVER" ]; then #If queue driver not set, set default to database !
            QUEUE_DRIVER="database"
        fi

        if [ "$CACHE_DRIVER" = redis ]; then
            echo "] Setting up redis driver for cache, session and queue"
            if [ -z $REDIS_PORT ]; then #If redis port not set, SET IT !!!!
                REDIS_PORT=6379
            fi

            echo "]     Waiting for redis to be ready ($REDIS_HOST:$REDIS_PORT)"
            until nc -z -w30 $REDIS_HOST $REDIS_PORT; do
                sleep 1
            done
            echo "]     Redis seems to be ready"

            if [ ! -z "$REDIS_PASSWORD" ] && [ ! "$REDIS_PASSWORD" = "" ] && [ ! "$REDIS_PASSWORD" = "null" ]; then #Special case for redis password present
                echo "]     Using Redis caching with password !"
                php artisan p:environment:setup -n -q \
                    --cache="$CACHE_DRIVER" --session="$SESSION_DRIVER" --queue="$QUEUE_DRIVER" \
                    --redis-host="$REDIS_HOST" --redis-port="$REDIS_PORT" --redis-pass="$REDIS_PASS" \
                    --url="$APP_URL" --timezone="$APP_TIMEZONE" --disable-settings-ui
            else
                echo "]     Using Redis caching without password, hope it's secured !"
                php artisan p:environment:setup -n -q \
                    --cache="$CACHE_DRIVER" --session="$SESSION_DRIVER" --queue="$QUEUE_DRIVER" \
                    --redis-host="$REDIS_HOST" --redis-port="$REDIS_PORT" \
                    --url="$APP_URL" --timezone="$APP_TIMEZONE" --disable-settings-ui
            fi
        else
            echo "] Using $CACHE_DRIVER as cache driver, $SESSION_DRIVER as session driver and $QUEUE_DRIVER as queue driver."
            php artisan p:environment:setup -n -q \
                --cache="$CACHE_DRIVER" --session="$SESSION_DRIVER" --queue="$QUEUE_DRIVER" \
                --url="$APP_URL" --timezone="$APP_TIMEZONE" --disable-settings-ui
        fi

        echo "] Setting up email configuration"
        case "$MAIL_DRIVER" in
            mail)
                echo "]     PHP Mail was chosen"
                php artisan p:environment:mail -n --driver=mail --email="$MAIL_FROM" --from="$MAIL_FROM_NAME"
            ;;
            mandrill)
                echo "]     Mandrill was chosen"
                php artisan p:environment:mail -n --driver=mandrill --email="$MAIL_FROM" --from="$MAIL_FROM_NAME" --username="$MAIL_USERNAME"
            ;;
            postmark)
                echo "]     Postmark was chosen"
                php artisan p:environment:mail -n --driver=postmark --email="$MAIL_FROM" --from="$MAIL_FROM_NAME" --username="$MAIL_USERNAME"
            ;;
            mailgun)
                echo "]     Mailgun was chosen"
                php artisan p:environment:mail -n --driver=mailgun --email="$MAIL_FROM" --from="$MAIL_FROM_NAME" --username="$MAIL_USERNAME" --host="$MAIL_HOST"
            ;;
            smtp)
                echo "]     SMTP was chosen"
                php artisan p:environment:mail -n --driver=smtp --email="$MAIL_FROM" --from="$MAIL_FROM_NAME" --username="$MAIL_USERNAME" --password="$MAIL_PASSWORD" --host="$MAIL_HOST" --port="$MAIL_PORT"
            ;;
            *)
                echo "]     '$MAIL_DRIVER' is not a valid MAIL_DRIVER option."
                exit 1
        esac

        echo "]   Migrating Database"
        php artisan migrate -n --force

        echo "]   Seeding Database"
        php artisan db:seed -n --force

    fi

    # create caddy log dir (error was thrown)
    mkdir -p /var/log/caddy/

    echo "] Configuration is done."
fi

exec "$@"
