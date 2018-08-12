all:
	docker-compose run --rm php composer install -o

test:
	docker-compose run --rm php composer test

coverage:
	docker-compose run --rm php composer coverage

cs:
	docker-compose run --rm php composer cs

phan:
	docker-compose run --rm php composer phan

qa:
	docker-compose run --rm php composer qa
