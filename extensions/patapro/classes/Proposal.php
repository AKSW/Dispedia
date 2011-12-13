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
    private $_lang;
    private $_patientModel;
    private $_store;
    
    public function __construct ($patientModel, $lang)
    {
        $this->_lang = $lang;
	$this->_patientModel = $patientModel;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    
    /**
     * get all proposals from a patient
     */
    public function getAllProposals ()
    {
        $proposals = array();
        $results = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
	    SELECT ?proposalUri ?proposalLabel
	    WHERE {
		?proposalUri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> dispediao:Proposal .
		?proposalUri <http://www.w3.org/2000/01/rdf-schema#label> ?proposalLabel .
                FILTER (langmatches(lang(?proposalLabel), "' . $this->_lang . '"))
	    };'
        );
        foreach ($results as $result)
	{
	    $proposals[$result['proposalUri']] = $result;
	}
        return $proposals;
    }
    
    /**
     * save proposals to the knowlegebase
     * first version delete and add all proposalallocations through a update
     * TODO: better version for updateing proposalallocations
     */
    public function saveProposals ($patientUri, $proposalUris) 
    {
    $return_value = true;
    if (!isset($proposalUris))
	$proposalUris = array();
    $proposalUris = $this->removeProposals($patientUri, $proposalUris);
    if (false == $proposalUris)
	$return_value = false;
    if ($return_value && 0 < count($proposalUris))
        foreach ($proposalUris as $proposalUri) {
        $this->addProposal($patientUri, urldecode($proposalUri));
        }
    return $return_value;
    }

    /**
     * add a new patient proposal allocation to the knowlegebase with all
     * resources, they are needed
     */
    private function addProposal ( $patientUri, $proposalUri )
    {
	// new Decision URI
	//http://als.dispedia.info/architecture/c/20110827/
	//http://patients.dispedia.de/i/Patient/20111115/ea1236/LarsEidam
	$newDecisionUri = 'http://patients.dispedia.de/i/Decision/';
	$newDecisionUri = $newDecisionUri . substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
	
	// create Decision instance
	$this->addStmt (
	    $newDecisionUri,
	    'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
	    'http://www.dispedia.de/o/Decision'
	);
	
	// connect Patient instance to Decision instance
	$this->addStmt (
	    $patientUri,
	    'http://www.dispedia.de/o/makes',
	    $newDecisionUri
	);
	
	// new ProposalAllocation URI
	$newProposalAllocationUri = 'http://patients.dispedia.de/i/ProposalAllocation/';
	$newProposalAllocationUri = $newProposalAllocationUri . substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
	
	// create ProposalAllocation instance
	$this->addStmt (
	    $newProposalAllocationUri,
	    'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
	    'http://www.dispedia.de/o/ProposalAllocation'
	);
	
	// connect Decision instance to ProposalAllocation instance
	$this->addStmt (
	    $newDecisionUri,
	    'http://www.dispedia.de/o/isPending',
	    $newProposalAllocationUri
	);
	
	// connect ProposalAllocation instance to Proposal instance
	$this->addStmt (
	    $newProposalAllocationUri,
	    'http://www.dispedia.de/o/allocatesProposal',
	    $proposalUri
	);
	
	// connect ProposalAllocation instance to Patient instance
	$this->addStmt (
	    $newProposalAllocationUri,
	    'http://www.dispedia.de/o/allocatesPatient',
	    $patientUri
	);
    }
    
    /**
     * remove all proposalallocations to a specific patient in the knowlegebase
     */
    private function removeProposals($patientUri, $proposalUris)
    {
	$return_value = true;
	// get all Decisions and ProposalAllocation instances
	// get symptomSet instance
        $decproalls = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
	    SELECT ?decision ?proposalallocation ?proposalUri
	    WHERE {
		<' . $patientUri . '> dispediao:makes ?decision .
		?decision dispediao:isPending ?proposalallocation .
		?proposalallocation dispediao:allocatesProposal ?proposalUri .
	    };'
        );
	foreach ($decproalls as $decproall)
	{
	    $proposalAlreadyExists = false;
	    foreach ($proposalUris as $key => $proposalUri)
	    {
		if (urlencode($decproall['proposalUri']) == $proposalUri)
		{
		    $proposalAlreadyExists = true;
		    unset($proposalUris[$key]);
		    break;
		}
	    }
	    if (!$proposalAlreadyExists)
	    {
		$removedStmt = 0;
		$removedStmt += $this->removeStmt($decproall['decision'], null, null);
		$removedStmt += $this->removeStmt(null, null, $decproall['decision']);
		$removedStmt += $this->removeStmt($decproall['proposalallocation'], null, null);
		
		if (6 != $removedStmt)
		    $return_value = false;
	    }
	}
	if ($return_value)
	    $return_value = $proposalUris;
	return $return_value;
    }
    
    /**
     * get all proposals with decision from a patient
     */
    public function getAllDecisinProposals ($patientUri)
    {
        $proposals = array();
        $results = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
	    SELECT ?proposalUri ?proposalLabel ?proposalAllocation ?decision ?statusUri
	    WHERE {
		?proposalUri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> dispediao:Proposal .
		?proposalUri <http://www.w3.org/2000/01/rdf-schema#label> ?proposalLabel .
		?proposalAllocation dispediao:allocatesProposal ?proposalUri .
		?proposalAllocation dispediao:allocatesPatient <' . $patientUri . '> .
		<' . $patientUri . '> dispediao:makes ?decision .
		?decision ?statusUri ?proposalAllocation .
                FILTER (langmatches(lang(?proposalLabel), "' . $this->_lang . '"))
	    }'
        );
	
        foreach ($results as $result)
	{
	    $result['status'] = preg_replace('/http:\/\/www.dispedia.de\/o\//', '', $result['statusUri']);
	    $proposals[$result['proposalUri']] = $result;
	}
	return $proposals;
    }

    /**
     * save a decision from patient
     */
    public function saveDecision ($proposalAllocation, $decisionUri, $decision)
    {
    $return_value = true;
    $removedStmt = 0;
    $addProperty = "";
    $removePropertyOne = "";
    $removePropertyTwo = "";
    switch ($decision)
    {
        case "isPending":
        $addProperty = "isPending";
        $removePropertyOne = "accepts";
        $removePropertyTwo = "denies";
        break;
        
        case "accepts":
        $addProperty = "accepts";
        $removePropertyOne = "isPending";
        $removePropertyTwo = "denies";
        break;
        
        case "denies":
        $addProperty = "denies";
        $removePropertyOne = "isPending";
        $removePropertyTwo = "accepts";
        break;
    }
    
    $removedStmt += $this->removeStmt(
        $decisionUri,
        "http://www.dispedia.de/o/" . $removePropertyOne,
        $proposalAllocation
    );
    $removedStmt += $this->removeStmt(
        $decisionUri,
        "http://www.dispedia.de/o/" . $removePropertyTwo,
        $proposalAllocation
    );
    $this->addStmt (
        $decisionUri,
        "http://www.dispedia.de/o/" . $addProperty,
        $proposalAllocation
    );
    
    if (1 != $removedStmt)
        $return_value &= false;
    
    return $return_value;
    }
    /**
     * adds a triple to datastore
     */
    private function addStmt($s, $p, $o)
    {
        // set type(uri or literal)
        $type = true == Erfurt_Uri::check($o) 
            ? 'uri'
            : 'literal';
        
        // add a triple to datastore
        return $this->_store->addStatement(
            (string) $this->_patientModel, 
            $s,
            $p, 
            array('value' => $o, 'type' => $type)
       );
    }
    
    
    /**
     *
     */
    private function removeStmt($s, $p, $o)
    {
    $options = array();
    // set subjecttype(uri or literal)
    if (isset($s))
        $options['subject_type'] = true == Erfurt_Uri::check($s)
        ? Erfurt_Store::TYPE_IRI
        : Erfurt_Store::TYPE_LITERAL;
        // set type(uri or literal)
    if (isset($o))
    {
        $options['object_type'] = true == Erfurt_Uri::check($o)
        ? Erfurt_Store::TYPE_IRI
        : Erfurt_Store::TYPE_LITERAL;
        // TODO: Fehler im Erfurt, Übergabe eines Arrays für das Object ist laut doc nicht vorgesehen wird aber vom Backend benötigt
        // set type(uri or literal)
        $type = Erfurt_Store::TYPE_IRI == $options['object_type'] 
            ? 'uri'
            : 'literal';
        $o = array('value' => $o, 'type' => $type);
    }
        // aremove a triple form datastore
        return $this->_store->deleteMatchingStatements(
            (string) $this->_patientModel,
            $s,
            $p,
        $o,
            $options
       );
    }

}

