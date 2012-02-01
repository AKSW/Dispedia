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
    public function getProposalLabel ($proposalUri)
    {
        $proposalLabelResult = $this->_store->sparqlQuery (
            'SELECT ?proposalLabel
              WHERE {
                 <' . $proposalUri . '> <http://www.w3.org/2000/01/rdf-schema#label> ?proposalLabel.
                FILTER (langmatches(lang(?proposalLabel), "' . $this->_lang . '"))
             };'
        );
        return $proposalLabelResult[0]['proposalLabel'];
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
     * Fucntion get get all Information of a Proposal.
     * Info, Actions aso.
     */
    public function getInformations($proposalUri)
    {
       // get proposalLabel, inforationUri and informationLabel
        $informationResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
            SELECT ?proposalLabel ?informationUri ?informationLabel
            WHERE {
                <' . $proposalUri . '> dispediao:linkedToProposalInfo ?informationUri.
                ?informationUri rdfs:label ?informationLabel.}
                FILTER (langmatches(lang(?informationLabel), "' . $this->_lang . '")) || !BOUND(?informationLabel)
            };'
        );
        
        $informations = array();
        foreach ($informationResults as $informationResult)
        {
            if ("" != $informationResult['informationUri'])
            {
                $information = array();
                //TODO: muss schon aus dem store kommen, aber momentan gibt es noch instanzen ohne hash
                $information['hash'] = substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
                $information['uri'] = $informationResult['informationUri'];
                $information['label'] = $informationResult['informationLabel'];
                $informations[$informationResult['informationUri']] = $information;
            }
        }
        
        // get informtionContent, informtionSuitableFor, informtionUsefulFor
        foreach ($informations as $information)
        {
            $informationResults = $this->_store->sparqlQuery (
                'PREFIX dispediao:<http://www.dispedia.de/o/>
                PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
                SELECT ?informtionContent ?informtionSuitableFor ?informtionUsefulFor
                WHERE {
                  {<' . $information['uri'] . '> dispediao:content ?informtionContent.}
                  UNION
                  {<' . $information['uri'] . '> dispediao:suitableFor ?informtionSuitableFor.}
                  UNION
                  {<' . $information['uri'] . '> dispediao:usefulFor ?informtionUsefulFor.}
                };'
            );
            
            $informations[$information['uri']]['suitableFor'] = array();
            $informations[$information['uri']]['usefulFor'] = array();
            // order the resultset
            foreach ($informationResults as $informationResult)
            {
                if ("" != $informationResult['informtionContent'])
                {
                    $informations[$information['uri']]['content'] = $informationResult['informtionContent'];
                }
                if ("" != $informationResult['informtionSuitableFor'])
                {
                    $informations[$information['uri']]['suitableFor'][$informationResult['informtionSuitableFor']] = "";
                }
                if ("" != $informationResult['informtionUsefulFor'])
                {
                    $informations[$information['uri']]['usefulFor'][$informationResult['informtionUsefulFor']] = "";
                }
            }
        }
        
        return $informations;
        //?informationUri dispediao:content ?informationContent.
        //?informationUri dispediao:suitableFor ?informationPatientType.
        //?informationUri dispediao:usefulFor ?informationTherapistType.
    }
    
     /**
    * 
    */
    public function getNewProposalInformation()
    {
        $result = array();
        $result['hash'] = substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
        $result['uri'] = "http://als.dispedia.de/i/Information/" . $result['hash'];
        return $result;
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
                        null,
                        null);
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

