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
    
    public function getAllPatientTypes()
    {
        $patientTypes = array();
        $patientTypeResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            PREFIX rdf:<http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
            SELECT ?patientTypeUri ?patientTypeLabel
            WHERE {
                ?patientTypeUri rdf:type dispediao:PatientType.
                ?patientTypeUri rdfs:label ?patientTypeLabel.
                FILTER (langmatches(lang(?patientTypeLabel), "' . $this->_lang . '"))
            };'
        );
        foreach ($patientTypeResults as $patientTypeResult)
        {
            $patientTypes[$patientTypeResult['patientTypeUri']] = $patientTypeResult['patientTypeLabel'];
        }
        return $patientTypes;
    }
    
    public function getAllTherapistTypes()
    {
        $therapistTypes = array();
        $therapistTypeResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            PREFIX rdf:<http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
            SELECT ?therapistTypeUri ?therapistTypeLabel
            WHERE {
                ?therapistTypeUri rdf:type dispediao:TherapistType.
                ?therapistTypeUri rdfs:label ?therapistTypeLabel.
                FILTER (langmatches(lang(?therapistTypeLabel), "' . $this->_lang . '"))
            };'
        );
        foreach ($therapistTypeResults as $therapistTypeResult)
        {
            $therapistTypes[$therapistTypeResult['therapistTypeUri']] = $therapistTypeResult['therapistTypeLabel'];
        }
        return $therapistTypes;
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

