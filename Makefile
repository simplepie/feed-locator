##
# DO NOT EDIT THIS FILE MANUALLY!
#
# Instead, update the template file and re-run the appropriate dev-unify task.
##

#-------------------------------------------------------------------------------
# Global variables.

PHP_LAST=7.2
PHP_CURR=7.3

PHP_LAST_EXT_DATE=20170718
PHP_CURR_EXT_DATE=20180731

BUILD_DOCKER=docker build --compress --force-rm --squash
BUILD_COMPOSE=docker-compose build --pull --compress --parallel

COMPOSE_72=tests-72 coverage-72
COMPOSE_73=tests-73 coverage-73

TEST_QUICK=tests-72 tests-73
TEST_COVER=coverage-72 coverage-73

IMAGES_72=simplepieng/base:$(PHP_LAST) simplepieng/test-coverage:$(PHP_LAST) simplepieng/test-runner:$(PHP_LAST)
IMAGES_73=simplepieng/base:$(PHP_CURR) simplepieng/test-coverage:$(PHP_CURR) simplepieng/test-runner:$(PHP_CURR)

#-------------------------------------------------------------------------------
# Running `make` will show the list of subcommands that will run.

all:
	@cat Makefile | grep "^[a-z]" | sed 's/://' | awk '{print $$1}'

#-------------------------------------------------------------------------------
# Running tests

.PHONY: test
test:
	bin/phpunit --testsuite all

.PHONY: test-quick
test-quick:
	docker-compose up $(TEST_QUICK)

.PHONY: test-coverage
test-coverage:
	docker-compose up $(TEST_COVER)

.PHONY: test-benchmark
test-benchmark:
	docker-compose up $(TEST_BENCH)

#-------------------------------------------------------------------------------
# PHP build process stuff

.PHONY: install-composer
install-composer:
	- SUDO="" && [[ $$UID -ne 0 ]] && SUDO="sudo"; \
	curl -sSL https://raw.githubusercontent.com/composer/getcomposer.org/master/web/installer \
	    | $$SUDO $$(which php) -- --install-dir=/usr/local/bin --filename=composer

.PHONY: install
install:
	composer self-update
	composer install -oa

.PHONY: dump
dump:
	composer dump-autoload -oa

.PHONY: install-hooks
install-hooks:
	printf '#!/usr/bin/env bash\nmake lint\nmake test' > .git/hooks/pre-commit
	chmod +x .git/hooks/pre-commit

#-------------------------------------------------------------------------------
# Documentation tasks

.PHONY: docs
docs:
	# composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader --ignore-platform-reqs
	# git reset --hard HEAD
	sami update --force docs/sami-config.php

.PHONY: push
push:
	rm -Rf /tmp/gh-pages
	git clone git@github.com:simplepie/simplepie-ng.git --branch gh-pages --single-branch /tmp/gh-pages
	rm -Rf /tmp/gh-pages/*
	cp -Rf ./docs/_build/* /tmp/gh-pages/
	cp -Rf ./docs/redirect.tmpl /tmp/gh-pages/index.html
	touch /tmp/gh-pages/.nojekyll
	find /tmp/gh-pages -type d | xargs chmod -f 0755
	find /tmp/gh-pages -type f | xargs chmod -f 0644
	cd /tmp/gh-pages/ && git add . && git commit -a -m "Automated commit on $$(date)" && git push origin gh-pages

.PHONY: push-travis
push-travis:
	git clone https://github.com/simplepie/simplepie-ng.git --branch gh-pages --single-branch /tmp/gh-pages
	rm -Rf /tmp/gh-pages/*
	cp -Rf ./docs/_build/* /tmp/gh-pages/
	cp -Rf ./docs/redirect.tmpl /tmp/gh-pages/index.html
	touch /tmp/gh-pages/.nojekyll
	find /tmp/gh-pages -type d | xargs chmod -f 0755
	find /tmp/gh-pages -type f | xargs chmod -f 0644
	cd /tmp/gh-pages/ && \
		git add . && \
		git remote add upstream "https://$$GH_TOKEN@github.com/simplepie/simplepie-ng.git" && \
		git commit -a -m "Automated commit on $$(date)" && git push upstream gh-pages

#-------------------------------------------------------------------------------
# Linting and Static Analysis

.PHONY: mkdir
mkdir:
	@ mkdir -p reports

.PHONY: phpcsfixer
phpcsfixer:
	@ echo " "
	@ echo "=====> Running PHP CS Fixer..."
	- bin/php-cs-fixer fix -vvv

.PHONY: phpcs
phpcs:
	@ echo " "
	@ echo "=====> Running PHP Code Sniffer..."
	- bin/phpcs --report-xml=reports/phpcs-src.xml -p --colors --encoding=utf-8 $$(find src/ -type f -name "*.php" | sort | uniq)
	- bin/phpcs --report-xml=reports/phpcs-tests.xml -p --colors --encoding=utf-8 $$(find tests/ -type f -name "*.php" | sort | uniq)
	- bin/phpcbf --encoding=utf-8 --tab-width=4 src/ 1>/dev/null
	- bin/phpcbf --encoding=utf-8 --tab-width=4 tests/ 1>/dev/null
	@ echo " "
	@ echo "---------------------------------------------------------------------------------------"
	@ echo " "
	@ php tools/reporter.php

.PHONY: lint
lint: mkdir phpcsfixer phpcs

.PHONY: xdebug
xdebug:
	bin/phpunit --dump-xdebug-filter tests/phpunit-xdebug-coverage.php

.PHONY: phpcpd
phpcpd:
	@ echo " "
	@ echo "=====> Running PHP Copy-Paste Detector..."
	- bin/phpcpd --names=*.php --log-pmd=$$(pwd)/reports/copy-paste.xml --fuzzy src/

.PHONY: phploc
phploc:
	@ echo " "
	@ echo "=====> Running PHP Lines-of-Code..."
	- bin/phploc --names=*.php --log-xml=$$(pwd)/reports/phploc-src.xml src/ > $$(pwd)/reports/phploc-src.txt
	- bin/phploc --names=*.php --log-xml=$$(pwd)/reports/phploc-tests.xml tests/ > $$(pwd)/reports/phploc-tests.txt

.PHONY: phpca
phpca:
	@ echo " "
	@ echo "=====> Running PHP Code Analyzer..."
	- php bin/phpca src/ --no-progress | tee reports/phpca-src.txt
	- php bin/phpca tests/ --no-progress | tee reports/phpca-tests.txt

.PHONY: licenses
licenses:
	@ echo " "
	@ echo "=====> Running Open-Source License Check..."
	- composer licenses | grep -v BSD-.-Clause | grep -v MIT | grep -v Apache-2.0 | grep -v ISC | tee reports/licenses.txt

.PHONY: vulns
vulns:
	@ echo " "
	@ echo "=====> Comparing Composer dependencies against the PHP Security Advisories Database..."
	- curl -sSL -H "Accept: text/plain" https://security.symfony.com/check_lock -F lock=@composer.lock | tee reports/sensiolabs.txt

.PHONY: psalm
psalm:
	@ echo " "
	@ echo "=====> Running Psalm..."
	- bin/psalm --find-unused-code=always --generate-json-map=./reports/psalm.json --output-format=console --show-info=true --show-snippet=true --stats --threads=$$(nproc) --php-version=7.2

.PHONY: phan
phan:
	@ echo " "
	@ echo "=====> Running Phan..."
	- bin/phan --output-mode=text --color --progress-bar --processes=$$(nproc)

.PHONY: phpstan
phpstan:
	@ echo " "
	@ echo "=====> Running PHPStan..."
	- bin/phpstan analyse --configuration=phpstan.neon.dist --level=max --error-format=raw src/ tests/

.PHONY: analyze
analyze: lint test phpcpd phploc phpca licenses vulns psalm phan phpstan

#-------------------------------------------------------------------------------
# Git Tasks

.PHONY: tag
tag:
	@ if [ $$(git status -s -uall | wc -l) != 1 ]; then echo 'ERROR: Git workspace must be clean.'; exit 1; fi;

	@echo "This release will be tagged as: $$(cat ./VERSION)"
	@echo "This version should match your release. If it doesn't, re-run 'make version'."
	@echo "---------------------------------------------------------------------"
	@read -p "Press any key to continue, or press Control+C to cancel. " x;

	@echo " "
	@chag update $$(cat ./VERSION)
	@echo " "

	@echo "These are the contents of the CHANGELOG for this release. Are these correct?"
	@echo "---------------------------------------------------------------------"
	@chag contents
	@echo "---------------------------------------------------------------------"
	@echo "Are these release notes correct? If not, cancel and update CHANGELOG.md."
	@read -p "Press any key to continue, or press Control+C to cancel. " x;

	@echo " "

	git add .
	git commit -a -m "Preparing the $$(cat ./VERSION) release."
	chag tag --sign

.PHONY: version
version:
	@echo "Current version: $$(cat ./VERSION)"
	@read -p "Enter new version number: " nv; \
	printf "$$nv" > ./VERSION

