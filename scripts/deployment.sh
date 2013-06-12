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

# change dispedia site header resource from local to de
ONTOWIKICONFIG="htdocs/dispedia/header.phtml"
sed -i $REPLACEREGEX $ONTOWIKICONFIG


########### eHealthServices2013 Site ###########
# change eHealthServices2013 Site Ontology URIs from local to de
REPLACEREGEX='s/http:\/\/www\.ehealth-services-2013\.local/http:\/\/www\.ehealth-services-2013\.de/g'
ONTOLOGYFOLDER="ontologies"
ONTOLOGYFILE="eHealthServices2013Website.n3"
sed -i $REPLACEREGEX $ONTOLOGYFOLDER/$ONTOLOGYFILE

# change Site config.ini URLs from local to de
SITECONFIG="htdocs/ehealthservices2013/config.ini"
sed -i $REPLACEREGEX $SITECONFIG

# change Ontowiki config.ini.dummy namespace eHealthServices2013 Site Ontology from local to de
ONTOWIKICONFIG="config.ini.dummy"
sed -i $REPLACEREGEX $ONTOWIKICONFIG

# change dispedia site header resource from local to de
ONTOWIKICONFIG="htdocs/ehealthservices2013/header.phtml"
sed -i $REPLACEREGEX $ONTOWIKICONFIG

# change dispedia site header resource from local to de
ONTOWIKICONFIG="htdocs/ehealthservices2013/footer.phtml"
sed -i $REPLACEREGEX $ONTOWIKICONFIG