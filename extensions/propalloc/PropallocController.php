<?php

require_once 'classes/Proposal.php';
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
    private $_proposal;
    private $_resource;
    private $_ontologies;
    private $_optionHelper;
    private $_titleHelper;
    
    // array for output messages
    private $_messages;
        
    /**
     * init controller
     */
    public function init()
    {
        parent::init();
        
        $this->_owApp->getNavigation()->disableNavigation();
        
        $this->_url = $this->_config->urlBase .'propalloc/';
        
        // init array for output messages
        $this->_messages = array();
        
        // get store
        $this->_store = Erfurt_App::getInstance()->getStore();
        // set standard language
        $this->_lang = OntoWiki::getInstance()->config->languages->locale;
        
        // get all models
        $this->_ontologies = $this->_config->ontologies->toArray();
        $this->_ontologies = $this->_ontologies['models'];
        $namespaces = array();
        // make model instances
        foreach ($this->_ontologies as $modelName => $model) {
            $this->_ontologies[$modelName]['instance'] = new Erfurt_Rdf_Model($model['namespace']);
            $namespaces[$model['namespace']] = $modelName;
        }
        $this->_ontologies['namespaces'] = $namespaces;

        $this->_optionHelper = new Option($this->_lang, $this->_ontologies, $this->_store);
        
        $this->_titleHelper = new OntoWiki_Model_TitleHelper($this->_ontologies['dispediaALS']['instance']);


        $this->_resource = new Resource1($this->_lang, $this->_ontologies, $this->_titleHelper);

        $this->_proposal = new Proposal(
            $this, $this->_lang,
            $this->_ontologies,
            $this->_resource,
            $this->_titleHelper
        );
        
        
        //TODO: if this deleted, there come an error but is this really needed?
        $this->view->headScript()->appendFile($this->_componentUrlBase .'libraries/jquery.tools.min.js');
    }
    
    /**
     * show messages after every action
     */
    public function postDispatch()
    {
        foreach ($this->_messages as $message) {
            $this->_owApp->appendMessage($message);
        }
    }

    /**
     * empty action
     */
    public function indexAction()
    {
        // disable rendering
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Action to edit the Proposal Allocations
     */
    public function allocAction ()
    {
        $topicHelper = new Topic($this->_lang);

        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/alloc.css');

        $this->view->url = $this->_url;
        
        // get selectedResource if it is set
        $selectedResource = $this->_owApp->__get("selectedResource");
        if (isset($selectedResource))
        {
            if('http://www.dispedia.de/o/Proposal' == $this->_resource->getClassUri($selectedResource)) 
                $this->view->currentProposal = $selectedResource->getIri();
            else
                $this->view->currentProposal = '';
        }
        else
            $this->view->currentProposal = '';

        $this->view->proposals = (array) $this->_proposal->getAllProposals();
        $this->view->proposal = $this->_proposal;
        
        if ("" != $this->view->currentProposal)
        {
            
            // build toolbar
            $toolbar = $this->_owApp->toolbar;
            $toolbar->appendButton(
                OntoWiki_Toolbar :: SUBMIT,
                array('name' => 'Save')
            );
            $this->view->placeholder('main.window.toolbar')->set($toolbar);

            $options = array ();
            //TODO: Change the access to the $_REQUEST object and use url encoding
            if ( 'save' == $this->getParam('do') ) {
                foreach ( $_REQUEST as $key => $value ) {
                    if ( 'selectedOption' == $value ) {
                        $options [] = str_replace('als_dispedia_de', 'als.dispedia.de', $key);
                    }
                }

                $this->_optionHelper->saveOptions(
                    $this->view->currentProposal,
                    $options,
                    $this->_proposal->getSettings($this->view->currentProposal)
                );
                $this->_messages[] = new OntoWiki_Message('successProposalAlloationEdit', OntoWiki_Message::SUCCESS);
            }

            $topics = array ();

            foreach ($topicHelper->getAllTopics() as $topic) {

                $entry = array ();
                $entry ['label'] = $topic ['label'];

                foreach ( $this->_optionHelper->getOptions($topic ['uri']) as $option) {

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
            $this->view->settings = $this->_proposal->getSettings($this->view->currentProposal);
        }
    }


    /**
     * action show the icf Tool from Christian Zinke
     * http://sysinno.uni-leipzig.de/innotool/icf/
     */
    //TODO: only simple integration with fix user and fix patient
    public function icfAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/icf.css');
        $this->view->url = $this->_url;
    }
}

