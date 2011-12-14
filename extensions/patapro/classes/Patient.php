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
    
    public function getPatientOptions($patientUri)
    {
        $options = array();
        $appropriateForSymptoms = $this->_store->sparqlQuery (
            'SELECT ?optionUri ?optionLabel ?topicLabel
              WHERE {

                  <' . $patientUri . '> <has> ?hs .
                ?hs <http://www.dispedia.de/o/includesSymptoms> ?ss .
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

                  <' . $patientUri . '> <has> ?hs .
                ?hs <http://www.dispedia.de/o/includesAffectedProperties> ?ps .
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

