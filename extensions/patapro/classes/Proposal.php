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
     * get all proposals from a patient
     */
    public function getAllProposals ($patientUri)
    {
        $proposals = array();
        $results = $this->_store->sparqlQuery (
            'PREFIX ns1:<http://als.dispedia.info/architecture/c/20110827/>
        SELECT ?proposalUri ?proposalLabel ?proposalAllocation
        WHERE {
        {
            ?proposalUri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ns1:Proposal .
            ?proposalUri <http://www.w3.org/2000/01/rdf-schema#label> ?proposalLabel .}
        UNION{
            ?proposalAllocation ns1:allocatesProposal ?proposalUri .
            ?proposalAllocation ns1:allocatesPatient <' . $patientUri . '> .
        }
        };'
        );
        foreach ($results as $result)
    {
        if ("" != $result['proposalLabel'])
        $proposals[$result['proposalUri']]['label'] = $result['proposalLabel'];
        if ("" != $result['proposalAllocation'])
        $proposals[$result['proposalUri']]['checked'] = $result['proposalAllocation'];
    }
        return $proposals;
    }
    
    /**
     * save proposals to the knowlegebase
     * first version delete and add all proposalallocations through a update
     * TODO: better version for updateing proposalallocations
     */
    public function saveProposals ( $patientUri, $proposalUris ) 
    {
    $return_value = true;
    $return_value &= $this->removeProposals($patientUri);
    if ($return_value)
        foreach ($proposalUris as $proposalUri) {
        $this->addProposal ($patientUri, $proposalUri);
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
    
    /**
     * remove all proposalallocations to a specific patient in the knowlegebase
     */
    private function removeProposals($patientUri)
    {
    $return_value = true;
    // get all Decisions and ProposalAllocation instances
    // get symptomSet instance
        $decproalls = $this->_store->sparqlQuery (
            'PREFIX ns1:<http://als.dispedia.info/architecture/c/20110827/>
         SELECT ?decision ?proposalallocation
         WHERE {
          <' . $patientUri . '> ns1:makes ?decision .
          ?decision ns1:isPending ?proposalallocation .
        };'
        );
    foreach ($decproalls as $decproall)
    {
        $removedStmt = 0;
        $removedStmt += $this->removeStmt($decproall['decision'], null, null);
        $removedStmt += $this->removeStmt(null, null, $decproall['decision']);
        $removedStmt += $this->removeStmt($decproall['proposalallocation'], null, null);

        if (6 != $removedStmt)
        $return_value &= false;
    }
    return $return_value;
    }
    
    /**
     * get all proposals with decision from a patient
     */
    public function getAllDecisinProposals ($patientUri)
    {
        $proposals = $this->_store->sparqlQuery (
            'PREFIX ns1:<http://als.dispedia.info/architecture/c/20110827/>
        SELECT ?proposalUri ?proposalLabel ?proposalAllocation ?decision ?status
        WHERE {
          ?proposalUri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ns1:Proposal .
          ?proposalUri <http://www.w3.org/2000/01/rdf-schema#label> ?proposalLabel .
          ?proposalAllocation ns1:allocatesProposal ?proposalUri .
          ?proposalAllocation ns1:allocatesPatient <' . $patientUri . '> .
          <' . $patientUri . '> ns1:makes ?decision .
          ?decision ?status ?proposalAllocation .
        }'
        );
    return $proposals;
    }

    /**
     * get all proposals with decision from a patient
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
        "http://als.dispedia.info/architecture/c/20110827/" . $removePropertyOne,
        $proposalAllocation
    );
    $removedStmt += $this->removeStmt(
        $decisionUri,
        "http://als.dispedia.info/architecture/c/20110827/" . $removePropertyTwo,
        $proposalAllocation
    );
    $this->addStmt (
        $decisionUri,
        "http://als.dispedia.info/architecture/c/20110827/" . $addProperty,
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
            (string) $this->_selectedModel, 
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
            (string) $this->_selectedModel,
            $s,
            $p,
        $o,
            $options
       );
    }

}

