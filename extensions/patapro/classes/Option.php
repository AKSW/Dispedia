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
    protected $_pertainsToPropertySet;
    
    public function __construct ( $lang, $model )
    {
        $this->_lang = $lang;
        
        $this->_selectedModel = $model;
        $this->_selectedModelUri = (string) $model;
        
        $this->_store = Erfurt_App::getInstance()->getStore();
        
        $this->_pertainsToPropertySet = array (
            'http://als.dispedia.info/frs/i/topic/t1',
            'http://als.dispedia.info/frs/i/topic/t3',
            'http://als.dispedia.info/frs/i/topic/t5a',
            'http://als.dispedia.info/frs/i/topic/t5b',
            'http://als.dispedia.info/frs/i/topic/t6',
            'http://als.dispedia.info/frs/i/topic/t7',
            'http://als.dispedia.info/frs/i/topic/t9',
            'http://als.dispedia.info/frs/i/topic/t12'
        );
        
        $this->_optionsPropertySet = array (
            'http://als_dispedia_info/frs/i/option/o1_0',
            'http://als_dispedia_info/frs/i/option/o1_1',
            'http://als_dispedia_info/frs/i/option/o1_2',
            'http://als_dispedia_info/frs/i/option/o1_3',
            'http://als_dispedia_info/frs/i/option/o1_4',
            'http://als_dispedia_info/frs/i/option/o3_0',
            'http://als_dispedia_info/frs/i/option/o3_1',
            'http://als_dispedia_info/frs/i/option/o3_2',
            'http://als_dispedia_info/frs/i/option/o3_3',
            'http://als_dispedia_info/frs/i/option/o3_4',
            'http://als_dispedia_info/frs/i/option/o5a_0',
            'http://als_dispedia_info/frs/i/option/o5a_1',
            'http://als_dispedia_info/frs/i/option/o5a_2',
            'http://als_dispedia_info/frs/i/option/o5a_3',
            'http://als_dispedia_info/frs/i/option/o5a_4',
            'http://als_dispedia_info/frs/i/option/o5b_0',
            'http://als_dispedia_info/frs/i/option/o5b_1',
            'http://als_dispedia_info/frs/i/option/o5b_2',
            'http://als_dispedia_info/frs/i/option/o5b_3',
            'http://als_dispedia_info/frs/i/option/o5b_4',
            'http://als_dispedia_info/frs/i/option/o6_0',
            'http://als_dispedia_info/frs/i/option/o6_1',
            'http://als_dispedia_info/frs/i/option/o6_2',
            'http://als_dispedia_info/frs/i/option/o6_3',
            'http://als_dispedia_info/frs/i/option/o6_4',
            'http://als_dispedia_info/frs/i/option/o7_0',
            'http://als_dispedia_info/frs/i/option/o7_1',
            'http://als_dispedia_info/frs/i/option/o7_2',
            'http://als_dispedia_info/frs/i/option/o7_3',
            'http://als_dispedia_info/frs/i/option/o7_4',
            'http://als_dispedia_info/frs/i/option/o9_0',
            'http://als_dispedia_info/frs/i/option/o9_1',
            'http://als_dispedia_info/frs/i/option/o9_2',
            'http://als_dispedia_info/frs/i/option/o9_3',
            'http://als_dispedia_info/frs/i/option/o9_4',
            'http://als_dispedia_info/frs/i/option/o12_0',
            'http://als_dispedia_info/frs/i/option/o12_1',
            'http://als_dispedia_info/frs/i/option/o12_2',
            'http://als_dispedia_info/frs/i/option/o12_3',
            'http://als_dispedia_info/frs/i/option/o12_4',
        );
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
                     <'. $topicUri .'> <http://als.dispedia.info/frs/o/hasOption> ?uri.
                     ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?label.
                     ?uri <http://als.dispedia.info/frs/o/hasScore> ?score .
                     FILTER (langmatches(lang(?label), "'. $this->_lang .'"))
                 };'
            );
            
            foreach ( $tmp as $option ) {
                
                // set belonging
                if ( true == in_array ( $option ['uri'], $this->_pertainsToPropertySet ) )
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
    public function saveOptions ( $proposalUri, $newOptions, $oldOptions )
    {
        // get propertySet instance
        $propertySetInstance = $this->_store->sparqlQuery (
            'SELECT ?uri
              WHERE {
                 <'. $proposalUri .'> <http://als.dispedia.info/architecture/c/20110827/appropriateForProperties> ?uri .
             };'
        );
        
        if (0 < count ($propertySetInstance))
            $propertySetInstance = $propertySetInstance [0]['uri'];
                          
        // get symptomSet instance
        $symptomSetInstance = $this->_store->sparqlQuery (
            'SELECT ?uri
              WHERE {
                 <'. $proposalUri .'> <http://als.dispedia.info/architecture/c/20110827/appropriateForSymptoms> ?uri .
             };'
        );  
                
        if (0 < count ($symptomSetInstance))
            $symptomSetInstance = $symptomSetInstance [0]['uri'];   
        
                
        // delete old options
        foreach ( $oldOptions as $option ) 
        {            
            if ( true == in_array ( $option, $this->_optionsPropertySet ) ) {
                $s = $propertySetInstance;
                $p = 'http://als.dispedia.info/architecture/c/20110827/includesAffectedProperties';
            }
            else {
                $s = $symptomSetInstance;
                $p = 'http://als.dispedia.info/architecture/c/20110827/includesSymptoms';
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
            $newUri = 'http://als.dispedia.info/wrapperAlsfrs/i/20111105/';
            $newUri = $newUri . substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
            
            // create propertySet instance
            $this->addStmt (
                $newUri,
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                'http://als.dispedia.info/wrapperAlsfrs/c/20111105/ALSFRSPropertySet'
            );
            
            // connect propertySet instance to proposalUri
            $this->addStmt (
                $proposalUri,
                'http://als.dispedia.info/architecture/c/20110827/appropriateForProperties',
                $newUri
            );
            
            $propertySetInstance = $newUri;
        }
        
        
        // if symptomSet instance does not exist, create it!
        if (true == is_array ($symptomSetInstance) && 0 == count ($symptomSetInstance)) {
            $newUri = 'http://als.dispedia.info/wrapperAlsfrs/i/20111105/';
            $newUri = $newUri . substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
            
            // create symptomSet instance
            $this->addStmt (
                $newUri,
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                'http://als.dispedia.info/wrapperAlsfrs/c/20111105/ALSFRSSymptomSet'
            );
            
            // connect propertySet instance to proposalUri
            $this->addStmt (
                $proposalUri,
                'http://als.dispedia.info/architecture/c/20110827/appropriateForSymptoms',
                $newUri
            );
            
            $symptomSetInstance = $newUri;
        }
        				
        // save new options
        foreach ( $newOptions as $option ) 
        {            
            if ( true == in_array ( $option, $this->_optionsPropertySet ) ) {
                $s = $propertySetInstance;
                $p = 'http://als.dispedia.info/architecture/c/20110827/includesAffectedProperties';
            }
            else {
                $s = $symptomSetInstance;
                $p = 'http://als.dispedia.info/architecture/c/20110827/includesSymptoms';
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
        return $this->_store->addStatement(
            $this->_selectedModelUri, 
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
        return $this->_store->deleteMatchingStatements(
            $this->_selectedModelUri,
            $s,
            $p,
            array('value' => $o, 'type' => $type)
       );
    }
}

