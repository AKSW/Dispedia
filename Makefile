ZENDVERSION=1.11.5

default: help

help:
	@echo "  install ........................ install dispedia"
	@echo "  update ......................... update dispedia"
	@echo "  install-owcli .................. install owcli"
	@echo "  install-kb ..................... install/update knowledgebases"
	@echo "  deploy-changes ................. make dispedia.de deployment"
	@echo "  dev-changes .................... change dispedia.de to dev"
	@echo "  update-docs NAME=<ontology>..... update documentation files"

# top level target
# Dispedia

install:
	git submodule init
	git submodule update
	cp config.ini.dummy application/config.ini
	# generate symlinks
	scripts/generate_symlinks.sh
	# add ignore paths
	cp -f scripts/application_exclude .git/modules/application/info/exclude

update:
	git pull
	git submodule update
	
install-owcli:
	scripts/install_script.sh -i

install-kb:
	scripts/install_script.sh -k

deploy-changes:
	scripts/deployment.sh
    
dev-changes:
	scripts/toDev.sh


CHANGED = $(shell git diff --name-only ontologies/$(NAME).*)

update-docs:
# Parameter check
ifndef NAME
	@echo "You must Set a ontology name."
	@echo "Example: make update-docs NAME=<ontologyName>"
	@exit 1
endif
ifneq (,$(findstring ontologies/$(NAME),$(CHANGED))) 
	scripts/dowl/bin/dowl ontologies/$(NAME).xml htdocs/dispedia/templates/dispedia.erb de > htdocs/dispedia/types/schemata/$(NAME)_de.html
	scripts/dowl/bin/dowl ontologies/$(NAME).xml htdocs/dispedia/templates/dispedia.erb en > htdocs/dispedia/types/schemata/$(NAME)_en.html
	rm bootstrap.*;
	rm jquery.js;
endif
