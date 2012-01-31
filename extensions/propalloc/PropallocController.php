<?php

require 'classes/Action.php';
require 'classes/Option.php';
require 'classes/Proposal.php';
require 'classes/Patient.php';
require 'classes/Topic.php';

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
	private $_selectedModel;
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
        $this->_selectedModel = $model;
        $this->_selectedModelUri = (string) $model;
        
        $this->_owApp->selectedModel = $model;
        
        // set standard language
        $this->_lang = true == isset ($_SESSION ['selectedLanguage'])
            ? $_SESSION ['selectedLanguage']
            : 'de';
    }
    
    
    /**
     * Action to view the Proposal overview
     */
    public function indexAction()
    {
        $t = new Topic ($this->_lang);
        $o = new Option ($this->_lang, $this->_selectedModel, new Erfurt_Rdf_Model ($this->_privateConfig->patientsModel));
        $p = new Proposal ($this->_lang, $this->_selectedModel, $this->_dispediaModel);
        
        $this->view->proposals = (array) $p->getAllProposals ();
        $this->view->url = $this->_url;
        $this->view->imagesUrl = $this->_config->urlBase . 'extensions/propalloc/resources/images/';
    }
    
    
    /**
     * Action to edit the Proposal Allocations
     */
    public function allocAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/index.css');
        
        $this->view->headScript()->appendFile($this->_componentUrlBase .'libraries/jquery.tools.min.js');
        
        $this->view->url = $this->_url;
        $this->view->currentProposal = $this->getParam ('proposal');
        
        // -------------------------------------------------------------
        
        $t = new Topic ($this->_lang);
        $o = new Option ($this->_lang, $this->_selectedModel, new Erfurt_Rdf_Model ($this->_privateConfig->patientsModel));
        $p = new Proposal ($this->_lang, $this->_selectedModel, $this->_dispediaModel);
        
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
     * Action to make add a new Proposal
     */
    public function editAction ()
    {
        $p = new Proposal ($this->_lang, $this->_selectedModel, $this->_dispediaModel);
        $currentProposalUri = urldecode($this->getParam ('proposalUri'));
        
        if (isset($currentProposalUri))
        {
            $patient = new Patient ($this->_lang);
            $this->view->patientTypes = $patient->getAllPatientTypes();
            $this->view->therapistTypes = $patient->getAllTherapistTypes();
            echo "<pre>";
            var_dump($this->view->therapistTypes);
            echo "</pre>";
            $this->view->currentProposal = $p->getInformations($currentProposalUri);
            echo "<pre>";
            var_dump($this->view->currentProposal);
            echo "</pre>";
        }
        
        if ( 'save' == $this->getParam ('do') )
        {
            $proposalUri = $p->saveProposal ( 
                $this->getParam ('proposalName'),
                $this->getParam ('proposalText') 
            );
            
            if ( '' != $this->getParam ('actionTitle') ) {            
                $action = new Action ($this->_lang, $this->_selectedModel, $this->_dispediaModel);
                $action->add ( 
                    $proposalUri, $this->getParam ('actionTitle'), $this->getParam ('actionText') 
                );
            }
        }  
    }
    
    /**
     * Action to remove Proposal
     */
    public function removeAction ()
    {   
        $a = new Action ($this->_lang, $this->_selectedModel, $this->_dispediaModel);
        $a->remove ( urldecode($this->getParam ('proposalUri')) );
        
        $p = new Proposal ($this->_lang, $this->_selectedModel, $this->_dispediaModel);
        $p->remove ( urldecode($this->getParam ('proposalUri')) );
    }
}

