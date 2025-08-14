SHELL = /bin/bash
### https://makefiletutorial.com/

docker-server := docker run -it --rm -p 8000:8000 -v $(PWD):/app -v $(PWD)/.composer:/root/.composer -w /app php:8.0-cli-alpine
docker := docker run -it --rm -v $(PWD):/app -v $(PWD)/.composer:/root/.composer -w /app php:8.0-cli-alpine
composer := $(docker) php composer.phar

init:
	@sh -c "if [ ! -f \"composer.phar\" ]; then curl -O https://getcomposer.org/download/2.8.10/composer.phar && chmod a+x composer.phar; fi"
	@sh -c "if [ ! -f \"vendor/autoload.php\" ]; then $(composer) u $(name) --no-plugins; fi"

bash:
	$(docker) sh

composer-u: init
	$(composer) u $(name) --no-plugins

cs-fix: init
	$(composer) cs-fix

cs-check: init
	$(composer) cs-check

test-ui: init
	@echo
	@echo "Start webserver on http://localhost:8000"
	@echo
	$(docker-server) php -S 0.0.0.0:8000 /app/example/index.php
