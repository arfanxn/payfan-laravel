# Payfan (Laravel/Backend)

Payfan is Paypal Clone application built with Laravel 8, Mysql, Pusher, and RabbitMQ 

## Installation

Payfan application require RabbitMQ to store message queues, install the RabbitMQ with docker-compose  
```sh
docker-compose -f=docker-compose-rabbitmq.yaml up -d 
```

Install all composer dependencies  
```sh
composer install --ignore-platform-reqs
```

Create environtment file from example.env file
```sh
cp example.env .env 
```

Generate JWT secret key / hash key 
```sh
php artisan key:generate
```

Link storage
```sh
php artisan storage:link
```

Migrate Payfan required tables and seed data to tables
```sh
php artisan migrate 
php artisan db:seed
```

## Docker

You can also deploy to docker container (Dockerizing/Containerizing)

By default, the Docker will expose port 8000, so change this within the
Dockerfile if necessary. When ready, simply use the Dockerfile to
build the image.

```sh
docker-compose build 
docker-compose docker-compose-rabbitmq.yaml down
docker-compose docker-compose-deploy.yaml up -d 
```

Verify the deployment by navigating to your server address in
your preferred browser.

```sh
127.0.0.1:8000
```

## License
MIT && PAYFAN

**Open Source**
