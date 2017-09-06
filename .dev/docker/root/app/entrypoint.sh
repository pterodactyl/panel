#!/bin/ash
set -e

ENV="/app/var/.env"

# Check if default command is used
if [ "$1" = "/sbin/tini" ]; then
    echo "] Configuring Pterodactyl Panel container"

    if [ -z $(echo "$APP_URL" | sed "/http:\/\//d") ]; then
        echo "] HTTPS is disabled (It's easy to enable!)"
        sed -i "s,<domain>,$APP_URL,g" /etc/caddy/caddy.conf
        sed -i "s,<email>,off,g" /etc/caddy/caddy.conf
    elif [ -z $(echo "$APP_URL" | sed "/https:\/\//d") ]; then
        echo "] HTTPS is enabled"
        sed -i "s,<domain>,$APP_URL,g" /etc/caddy/caddy.conf
        sed -i "s,<email>,$LETSENCRYPT_EMAIL,g" /etc/caddy/caddy.conf
    else
        echo "] Your APP_URL is missing a protocol."
        exit 1
    fi

    echo "] Waiting for mysql to be ready"
    until nc -z -w30 $DB_HOST $DB_PORT; do
        sleep 1
    done
    echo "] Mysql seems to be ready"

    if [ ! -e .env ] || [ ! -s .env ]; then #Didn't find the .env file

        echo "] No .env file found in /app/var. Generating according to environment variables."
        if [ ! -e $ENV ]; then
            mkdir -p var
            cp /app/.env.example $ENV
        else
            echo "] Failed to create $ENV. This should never happen."
        fi
        ln -s $ENV /app/.env

        echo "]   Generating application key"
        php artisan key:generate --force

        echo "]   Setting up database connection"
        php artisan pterodactyl:env \
            --dbhost="$DB_HOST" --dbport="$DB_PORT" --dbname="$DB_DATABASE" --dbuser="$DB_USERNAME" --dbpass="$DB_PASSWORD" \
            --driver="$CACHE_DRIVER" --session-driver=database --queue-driver=database \
            --url="$APP_URL" --timezone="$APP_TIMEZONE"

        echo "]   Setting up email configuration"
        case "$MAIL_DRIVER" in
            mail)
                echo "]     PHP Mail was chosen"
                php artisan pterodactyl:mail --driver=mail --email="$MAIL_FROM" --from-name="$MAIL_FROM_NAME"
            ;;
            mandrill)
                echo "]     Mandrill was chosen"
                php artisan pterodactyl:mail --driver=mandrill --email="$MAIL_FROM" --from-name="$MAIL_FROM_NAME" --username="$MAIL_USERNAME"
            ;;
            postmark)
                echo "]     Postmark was chosen"
                php artisan pterodactyl:mail --driver=postmark --email="$MAIL_FROM" --from-name="$MAIL_FROM_NAME" --username="$MAIL_USERNAME"
            ;;
            mailgun)
                echo "]     Mailgun was chosen"
                php artisan pterodactyl:mail --driver=mailgun --email="$MAIL_FROM" --from-name="$MAIL_FROM_NAME" --username="$MAIL_USERNAME" --host="$MAIL_HOST"
            ;;
            smtp)
                echo "]     SMTP was chosen"
                php artisan pterodactyl:mail --driver=smtp --email="$MAIL_FROM" --from-name="$MAIL_FROM_NAME" --username="$MAIL_USERNAME" --password="$MAIL_PASSWORD" --host="$MAIL_HOST" --port="$MAIL_PORT"
            ;;
            *)
                echo "]     '$MAIL_DRIVER' is not a valid MAIL_DRIVER option."
                exit 1
        esac

        echo "]   Migrating Database"
        php artisan migrate --force

        echo "]   Seeding Database"
        php artisan db:seed --force

    else # Found an env file and testing for panel version
        echo "] Found .env file."
    fi

    echo "] Configuration is done."
fi

exec "$@"
