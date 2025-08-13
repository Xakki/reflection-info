SHELL = /bin/bash
### https://makefiletutorial.com/

docker := docker run -it -v $(PWD):/app -v  $(PWD)/.composer:/root/.composer -w /app php:5.6-cli-alpine
composer := $(docker) php composer.phar

init:
	sh -c "if [ ! -f \"composer.phar\" ]; then curl -O https://getcomposer.org/download/1.10.17/composer.phar && chmod a+x composer.phar; fi"

bash:
	$(docker) sh

composer-i: init
	$(composer) i

composer-u: init
	$(composer) u $(name) --no-plugins

cs-fix: init
	$(composer) cs-fix

cs-check: init
	$(composer) cs-check
