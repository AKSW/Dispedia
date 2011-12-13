<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_proppalloc
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class Topic
{
    private $_lang;
    private $_store;
    
    public function __construct ($lang)
    {
        $this->_lang = $lang;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    public function getAllTopics ()
    {
         return $this->_store->sparqlQuery (
            'SELECT ?uri ?label
              WHERE {
                 ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://als.dispedia.de/frs/o/Topic>.
                 ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?label.
                 FILTER (langmatches(lang(?label), "'. $this->_lang .'"))
             };'
        );
    }
}

