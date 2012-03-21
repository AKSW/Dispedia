<?php

require_once 'classes/Action.php';
require_once 'classes/Proposal.php';
require_once 'classes/Patient.php';
require_once 'classes/Information.php';
require_once 'classes/Resource.php';
require_once 'classes/Topic.php';
require_once 'classes/Option.php';

/**
 * Controller for Propalloc.
 *
 * @category   OntoWiki
 * @package    OntoWiki_extensions_propalloc
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class PropallocController extends OntoWiki_Controller_Component
{
	private $_url;
        private $_lang;
	private $_patientsModel;
	private $_dispediaModel;
	
    /**
     * init controller
     */     
    public function init()
    {
        parent::init();
        $this->_url = $this->_config->urlBase .'propalloc/'; 
        
        $dispediaModel = new Erfurt_Rdf_Model ($this->_privateConfig->dispediaModel);
        $this->_dispediaModel = $dispediaModel;
        
        $model = new Erfurt_Rdf_Model ($this->_privateConfig->patientsModel);
        $this->_patientsModel = $model;
        
        // set standard language
        $this->_lang = true == isset ($_SESSION ['selectedLanguage'])
            ? $_SESSION ['selectedLanguage']
            : 'de';
            
        $this->view->headScript()->appendFile($this->_componentUrlBase .'libraries/jquery.tools.min.js');
    }
    
    
    /**
     * Action to view the Proposal overview
     */
    public function indexAction()
    {
        $p = new Proposal ($this, $this->_lang, $this->_patientsModel, $this->_dispediaModel);
        
        $this->view->proposals = (array) $p->getAllProposals ();
        $this->view->url = $this->_url;
        $this->view->imagesUrl = $this->_config->urlBase . 'extensions/propalloc/resources/images/';
    }
    
    /**
     * Action change patient in session
     */
     public function changepatientAction()
     {
        $currentPatientUri = urldecode($this->getParam ('curentPatientUri'));
        $_SESSION['selectedPatientUri'] = $currentPatientUri;
     }
    
    
    /**
     * Action to edit the Proposal Allocations
     */
    public function allocAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/alloc.css');
        
        
        $this->view->url = $this->_url;
        $this->view->currentProposal = $this->getParam ('proposal');
        
        // -------------------------------------------------------------
        
        $t = new Topic ($this->_lang);
        $o = new Option ($this->_lang, $this->_patientsModel, new Erfurt_Rdf_Model ($this->_privateConfig->patientsModel));
        $p = new Proposal ($this, $this->_lang, $this->_patientsModel, $this->_dispediaModel);
        
        $this->view->proposals = (array) $p->getAllProposals ();        
        $this->view->proposal = $p; 
        
        // -------------------------------------------------------------
        if ( '' != $this->getParam ('proposal') ) 
        {
			$options = array ();
			
			if ( 'save' == $this->getParam ('do') )
			{
				foreach ( $_REQUEST as $key => $value )
				{
					if ( 'selectedOption' == $value ) {
						$options [] = str_replace ( 'als_dispedia_de', 'als.dispedia.de', $key );
					}
				}
				
				$o->saveOptions ( 
                    new Erfurt_Rdf_Model ($this->_privateConfig->patientsModel),
					$p->getProposalUri ( $this->getParam ('proposal') ),
					$options, 
					$p->getSettings ( $this->getParam ('proposal') ) 
				);
			}  
						
            $topics = array ();
            
            foreach ( $t->getAllTopics () as $topic ) {
                
                $entry = array ();
                $entry ['label'] = $topic ['label'];
                
                foreach ( $o->getOptions ( $topic ['uri'] ) as $option ) {
                    
                    $entry ['options'][] = array (
                        'label' => $option ['label'],
                        'uri' => $option ['uri'],
                        'score' => $option ['score']
                    );                
                }
                
                $topics [] = $entry;
            }
            
            $this->view->topics = $topics;
            
            // get saved settings
            $this->view->settings = $p->getSettings ( $this->getParam ('proposal') );
        }
    }

    /**
     * Action to make add a new Proposal, with inforamtions and actions
     */
    public function editAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/proposal.css');
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/action.css');
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/information.css');
        $this->view->headScript()->appendFile($this->_componentUrlBase .'js/edit.js');
        
        $this->view->url = $this->_url;
        
        $currentProposalUri = urldecode($this->getParam ('proposalUri'));
        
        $resource = new Resource ($this->_lang, $this->_patientsModel, $this->_dispediaModel);
        $proposal = new Proposal ($this, $this->_lang, $this->_patientsModel, $this->_dispediaModel);   
        
        if (isset($currentProposalUri) && "" != $currentProposalUri)
        {
            $currentProposal = array();
            $currentProposal['uri'] = $currentProposalUri;
            $currentProposal['hash'] = substr ( md5 ($currentProposalUri), 0, 8 );
            $currentProposal['label'] = $resource->getLabel($currentProposalUri);
            $currentProposal['status'] = "edit";
            $actions = $proposal->getActions($currentProposalUri);
            if (0 < count($actions))
            {
                $currentProposal['actions'] = array();
                foreach ($actions as $action)
                {
                    $currentProposal['actions'][] = $this->actionAction($action['uri']);
                };
            }
            $this->view->currentProposal = $currentProposal;
        }
        else
        {
            $this->view->currentProposal = $resource->getNewInstance("Proposal");
        }
        
        if ( 'save' == $this->getParam ('do') )
        {
            $this->showMessage($proposal->saveProposal (
                $this->getParam ('currentProposal'),
                json_decode(urldecode($this->getParam ('currentProposalOldData')), true)
            ));
            $this->_forward('index');
        }
    }
    
    
    /**
     * Action to remove Proposal
     */
    public function removeAction ()
    {       
        $proposal = new Proposal ($this, $this->_lang, $this->_patientsModel, $this->_dispediaModel);
        $this->showMessage($proposal->removeProposal($this->getParam('currentProposal')));
        $this->_forward('index');
    }
    
    
    /**
     * get the Action Layout for dynamicly add new or existing action to a edit proposal Layout
     */
    public function actionAction ($actionUri = false)
    {
        if (false == $actionUri)
        {
            $this->view->actionOverClass = $this->getParam('entityOverClass');
            $resource = new Resource ($this->_lang, $this->_patientsModel, $this->_dispediaModel);
            $this->view->action = $resource->getNewInstance("Action");
        }
        else
        {
            $actionHelper = new Action ($this, $this->_lang, $this->_patientsModel, $this->_dispediaModel);
            if (!isset($this->view->actions))
                $this->view->actions = array();
                
            $action = $actionHelper->getAction($actionUri);
            
            $informations = $actionHelper->getInformations($actionUri);
            if (0 < count($informations))
            {
                $action['informations'] = array();
                foreach ($informations as $information)
                {
                    $action['informations'][] = $this->informationAction($information['uri']);
                }
            }
            $this->view->actions['action' . $action['hash']] = $action;
            return 'action' . $action['hash'];
        }
    }
    
    
    /**
     * get the Information Layout for dynamicly add new or existing information to a edit proposal Layout
     */
    public function informationAction ($informationUri = false)
    {
        $patient = new Patient ($this->_lang);
        $this->view->patientTypes = $patient->getAllPatientTypes();
        $this->view->therapistTypes = $patient->getAllTherapistTypes();
        
        if (false == $informationUri)
        {
            $this->view->informationOverClass = $this->getParam('entityOverClass');
            $resource = new Resource ($this->_lang, $this->_patientsModel, $this->_dispediaModel);
            $this->view->information = $resource->getNewInstance("Information");
            $this->view->information['content'] = "";
        }
        else
        {
            $informationHelper = new Information ($this->_lang, $this->_patientsModel, $this->_dispediaModel);
            if (!isset($this->view->informations))
                $this->view->informations = array();
            $information = $informationHelper->getInformation($informationUri);
            $this->view->informations['information' . $information['hash']] = $information;
            return 'information' . $information['hash'];
        }
    }
    
    /**
     * Action to remove Proposal
     */
    private function showMessage ($messages)
    {
        foreach ($messages as $message)
        {
            $this->_owApp->appendMessage($message);
        }
    }
}

