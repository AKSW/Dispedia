<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_patapro
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class Proposal
{
    private $_selectedModel;
    private $_store;
    
    public function __construct ($model)
    {
        $this->_selectedModel = $model;
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
            $proposals [] = $proposal;
        }
        
        return $proposals;
    }
    
    
    /**
     * 
     */
    public function saveProposal ( $patientUri, $proposalUris ) 
    {
	foreach ($proposalUris as $proposalUri) {
//	// get decision instances
//        $decisions = $this->_store->sparqlQuery (
//            'SELECT ?uri
//              WHERE {
//                 <'. $patientUri .'> <http://als.dispedia.info/architecture/c/20110827/makes> ?uri .
//             };'
//        );
//	
//	
//	// remove decision instances
//	$this->removeStmt ( 
//                $patientUri,
//                $p,
//                $option
//            );
	
	    // new Decision URI
	    //http://als.dispedia.info/architecture/c/20110827/
	    //http://als.dispedia.info/i/Patient/20111115/ea1236/LarsEidam
	    $newDecisionUri = 'http://als.dispedia.info/i/Decision/20110827/';
	    $newDecisionUri = $newDecisionUri . substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
	    
	    // create Decision instance
	    $this->addStmt (
		$newDecisionUri,
		'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
		'http://als.dispedia.info/architecture/c/20110827/Decision'
	    );
	    
	    // connect Patient instance to Decision instance
	    $this->addStmt (
		$patientUri,
		'http://als.dispedia.info/architecture/c/20110827/makes',
		$newDecisionUri
	    );
	    
	    // new ProposalAllocation URI
	    $newProposalAllocationUri = 'http://als.dispedia.info/i/ProposalAllocation/20110827/';
	    $newProposalAllocationUri = $newProposalAllocationUri . substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
	    
	    // create ProposalAllocation instance
	    $this->addStmt (
		$newProposalAllocationUri,
		'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
		'http://als.dispedia.info/architecture/c/20110827/ProposalAllocation'
	    );
	    
	    // connect Decision instance to ProposalAllocation instance
	    $this->addStmt (
		$newDecisionUri,
		'http://als.dispedia.info/architecture/c/20110827/isPending',
		$newProposalAllocationUri
	    );
	    
	    // connect ProposalAllocation instance to Proposal instance
	    $this->addStmt (
		$newProposalAllocationUri,
		'http://als.dispedia.info/architecture/c/20110827/allocatesProposal',
		$proposalUri
	    );
	    
	    // connect ProposalAllocation instance to Patient instance
	    $this->addStmt (
		$newProposalAllocationUri,
		'http://als.dispedia.info/architecture/c/20110827/allocatesPatient',
		$patientUri
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
            (string) $this->_selectedModel, 
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
            (string) $this->_selectedModel,
            $s,
            $p,
            array('value' => $o, 'type' => $type)
       );
    }

}

