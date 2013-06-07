#!/bin/bash
# change Dispedia Site Ontology URIs from local to de
ONTOLOGYFOLDER="ontologies"
ONTOLOGYFILE="dispediaWebsite.n3"
sed -i 's/http:\/\/www\.dispedia\.local/http:\/\/www\.dispedia\.de/g' $ONTOLOGYFOLDER/$ONTOLOGYFILE

# change Site config.ini URLs from local to de
SITECONFIG="htdocs/dispedia/config.ini"
sed -i 's/http:\/\/www\.dispedia\.local/http:\/\/www\.dispedia\.de/g' $SITECONFIG

# change Ontowiki config.ini.dummy namespace Dispedia Site Ontology from local to de
ONTOWIKICONFIG="config.ini.dummy"
sed -i 's/http:\/\/www\.dispedia\.local/http:\/\/www\.dispedia\.de/g' $ONTOWIKICONFIG

# change dispedia site header resource from local to de
ONTOWIKICONFIG="htdocs/dispedia/header.phtml"
sed -i 's/http:\/\/www\.dispedia\.local/http:\/\/www\.dispedia\.de/g' $ONTOWIKICONFIG