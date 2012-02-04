<?php
require_once 'Resource.php';

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
    private $_controller;
    private $_resource;
    private $_dispediaModel;
    private $_patientsModel;
    private $_lang;
    
    public function __construct ($controller, $lang, $patientsModel, $dispediaModel)
    {
        $this->_controller = $controller;
        $this->_resource = new Resource ($lang, $patientsModel, $dispediaModel);
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
                FILTER (langmatches(lang(?label), "' . $this->_lang . '") || !BOUND(?label))
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
    public function saveProposal($currentProposal)
    {
        $messages = array();
        $this->_dispediaModel->addStatement(
            $currentProposal['uri'],
            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
            array('value' => 'http://www.dispedia.de/o/Proposal', 'type' => 'uri')
        );

        $this->_dispediaModel->addStatement(
            $currentProposal['uri'],
            'http://www.w3.org/2000/01/rdf-schema#label', 
            array('value' => $currentProposal['label'], 'type' => 'literal', 'lang' => $this->_lang)
        );
        if (defined('_OWDEBUG'))
            $messages[] = new OntoWiki_Message('Proposal created: ' . $currentProposal['uri'], OntoWiki_Message::INFO);
        $messages[] = new OntoWiki_Message('successProposalEdit', OntoWiki_Message::SUCCESS);
        return $messages;
    }
    
    /**
    * 
    */
    public function removeProposal($currentProposal)
    {
        $messages = array();
        $result = $this->_resource->removeStmt (urldecode($currentProposal['uri']), null, null);
        
        if (defined('_OWDEBUG'))
        {
            $messages[] = new OntoWiki_Message('Proposal deleted: ' . $currentProposal['uri'], OntoWiki_Message::INFO);
            $messages[] = new OntoWiki_Message($result . ' tribles deleted', OntoWiki_Message::INFO);
        }
        
        if (0 < $result)
            $messages[] = new OntoWiki_Message('successProposalDelete', OntoWiki_Message::SUCCESS);
        else
            $messages[] = new OntoWiki_Message('errorProposalDelete', OntoWiki_Message::ERROR);
        return $messages;
    }
}

