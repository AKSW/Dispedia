<?php

require_once 'classes/Action.php';
require_once 'classes/Proposal.php';
require_once 'classes/Patient.php';
require_once 'classes/Information.php';
require_once 'classes/Resource.php';
require_once 'classes/Supporter.php';
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
        private $_coreModel;
        private $_dispediaModel;
        private $_patientModel;
        private $_titleHelper;

    /**
     * init controller
     */
    public function init()
    {
        parent::init();
        OntoWiki_Navigation::disableNavigation();
        $this->_url = $this->_config->urlBase .'propalloc/';

        $dispediaModel = new Erfurt_Rdf_Model($this->_privateConfig->dispediaModel);
        $this->_dispediaModel = $dispediaModel;

        $model = new Erfurt_Rdf_Model($this->_privateConfig->patientsModel);
        $this->_patientModel = $model;

        $model = new Erfurt_Rdf_Model($this->_privateConfig->coreModel);
        $this->_coreModel = $model;

        $this->_titleHelper = new OntoWiki_Model_TitleHelper($this->_dispediaModel);

        // set standard language
        $this->_lang = OntoWiki::getInstance()->config->languages->locale;

        $this->_resource = new Resource($this->_lang, $this->_coreModel, $this->_patientModel, $this->_dispediaModel);

        $this->_proposal = new Proposal(
            $this, $this->_lang,
            $this->_patientModel,
            $this->_dispediaModel,
            $this->_resource
        );

        $this->view->headScript()->appendFile($this->_componentUrlBase .'libraries/jquery.tools.min.js');
    }


    /**
     * Action to view the Proposal overview
     */
    public function indexAction()
    {
        $this->view->proposals = $this->_proposal->getAllProposals();

        $this->view->url = $this->_url;
        $this->view->imagesUrl = $this->_config->urlBase . 'extensions/propalloc/resources/images/';
    }

    /**
     * Action change patient in session
     */
     public function changepatientAction()
     {
        $dispediaSession = new Zend_Session_Namespace('Dispedia');
        $currentPatientUri = urldecode($this->getParam('curentPatientUri'));
        if ("" == $currentPatientUri)
            unset($dispediaSession->selectedPatientUri);
        else
            $dispediaSession->selectedPatientUri = $currentPatientUri;
     }


    /**
     * Action to edit the Proposal Allocations
     */
    public function allocAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/alloc.css');

        // build toolbar
        $toolbar = $this->_owApp->toolbar;
        $toolbar->appendButton(
            OntoWiki_Toolbar :: SUBMIT,
            array('name' => 'Save')
        );
        $this->view->placeholder('main.window.toolbar')->set($toolbar);

        $this->view->url = $this->_url;
        $this->view->currentProposal = $this->getParam('proposal');

        // -------------------------------------------------------------

        $t = new Topic($this->_lang);
        $o = new Option(
            $this->_lang,
            $this->_patientModel,
            new Erfurt_Rdf_Model($this->_privateConfig->patientsModel)
        );

        $this->view->proposals = (array) $this->_proposal->getAllProposals();
        $this->view->proposal = $this->_proposal;

        // -------------------------------------------------------------
        if ( '' != $this->getParam('proposal') ) {
            $options = array ();
            //TODO: Change the access to the $_REQUEST object
            if ( 'save' == $this->getParam('do') ) {
                foreach ( $_REQUEST as $key => $value ) {
                    if ( 'selectedOption' == $value ) {
                        $options [] = str_replace('als_dispedia_de', 'als.dispedia.de', $key);
                    }
                }

                $o->saveOptions(
                    new Erfurt_Rdf_Model($this->_privateConfig->patientsModel),
                    $this->_proposal->getProposalUri($this->getParam('proposal')),
                    $options,
                    $this->_proposal->getSettings($this->getParam('proposal'))
                );
            }

            $topics = array ();

            foreach ($t->getAllTopics() as $topic) {

                $entry = array ();
                $entry ['label'] = $topic ['label'];

                foreach ( $o->getOptions($topic ['uri']) as $option) {

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
            $this->view->settings = $this->_proposal->getSettings($this->getParam('proposal'));
        }
    }

    /**
     * Action to make add or edit a Proposal, with informations and actions
     */
    public function editAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/proposal.css');
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/action.css');
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/information.css');
        $this->view->headScript()->appendFile($this->_componentUrlBase .'js/edit.js');

        // build toolbar
        $toolbar = $this->_owApp->toolbar;
        $toolbar->appendButton(
            OntoWiki_Toolbar :: SUBMIT,
            array('name' => 'Save')
        );
        $this->view->placeholder('main.window.toolbar')->set($toolbar);

        $this->view->url = $this->_url;

        $currentProposalUri = urldecode($this->getParam('proposalUri'));

        if (isset($currentProposalUri) && "" != $currentProposalUri) {
            $currentProposal = array();
            $currentProposal['uri'] = $currentProposalUri;
            $currentProposal['hash'] = substr(md5($currentProposalUri), 0, 8);
            $currentProposal['label'] = $this->_resource->getLabel($currentProposalUri);
            $currentProposal['status'] = "edit";
            $actions = $this->_proposal->getActionHelper()->getAllActions($currentProposalUri);
            if (0 < count($actions)) {
                $currentProposal['actions'] = array();
                foreach ($actions as $action) {
                    $currentProposal['actions'][] = $this->actionAction($action['uri']);
                };
            }
            $this->view->currentProposal = $currentProposal;
        } else {
            $this->view->currentProposal = $this->_resource->getNewInstance("Proposal");
        }

        if ( 'save' == $this->getParam('do') ) {
            $this->showMessage(
                $this->_proposal->saveProposal(
                    $this->getParam('currentProposal'),
                    json_decode(urldecode($this->getParam('currentProposalOldData')), true)
                )
            );
            $this->_forward('index');
        }
    }


    /**
     * Action to remove Proposal
     */
    public function removeAction ()
    {
        $this->showMessage($this->_proposal->removeProposal($this->getParam('currentProposal')));
        $this->_forward('index');
    }


    /**
     * get the Action Layout for dynamicly add new or existing action to a edit proposal Layout
     */
    public function actionAction ($actionUri = false)
    {
        if (false == $actionUri) {
            $this->view->actionOverClass = $this->getParam('entityOverClass');
            $this->view->action = $this->_resource->getNewInstance("Action");
        } else {
            $actionHelper = new Action(
                $this,
                $this->_lang,
                $this->_patientModel,
                $this->_dispediaModel,
                $this->_resource
            );
            if (!isset($this->view->actions))
                $this->view->actions = array();

            $action = $actionHelper->getAction($actionUri);

            $informations = $actionHelper->getInformationHelper()->getAllInformations($actionUri);
            if (0 < count($informations)) {
                $action['informations'] = array();
                foreach ($informations as $information) {
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
        $patient = new Patient($this->_lang);
        $this->view->informationClasses = $patient->getAllInformationClasses();
        $this->view->supporterClasses = $patient->getAllSupporterClasses();

        if (false == $informationUri) {
            $this->view->informationOverClass = $this->getParam('entityOverClass');
            $this->view->information = $this->_resource->getNewInstance("Information");
            $this->view->information['content'] = "";
        } else {
            $informationHelper = new Information(
                $this->_lang,
                $this->_patientModel,
                $this->_dispediaModel,
                $this->_resource
            );
            if (!isset($this->view->informations))
                $this->view->informations = array();
            $information = $informationHelper->getInformation($informationUri);
            $this->view->informations['information' . $information['hash']] = $information;
            return 'information' . $information['hash'];
        }
    }

    /**
     * show a a overview over all supporter classes
     */
    public function supporterclassoverviewAction ()
    {
        $this->view->url = $this->_url;
        $this->view->imagesUrl = $this->_config->urlBase . 'extensions/propalloc/resources/images/';

        $supporterHelper = new Supporter(
            $this,
            $this->_lang,
            $this->_coreModel,
            $this->_dispediaModel,
            $this->_resource
        );

        $this->view->supporterClasses = $supporterHelper->getAllSupporterClasses();
    }

    /**
     * show a form to add a new supporter class
     */
    public function supporterclassAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/supporterclass.css');
        $this->view->headScript()->appendFile($this->_componentUrlBase .'js/supporterclass.js');

        // build toolbar
        $toolbar = $this->_owApp->toolbar;
        $toolbar->appendButton(
            OntoWiki_Toolbar :: SUBMIT,
            array('name' => 'Save')
        );
        $this->view->placeholder('main.window.toolbar')->set($toolbar);

        $currentSupporterClassUri = urldecode($this->getParam('supporterClassUri'));
        $supporterClass = $this->getParam('currentSupporterClass');

        $supporterHelper = new Supporter(
            $this,
            $this->_lang,
            $this->_coreModel,
            $this->_dispediaModel,
            $this->_resource
        );

        if (isset($currentSupporterClassUri) && "" != $currentSupporterClassUri) {
            $titleHelper = new OntoWiki_Model_TitleHelper($this->_coreModel);
            $titleHelper->addResource($currentSupporterClassUri);
            $currentSupporterClass = array();
            $currentSupporterClass['uri'] = $currentSupporterClassUri;
            $currentSupporterClass['label'] = $titleHelper->getTitle($currentSupporterClassUri, $this->_lang);
            $currentSupporterClass['status'] = "edit";
            $currentSupporterClass['properties'] = $supporterHelper->getAllProperties($currentSupporterClassUri);
        } else if (isset($supporterClass)) {
            $currentSupporterClass = $supporterClass;
        } else {
            $currentSupporterClass = array();
            $currentSupporterClass['uri'] = "";
            $currentSupporterClass['label'] = "";
            $currentSupporterClass['status'] = "new";
            $currentSupporterClass['properties'] = array();
        }

        $this->view->currentSupporterClass = $currentSupporterClass;

        if ( 'save' == $this->getParam('do') ) {
            $this->showMessage(
                $supporterHelper->saveSupporterClass(
                    $this->getParam('currentSupporterClass'),
                    json_decode(urldecode($this->getParam('currentSupporterClassOldData')), true)
                )
            );
            $this->_forward('supporterclassoverview');
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

    /**
     * Action to remove Proposal
     */
    private function showMessage ($messages)
    {
        foreach ($messages as $message) {
            $this->_owApp->appendMessage($message);
        }
    }
}

