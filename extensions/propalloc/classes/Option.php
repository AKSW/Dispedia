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
    private $_lang;
    private $_store;
    
    public function __construct ( $lang )
    {
        $this->_lang = $lang;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    public function getOptions ( $topicUri )
    {
        if ( false == Erfurt_Uri::check ( $topicUri ) ) 
            return array ();
        
        else {
            
            $pertainsToPropertySet = array (
                'http://als.dispedia.info/frs/i/topic/t1',
                'http://als.dispedia.info/frs/i/topic/t3',
                'http://als.dispedia.info/frs/i/topic/t5a',
                'http://als.dispedia.info/frs/i/topic/t5b',
                'http://als.dispedia.info/frs/i/topic/t6',
                'http://als.dispedia.info/frs/i/topic/t7',
                'http://als.dispedia.info/frs/i/topic/t9',
                'http://als.dispedia.info/frs/i/topic/t12'
            );
            // if an option is not in pertainsToPropertySet than its 
            // automatically in pertainsToSymptomSet
            
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
                if ( true == in_array ( $option ['uri'], $pertainsToPropertySet ) )
                    $option ['pertainsTo'] = 'propertySet';
                else
                    $option ['pertainsTo'] = 'symptomSet';
                
                $options [] = $option;
            }
            
            return $options; 
        }
    }
}

