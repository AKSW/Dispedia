<?php

require 'classes/Option.php';
require 'classes/Proposal.php';
require 'classes/Topic.php';

/**
 * Controller for Propalloc.
 *
 * @category   OntoWiki
 * @package    OntoWiki_extensions_formgenerator
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class PropallocController extends OntoWiki_Controller_Component
{
    /**
     * init controller
     */     
    public function init()
    {
        parent::init();
        $this->_url = $this->_request->uri;    
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
        
        $p = new Proposal ();
        
        $this->view->proposals = $p->getAllProposals ();        
        
        
        // -------------------------------------------------------------
        if ( '' != $this->getParam ('proposal') ) {
            $t = new Topic ($lang);
            $o = new Option ($lang);
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
        }
    }
}

