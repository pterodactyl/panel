#!/bin/ash -e
cd /app

mkdir -p /var/log/panel/logs/ /var/log/supervisord/ /var/log/nginx/ /var/log/php7/ \
  && chmod 777 /var/log/panel/logs/ \
  && ln -s /app/storage/logs/ /var/log/panel/

# Check that user has mounted the /app/var directory
if [ ! -d /app/var ]; then
  echo "You must mount the /app/var directory to the container."
  exit 1
fi

# Check the .env file exists and make a blank one if needed
if [ ! -f /app/var/.env ]; then
  echo "Creating .env file."
  touch /app/var/.env
fi

# Replace .env in container with our external .env file
rm -f /app/.env
ln -s /app/var/.env /app/


# Use a subshell to avoid polluting the global environment
(
    # Load in any existing environment variables in the .env file
    source /app/.env

    # Check if APP_KEY is set
    if [ -z "$APP_KEY" ]; then
        echo "Generating APP_KEY"
        echo "APP_KEY=" >> /app/.env
        APP_ENVIRONMENT_ONLY=true php artisan key:generate
    fi

    # Check if HASHIDS_LENGTH is set
    if [ -z "$HASHIDS_LENGTH" ]; then
        echo "Defaulting HASHIDS_LENGTH to 8"
        echo "HASHIDS_LENGTH=8" >> /app/.env
    fi


    # Check if HASHID_SALT is set
    if [ -z "$HASHIDS_SALT" ]; then
        echo "Generating HASHIDS_SALT"
        HASHIDS_SALT=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 20 | head -n 1)
        echo "HASHIDS_SALT=$HASHIDS_SALT" >> /app/.env
    fi
)


echo "Checking if https is required."
if [ -f /etc/nginx/http.d/panel.conf ]; then
  echo "Using nginx config already in place."
  if [ $LE_EMAIL ]; then
    echo "Checking for cert update"
    certbot certonly -d $(echo $APP_URL | sed 's~http[s]*://~~g')  --standalone -m $LE_EMAIL --agree-tos -n
  else
    echo "No letsencrypt email is set"
  fi
else
  echo "Checking if letsencrypt email is set."
  if [ -z $LE_EMAIL ]; then
    echo "No letsencrypt email is set using http config."
    cp .github/docker/default.conf /etc/nginx/http.d/panel.conf
  else
    echo "writing ssl config"
    cp .github/docker/default_ssl.conf /etc/nginx/http.d/panel.conf
    echo "updating ssl config for domain"
    sed -i "s|<domain>|$(echo $APP_URL | sed 's~http[s]*://~~g')|g" /etc/nginx/http.d/panel.conf
    echo "generating certs"
    certbot certonly -d $(echo $APP_URL | sed 's~http[s]*://~~g')  --standalone -m $LE_EMAIL --agree-tos -n
  fi
  echo "Removing the default nginx config"
  rm -rf /etc/nginx/http.d/default.conf
fi

if [[ -z $DB_PORT ]]; then
  echo -e "DB_PORT not specified, defaulting to 3306"
  DB_PORT=3306
fi

## check for DB up before starting the panel
echo "Checking database status."
until nc -z -v -w30 $DB_HOST $DB_PORT
do
  echo "Waiting for database connection..."
  # wait for 1 seconds before check again
  sleep 1
done

## make sure the db is set up
echo -e "Migrating and Seeding D.B"
php artisan migrate --seed --force

## start cronjobs for the queue
echo -e "Starting cron jobs."
crond -L /var/log/crond -l 5

echo -e "Starting supervisord."
exec "$@"
