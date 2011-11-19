<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_proppalloc
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class Proposal
{
    private $_model;
    
    public function __construct ()
    {
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    public function getAllProposals ()
    {
        return $this->_store->sparqlQuery (
            'SELECT ?uri ?label
              WHERE {
                 ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://als.dispedia.info/architecture/c/20110827/Proposal>.
                 ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?label.
             };'
        );
    }
}

