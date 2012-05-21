<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_patapro
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class Patient
{
    private $_lang;
    
    public function __construct ($lang)
    {
        $this->_lang = $lang;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    public function getAllInformationClasses()
    {
        $informationClasses = array();
        $informationClassResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
            SELECT ?informationClassUri ?informationClassLabel
            WHERE {
                ?informationClassUri rdfs:subClassOf dispediao:Information.
                ?informationClassUri rdfs:label ?informationClassLabel.
                FILTER (langmatches(lang(?informationClassLabel), "' . $this->_lang . '"))
            };'
        );
        foreach ($informationClassResults as $informationClassResult)
        {
            $informationClasses[$informationClassResult['informationClassUri']] = $informationClassResult['informationClassLabel'];
        }
        return $informationClasses;
    }
    
    public function getAllSupporterClasses()
    {
        $supporterClasses = array();
        $supporterClassResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
            SELECT ?supporterClassUri ?supporterClassLabel
            WHERE {
                ?supporterClassUri rdfs:subClassOf dispediao:Supporter.
                ?supporterClassUri rdfs:label ?supporterClassLabel.
                FILTER (langmatches(lang(?supporterClassLabel), "' . $this->_lang . '"))
            };'
        );
        foreach ($supporterClassResults as $supporterClassResult)
        {
            $supporterClasses[$supporterClassResult['supporterClassUri']] = $supporterClassResult['supporterClassLabel'];
        }
        return $supporterClasses;
    }
    
    
    
    public function getPatientOptions($patientUri)
    {
        $options = array();
        $healthstate = $this->_store->sparqlQuery (
            'SELECT ?healthstate
            WHERE {
                <' . $patientUri . '> <has> ?healthstate .
                ?healthstate <http://purl.org/dc/terms/created> ?hsts .                                                     
            }
            ORDER BY DESC(?hsts)
            LIMIT 1;'
        );
        if (isset ($healthstate[0]['healthstate']))
            $healthstateUri = $healthstate[0]['healthstate'];
        else
            $healthstateUri = "";
        
        $appropriateForSymptoms = $this->_store->sparqlQuery (
            'SELECT ?optionUri ?optionLabel ?topicLabel
              WHERE {
                <' . $healthstateUri . '> <http://www.dispedia.de/o/includesSymptoms> ?ss .
                ?ss <http://www.dispedia.de/wrapper/alsfrs/containsSymptomOption> ?optionUri .
                ?optionUri  <http://www.w3.org/2000/01/rdf-schema#label> ?optionLabel .
                ?topicUri <http://als.dispedia.de/frs/o/hasOption> ?optionUri .
                ?topicUri <http://www.w3.org/2000/01/rdf-schema#label> ?topicLabel .
                FILTER (langmatches(lang(?optionLabel), "' . $this->_lang . '"))
                FILTER (langmatches(lang(?topicLabel), "' . $this->_lang . '"))
             };'
        );        
        
        $appropriateForProperties = $this->_store->sparqlQuery (
            'SELECT ?optionUri ?optionLabel ?topicLabel
              WHERE {
                <' . $healthstateUri . '> <http://www.dispedia.de/o/includesAffectedProperties> ?ps .
                ?ps <http://www.dispedia.de/wrapper/alsfrs/containsPropertyOption> ?optionUri .
                ?optionUri  <http://www.w3.org/2000/01/rdf-schema#label> ?optionLabel .
                ?topicUri <http://als.dispedia.de/frs/o/hasOption> ?optionUri .
                ?topicUri <http://www.w3.org/2000/01/rdf-schema#label> ?topicLabel .
                FILTER (langmatches(lang(?optionLabel), "' . $this->_lang . '"))
                FILTER (langmatches(lang(?topicLabel), "' . $this->_lang . '"))
             };'
        );
        $appropriateForSymptoms = array_merge($appropriateForSymptoms, $appropriateForProperties);
        $options['sorted'] = array();
        $options['uriList'] = array();
        foreach ($appropriateForSymptoms as $symptom) {
            $options['sorted'][$symptom['topicLabel']][] = $symptom['optionLabel'];
            $options['uriList'][] = $symptom['optionUri'];
        }
        return $options;
    }
    
    
    /**
     * 
     */
    public function getAllPatients ()
    {
        $patients = $this->_store->sparqlQuery (
            'SELECT ?uri ?firstName ?lastName
              WHERE {
                 ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.dispedia.de/o/Patient>.
                 ?uri <http://www.dispedia.de/o/firstName> ?firstName.
                 ?uri <http://www.dispedia.de/o/lastName> ?lastName.
             };'
        );

        return $patients;
    }
}

