<?php

require 'classes/Option.php';
require 'classes/Proposal.php';
require 'classes/Topic.php';

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
        
        // -------------------------------------------------------------
        
		$t = new Topic ($lang);
		$o = new Option ($lang, $this->_selectedModel);
        $p = new Proposal ();
        
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
						$options [] = str_replace ( 'als_dispedia_info', 'als.dispedia.info', $key );
					}
				}
				
				$o->saveOptions ( 
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
}

