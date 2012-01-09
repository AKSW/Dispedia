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
    private $_dispediaModel;
    private $_patientsModel;
    private $_lang;
    
    public function __construct ($lang, $patientsModel, $dispediaModel)
    {
        $this->_lang = $lang;
        $this->_patientsModel = $patientsModel;
        $this->_dispediaModel = $dispediaModel;
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
                        
        // save with language tag DE
        $this->addStmt ($ProposalInstance,
                        'http://www.w3.org/2000/01/rdf-schema#label',
                        $proposalName,
                        'de');
        $this->addStmt ($ProposalInstance,
                        'http://www.w3.org/2004/02/skos/core#note',
                        $proposalText,
                        'de');
                        
        // save with language tag EN
        $this->addStmt ($ProposalInstance,
                        'http://www.w3.org/2000/01/rdf-schema#label',
                        $proposalName,
                        'en');
        $this->addStmt ($ProposalInstance,
                        'http://www.w3.org/2004/02/skos/core#note',
                        $proposalText,
                        'en');
                        
        return $ProposalInstance;
    }
    
    /**
	 * 
	 */
    public function remove($proposalUri)
    {              
        $proposal = $this->_patientsModel->sparqlQuery (
            "SELECT ?action ?label ?text
              WHERE {
                <". $proposalUri ."> <http://www.dispedia.de/o/containsAction> ?action .
                <". $proposalUri ."> <http://www.w3.org/2000/01/rdf-schema#label> ?label .
                <". $proposalUri ."> <http://www.w3.org/2004/02/skos/core#note> ?text .
              };"
        );
        
        $this->removeStmt ($proposalUri,
                        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                        'http://www.dispedia.de/o/Proposal');
        
        // save with language tag DE
        $this->removeStmt ($proposalUri,
                        'http://www.w3.org/2000/01/rdf-schema#label',
                        $proposal [0] ['label'] ,
                        'de');
        $this->removeStmt ($proposalUri,
                        'http://www.w3.org/2004/02/skos/core#note',
                        $proposal [0] ['text'],
                        'de');
                        
        // save with language tag EN
        $this->removeStmt ($proposalUri,
                        'http://www.w3.org/2000/01/rdf-schema#label',
                        $proposal [0] ['label'] ,
                        'en');
        $this->removeStmt ($proposalUri,
                        'http://www.w3.org/2004/02/skos/core#note',
                        $proposal [0] ['text'],
                        'en');
    }
    
    
    /**
     * adds a triple to datastore
     */
    public function addStmt($s, $p, $o, $lang = '')
    {
        // set type(uri or literal)
        $type = true == Erfurt_Uri::check($o) 
            ? 'uri'
            : 'literal';
        
        // add a triple to datastore
        if ( '' == $lang )
            return $this->_patientsModel->addStatement(
                $s,
                $p, 
                array('value' => $o, 'type' => $type)
            );
        else
            return $this->_patientsModel->addStatement(
                $s,
                $p, 
                array('value' => $o, 'type' => $type, 'lang' => $lang)
            );
    }
    
    
    /**
     *
     */
    public function removeStmt($s, $p, $o, $lang = '')
    {
        // set type(uri or literal)
        $type = true == Erfurt_Uri::check($o) 
            ? 'uri'
            : 'literal';
            
        // remove a triple form datastore
        if ( '' == $lang )
            return $this->_patientsModel->deleteMatchingStatements(
                $s,
                $p,
                array('value' => $o, 'type' => $type)
            );
        else
            return $this->_patientsModel->deleteMatchingStatements(
                $s,
                $p,
                array('value' => $o, 'type' => $type, 'lang' => $lang)
            );
    }
}

