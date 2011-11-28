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
	protected $_url;
	protected $_selectedModel;
	
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
    }
    
    public function indexAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/index.css');
        
        $this->view->headScript()->appendFile($this->_componentUrlBase .'libraries/jquery.tools.min.js');
        
        $this->view->url = $this->_url;
        $this->view->currentProposal = $this->getParam ('proposal');
        
        // set standard language
        $lang = true == isset ($_SESSION ['selectedLanguage'])
            ? $_SESSION ['selectedLanguage']
            : 'de';
        
        $patient = new Patient($lang);
        $this->view->patients = $patient->getAllPatients();
        
        
        $this->view->options = $patient->getPatientOptions($this->getParam('patientUri'));
        $this->view->uri = $this->_url;
            
        
    }
}

