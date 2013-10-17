#!/bin/bash
########### Dispedia Site ###########
# change Dispedia Site Ontology URIs from de to local
REPLACEREGEX='s/http:\/\/www\.dispedia\.de/http:\/\/www\.dispedia\.local/g'
ONTOLOGYFOLDER="ontologies"
ONTOLOGYFILE="dispediaWebsite.n3"
sed -i $REPLACEREGEX $ONTOLOGYFOLDER/$ONTOLOGYFILE

########### eHealthServices Site ###########
# change eHealthServices Site Ontology URIs from com to local
REPLACEREGEX='s/http:\/\/www\.ehealth-services\.com/http:\/\/www\.ehealth-services\.local/g'
ONTOLOGYFOLDER="ontologies"
ONTOLOGYFILE="eHealthServicesWebsite.n3"
sed -i $REPLACEREGEX $ONTOLOGYFOLDER/$ONTOLOGYFILE
