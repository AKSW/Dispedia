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
    private $_titleHelper;
    
    public function __construct ($patientModel, $lang, $titleHelper)
    {
        $this->_lang = $lang;
	$this->_patientModel = $patientModel;
	$this->_titleHelper = $titleHelper;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    
    //TODO: should be the same like in the patapro Proposal class
    /**
     * get all proposals
     */
    public function getAllProposals ()
    {
        $proposalResult = $this->_store->sparqlQuery (
            'SELECT ?uri
              WHERE {
                 ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.dispedia.de/o/Proposal>.
             };'
        );
        
        $proposals = array ();
		
        $this->_titleHelper->reset();
        $this->_titleHelper->addResources ($proposalResult, 'uri');

        foreach ( $proposalResult as $proposal )
        {
            $newProposal['shortcut'] = md5 ( $proposal ['uri'] );
            $newProposal['uri'] = $proposal['uri'];
            $newProposal['label'] = $this->_titleHelper->getTitle($proposal['uri'], $this->_lang);
            $proposals[$newProposal['uri']] = $newProposal;
        }

        return $proposals;
    }
	
	/**
	 * function determine which proposal description received or read by a patient
	 */
	public function getPatientProposalDescription($patientUri, $proposalUri)
	{
		$proposaldescriptions = array();
		$proposaldescriptions['received'] = array();
		$proposaldescriptions['read'] = array();
		
		$proposalDescriptionResult = $this->_store->sparqlQuery (
            'SELECT ?proposalDescription ?status
			WHERE {
				<' . $proposalUri . '> <http://www.dispedia.de/o/containsProposalComponent> ?proposalComponent.
				?proposalComponent <http://www.dispedia.de/o/containsProposalDescription> ?proposalDescription.
				<' . $patientUri . '> <http://www.dispedia.de/o/hasHealthState> ?healthState.
				?healthState ?status ?proposalDescription.
			}'
        );
		foreach ($proposalDescriptionResult as $proposalDescription)
		{
			if ("http://www.dispedia.de/o/receivedProposalDescription" == $proposalDescription['status'])
				$proposaldescriptions['received'][$proposalDescription['proposalDescription']] = $proposalDescription['proposalDescription'];
				
			if ("http://www.dispedia.de/o/readProposalDescription" == $proposalDescription['status'])
				$proposaldescriptions['read'][$proposalDescription['proposalDescription']] = $proposalDescription['proposalDescription'];
		}
		
		return $proposaldescriptions;
	}
	
	/*
	 * function determine the propsal information to a specific proposal and patient
	 */
	public function getProposalData($proposalUri)
	{
		$proposalData = array();
		$proposalDataResult = $this->_store->sparqlQuery (
            'SELECT ?proposalComponent ?proposalDescription ?proposalDescriptionType
			WHERE {
			  <' . $proposalUri . '> <http://www.dispedia.de/o/containsProposalComponent> ?proposalComponent.
			  OPTIONAL{
				?proposalComponent <http://www.dispedia.de/o/containsProposalDescription> ?proposalDescription.
				?proposalDescription <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?proposalDescriptionType.
			  }
			};'
        );
		
		// get labels
		$this->_titleHelper->reset();
		$this->_titleHelper->addResources($proposalDataResult, 'proposalComponent');
		$this->_titleHelper->addResources($proposalDataResult, 'proposalDescription');
		$this->_titleHelper->addResources($proposalDataResult, 'proposalDescriptionType');
		
		$proposalData['data'] = array();
		$proposalData['labels'] = array();
		foreach ($proposalDataResult as $proposal)
		{
			if ("" != $proposal['proposalComponent'])
			{
				if (!isset($proposalData['data'][$proposal['proposalComponent']]))
					$proposalData['data'][$proposal['proposalComponent']] = array();
				
				if (!isset($proposalData['labels'][$proposal['proposalComponent']]))
						$proposalData['labels'][$proposal['proposalComponent']] = $this->_titleHelper->getTitle($proposal['proposalComponent'], $this->_lang);
				
				if ("" != $proposal['proposalDescription'])
				{
					if (!isset($proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]))
						$proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']] = array();
					$proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]['descriptionName'] = $proposal['proposalDescription'];
					
					if (!isset($proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]['type']))
						$proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]['type'] = array();
					
					$proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]['type'][$proposal['proposalDescriptionType']] = $proposal['proposalDescriptionType'];
					
					if (!isset($proposalData['labels'][$proposal['proposalDescription']]))
						$proposalData['labels'][$proposal['proposalDescription']] = $this->_titleHelper->getTitle($proposal['proposalDescription'], $this->_lang);
					
					if (!isset($proposalData['labels'][$proposal['proposalDescriptionType']]))
						$proposalData['labels'][$proposal['proposalDescriptionType']] = $this->_titleHelper->getTitle($proposal['proposalDescriptionType'], $this->_lang);
					
					//get description content
					$proposalDescriptionContent = $this->_store->sparqlQuery (
						'SELECT ?proposalDescriptionContent
						WHERE {
							<' . $proposal['proposalDescription'] . '> <http://www.dispedia.de/o/content> ?proposalDescriptionContent.
							FILTER (langmatches(lang(?proposalDescriptionContent), "' . $this->_lang . '"))
						};'
					);
					if (isset($proposalDescriptionContent[0]) && "" != $proposalDescriptionContent[0]['proposalDescriptionContent'])
						$proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]['content'] = $proposalDescriptionContent[0]['proposalDescriptionContent'];
				}
			}
		}
		
		return $proposalData;
	}
    
    /**
     * 
     */
    public function getSettings ( $proposalUri ) 
    {
		$appropriateForSymptoms = $this->_store->sparqlQuery (
            'SELECT ?optionUri
              WHERE {
                 <'. $proposalUri .'> <http://www.dispedia.de/o/appropriateForSymptoms> ?ss .
                 ?ss <http://www.dispedia.de/wrapper/alsfrs/containsSymptomOption> ?optionUri .
             };'
        );        
        
		$appropriateForProperties = $this->_store->sparqlQuery (
            'SELECT ?optionUri
              WHERE {
                 <'. $proposalUri .'> <http://www.dispedia.de/o/appropriateForProperties> ?ps .
                 ?ps <http://www.dispedia.de/wrapper/alsfrs/containsPropertyOption> ?optionUri .
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
     *
     */
    public function calcCorrespondenceProposals($patientOptions, $allProposals)
    {
		$sortArray = array();
		$correspondenceProposals = array();
		foreach ($allProposals as $proposalUri => $proposal)
		{
			$correspondence = 0;
			$proposalOptions = $this->getSettings($proposalUri);
			if (0 < count($proposalOptions))
			{
			foreach ($proposalOptions as $proposalOptionUri)
			{
				if (true == in_array($proposalOptionUri, $patientOptions))
				$correspondence++;
			}
			$proposal['correspondence'] = round($correspondence*100/count($proposalOptions));
			}
			else
			$proposal['correspondence'] = 0;
			$sortArray[$proposalUri] = $proposal['correspondence'];
			$correspondenceProposals[$proposalUri] = $proposal;
		}
		$allProposals = array();
		arsort ($sortArray);
		foreach ($sortArray as $proposalUri => $value)
			$allProposals[$proposalUri] = $correspondenceProposals[$proposalUri];
			
		return $allProposals;
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
			SELECT ?uri ?label ?healthstate ?statusUri
			WHERE {
				?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> dispediao:Proposal .
				?uri <http://www.w3.org/2000/01/rdf-schema#label> ?label .
				?healthstate ?statusUri ?uri .
				<' . $patientUri . '> dispediao:hasHealthState ?healthstate .
				FILTER (langmatches(lang(?label), "' . $this->_lang . '"))
			}'
        );
	
        foreach ($results as $result)
		{
			$result['status'] = preg_replace('/http:\/\/www.dispedia.de\/o\//', '', $result['statusUri']);
			$proposals[$result['uri']] = $result;
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

