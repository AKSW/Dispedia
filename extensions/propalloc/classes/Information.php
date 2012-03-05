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
class Information
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
    public function getInformations($proposalUri)
    {
        // get inforationUri and informationLabel
        $informationResults = $this->_store->sparqlQuery (
            'PREFIX dispediao:<http://www.dispedia.de/o/>
            PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
            SELECT ?informationUri ?informationLabel
            WHERE {
                <' . $proposalUri . '> dispediao:linkedToProposalInfo ?informationUri.
                ?informationUri rdfs:label ?informationLabel.
                FILTER (langmatches(lang(?informationLabel), "' . $this->_lang . '"))
            };'
        );
        
        $informations = array();
        foreach ($informationResults as $informationResult)
        {
            if ("" != $informationResult['informationUri'])
            {
                $information = array();
                $information['uri'] = $informationResult['informationUri'];
                //TODO: muss schon aus dem store kommen, aber momentan gibt es noch instanzen ohne hash
                $information['hash'] = substr ( md5 ($informationResult['informationUri']), 0, 8 );
                $information['label'] = $informationResult['informationLabel'];
                $information['status'] = "edit";
                $informations['information' . $information['hash']] = $information;
            }
        }
        
        // get informationContent, informationSuitableFor, informationUsefulFor
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
            
            $informations['information' . $information['hash']]['suitableFor'] = array();
            $informations['information' . $information['hash']]['usefulFor'] = array();
            // order the resultset
            foreach ($informationResults as $informationResult)
            {
                if ("" != $informationResult['informtionContent'])
                {
                    $informations['information' . $information['hash']]['content'] = $informationResult['informtionContent'];
                }
                if ("" != $informationResult['informtionSuitableFor'])
                {
                    $informations['information' . $information['hash']]['suitableFor'][$informationResult['informtionSuitableFor']] = "";
                }
                if ("" != $informationResult['informtionUsefulFor'])
                {
                    $informations['information' . $information['hash']]['usefulFor'][$informationResult['informtionUsefulFor']] = "";
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
    public function saveInformation($currentInformation, $currentInformationOldData)
    {        
        // array for output messages
        $messages = array();
        
        // make 'type' relation
        if ("new" == $currentInformation['status'])
        {
            $this->_dispediaModel->addStatement(
                $currentInformation['uri'],
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
                array('value' => 'http://www.dispedia.de/o/Information', 'type' => 'uri')
            );
            if (defined('_OWDEBUG'))
                $messages[] = new OntoWiki_Message('Information created: ' . $currentInformation['uri'] . ' => rdfs:type => http://www.dispedia.de/o/Information', OntoWiki_Message::INFO);
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