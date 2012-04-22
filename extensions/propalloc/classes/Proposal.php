<?php
require_once 'Action.php';

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
    private $_dispediaModel;
    private $_patientsModel;
    private $_titleHelper;
    private $_lang;
    
    public function __construct ($controller, $lang, $patientsModel, $dispediaModel, $resource, $titleHelper)
    {
        $this->_controller = $controller;
        $this->_resource = $resource;
        $this->_action = new Action ($controller, $lang, $patientsModel, $dispediaModel, $resource);
        $this->_titleHelper = $titleHelper;
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
        $proposalResult = $this->_store->sparqlQuery (
            'SELECT ?uri
              WHERE {
                 ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.dispedia.de/o/Proposal>.
             };'
        );
        
        $proposals = array ();
        
        foreach ( $proposalResult as $proposal )
        {
            $this->_titleHelper->addResource ($proposal['uri']);
        }

        foreach ( $proposalResult as $proposal )
        {
            $newProposal['shortcut'] = md5 ( $proposal ['uri'] );
            $newProposal['uri'] = $proposal['uri'];
            $newProposal['label'] = $this->_titleHelper->getTitle($proposal['uri'], $this->_lang);
            $proposals[] = $newProposal;
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
    
    /*
     * function getActions
     * @param $proposalUri
     */
    
    function getActions($proposalUri) {
        // get actionUri
        $actionResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            SELECT ?uri
            WHERE {
                <' . $proposalUri . '> dispediao:containsAction ?uri.
            };'
        );

        return $actionResults;
    }
    
    /**
    * 
    */
    public function saveProposal($currentProposal, $currentProposalOldData)
    {        
        // array for output messages
        $messages = array();
        
        // make 'type' relation
        if ("new" == $currentProposal['status'])
        {
            $this->_dispediaModel->addStatement(
                $currentProposal['uri'],
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
                array('value' => 'http://www.dispedia.de/o/Proposal', 'type' => 'uri')
            );
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('Proposal created: ' . $currentProposal['uri'] . ' => rdfs:type => http://www.dispedia.de/o/Proposal', OntoWiki_Message::INFO);
        }
        
        // make or update 'label' relation
        if ($currentProposal['label'] != $currentProposalOldData['label'])
        {
            $this->_dispediaModel->deleteMatchingStatements
            (
                $currentProposal['uri'],
                'http://www.w3.org/2000/01/rdf-schema#label', 
                array('value' => null, 'type' => 'literal', 'lang' => $this->_lang)
            );
            $this->_dispediaModel->addStatement(
                $currentProposal['uri'],
                'http://www.w3.org/2000/01/rdf-schema#label', 
                array('value' => $currentProposal['label'], 'type' => 'literal', 'lang' => $this->_lang)
            );
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('Proposal label update: ' . $currentProposal['uri'] . ' => rdfs:label => ' . $currentProposal['label'] . ' (old: ' . $currentProposalOldData['label'] . ')', OntoWiki_Message::INFO);
        }
        
        if (isset($currentProposal['actions']))
        {
            foreach ($currentProposal['actions'] as $actionName)
            {
                $action = $this->_controller->getParam($actionName);
            
                $actionOldData = json_decode(urldecode($this->_controller->getParam($actionName . 'OldData')), true);
                if ("new" == $action['status'])
                {
                    $this->_dispediaModel->addStatement(
                        $currentProposal['uri'],
                        'http://www.dispedia.de/o/containsAction', 
                        array('value' => $action['uri'], 'type' => 'uri')
                    );
                    if (defined('_OWDEBUG'))
                        $messages[] = new OntoWiki_Message('Proposal to Action created: ' . $currentProposal['uri'] . ' => dispediao:containsAction => ' . $action['uri'], OntoWiki_Message::INFO);
                }
                $messages = array_merge($messages, $this->_action->saveAction($action, $actionOldData));
            }

            if (isset($currentProposalOldData['actions']))
            {
                foreach (array_diff($currentProposalOldData['actions'], $currentProposal['actions']) as $action)
                {
                    $actionOldData = json_decode(urldecode($this->_controller->getParam($action . 'OldData')), true);
                    $deletedStatements = $this->_resource->removeStmt
                    (
                        $currentProposal['uri'],
                        'http://www.dispedia.de/o/containsAction', 
                        $actionOldData['uri']
                    );
                    if (defined('_OWDEBUG'))
                    {
                        $messages[] = new OntoWiki_Message('Proposal to Action deleted: ' . $currentProposal['uri'] . ' => dispediao:containsAction => ' . $actionOldData['uri'], OntoWiki_Message::INFO);
                        $messages[] = new OntoWiki_Message($deletedStatements . ' tribles deleted', OntoWiki_Message::INFO);
                    }
                    $messages = array_merge($messages, $this->_action->removeAction($actionOldData));
                }
            }
        }
        else
        {
            if (isset($currentProposalOldData['actions']))
            {
                foreach ($currentProposalOldData['actions'] as $action)
                {
                    $actionOldData = json_decode(urldecode($this->_controller->getParam($action . 'OldData')), true);
                    $deletedStatements = $this->_resource->removeStmt
                    (
                        $currentProposal['uri'],
                        'http://www.dispedia.de/o/containsAction', 
                        $actionOldData['uri']
                    );
                    if (defined('_OWDEBUG'))
                    {
                        $messages[] = new OntoWiki_Message('Proposal to Action deleted: ' . $currentProposal['uri'] . ' => dispediao:containsAction => ' . $actionOldData['uri'], OntoWiki_Message::INFO);
                        $messages[] = new OntoWiki_Message($deletedStatements . ' tribles deleted', OntoWiki_Message::INFO);
                    }
                    $messages = array_merge($messages, $this->_action->removeAction($actionOldData));
                }
            }
        }
        
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

