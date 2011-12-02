<?php

require 'classes/Option.php';
require 'classes/Proposal.php';
require 'classes/Topic.php';
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
        
    
        $model = new Erfurt_Rdf_Model ($this->_privateConfig->defaultModel);
        $this->_selectedModel = $model;
        $this->_selectedModelUri = (string) $model;
        $this->_owApp->selectedModel = $model;
    
    // set standard language
        $this->_lang = true == isset ($_SESSION ['selectedLanguage'])
            ? $_SESSION ['selectedLanguage']
            : 'de';
        
        $this->_patient = new Patient($this->_lang);
    $this->_proposal = new Proposal($this->_selectedModel);
            
        $this->view->headScript()->appendFile($this->_componentUrlBase .'libraries/jquery.tools.min.js');
    }
    
    public function indexAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/index.css');
        
        $this->view->url = $this->_url;
        $this->view->currentProposal = $this->getParam ('proposal');

        $currentPatient = $this->getParam('patientUri');

    if ( '' != $currentPatient ) 
    {
        $proposals = array ();
        
        if ( 'save' == $this->getParam ('do') )
        {
            foreach ( $_REQUEST as $key => $value )
            {
                if ( 'selectedOption' == $value ) {
                    $proposals [] = str_replace ( 'als_dispedia_info', 'als.dispedia.info', $key );
                }
            }
            //TODO: auf Erfolg prüfen
            $this->_proposal->saveProposals (
                $currentPatient,
                $proposals
            );
        }
    }

        $this->view->patients = $this->_patient->getAllPatients();
    $this->view->currentPatient = $currentPatient;
    $this->view->options = $this->_patient->getPatientOptions($currentPatient);
    
        $this->view->proposals = $this->_proposal->getAllProposals($currentPatient);
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

