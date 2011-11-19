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
    private $_store;
    
    public function __construct ()
    {
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    public function getAllOptions ()
    {
        var_dump ( $this->_store->sparqlQuery (
        
            'SELECT ?optionUri ?label ?score
              WHERE {
                 ?optionUri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://als.dispedia.info/frs/o/Option>.
                 ?optionUri <http://www.w3.org/2000/01/rdf-schema#label> ?label.
                 ?optionUri <http://als.dispedia.info/frs/o/hasScore> ?score .
             };'
        
        ) );
    }
}

