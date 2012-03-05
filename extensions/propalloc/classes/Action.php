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
class Action
{
    private $_dispediaModel;
    private $_patientsModel;
    private $_resource;
    private $_lang;
    
    public function __construct ($lang, $patientsModel, $dispediaModel)
    {
        $this->_lang = $lang;
        $this->_resource = new Resource ($lang, $patientsModel, $dispediaModel);
        $this->_patientsModel = $patientsModel;
        $this->_dispediaModel = $dispediaModel;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    /**
     * Function get get all Information of a Proposal.
     * Info, Actions aso.
     */
    public function getAction($actionUri)
    {
       // get actionLabel
        $actionLabelResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
            SELECT ?actionLabel
            WHERE {
                <' . $actionUri . '> rdfs:label ?actionLabel.
                FILTER (langmatches(lang(?actionLabel), "' . $this->_lang . '"))
            };'
        );
        

        $action = array();
        $action['uri'] = $actionUri;
        //TODO: muss schon aus dem store kommen, aber momentan gibt es noch instanzen ohne hash
        $action['hash'] = substr ( md5 ($actionUri), 0, 8 );
        $action['label'] = $actionLabelResults[0]['actionLabel'];
        $action['status'] = "edit";
        
        return $action;
    }
    
    /*
     * function saveAction
     * @param $currentAction, $currentActionOldData
     */
    
    function saveAction($currentAction, $currentActionOldData)
    {
        // array for output messages
        $messages = array();
        
        // make 'type' relation
        if ("new" == $currentAction['status'])
        {
            $this->_dispediaModel->addStatement(
                $currentAction['uri'],
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
                array('value' => 'http://www.dispedia.de/o/Action', 'type' => 'uri')
            );
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('Action created: ' . $currentAction['uri'] . ' => rdfs:type => http://www.dispedia.de/o/Action', OntoWiki_Message::INFO);
        }
        
        // make or update 'label' relation
        if ($currentAction['label'] != $currentActionOldData['label'])
        {
            $this->_dispediaModel->deleteMatchingStatements
            (
                $currentAction['uri'],
                'http://www.w3.org/2000/01/rdf-schema#label', 
                array('value' => null, 'type' => 'literal', 'lang' => $this->_lang)
            );
            $this->_dispediaModel->addStatement(
                $currentAction['uri'],
                'http://www.w3.org/2000/01/rdf-schema#label', 
                array('value' => $currentAction['label'], 'type' => 'literal', 'lang' => $this->_lang)
            );
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('Action label update: ' . $currentAction['uri'] . ' => rdfs:label => ' . $currentAction['label'] . ' (old: ' . $currentActionOldData['label'] . ')', OntoWiki_Message::INFO);
        }

        $messages[] = new OntoWiki_Message('successActionEdit', OntoWiki_Message::SUCCESS);
        return $messages;
    }
    
    /*
     * function removeAction
     * @param $currentAction
     */
    
    function removeAction($currentAction)
    {
        $messages = array();
        $result = $this->_resource->removeStmt(urldecode($currentAction['uri']), null, null);
        
        if (defined('_OWDEBUG'))
        {
            $messages[] = new OntoWiki_Message('Action deleted: ' . $currentAction['uri'], OntoWiki_Message::INFO);
            $messages[] = new OntoWiki_Message($result . ' tribles deleted', OntoWiki_Message::INFO);
        }
        
        if (0 < $result)
            $messages[] = new OntoWiki_Message('successActionDelete', OntoWiki_Message::SUCCESS);
        else
            $messages[] = new OntoWiki_Message('errorActionDelete', OntoWiki_Message::ERROR);
        return $messages;
    }
}

