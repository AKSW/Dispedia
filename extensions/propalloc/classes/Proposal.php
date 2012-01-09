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
    private $_patientsModel;
    private $_lang;
    
    public function __construct ($lang, $patientsModel)
    {
        $this->_lang = $lang;
        $this->_patientsModel = $patientsModel;
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
                 ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.dispedia.de/o/Proposal>.
                 ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?label.
                FILTER (langmatches(lang(?label), "' . $this->_lang . '"))
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
                 <'. $proposalUri .'> <http://www.dispedia.de/o/appropriateForSymptoms> ?ss .
                 ?ss <http://www.dispedia.de/o/includesSymptoms> ?optionUri .
             };'
        );        
        
		$appropriateForProperties = $this->_store->sparqlQuery (
            'SELECT ?optionUri
              WHERE {
                 <'. $proposalUri .'> <http://www.dispedia.de/o/appropriateForProperties> ?ps .
                 ?ps <http://www.dispedia.de/o/includesAffectedProperties> ?optionUri .
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
    
    /**
	 * 
	 */
    public function saveProposal($proposalName, $proposalText)
    {
        $proposalNameNoSpaces = str_replace (' ', '', $proposalName);
        $ProposalInstance = 'http://patients.dispedia.de/' . $proposalNameNoSpaces . substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
        $this->addStmt ($ProposalInstance,
                        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                        'http://www.dispedia.de/o/Proposal');
        $this->addStmt ($ProposalInstance,
                        'http://www.w3.org/2000/01/rdf-schema#label',
                        $proposalName,
                        'de');
        $this->addStmt ($ProposalInstance,
                        'http://www.w3.org/2004/02/skos/core#note',
                        $proposalText,
                        'de');
    }
    
    /**
     * adds a triple to datastore
     */
    public function addStmt($s, $p, $o, $lang = 'de')
    {
        // set type(uri or literal)
        $type = true == Erfurt_Uri::check($o) 
            ? 'uri'
            : 'literal';
        
        // add a triple to datastore
        return $this->_patientsModel->addStatement(
            $s,
            $p, 
            array('value' => $o, 'type' => $type, 'lang' => $lang)
       );
    }
}

