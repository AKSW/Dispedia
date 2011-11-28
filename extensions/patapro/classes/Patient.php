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
    
    public function __construct ()
    {
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
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

