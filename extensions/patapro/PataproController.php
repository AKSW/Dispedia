<?php

require 'classes/Proposal.php';
require 'classes/Patient.php';

/**
 * Controller for Propalloc.
 *
 * @category   OntoWiki
 * @package    OntoWiki_extensions_patapro
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class PataproController extends OntoWiki_Controller_Component
{
    private $_url;
    private $_patientModel;
    private $_dispediaModel;
    private $_alsfrsModel;
    private $_selectedModel;
    private $_lang;
    private $_patient;
    private $_proposal;
    
    /**
     * init controller
     */     
    public function init()
    {
        parent::init();
        $this->_url = $this->_request->uri;   
        
        $this->_patientModel = new Erfurt_Rdf_Model ($this->_privateConfig->patientModel);
        $this->_dispediaModel = new Erfurt_Rdf_Model ($this->_privateConfig->dispediaModel);
        $this->_alsfrsModel = new Erfurt_Rdf_Model ($this->_privateConfig->alsfrsModel);
        $this->_owApp->selectedModel = $this->_patientModel;
    
        // set standard language
        $this->_lang = true == isset ($_SESSION ['selectedLanguage'])
            ? $_SESSION ['selectedLanguage']
            : 'de';
        
        $this->_patient = new Patient($this->_lang);
        $this->_proposal = new Proposal($this->_patientModel, $this->_lang);
            
        $this->view->headScript()->appendFile($this->_componentUrlBase .'libraries/jquery.tools.min.js');
    }
    
    public function indexAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/index.css');
        
        $this->view->url = $this->_url;

        $currentPatient = $this->getParam('patientUri');

        if ( '' != $currentPatient ) 
        {
            if ( 'save' == $this->getParam ('do') )
            {
                //TODO: auf Erfolg prüfen
                $this->_proposal->saveProposals (
                    $currentPatient,
                    $this->getParam ('proposals')
                );
            }
            
            $this->view->currentPatient = $currentPatient;
            $patientOptions = $this->_patient->getPatientOptions($currentPatient);
            $this->view->options = $patientOptions['sorted'];
            
            $allProposals = $this->_proposal->getAllProposals();
            $patientProposals = $this->_proposal->getAllDecisinProposals($currentPatient);
            
            foreach ($allProposals as $proposalUri => $proposal)
            {
                if (isset($patientProposals[$proposalUri]))
                $allProposals[$proposalUri] = $patientProposals[$proposalUri];
            }
            $allProposals = $this->_proposal->calcCorrespondenceProposals($patientOptions['uriList'], $allProposals);
            $this->view->proposals = $allProposals;
            echo "<pre>";
            var_dump($patientOptions['sorted']);
            echo "</pre>";
        }
        else
            $this->view->currentPatient = $currentPatient;
        
        $this->view->patients = $this->_patient->getAllPatients();
    }
    public function patientAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/patient.css');
        $this->view->url = $this->_url;
        $currentPatient = $this->getParam('patientUri');
        if ( '' != $currentPatient ) 
        {
            if ( 'save' == $this->getParam ('do') )
            {
                $decisionProposals = $this->_proposal->getAllDecisinProposals($currentPatient);
                foreach ($decisionProposals as $decisionProposal)
                {
                    $decision = $this->getParam(base64_encode($decisionProposal['decision']));
                    if (isset($decision))
                        //TODO: auf Erfolg prüfen
                        $this->_proposal->saveDecision($decisionProposal['proposalAllocation'], $decisionProposal['decision'], $decision);
                }
            }
            $this->view->decisionProposals = $this->_proposal->getAllDecisinProposals($currentPatient);
        }
        $this->view->patients = $this->_patient->getAllPatients();
        $this->view->currentPatient = $currentPatient;
    }
}

