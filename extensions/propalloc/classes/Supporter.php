<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_proppalloc
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class Supporter
{
    private $_lang;
    private $_controller;
    private $_resource;
    private $_dispediaModel;
    private $_coreModel;
    private $_titleHelper;
    
    public function __construct ($controller, $lang, $coreModel, $dispediaModel, $resource)
    {
        $this->_lang = $lang;
        $this->_controller = $controller;
        $this->_resource = $resource;
        $this->_titleHelper = new OntoWiki_Model_TitleHelper ($dispediaModel);
        $this->_dispediaModel = $dispediaModel;
        $this->_coreModel = $coreModel;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    /*
     * function return a array with all supporter Classes
     */
    
    function getAllSupporterClasses()
    {
        $supporterClassResult = $this->_store->sparqlQuery (
            'SELECT ?uri
              WHERE {
                 ?uri <http://www.w3.org/2000/01/rdf-schema#subClassOf> <http://www.dispedia.de/o/Supporter>.
             };'
        );
        
        $supporterClasses = array ();
        
        foreach ( $supporterClassResult as $supporterClass )
        {
            $this->_titleHelper->addResource ($supporterClass['uri']);
        }

        foreach ( $supporterClassResult as $supporterClass )
        {
            $newSupporterClass['uri'] = $supporterClass['uri'];
            $newSupporterClass['label'] = $this->_titleHelper->getTitle($supporterClass['uri'], $this->_lang);
            $supporterClasses[] = $newSupporterClass;
        }

        return $supporterClasses;
    }
    
    /*
     * function saveSupporterClass
     * @param $currentAction, $currentActionOldData
     */
    
    function saveSupporterClass($currentSupporterClass, $currentSupporterClassOldData)
    {
        // array for output messages
        $messages = array();
        
        
        //TODO: in which model to write
        // make 'type' and 'subClass' relation
        if ("new" == $currentSupporterClass['status'])
        {
            $currentSupporterClass['uri'] = "http://www.dispedia.de/o/SupporterClass#" . $currentSupporterClass['label'];
            
            $this->_coreModel->addStatement(
                $currentSupporterClass['uri'],
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
                array('value' => 'http://www.w3.org/2002/07/owl#Class', 'type' => 'uri')
            );
            
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('SupporterClass created: ' . $currentSupporterClass['uri'] . ' => rdfs:type => http://www.w3.org/2002/07/owl#Class', OntoWiki_Message::INFO);
        
            $this->_coreModel->addStatement(
                $currentSupporterClass['uri'],
                'http://www.w3.org/2000/01/rdf-schema#subClassOf', 
                array('value' => 'http://www.dispedia.de/o/Supporter', 'type' => 'uri')
            );
            
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('SupporterClass created: ' . $currentSupporterClass['uri'] . ' => rdfs:subClassOf => http://www.dispedia.de/o/Supporter', OntoWiki_Message::INFO);
        }
        
        // make or update 'label' relation
        if ($currentSupporterClass['label'] != $currentSupporterClassOldData['label'])
        {
            $this->_dispediaModel->deleteMatchingStatements
            (
                $currentSupporterClass['uri'],
                'http://www.w3.org/2000/01/rdf-schema#label', 
                array('value' => null, 'type' => 'literal', 'lang' => $this->_lang)
            );
            $this->_dispediaModel->addStatement(
                $currentSupporterClass['uri'],
                'http://www.w3.org/2000/01/rdf-schema#label', 
                array('value' => $currentSupporterClass['label'], 'type' => 'literal', 'lang' => $this->_lang)
            );
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('SupporterClass label update: ' . $currentSupporterClass['uri'] . ' => rdfs:label => ' . $currentSupporterClass['label'] . ' (old: ' . $currentSupporterClassOldData['label'] . ')', OntoWiki_Message::INFO);
        }
        
        //if (isset($currentSupporterClass['properties']))
        //{
        //    foreach ($currentSupporterClass['properties'] as $propertyNumber => $property)
        //    {
        //        $messages = array_merge($messages, $this->_information->saveSupporterClassProperty($property, $currentSupporterClass['properties'][$propertyNumber]));
        //    }
        //
        //    if (isset($currentSupporterClassOldData['properties']))
        //    {
        //        foreach (array_diff($currentSupporterClassOldData['informations'], $currentSupporterClass['informations']) as $information)
        //        {
        //            $informationOldData = json_decode(urldecode($this->_controller->getParam($information . 'OldData')), true);
        //            $deletedStatements = $this->_resource->removeStmt
        //            (
        //                $currentSupporterClass['uri'],
        //                'http://www.dispedia.de/o/containsInformation', 
        //                $informationOldData['uri']
        //            );
        //            
        //            if (defined('_OWDEBUG'))
        //            {
        //                $messages[] = new OntoWiki_Message('SupporterClass to Information deleted: ' . $currentSupporterClass['uri'] . ' => dispediao:containsInformation => ' . $informationOldData['uri'], OntoWiki_Message::INFO);
        //                $messages[] = new OntoWiki_Message($deletedStatements . ' tribles deleted', OntoWiki_Message::INFO);
        //            }
        //            $messages = array_merge($messages, $this->_information->removeInformation($informationOldData));
        //        }
        //    }
        //}
        //else
        //{
        //    if (isset($currentSupporterClassOldData['informations']))
        //    {
        //        foreach ($currentSupporterClassOldData['informations'] as $information)
        //        {
        //            $informationOldData = json_decode(urldecode($this->_controller->getParam($information . 'OldData')), true);
        //            $deletedStatements = $this->_resource->removeStmt
        //            (
        //                $currentSupporterClass['uri'],
        //                'http://www.dispedia.de/o/containsInformation', 
        //                $informationOldData['uri']
        //            );
        //            if (defined('_OWDEBUG'))
        //            {
        //                $messages[] = new OntoWiki_Message('SupporterClass to Information deleted: ' . $currentSupporterClass['uri'] . ' => dispediao:containsInformation => ' . $informationOldData['uri'], OntoWiki_Message::INFO);
        //                $messages[] = new OntoWiki_Message($deletedStatements . ' tribles deleted', OntoWiki_Message::INFO);
        //            }
        //            $messages = array_merge($messages, $this->removeInformation($informationOldData));
        //        }
        //    }
        //}
        
        $messages[] = new OntoWiki_Message('successSupporterClassEdit', OntoWiki_Message::SUCCESS);
        return $messages;
    }
    
/*
     * function saveSupporterClass
     * @param $currentAction, $currentActionOldData
     */
    
    function saveSupporterClassProperty($SupporterClassProperty, $currentSupporterClassPropertyOldData)
    {
        // array for output messages
        $messages = array();
        
        $supportPropertyUri = "http://www.dispedia.de/o/SupporterProperty#" . $property['label'];
        $this->_coreModel->addStatement(
            $supportPropertyUri,
            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
            array('value' => "http://www.w3.org/2002/07/owl#ObjectProperty", 'type' => 'uri')
        );
        $this->_coreModel->addStatement(
            $supportPropertyUri,
            'http://www.w3.org/2000/01/rdf-schema#label', 
            array('value' => $property['label'], 'type' => 'literal', 'lang' => $this->lang)
        );
        $this->_coreModel->addStatement(
            $supportPropertyUri,
            'http://www.w3.org/2000/01/rdf-schema#range', 
            array('value' => "http://www.dispedia.de/o/Person", 'type' => 'uri')
        );
        $this->_coreModel->addStatement(
            $supportPropertyUri,
            'http://www.w3.org/2000/01/rdf-schema#range', 
            array('value' => "http://www.dispedia.de/o/Patient", 'type' => 'uri')
        ); 
        $this->_coreModel->addStatement(
            $supportPropertyUri,
            'http://www.w3.org/2000/01/rdf-schema#domain', 
            array('value' => $currentSupporterClassUri, 'type' => 'uri')
        );
        
        return $messages;
    }
}