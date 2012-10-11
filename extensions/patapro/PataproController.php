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
    private $_ontologies;
    private $_lang;
    private $_patient;
    private $_proposal;
    private $_titleHelper;
    private $_translate;
    private $_store;
    
    // array for output messages
    private $_messages;

    /**
     * init controller
     */
    public function init()
    {
        parent::init();
        
        $on = new OntoWiki_Navigation();
        $on->disableNavigation();
        
        $this->_url = $this->_config->urlBase .'patapro/';
        
        // init array for output messages
        $this->_messages = array();

        // get all models
        $this->_ontologies = $this->_config->ontologies->toArray();
        $this->_ontologies = $this->_ontologies['models'];
        // make model instances
        foreach ($this->_ontologies as $modelName => $model) {
            $this->_ontologies[$modelName]['instance'] = $dispediaModel = new Erfurt_Rdf_Model($model['namespace']);
        }
        
        //TODO:change this to global ontology array
        $this->_patientModel = $this->_ontologies['dispediaPatient']['instance'];
        $this->_dispediaModel = $this->_ontologies['dispediaCore']['instance'];
        $this->_alsfrsModel = $this->_ontologies['dispediaALS']['instance'];
        $this->_titleHelper = new OntoWiki_Model_TitleHelper ($this->_alsfrsModel);
        $this->_translate = $this->_owApp->translate;
        
        $this->_store = Erfurt_App::getInstance()->getStore();

        // set standard language
        $this->_lang = OntoWiki::getInstance()->config->languages->locale;

        $this->_patient = new Patient($this->_lang);
        $this->_proposal = new Proposal($this->_patientModel, $this->_lang, $this->_titleHelper);

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
      * get the classes of an resource
      */
    private function getClasses($currentResource)
    {
        $resources = array();
        $resourceClassesResult = $this->_store->sparqlQuery (
            'SELECT ?class
            WHERE {
                <' . $currentResource . '> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?class.
                }
            };'
        );
        
        foreach($resourceClassesResult as $resource) {
            $resources[$resource['class']] = $resource['class'];
        }
        
        return $resources;
    }

    /**
      * Action to make proposal for a patient
      */
    public function indexAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/index.css');
        $this->view->headScript()->appendFile($this->_componentUrlBase .'js/index.js');

        $this->view->url = $this->_url;

        $currentPatient = "";
        
        // get selectedResource if it is set
        $selectedResource = $this->_owApp->__get("selectedResource");
        if (isset($selectedResource))
        {
            $selectedResourceUri = $selectedResource->getUri();
            if (in_array("http://www.dispedia.de/o/Patient", $this->getClasses($selectedResourceUri)))
                $currentPatient = $selectedResourceUri;
        }

        $this->view->currentPatient = $currentPatient;
        if ( '' != $currentPatient )
        {
            // build toolbar
            $toolbar = $this->_owApp->toolbar;
            $toolbar->appendButton(OntoWiki_Toolbar :: SUBMIT, array(
                'name' => 'Save'
            ));
            $this->view->placeholder('main.window.toolbar')->set($toolbar);

            if ( 'save' == $this->getParam ('do') )
            {
                //TODO: auf Erfolg prüfen
                $this->_proposal->saveProposals (
                    $currentPatient,
                    $this->getParam ('proposals')
                );
            }

            // get a list of all healthstates
            $this->view->healthstates = $this->_patient->getAllHealthstates($currentPatient);

            $allProposals = $this->_proposal->getAllProposals();
            $patientProposals = $this->_proposal->getAllDecisinProposals($currentPatient);

            foreach ($allProposals as $proposalUri => $proposal)
            {
                if (isset($patientProposals[$proposalUri]))
                    $allProposals[$proposalUri] = $patientProposals[$proposalUri];
            }


            $healthstateUris = array_keys($this->view->healthstates);

            //get the options from the last healthstate
            $patientOptions = $this->healthstateAction(array_shift($healthstateUris));

            // only if patientOptions not empty
            if (0 < count($patientOptions))
            {
                // compare the patient proposals with the template proposals
                $allProposals = $this->_proposal->calcCorrespondenceProposals($patientOptions['uriList'], $allProposals);
            }

            foreach ($allProposals as $proposalUri => $proposal)
            {
                $allProposals[$proposalUri]['components'] = $this->_proposal->getProposalData($proposalUri);
            }
            
            $this->view->patientUri = $currentPatient;
            $this->view->proposals = $allProposals;
        }
        else
        {
            $message = new OntoWiki_Message($this->_translate->_('nopatientselected'), OntoWiki_Message::WARNING);
            $this->_owApp->appendMessage($message);
        }
    }

    /**
     * get proposaldata for ajax
     */
    public function proposaldataAction()
    {
        $proposalUri = urldecode($this->getParam ('proposalUri'));
        $patientUri = urldecode($this->getParam ('patientUri'));
        if (isset($proposalUri) && "" != $proposalUri && isset($patientUri) && "" != $patientUri)
        {
            $this->view->patientUri = $patientUri;
            $this->view->proposalUri = $proposalUri;
            
            $this->view->proposalDescriptions = $this->_proposal->getPatientProposalDescription($patientUri, $proposalUri);
            
            $this->_titleHelper->reset();
            $this->_titleHelper->addResource ($proposalUri);
            $proposal = array();
            $proposal['label'] = $this->_titleHelper->getTitle($proposalUri, $this->_lang);
            $proposal['components'] = $this->_proposal->getProposalData($proposalUri);
            $this->view->proposal = $proposal;
        }
        else
        {
            echo "noproposaluri";
        }
            
    }
    /**
     * get a special healthstate
     */
    
    public function healthstateAction($healthstateUri = null)
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/healthstate.css');

        if (false == isset ($healthstateUri))
            $healthstateUri = $this->getParam ('healthstateUri');
        if ('' != $healthstateUri)
        {
            $patientOptions = $this->_patient->getHealthstate($healthstateUri);
            $this->view->options = $patientOptions['sorted'];
        }
        else
        {
            $patientOptions = array();
            $message = new OntoWiki_Message($this->_translate->_('noHealthstateFound'), OntoWiki_Message::ERROR);
            $this->_owApp->appendMessage($message);
        }

        return $patientOptions;
    }
    
    public function patientAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/patient.css');
        $this->view->url = $this->_url;

        $currentPatient = "";

        // get selectedResource if it is set
        $selectedResource = $this->_owApp->__get("selectedResource");
        if (isset($selectedResource))
        {
            $selectedResourceUri = $selectedResource->getUri();
            if (in_array("http://www.dispedia.de/o/Patient", $this->getClasses($selectedResourceUri)))
                $currentPatient = $selectedResourceUri;
        }

        if ( '' != $currentPatient )
        {
            // build toolbar
            $toolbar = $this->_owApp->toolbar;
            $toolbar->appendButton(OntoWiki_Toolbar :: SUBMIT, array(
                'name' => 'Save'
            ));
            $this->view->placeholder('main.window.toolbar')->set($toolbar);

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
        else
        {
            $message = new OntoWiki_Message($this->_translate->_('nopatientselected'), OntoWiki_Message::WARNING);
            $this->_owApp->appendMessage($message);
        }
        $this->view->currentPatient = $currentPatient;
    }

    public function storeAction()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/store.css');
        $this->view->headScript()->appendFile($this->_componentUrlBase .'js/store.js');
    
        // get ontologies config object
        $ontologies = $this->_config->ontologies->toArray();
        
        $ontologiePath = getcwd() . '/' . $ontologies['folder'] . '/';
        
        $this->view->url = $this->_config->urlBase;

        foreach ($ontologies['models'] as $modelName => $model) {
            foreach ($model['files'] as $fileNumber => $filename) {
                $file = array(
                    'name' => $filename,
                    'content' => file_get_contents($ontologiePath . $filename)
                    );
                $ontologies['models'][$modelName]['files'][$fileNumber] = $file;
            }
        }
        $this->view->models = $ontologies['models'];
    }
}

