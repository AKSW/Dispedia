<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_proppalloc
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class Information
{
    private $_dispediaModel;
    private $_patientsModel;
    private $_resource;
    private $_lang;
    private $_titleHelper;
    
    public function __construct ($lang, $patientsModel, $dispediaModel, $resource)
    {
        $this->_lang = $lang;
        $this->_resource = $resource;
        $this->_patientsModel = $patientsModel;
        $this->_dispediaModel = $dispediaModel;
        $this->_titleHelper = new OntoWiki_Model_TitleHelper ($dispediaModel);
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }

    /*
     * function getInformations
     * @param $actionUri
     */
    
    function getAllInformations($actionUri) {
        // get informationUri
        $informationResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            SELECT ?uri
            WHERE {
                <' . $actionUri . '> dispediao:containsInformation ?uri.
            };'
        );

        return $informationResults;
    }
    
    /**
     * Function get get all Information of a Proposal.
     * Info, Actions aso.
     */
    public function getInformation($informationUri)
    {
        // informationLabel
        $this->_titleHelper->addResource ($informationUri);
        
        $information = array();
        $information['uri'] = $informationUri;
        //TODO: muss schon aus dem store kommen, aber momentan gibt es noch instanzen ohne hash
        $information['hash'] = substr ( md5 ($informationUri), 0, 8 );
        $information['label'] = $this->_titleHelper->getTitle($informationUri, $this->_lang);
        $information['status'] = "edit";
        
        // get informationContent, informationSuitableFor, informationUsefulFor
        $informationResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            PREFIX rdf:<http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
            SELECT ?informationContent ?informationClass ?informationTherapistClass
            WHERE {
              {<' . $information['uri'] . '> dispediao:content ?informationContent.}
              UNION
              {<' . $information['uri'] . '> rdf:type ?informationClass.}
              UNION
              {<' . $information['uri'] . '> dispediao:usefulFor ?informationTherapistClass.}
            };'
        );
        
        $information['content'] = "";
        $information['informationClasses'] = array();
        $information['therapistClasses'] = array();
        // order the resultset
        foreach ($informationResults as $informationResult)
        {
            if ("" != $informationResult['informationContent'])
            {
                $information['content'] = $informationResult['informationContent'];
            }
            if ("" != $informationResult['informationClass'])
            {
                $information['informationClasses'][$informationResult['informationClass']] = $informationResult['informationClass'];
            }
            if ("" != $informationResult['informationTherapistClass'])
            {
                $information['therapistClasses'][$informationResult['informationTherapistClass']] = $informationResult['informationTherapistClass'];
            }
        }
        return $information;
    }
    
    /**
    * 
    */
    public function saveInformation($currentInformation, $currentInformationOldData)
    {        
        // array for output messages
        $messages = array();
        
        // check witch kind of information
        if (isset($currentInformation['informationClasses']))
            $informationClasses = $currentInformation['informationClasses'];
        else
            $informationClasses[] = 'http://www.dispedia.de/o/Information';
        
        //TODO: nicht einfach alle löschen sondern anhand der olddata nur die löschen und hinzufügen die sich geändert haben
        $this->_dispediaModel->deleteMatchingStatements
        (
            $currentInformation['uri'],
            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
            null
        );
        foreach ($informationClasses as $informationClass)
        {
            $this->_dispediaModel->addStatement(
                $currentInformation['uri'],
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
                array('value' => $informationClass, 'type' => 'uri')
            );
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('Information update: ' . $currentInformation['uri'] . ' => rdfs:type => ' . $informationClass, OntoWiki_Message::INFO);
        }
        
        
        // make or update 'label' relation
        if ($currentInformation['label'] != $currentInformationOldData['label'])
        {
            $this->_dispediaModel->deleteMatchingStatements
            (
                $currentInformation['uri'],
                'http://www.w3.org/2000/01/rdf-schema#label', 
                array('value' => null, 'type' => 'literal', 'lang' => $this->_lang)
            );
            $this->_dispediaModel->addStatement(
                $currentInformation['uri'],
                'http://www.w3.org/2000/01/rdf-schema#label', 
                array('value' => $currentInformation['label'], 'type' => 'literal', 'lang' => $this->_lang)
            );
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('Information label update: ' . $currentInformation['uri'] . ' => rdfs:label => ' . $currentInformation['label'] . ' (old: ' . $currentInformationOldData['label'] . ')', OntoWiki_Message::INFO);
        }
        
        // make or update 'content' relation
        if ($currentInformation['content'] != $currentInformationOldData['content'])
        {
            $this->_dispediaModel->deleteMatchingStatements
            (
                $currentInformation['uri'],
                'http://www.dispedia.de/o/content', 
                array('value' => null, 'type' => 'literal', 'lang' => $this->_lang)
            );
            $this->_dispediaModel->addStatement(
                $currentInformation['uri'],
                'http://www.dispedia.de/o/content', 
                array('value' => $currentInformation['content'], 'type' => 'literal', 'lang' => $this->_lang)
            );
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('Information content update: ' . $currentInformation['uri'] . ' => dispediao:content => ' . $currentInformation['content'] . ' (old: ' . $currentInformationOldData['content'] . ')', OntoWiki_Message::INFO);
        }

        $messages[] = new OntoWiki_Message('successInformationEdit', OntoWiki_Message::SUCCESS);
        return $messages;
    }
    
    /**
    * 
    */
    public function removeInformation($currentInformation)
    {
        $messages = array();
        $result = $this->_resource->removeStmt(urldecode($currentInformation['uri']), null, null);
        
        if (defined('_OWDEBUG'))
        {
            $messages[] = new OntoWiki_Message('Information deleted: ' . $currentInformation['uri'], OntoWiki_Message::INFO);
            $messages[] = new OntoWiki_Message($result . ' tribles deleted', OntoWiki_Message::INFO);
        }
        
        if (0 < $result)
            $messages[] = new OntoWiki_Message('successInformationDelete', OntoWiki_Message::SUCCESS);
        else
            $messages[] = new OntoWiki_Message('errorInformationDelete', OntoWiki_Message::ERROR);
        return $messages;
    }
}