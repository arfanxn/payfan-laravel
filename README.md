# Payfan (Laravel/Backend)

Payfan is Paypal Clone application build with Laravel 8 framework, Mysql Database, and RabbitMQ message broker 

## Installation

this application requires RabbitMQ to run, install the RabbitMQ with docker-compose  
```sh
docker-compose up -d docker-compose-rabbitmq.yaml 
```

Create mysql required tables and seed data to tables
```sh
php artisan migrate 
php artisan db:seed
```

## Docker

Deploy to docker container (Dockerizing/Containerizing)

By default, the Docker will expose port 8000, so change this within the
Dockerfile if necessary. When ready, simply use the Dockerfile to
build the image.

```sh
docker-compose build 
docker-compose up -d docker-compose-deploy.yaml  
```

Verify the deployment by navigating to your server address in
your preferred browser.

```sh
127.0.0.1:8000
```

## License
MIT && PAYFAN

**Open Source**
