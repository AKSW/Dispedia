#!/bin/bash
########### Dispedia Site ###########
# change Dispedia Site Ontology URIs from local to de
REPLACEREGEX='s/http:\/\/www\.dispedia\.local/http:\/\/www\.dispedia\.de/g'
ONTOLOGYFOLDER="ontologies"
ONTOLOGYFILE="dispediaWebsite.n3"
sed -i $REPLACEREGEX $ONTOLOGYFOLDER/$ONTOLOGYFILE

# change Site config.ini URLs from local to de
SITECONFIG="htdocs/dispedia/config.ini"
sed -i $REPLACEREGEX $SITECONFIG

# change Ontowiki config.ini.dummy namespace Dispedia Site Ontology from local to de
ONTOWIKICONFIG="config.ini.dummy"
sed -i $REPLACEREGEX $ONTOWIKICONFIG

make update-docs NAME=dispediaCore


########### eHealthServices Site ###########
# change eHealthServices Site Ontology URIs from local to com
REPLACEREGEX='s/http:\/\/www\.ehealth-services\.local/http:\/\/www\.ehealth-services\.com/g'
ONTOLOGYFOLDER="ontologies"
ONTOLOGYFILE="eHealthServicesWebsite.n3"
sed -i $REPLACEREGEX $ONTOLOGYFOLDER/$ONTOLOGYFILE

# change Site config.ini URLs from local to com
SITECONFIG="htdocs/ehealthservices/config.ini"
sed -i $REPLACEREGEX $SITECONFIG

# change Ontowiki config.ini.dummy namespace eHealthServices Site Ontology from local to com
ONTOWIKICONFIG="config.ini.dummy"
sed -i $REPLACEREGEX $ONTOWIKICONFIG
