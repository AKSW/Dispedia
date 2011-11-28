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
    private $_model;
    protected $_lang;
    
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
                ?hs <http://als.dispedia.info/architecture/c/20110827/includesSymptoms> ?ss .
                ?ss <http://als.dispedia.info/wrapperAlsfrs/c/20111105/containsSymptomOption> ?optionUri .
                ?optionUri  <http://www.w3.org/2000/01/rdf-schema#label> ?optionLabel .
                ?topicUri <http://als.dispedia.info/frs/o/hasOption> ?optionUri .
                ?topicUri <http://www.w3.org/2000/01/rdf-schema#label> ?topicLabel .
                FILTER (langmatches(lang(?optionLabel), "' . $this->_lang . '"))
                FILTER (langmatches(lang(?topicLabel), "' . $this->_lang . '"))
             };'
        );        
        
		$appropriateForProperties = $this->_store->sparqlQuery (
            'SELECT ?optionUri ?optionLabel ?topicLabel
              WHERE {

                  <' . $patientUri . '> <has> ?hs .
                ?hs <http://als.dispedia.info/architecture/c/20110827/includesAffectedProperties> ?ps .
                ?ps <http://als.dispedia.info/wrapperAlsfrs/c/20111105/containsPropertyOption> ?optionUri .
                ?optionUri  <http://www.w3.org/2000/01/rdf-schema#label> ?optionLabel .
                ?topicUri <http://als.dispedia.info/frs/o/hasOption> ?optionUri .
                ?topicUri <http://www.w3.org/2000/01/rdf-schema#label> ?topicLabel .
                FILTER (langmatches(lang(?optionLabel), "' . $this->_lang . '"))
                FILTER (langmatches(lang(?topicLabel), "' . $this->_lang . '"))
             };'
        );
        $appropriateForSymptoms = array_merge($appropriateForSymptoms, $appropriateForProperties);
        
        foreach ($appropriateForSymptoms as $symptom) {
            $options[$symptom['topicLabel']][] = $symptom;
        }
        return $options;
    }
    
    
    /**
     * 
     */
    public function getAllPatients ()
    {
        $tmp = $this->_store->sparqlQuery (
            'SELECT ?uri ?firstName ?lastName
              WHERE {
                 ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://als.dispedia.info/architecture/c/20110827/Patient>.
                 ?uri <http://als.dispedia.info/architecture/c/20110827/firstName> ?firstName.
                 ?uri <http://als.dispedia.info/architecture/c/20110827/lastName> ?lastName.
             };'
        );
        
        $patients = array ();
        
        foreach ( $tmp as $patient ) {
            
            $patients [] = $patient;
        }
        
        return $patients;
    }
}

