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
    private $_titleHelper;
    
    public function __construct ($lang, $titleHelper)
    {
        $this->_lang = $lang;
        $this->_titleHelper = $titleHelper;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    public function getPatientType($patientUri)
    {
        $patientTypeReturnValue = "";
        $patientTypeResult = $this->_store->sparqlQuery (
            'SELECT ?patientType
            WHERE {
                <' . $patientUri . '> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?patientType .
                FILTER (?patientType != <http://www.w3.org/2002/07/owl#NamedIndividual>)
                FILTER (?patientType != <http://www.dispedia.de/o/Patient>)
            };'
        );

        if (!isset($patientTypeResult[0]['patientType']) || "" == $patientTypeResult[0]['patientType'])
            $patientTypeReturnValue = "http://www.dispedia.de/o/Patient";
        else
            $patientTypeReturnValue = $patientTypeResult[0]['patientType'];

        return $patientTypeReturnValue;
    }
    
    public function getAllHealthstates($patientUri)
    {
        $healthstates = array();
        $healthstatesResult = $this->_store->sparqlQuery (
            'SELECT ?uri ?timestamp ?type
            WHERE {
                <' . $patientUri . '> <http://www.dispedia.de/o/hasHealthState> ?uri .
                ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?type.
                ?uri <http://www.dispedia.de/o/hasDate> ?timestamp .
                FILTER (?type != <http://www.w3.org/2002/07/owl#NamedIndividual>)
            }
            ORDER BY DESC(?timestamp);'
        );
        // Filter Statement for ALSFRS
        //?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.dispedia.de/wrapper/alsfrs/ALSFRSHealthState>.
        
        $this->_titleHelper->reset();
        
        $this->_titleHelper->addResources($healthstatesResult, 'uri');
        $this->_titleHelper->addResources($healthstatesResult, 'type');
        
        foreach ($healthstatesResult as $healthstate)
        {
            $healthstates[$healthstate['uri']] = array(
                'label' => $this->_titleHelper->getTitle($healthstate['uri'], $this->_lang),
                'type' => $healthstate['type'],
                'typeLabel' => $this->_titleHelper->getTitle($healthstate['type'], $this->_lang),
                'timestamp' => $healthstate['timestamp']
            );
        }
        
        return $healthstates;
    }
    
    public function getHealthstate($healthstateUri)
    {
        $options = array();
        
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
                <' . $healthstateUri . '> <http://www.dispedia.de/o/includesHealthProperties> ?ps .
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

