<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_proppalloc
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class Option
{
    protected $_lang;
    protected $_store;
    protected $_selectedModel;
    protected $_patientsModel;
    private $_propertyRegEx;
    
    public function __construct ( $lang, $model, $patientsModel )
    {
        $this->_lang = $lang;
        
        $this->_selectedModel = $model;
        $this->_patientsModel = $patientsModel;
        $this->_selectedModelUri = (string) $model;
        
        $this->_store = Erfurt_App::getInstance()->getStore();
        
        $this->_propertyRegEx = "/\/[ot][135679][^01]/";
    }
    
    public function getOptions ( $topicUri )
    {
        if ( false == Erfurt_Uri::check ( $topicUri ) ) 
            return array ();
        
        else {
            
            // get all options of the given topic
            $tmp = $this->_store->sparqlQuery (        
               'SELECT ?uri ?label ?score
                  WHERE {
                     <'. $topicUri .'> <http://als.dispedia.de/frs/o/hasOption> ?uri.
                     ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?label.
                     ?uri <http://als.dispedia.de/frs/o/hasScore> ?score .
                     FILTER (langmatches(lang(?label), "'. $this->_lang .'"))
                 };'
            );
            
            foreach ( $tmp as $option ) {
                
                // set belonging
                if ( 1 == preg_match($this->_propertyRegEx, $option['uri']) )
                    $option ['pertainsTo'] = 'propertySet';
                else
                    $option ['pertainsTo'] = 'symptomSet';
                
                $options [] = $option;
            }
            
            return $options; 
        }
    }
    
    
    /**
     * 
     */
    public function saveOptions ( $dataModel, $proposalUri, $newOptions, $oldOptions )
    {
        // get propertySet instance
        $propertySetInstance = $dataModel->sparqlQuery (
            'SELECT ?uri
              WHERE {
                 <'. $proposalUri .'> <http://www.dispedia.de/o/appropriateForProperties> ?uri .
             };'
        );
        
        if (0 < count ($propertySetInstance))
            $propertySetInstance = $propertySetInstance [0]['uri'];
                          
        // get symptomSet instance
        $symptomSetInstance = $dataModel->sparqlQuery (
            'SELECT ?uri
              WHERE {
                 <'. $proposalUri .'> <http://www.dispedia.de/o/appropriateForSymptoms> ?uri .
             };'
        );  
                
        if (0 < count ($symptomSetInstance))
            $symptomSetInstance = $symptomSetInstance [0]['uri'];   
        
        // delete old options
        foreach ( $oldOptions as $option ) 
        {            
            if ( 1 == preg_match($this->_propertyRegEx, $option) ) {
                $s = $propertySetInstance;
                $p = 'http://www.dispedia.de/o/includesAffectedProperties';
            }
            else {
                $s = $symptomSetInstance;
                $p = 'http://www.dispedia.de/o/includesSymptoms';
            }
                
            $this->removeStmt ( 
                $s,
                $p,
                $option
            );
        }
        
        
        // -------------------------------------------------------------
        
        
        // if propertySet instance does not exist, create it!
        if (true == is_array ($propertySetInstance) && 0 == count ($propertySetInstance)) {
            $newUri = 'http://www.dispedia.de/wrapper/alsfrs/ALSFRSPropertySet/';
            $newUri = $newUri . substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
            
            // create propertySet instance
            $this->addStmt (
                $newUri,
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                'http://www.dispedia.de/wrapper/alsfrs/ALSFRSPropertySet'
            );
            
            // connect propertySet instance to proposalUri
            $this->addStmt (
                $proposalUri,
                'http://www.dispedia.de/o/appropriateForProperties',
                $newUri
            );
            
            $propertySetInstance = $newUri;
        }
        
        
        // if symptomSet instance does not exist, create it!
        if (true == is_array ($symptomSetInstance) && 0 == count ($symptomSetInstance)) {
            $newUri = 'http://www.dispedia.de/wrapper/alsfrs/ALSFRSSymptomSet/';
            $newUri = $newUri . substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
            
            // create symptomSet instance
            $this->addStmt (
                $newUri,
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                'http://www.dispedia.de/wrapper/alsfrs/ALSFRSSymptomSet'
            );
            
            // connect propertySet instance to proposalUri
            $this->addStmt (
                $proposalUri,
                'http://www.dispedia.de/o/appropriateForSymptoms',
                $newUri
            );
            
            $symptomSetInstance = $newUri;
        }
        				
        // save new options
        foreach ( $newOptions as $option ) 
        {            
            if ( 1 == preg_match($this->_propertyRegEx, $option) ) {
                $s = $propertySetInstance;
                $p = 'http://www.dispedia.de/o/includesAffectedProperties';
            }
            else {
                $s = $symptomSetInstance;
                $p = 'http://www.dispedia.de/o/includesSymptoms';
            }
                        
            $this->addStmt ( 
                $s,
                $p,
                $option
            );
        }
    }
    
    
    /**
     * adds a triple to datastore
     */
    public function addStmt($s, $p, $o)
    {
        // set type(uri or literal)
        $type = true == Erfurt_Uri::check($o) 
            ? 'uri'
            : 'literal';
        
        // add a triple to datastore
        return $this->_patientsModel->addStatement(
            $s,
            $p, 
            array('value' => $o, 'type' => $type)
       );
    }
    
    
    /**
     *
     */
    public function removeStmt($s, $p, $o)
    {
        // set type(uri or literal)
        $type = true == Erfurt_Uri::check($o) 
            ? 'uri'
            : 'literal';
            
        // aremove a triple form datastore
        return $this->_patientsModel->deleteMatchingStatements(
            $s,
            $p,
            array('value' => $o, 'type' => $type)
       );
    }
}

