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
	private $_patientHelper;
	private $_translate;
    
    public function __construct ($patientModel, $lang, $titleHelper, $patientHelper, $translate)
    {
        $this->_lang = $lang;
        $this->_translate = $translate;
		$this->_patientModel = $patientModel;
		$this->_titleHelper = $titleHelper;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
		$this->_patientHelper = $patientHelper;
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
	 * function determine which proposal description are preselected for a patient
	 */
	public function getProposalDescriptionByType($patientUri, $proposalUri)
	{
		$proposaldescriptions = array();
		$proposaldescriptions['received'] = array();
		$proposaldescriptions['read'] = array();
		$patientType = $this->_patientHelper->getPatientType($patientUri);
		if ( false !== preg_match("/sensible/", $patientType) )
		{
			$patientType = "sensible";
		}
		else if ( false !== preg_match("/experienced/", $patientType) )
		{
			$patientType = "experienced";
		}
		else
		{
			$patientType = "";
		}
		
		$proposalComponents = $this->getProposalData($proposalUri);
		//TODO: find a better was to determine patienttype to description type
		foreach ($proposalComponents['data'] as $proposalDescriptions)
		{
			foreach ($proposalDescriptions as $proposalDescriptionUri => $proposalDescription)
			{
				foreach($proposalDescription['type'] as $proposalDescriptionType)
				if (false !== preg_match("/" . $patientType . "/", $proposalDescriptionType) || $patientType = "")
					$proposaldescriptions['received'][$proposalDescriptionUri] = $proposalDescriptionUri;
			}
		}
		
		return $proposaldescriptions;
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
                FILTER !REGEX(str(?proposalDescriptionType), "NamedIndividual"))
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
					
					if ("http://www.dispedia.de/o/ProposalDescription" != $proposal['proposalDescriptionType'])
					{
						if (isset($proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]['type']["http://www.dispedia.de/o/ProposalDescription"]))
							unset($proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]['type']["http://www.dispedia.de/o/ProposalDescription"]);
						$proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]['type'][$proposal['proposalDescriptionType']] = $proposal['proposalDescriptionType'];
					}
					else
					{
						if (0 == count($proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]['type']))
							$proposalData['data'][$proposal['proposalComponent']][$proposal['proposalDescription']]['type']["http://www.dispedia.de/o/ProposalDescription"] = "http://www.dispedia.de/o/ProposalDescription";
					}
					
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
    public function saveProposals ($patientUri, $proposalUris, $oldProposalUris) 
    {
		$messages = array();
		
		$deleteProposals = array_diff($oldProposalUris, $proposalUris);
		$messages = array_merge($messages, $this->removeProposals($patientUri, $deleteProposals));

		$addProposals = array_diff($proposalUris, $oldProposalUris);
		$messages = array_merge($messages, $this->addProposals($patientUri, $addProposals));
		
		return $messages;
    }

    /**
     * add a new patient proposal allocation to the knowlegebase with all
     * resources, they are needed
     */
    private function addProposals ( $patientUri, $proposalUris )
    {
		$messages = array();
		$healthstates = $this->_patientHelper->getAllHealthstates($patientUri);
		
		foreach ($proposalUris as $proposalUri)
		{
			$this->addStmt (
				key($healthstates),
				'http://www.dispedia.de/o/isPending',
				$proposalUri
			);
			$messages[] = new OntoWiki_Message($proposalUri . " " . $this->_translate->_('added'), OntoWiki_Message::SUCCESS);			
		}	
		return $messages;
    }
    
    /**
     * remove all proposalallocations to a specific patient in the knowlegebase
     */
    private function removeProposals($patientUri, $proposalUris)
    {
		$messages = array();
		$healthstates = $this->_patientHelper->getAllHealthstates($patientUri);
		foreach ($proposalUris as $proposalUri)
		{
			$proposalData = $this->getProposalData($proposalUri);
			foreach ($healthstates as $healthstatesUri => $healthstatesTimestamp)
			{
				// delete proposal in healthstates
				$deletedStatements = $this->removeStmt(
					$healthstatesUri,
					'http://www.dispedia.de/o/isPending',
					$proposalUri
				);
				
				//// delete all received proposal descriptions
				//foreach ($proposalData as $proposalComponent => $proposalDescriptions)
				//	foreach ($proposalDescriptions as $proposalDescriptionUri => $proposalDescription)
				//		$this->removeStmt(
				//			$healthstatesUri,
				//			"http://www.dispedia.de/o/receivedProposalDescription",
				//			$proposalDescriptionUri
				//		);
				
				// if deleted show message
				if (0 < $deletedStatements)
					$messages[] = new OntoWiki_Message($proposalUri . " " . $this->_translate->_('deleted'), OntoWiki_Message::SUCCESS);
			}
		}
		
		return $messages;
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
    public function addStmt($s, $p, $o)
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
    public function removeStmt($s, $p, $o)
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

