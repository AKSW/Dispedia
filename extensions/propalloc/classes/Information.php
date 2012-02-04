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
    private $_lang;
    
    public function __construct ($lang, $patientsModel, $dispediaModel)
    {
        $this->_lang = $lang;
        $this->_patientsModel = $patientsModel;
        $this->_dispediaModel = $dispediaModel;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
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
    
}