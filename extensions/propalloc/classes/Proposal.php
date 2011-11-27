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
    
    
    /**
     * 
     */
    public function getAllProposals ()
    {
        $tmp = $this->_store->sparqlQuery (
            'SELECT ?uri ?label
              WHERE {
                 ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://als.dispedia.info/architecture/c/20110827/Proposal>.
                 ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?label.
             };'
        );
        
        $proposals = array ();
        
        foreach ( $tmp as $proposal ) {
            $proposal ['shortcut'] = md5 ( $proposal ['uri'] );
            
            $proposals [] = $proposal;
        }
        
        return $proposals;
    }
    
    
    /**
     * 
     */
    public function getSettings ( $proposalMd5 ) 
    {
		$proposalUri = $this->getProposalUri ( $proposalMd5 );
		
		$appropriateForSymptoms = $this->_store->sparqlQuery (
            'SELECT ?optionUri
              WHERE {
                 <'. $proposalUri .'> <http://als.dispedia.info/architecture/c/20110827/appropriateForSymptoms> ?ss .
                 ?ss <http://als.dispedia.info/architecture/c/20110827/includesSymptoms> ?optionUri .
             };'
        );        
        
		$appropriateForProperties = $this->_store->sparqlQuery (
            'SELECT ?optionUri
              WHERE {
                 <'. $proposalUri .'> <http://als.dispedia.info/architecture/c/20110827/appropriateForProperties> ?ps .
                 ?ps <http://als.dispedia.info/architecture/c/20110827/includesAffectedProperties> ?optionUri .
             };'
        );
        
        $optionUris = array ();
        
        foreach ( $appropriateForProperties as $p )
			$optionUris [] = $p ['optionUri'];
        
        foreach ( $appropriateForSymptoms as $p )
			$optionUris [] = $p ['optionUri'];
			
		return $optionUris;
	}
	
	
	/**
	 * 
	 */
	public function getProposalUri ( $md5 )
	{
		foreach ( $this->getAllProposals () as $p )
		{
			if ( $p ['shortcut'] == $md5 ) return $p ['uri'];
		}
		return null;
	}
}

