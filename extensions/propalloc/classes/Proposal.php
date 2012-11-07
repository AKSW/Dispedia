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
    private $_controller;
    private $_resource;
    private $_action;
    private $_ontologies;
    private $_dispediaModel;
    private $_patientsModel;
    private $_titleHelper;
    private $_lang;
    
    public function __construct ($controller, $lang, $ontologies, $resource, $titleHelper)
    {
        $this->_controller = $controller;
        $this->_resource = $resource;
        $this->_lang = $lang;
        $this->_ontologies = $ontologies;
        $this->_titleHelper = $titleHelper;
        $this->_store = Erfurt_App::getInstance()->getStore();
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
        foreach ( $proposalResult as $proposal )
        {
            $this->_titleHelper->addResource ($proposal['uri']);
        }

        foreach ( $proposalResult as $proposal )
        {
            $newProposal['uri'] = $proposal['uri'];
            $newProposal['label'] = $this->_titleHelper->getTitle($proposal['uri'], $this->_lang);
            $proposals[$newProposal['uri']] = $newProposal;
        }

        return $proposals;
    }
    
    /**
     * 
     */
    public function getSettings ( $proposalUri ) 
    {
        $optionUris = array ();
            
        $symptomsOptions = $this->_store->sparqlQuery (
            'SELECT ?optionUri
              WHERE {
                 <'. $proposalUri .'> <http://www.dispedia.de/o/appropriateForSymptoms> ?ss .
                 ?ss <http://www.dispedia.de/wrapper/alsfrs/containsSymptomOption> ?optionUri .
             };'
        );        
            
        $propertyOptions = $this->_store->sparqlQuery (
            'SELECT ?optionUri
              WHERE {
                 <'. $proposalUri .'> <http://www.dispedia.de/o/appropriateForProperties> ?ps .
                 ?ps <http://www.dispedia.de/wrapper/alsfrs/containsPropertyOption> ?optionUri .
             };'
        );
        
        
        foreach ( $propertyOptions as $p )
            $optionUris [] = $p ['optionUri'];
        
        foreach ( $symptomsOptions as $p )
            $optionUris [] = $p ['optionUri'];
	
        return $optionUris;
    }
}

