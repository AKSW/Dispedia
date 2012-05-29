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
            $this->_coreModel->deleteMatchingStatements
            (
                $currentSupporterClass['uri'],
                'http://www.w3.org/2000/01/rdf-schema#label', 
                array('value' => null, 'type' => 'literal', 'lang' => $this->_lang)
            );
            $this->_coreModel->addStatement(
                $currentSupporterClass['uri'],
                'http://www.w3.org/2000/01/rdf-schema#label', 
                array('value' => $currentSupporterClass['label'], 'type' => 'literal', 'lang' => $this->_lang)
            );
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('SupporterClass label update: ' . $currentSupporterClass['uri'] . ' => rdfs:label => ' . $currentSupporterClass['label'] . ' (old: ' . $currentSupporterClassOldData['label'] . ')', OntoWiki_Message::INFO);
        }
        
        $messages = array_merge(
            $messages,
            $this->saveSupporterClassProperties(
                isset($currentSupporterClass['properties']) ? $currentSupporterClass['properties'] : array(),
                isset($currentSupporterClassOldData['properties']) ? $currentSupporterClassOldData['properties'] : array(),
                $currentSupporterClass['uri']
            )
        );

        $messages[] = new OntoWiki_Message('successSupporterClassEdit', OntoWiki_Message::SUCCESS);
        return $messages;
    }
    
    
    /*
     * function return all properties of a supporter class
     */
    function getAllProperties($supporterClassUri)
    {
        $propertiesResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
            SELECT ?uri ?parentPropertyUri
            WHERE {
                ?uri rdfs:domain <' . $supporterClassUri . '>.
                OPTIONAL {
                    ?uri rdfs:subPropertyOf ?parentPropertyUri
                }
            };'
        );
        
        $properties = array ();
        $propertiesReturn = array ();
        
        foreach ( $propertiesResults as $property )
        {
            $this->_titleHelper->addResource ($property['uri']);
        }

        foreach ( $propertiesResults as $property )
        {
            $newProperty = array();
            $newProperty['uri'] = $property['uri'];
            $newProperty['label'] = $this->_titleHelper->getTitle($property['uri'], $this->_lang);
            if (isset($property['parentPropertyUri']) && "" != $property['parentPropertyUri'])
            {
                if(!isset($properties[$property['parentPropertyUri']]['subproperties']))
                    $properties[$property['parentPropertyUri']]['subproperties'] = array();
                    
                $properties[$property['parentPropertyUri']]['subproperties'][] = $newProperty;
            }
            else
            {
                if (isset($properties[$newProperty['uri']]))
                {
                    $properties[$newProperty['uri']] = array_merge($properties[$newProperty['uri']], $newProperty);
                }
                else
                    $properties[$newProperty['uri']] = $newProperty;
            }
        }
        
        //change the index from uris to numbers, this is needed to compare the new and old property arrays
        foreach ( $properties as $property )
        {
            $propertiesReturn[] = $property;
        }
        
        return $propertiesReturn;
    }
    
    /*
     * function saveSupporterClassProperty
     * @param $supporterClassProperties, $currentSupporterClassOldData, $parentProperty
     */
    function saveSupporterClassProperties($supporterClassProperties, $supporterClassPropertiesOldData, $supporterClassUri, $parentPropertyUri = null)
    {
        // array for output messages
        $messages = array();
        
        // remember the deleted and new properties
        $saveProperties = array_diff_assoc($supporterClassProperties, $supporterClassPropertiesOldData);
        $deleteProperties = array_diff_assoc($supporterClassPropertiesOldData, $supporterClassProperties);
        
        // rember the changed properties, they not deleted or new
        foreach ($supporterClassProperties as $propertyNumber => $property)
        {
            if (isset($supporterClassProperties[$propertyNumber]['label']) && isset($supporterClassPropertiesOldData[$propertyNumber]['label']))
            {
                if ($supporterClassProperties[$propertyNumber]['label'] != $supporterClassPropertiesOldData[$propertyNumber]['label'])
                    $saveProperties[] = $supporterClassProperties[$propertyNumber];
            }
        }
        
        // add all new properties
        foreach ($saveProperties as $propertyNumber => $property)
        {
            //if (!isset($property['uri']) || "" == $property['uri'])
                $property['uri'] = "http://www.dispedia.de/o/SupporterProperty#" . $property['label'];
            
            $messages = array_merge(
                $messages,
                $this->saveSupporterClassProperty(
                    $property,
                    isset($parentPropertyUri) ? $parentPropertyUri : null
                )
            );
            
            $this->_coreModel->addStatement(
                $property['uri'],
                'http://www.w3.org/2000/01/rdf-schema#domain', 
                array('value' => $supporterClassUri, 'type' => 'uri')
            );
            
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('Property to SupporterClass created: ' . $property['uri'] . ' => rdfs:domain => ' . $supporterClassUri, OntoWiki_Message::INFO);
            
        }
        
        // save subproperties
        foreach ($supporterClassProperties + $supporterClassPropertiesOldData as $propertyNumber => $property)
        {
            $messages = array_merge(
                $messages,
                $this->saveSupporterClassProperties(
                    isset($supporterClassProperties[$propertyNumber]['subproperties']) ? $supporterClassProperties[$propertyNumber]['subproperties'] : array(),
                    isset($supporterClassPropertiesOldData[$propertyNumber]['subproperties']) ? $supporterClassPropertiesOldData[$propertyNumber]['subproperties'] : array(),
                    $supporterClassUri,
                    // change that so only one uri generation
                    isset($property['uri']) && "" != $property['uri'] ? $property['uri'] : "http://www.dispedia.de/o/SupporterProperty#" . $property['label']
                )
            );
        }
        
        // delete all old properties
        foreach ($deleteProperties as $property)
        {
            $messages = array_merge($messages, $this->removeSupporterClassProperty($property));
        }
        
        return $messages;
    }
    /*
     * function saveSupporterClassProperty
     * @param $property, $parentPropertyUri
     */
    function saveSupporterClassProperty($property, $parentPropertyUri)
    {
        // array for output messages
        $messages = $this->removeSupporterClassProperty($property);

        $this->_coreModel->addStatement(
            $property['uri'],
            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
            array('value' => "http://www.w3.org/2002/07/owl#ObjectProperty", 'type' => 'uri')
        );
        $messages[] = new OntoWiki_Message('SupportClassProperty created: ' . $property['uri'] . ' => rdf:type => http://www.w3.org/2002/07/owl#ObjectProperty', OntoWiki_Message::INFO);

        $this->_coreModel->addStatement(
            $property['uri'],
            'http://www.w3.org/2000/01/rdf-schema#label', 
            array('value' => $property['label'], 'type' => 'literal', 'lang' => $this->_lang)
        );
        $messages[] = new OntoWiki_Message('SupportClassProperty created: ' . $property['uri'] . ' => rdfs:label => ' . $property['label'], OntoWiki_Message::INFO);

        $this->_coreModel->addStatement(
            $property['uri'],
            'http://www.w3.org/2000/01/rdf-schema#range', 
            array('value' => "http://www.dispedia.de/o/Person", 'type' => 'uri')
        );
        $messages[] = new OntoWiki_Message('SupportClassProperty created: ' . $property['uri'] . ' => rdfs:range => http://www.dispedia.de/o/Person', OntoWiki_Message::INFO);
        
        $this->_coreModel->addStatement(
            $property['uri'],
            'http://www.w3.org/2000/01/rdf-schema#range', 
            array('value' => "http://www.dispedia.de/o/Patient", 'type' => 'uri')
        );
        $messages[] = new OntoWiki_Message('SupportClassProperty created: ' . $property['uri'] . ' => rdfs:range => http://www.dispedia.de/o/Patient', OntoWiki_Message::INFO);
        
        if (isset($parentPropertyUri))
        {
            $this->_coreModel->addStatement(
                $property['uri'],
                'http://www.w3.org/2000/01/rdf-schema#subPropertyOf', 
                array('value' => $parentPropertyUri, 'type' => 'uri')
            );
            $messages[] = new OntoWiki_Message('SupportClassProperty created: ' . $property['uri'] . ' => rdfs:subPropertyOf => ' . $parentPropertyUri, OntoWiki_Message::INFO);
        }
        
        return $messages;
    }
    
    /*
     * function removeSupporterClassProperty
     * @param $property
     */
    function removeSupporterClassProperty($property)
    {
        // array for output messages
        $messages = array();
        
        $deletedStatements = $this->_resource->removeStmt
        (
            $property['uri'],
            null, 
            null
        );
        
        if (defined('_OWDEBUG'))
        {
            $messages[] = new OntoWiki_Message('SupporterClassProperty deleted: ' . $property['uri'] . ' => * => *', OntoWiki_Message::INFO);
            $messages[] = new OntoWiki_Message($deletedStatements . ' tribles deleted', OntoWiki_Message::INFO);
        }
        return $messages;
    }
}