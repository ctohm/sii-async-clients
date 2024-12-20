
SHELL = /usr/bin/env bash
APP_ENV = $(shell cat .env | grep 'APP_ENV' | head -1 | sed 's/APP_ENV=//')
COMPOSER_VERSION = $(shell cat composer.json | sed -n 's/.*"version": "\([^"]*\)"/\1/p')
NPM_VERSION = $(shell cat package.json | sed -n 's/.*"version": "\([^"]*\)",/\1/p')

XDSWI := $(shell command -v xd_swi 2> /dev/null)
XDSWI_STATUS:=$(shell command xd_swi stat 2> /dev/null)
HAS_PHIVE:=$(shell command phive --version 2> /dev/null)
CURRENT_BRANCH:=$(shell command git rev-parse --abbrev-ref HEAD 2> /dev/null)
DATENOW:=`date +'%Y-%m-%d'`
YELLOW=\033[0;33m
RED=\033[0;31m
WHITE=\033[0m
GREEN=\u001B[32m
BLUE=\033[34m
BRIGHT=\033[37m
CYAN=\033[36m

fix_folder:='.'

version:
	@echo "COMPOSER_VERSION " ${COMPOSER_VERSION} ;\
	echo "NPM_VERSION " ${NPM_VERSION} 

update_version:
	@echo "Current version is " ${COMPOSER_VERSION} ;\
	echo "Next version is " $(v) ;\
	sed -i s/"$(COMPOSER_VERSION)"/"$(v)"/g composer.json
	sed -i s/"$(NPM_VERSION)"/"$(v)"/g package.json
	composer update  --lock



disable_xdebug:
		@if [[ "$(XDSWI)" != "" ]]; then \
				xd_swi off ;\
		fi

enable_xdebug:
		@if [[ "$(XDSWI)" != "" ]]; then \
				xd_swi $(new_status) ;\
		fi

abort_suggesting_composer:
		@echo
		@if [ "0" != "$(XDSWI_STATUS)" ]; then \
				 $(YELLOW)Warn: $(GREEN)xdebug$(WHITE) is enabled. Just saying... ;\
		fi
		@if [ ! -f "$(executable)" ]; then \
				echo -e "    $(GREEN)$(package_name)$(WHITE) $(RED)NOT FOUND$(WHITE) on $(CYAN)$(executable)$(WHITE). " ;\
				echo -e "    Install it with $(GREEN)composer require --dev $(package_name)$(WHITE)" ;\
				echo ;\
				exit 1 ;\
		fi

install_phive:
		@wget -O phive.phar "https://phar.io/releases/phive.phar" ;\
		wget -O phive.phar.asc "https://phar.io/releases/phive.phar.asc" ;\
		gpg --keyserver hkps.pool.sks-keyservers.net --recv-keys 0x9D8A98B29B2D5D79 ;\
		gpg --verify phive.phar.asc phive.phar ;\
		rm phive.phar.asc ;\
		chmod +x phive.phar ;\
		sudo mv phive.phar /usr/local/bin/phive

check_executable_or_exit_with_phive:
		@if [[ "$(HAS_PHIVE)" == "" ]]; then \
				${MAKE} install_phive --no-print-directory  ;\
		fi
		
		@if [ ! -f "$(executable)" ]; then \
				echo -e "    $(GREEN)$(package_name)$(WHITE) $(RED)NOT FOUND$(WHITE) on $(CYAN)$(executable)$(WHITE). " ;\
				echo ;\
				echo -e " Install it with $(GREEN)phive install $(package_name)$(WHITE)" ;\
				echo __________;\
				exit 0 ;\
		fi
		@if [ "0" != "$(XDSWI_STATUS)" ]; then \
				 $(YELLOW)Warn: $(GREEN)xdebug$(WHITE) is enabled. Just saying... ;\
		fi

tag: all_checks update_version csfixer tag_and_push  

tag_and_push:
	@git commit -a -m "Creating Tag v$(v) at $(DATENOW) - $(m)" ;\
	git push ;\
	if [[ "$(CURRENT_BRANCH)" != "master" ]]; then \
		git checkout master ;\
		git merge $(CURRENT_BRANCH) ;\
	fi

	git tag v$(v) ;\
	git push ;\
	git push --tags ;\
	git checkout $(CURRENT_BRANCH)
 
lint:
	$(eval executable:=vendor/bin/parallel-lint )
	$(eval package_name:=php-parallel-lint/php-parallel-lint )
	@${MAKE} abort_suggesting_composer executable=$(executable) package_name=$(package_name) --no-print-directory
	mkdir -p .build/parallel ;\
	$(executable) --ignore-fails --exclude vendor  --exclude .build --exclude storage .

update_baselines:
		@${MAKE} disable_xdebug  --no-print-directory ;\
		find .build/phpstan -mtime +5 -type f -name "*.php" -exec rm -rf {} \;
		@vendor/bin/phpstan analyze --configuration phpstan.neon --generate-baseline ;\
		find .build/psalm -mtime +5 -type f   -exec rm -rf {} \;
		@vendor/bin/psalm --config=psalm.xml --update-baseline --ignore-baseline  --set-baseline=psalm-baseline.xml ;\
		${MAKE} enable_xdebug new_status=$(XDSWI_STATUS)  --no-print-directory

.PHONY:abort_suggesting_composer check_executable_or_exit_with_phive update_baselines

phpmd: package_name:=phpmd
phpmd: executable:= $(shell command -v phpmd 2> /dev/null)
phpmd:
		@${MAKE} check_executable_or_exit_with_phive  executable=$(executable) package_name=$(package_name) --no-print-directory
		@phpmd app,config,tests text .phpmd.xml --exclude=vendor/*,.build/*

phpmd_checkstyle:
		@${MAKE} phpmd   > temp/phpmd.report.json  ;\
		echo -e "$(GREEN)Finished PHPMD$(WHITE): waiting 1s"
		@sleep 1 ;\
		php tools/phpmd_checkstyle.php ;\
		echo -e "$(GREEN)Formatted PHPMD$(WHITE): as checkStyle"
		cat temp/phpmd.checkstyle.xml | vendor/bin/cs2pr

csfixer:
ifeq (,$(reportformat))
		$(eval reportformat='txt')
endif
		$(eval executable:=vendor/bin/php-cs-fixer)
		$(eval package_name:=friendsofphp/php-cs-fixer)
		@${MAKE} abort_suggesting_composer executable=$(executable) package_name=$(package_name) --no-print-directory
		@mkdir -p .build/phpcs && touch .build/phpcs/csfixer.cache ;\
		echo -e $(executable) ;\
		$(executable)   fix --config=.php_cs.php --cache-file=.build/phpcs/csfixer.cache --format=$(reportformat)   --diff


phpcs:
ifeq (,$(reportformat))
		$(eval reportformat='diff')
endif
		$(eval executable:=tools/phpcs)
		$(eval package_name:=phpcs)
		@${MAKE} check_executable_or_exit_with_phive  executable=$(executable) package_name=$(package_name) --no-print-directory
		@mkdir -p .build/phpcs && touch .build/phpcs/php-cs.cache ;\
		$(executable)  --standard=.phpcs.xml  --parallel=2 --cache=.build/phpcs/php-cs.cache --report=$(reportformat) src/* tests/*

phpcbf:
ifeq (,$(reportformat))
		$(eval reportformat='diff')
endif
		$(eval executable:=tools/phpcbf)
		$(eval package_name:=phpcbf)
		@${MAKE} check_executable_or_exit_with_phive  executable=$(executable) package_name=$(package_name) --no-print-directory
		@mkdir -p .build/phpcs && touch .build/phpcs/php-cs.cache ;\
		tools/phpcbf  --standard=.phpcs.xml  --parallel=2 --cache=.build/phpcs/php-cs.cache --report=$(reportformat) src/* tests/*


psalm:
	@yarn psalm

phpstan:
	@yarn phpstan	

all_checks: lint  phpcs  csfixer phpcbf composer_unused psalm phpstan 
fixers:  lint csfixer composer_unused psalm phpstan phpcs 


.PHONY: composer_unused
composer_unused: vendor ## Runs a dependency analysis with maglnet/composer-require-checker
		$(eval executable:=tools/composer-unused)
		$(eval package_name:=composer-unused )
		@${MAKE} check_executable_or_exit_with_phive executable=$(executable) package_name=$(package_name) --no-print-directory
		@$(executable) --excludePackage=wsdltophp/packagegenerator



PHONY: reviewdog permissions clean_env install


reviewdog:
		$(eval executable:=tools/reviewdog)
		$(eval package_name:=reviewdog )
		@if [ ! -f "$(executable)" ]; then \
				echo -e "$(GREEN)$(package_name)$(WHITE) $(RED)NOT FOUND$(WHITE) on $(CYAN)$(executable)$(WHITE). " ;\
				echo -e "Install it with " ;\
				echo -e "curl -sfL https://raw.githubusercontent.com/reviewdog/reviewdog/master/install.sh | sh -s -- -b tools " ;\
				exit 1 ;\
		fi
		@tools/reviewdog -diff="git diff develop"


