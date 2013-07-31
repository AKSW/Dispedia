#!/bin/bash
########### Dispedia Site ###########
# change Dispedia Site Ontology URIs from de to local
REPLACEREGEX='s/http:\/\/www\.dispedia\.de/http:\/\/www\.dispedia\.local/g'
ONTOLOGYFOLDER="ontologies"
ONTOLOGYFILE="dispediaWebsite.n3"
sed -i $REPLACEREGEX $ONTOLOGYFOLDER/$ONTOLOGYFILE

########### eHealthServices2013 Site ###########
# change eHealthServices2013 Site Ontology URIs from de to local
REPLACEREGEX='s/http:\/\/www\.ehealth-services-2013\.de/http:\/\/www\.ehealth-services-2013\.local/g'
ONTOLOGYFOLDER="ontologies"
ONTOLOGYFILE="eHealthServices2013Website.n3"
sed -i $REPLACEREGEX $ONTOLOGYFOLDER/$ONTOLOGYFILE
