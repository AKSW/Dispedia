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

        $this->_store = Erfurt_App::getInstance()->getStore();
        
        $this->_owApp->getNavigation()->disableNavigation();

        $this->_url = $this->_config->urlBase .'patapro/';

        // init array for output messages
        $this->_messages = array();

        // get all models
        $this->_ontologies = $this->_config->ontologies->toArray();
        $this->_ontologies = $this->_ontologies['models'];
        $namespaces = array();
        // make model instances
        foreach ($this->_ontologies as $modelName => $model) {
            if ($this->_store->isModelAvailable($model['namespace']))
            {
                $this->_ontologies[$modelName]['instance'] = $this->_store->getModel($model['namespace']);
            }
            $namespaces[$model['namespace']] = $modelName;
        }
        $this->_ontologies['namespaces'] = $namespaces;

        //TODO:change this to global ontology array
        if ($this->_store->isModelAvailable($this->_ontologies['dispediaPatient']['namespace'])) {
            $this->_patientModel = $this->_ontologies['dispediaPatient']['instance'];
        }
        if ($this->_store->isModelAvailable($this->_ontologies['dispediaCore']['namespace'])) {
            $this->_dispediaModel = $this->_ontologies['dispediaCore']['instance'];
        }
        if ($this->_store->isModelAvailable($this->_ontologies['dispediaALS']['namespace'])) {
            $this->_alsfrsModel = $this->_ontologies['dispediaALS']['instance'];
        }
        $this->_titleHelper = new OntoWiki_Model_TitleHelper ($this->_alsfrsModel);
        $this->_translate = $this->_owApp->translate;

        // set standard language
        $this->_lang = OntoWiki::getInstance()->config->languages->locale;

        $this->_patient = new Patient($this->_lang, $this->_titleHelper);
        $this->_proposal = new Proposal($this->_patientModel, $this->_lang, $this->_titleHelper, $this->_patient, $this->_translate);

        $this->view->headScript()->appendFile($this->_componentUrlBase .'libraries/jquery.tools.min.js');
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
            // get a list of all healthstates
            $this->view->healthstates = $this->_patient->getAllHealthstates($currentPatient);
            
            // only if no healthstates was found for this patient
            if ( 0 < count($this->view->healthstates) )
            {
                // set array with status translations
                $statusArray = array(
                    'isPending' => $this->_translate->_('isPending'),
                    'accepts' => $this->_translate->_('accepts'),
                    'denies' => $this->_translate->_('denies'),
                    'new' => ""
                );
                $this->view->statusArray = $statusArray;

                $allProposals = $this->_proposal->getAllProposals();
                $patientProposals = $this->_proposal->getAllDecisinProposals($currentPatient);

                $lastAlsFrsHealthstateUri = '';

                //get the options from the last alsfrs healthstate (alsfrs because only for this classification exist a compare algorithm)
                foreach ($this->view->healthstates as $healthstateUri => $healthstate)
                {
                    if ('http://www.dispedia.de/wrapper/alsfrs/ALSFRSHealthState' == $healthstate['type'])
                    {
                        $lastAlsFrsHealthstateUri = $healthstateUri;
                        break;
                    }
                }
                $patientOptions = $this->healthstateAction($lastAlsFrsHealthstateUri);

                // only if patientOptions not empty
                if (0 < count($patientOptions))
                {
                    // compare the patient proposals with the template proposals
                    $patientProposals = $this->_proposal->calcCorrespondenceProposals($patientOptions['uriList'], $patientProposals);
                    $allProposals = $this->_proposal->calcCorrespondenceProposals($patientOptions['uriList'], $allProposals);
                }
                
                uasort($allProposals, $this->build_sorter('label'));

                $sortArray = array();
                $sortArrayIndex = 0;
                
                foreach ($allProposals as $proposalUri => $proposal)
                {
                    if (isset($patientProposals[$proposalUri]))
                        unset($allProposals[$proposalUri]);
                    $sortArray[md5($proposalUri)] = $sortArrayIndex;
                    $sortArrayIndex++;
                }

                foreach ($allProposals as $proposalUri => $proposal)
                {
                    $allProposals[$proposalUri]['components'] = $this->_proposal->getProposalData($proposalUri);
                }
                
                uasort($patientProposals, $this->build_sorter('status', 'label'));
                uasort($allProposals, $this->build_sorter('correspondence', 'label', null, true, false, false));

                $this->view->sortArray = $sortArray;
                $this->view->patientUri = $currentPatient;
                $this->view->patientProposals = $patientProposals;
                $this->view->proposals = $allProposals;
            }
            else
                $this->addMessages(new OntoWiki_Message($this->_translate->_('noHealthstateFound'), OntoWiki_Message::ERROR));
        }
        else
        {
            $this->addMessages(new OntoWiki_Message($this->_translate->_('nopatientselected'), OntoWiki_Message::WARNING));
        }
    }
    
    /**
     * sort a proposal array
     */
    private function build_sorter($key, $key2 = null, $key3 = null, $inverse = false, $inverse2 = false, $inverse3 = false) {
        return function($a, $b) use ($key, $key2, $key3, $inverse, $inverse2, $inverse3) {
            if(!function_exists("sortValues")) {
                function sortValues($a, $b) {
                    if (is_float($a)) {
                        $array = array();
                        $array[0] = $a;
                        $array[1] = $b;
                        if (sort($array)) {
                            if ($a == $array[0])
                                return -1;
                            else
                                return 1;
                        }
                    } else
                        return strcasecmp($a, $b);
                }
            }
            if ($inverse) {
                $first = $b[$key];
                $second = $a[$key];
            } else {
                $first = $a[$key];
                $second = $b[$key];
            }
            
            if (null === $key2 && null === $key3) {
                return sortValues($first, $second);
            } else {
                if ($inverse2) {
                    $first2 = $b[$key2];
                    $second2 = $a[$key2];
                } else {
                    $first2 = $a[$key2];
                    $second2 = $b[$key2];
                }
                
                if (null === $key3)
                    return (0 == sortValues($first, $second) ? sortValues($first2, $second2) : sortValues($first, $second));
                else {
                    if ($inverse3) {
                        $first3 = $b[$key3];
                        $second3 = $a[$key3];
                    } else {
                        $first3 = $a[$key3];
                        $second3 = $b[$key3];
                    }
                    
                    $return_value = (0 == sortValues($first2, $second2) ? sortValues($first3, $second3) : $this->sortValues($first2, $second2));
                    return (0 == sortValues($first, $second) ? $return_value : sortValues($first, $second));
                }
            }
        };
    }

    /**
     * get proposaldata for ajax
     */
    public function proposaldataAction()
    {
        $proposalUri = urldecode($this->getParam ('proposalUri'));
        $patientUri = urldecode($this->getParam ('patientUri'));
        $status = urldecode($this->getParam ('status'));
        if (isset($proposalUri) && "" != $proposalUri && isset($patientUri) && "" != $patientUri)
        {
            if ("save" != $this->getParam ('do'))
            {
                //add buttons to toolbar
                $toolbar = $this->_owApp->toolbar;
                $toolbar->appendButton(OntoWiki_Toolbar :: SAVE, array(
                    'url'  => "javascript:submitProposalBox('" . md5($proposalUri) . "', '" . urlencode($proposalUri) . "', '" . urlencode($patientUri) . "');"
                ));
                $toolbar->appendButton(OntoWiki_Toolbar :: CANCEL, array(
                    'url'  => "javascript:closeProposalBox('" . md5($proposalUri) . "');"
                ));
                $this->view->boxtoolbar = $toolbar->__toString();

                $this->view->patientUri = $patientUri;
                $this->view->patientType = $this->_patient->getPatientType($patientUri);

                $this->_titleHelper->reset();
                $this->_titleHelper->addResource($patientUri);
                $this->_titleHelper->addResource($this->view->patientType);
                $this->view->patientLabel = $this->_titleHelper->getTitle($patientUri, $this->_lang);
                $this->view->patientTypeLabel = $this->_titleHelper->getTitle($this->view->patientType, $this->_lang);

                $this->view->proposalUri = $proposalUri;

                if ("new" == $status)
                    $this->view->proposalDescriptions = $this->_proposal->getProposalDescriptionByType($this->view->patientType, $proposalUri);
                else
                    $this->view->proposalDescriptions = $this->_proposal->getPatientProposalDescription($patientUri, $proposalUri);

                $this->_titleHelper->reset();
                $this->_titleHelper->addResource ($proposalUri);
                $proposal = array();
                $proposal['uri'] = $proposalUri;
                $proposal['label'] = $this->_titleHelper->getTitle($proposalUri, $this->_lang);
                $proposal['components'] = $this->_proposal->getProposalData($proposalUri);
                $this->view->proposal = $proposal;
            }
            else
            {
                $messages = array();
                $jsonResponse = array();
                $jsonResponse['error'] = 0;
                $jsonResponse['messages'] = array();
                if ('new' == $status) {
                    $messages = $this->_proposal->addProposals($patientUri, array($proposalUri));
                } elseif  ('delete' == $status) {
                    $messages = $this->_proposal->removeProposals($patientUri, array($proposalUri));
                }
                $proposal['components'] = $this->_proposal->getProposalData($proposalUri);
                $proposalDescriptionReceivedStatusOld = $this->_proposal->getPatientProposalDescription($patientUri, $proposalUri);
                $updated = false;
                foreach ($proposal['components']['data'] as $proposalDescriptions)
                {
                    foreach ($proposalDescriptions as $proposalDescriptionUri => $proposalDescription)
                    {
                        if ("yes" == $this->getParam (md5($proposalDescriptionUri)))
                        {
                            if (!isset($proposalDescriptionReceivedStatusOld['received'][$proposalDescriptionUri]))
                            {
                                $healthstates = $this->_patient->getAllHealthstates($patientUri);
                                $this->_proposal->addStmt(
                                    key($healthstates),
                                    "http://www.dispedia.de/o/receivedProposalDescription",
                                    $proposalDescriptionUri
                                );
                                $updated = true;
                            }
                        }
                        else
                        {
                            if (isset($proposalDescriptionReceivedStatusOld['received'][$proposalDescriptionUri]))
                            {
                                $healthstates = $this->_patient->getAllHealthstates($patientUri);
                                foreach ($healthstates as $healthstatesUri => $healthstate)
                                    $this->_proposal->removeStmt(
                                        $healthstatesUri,
                                        "http://www.dispedia.de/o/receivedProposalDescription",
                                        $proposalDescriptionUri
                                );
                                $updated = true;
                            }
                        }
                    }
                }
                if ($updated) {
                    $messages[] = new OntoWiki_Message($this->_translate->_('proposalDescription') . " " . $this->_translate->_('saved'), OntoWiki_Message::SUCCESS);
                }
                foreach ($messages as $message)
                {
                    $jsonResponse['messages'][] = array(
                        'type' => $message->getType(),
                        'text' => $message->getText()
                    );
                }
                echo json_encode($jsonResponse);
            }
        }
        else
        {
            echo json_encode("noproposaluri");
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
            $this->addMessages(new OntoWiki_Message($this->_translate->_('noHealthstateFound'), OntoWiki_Message::ERROR));
        }

        return $patientOptions;
    }

    public function patientAction ()
    {
        $this->view->headLink()->appendStylesheet($this->_componentUrlBase .'css/patient.css');
        $this->view->headScript()->appendFile($this->_componentUrlBase .'js/patient.js');
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
                'name' => 'Save',
                'url' => 'javascript:$("#patapro").submit()'
            ));
            $this->view->placeholder('main.window.toolbar')->set($toolbar);

            if ( 'save' == $this->getParam ('do') )
            {
                $decisionProposals = $this->_proposal->getAllDecisinProposals($currentPatient);
                foreach ($decisionProposals as $decisionProposal)
                {
                    //TODO: find a better way to save only changed decisions
                    $decision = $this->getParam(base64_encode($decisionProposal['uri']));
                    if (isset($decision))
                        //TODO: auf Erfolg prüfen
                        $this->_proposal->saveDecision($decisionProposals, $decisionProposal['uri'], $decision);
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

    /**
     * add status messages to global array
     */
    public function addMessages($messages)
    {
        if (is_array($messages))
            $this->_messages = array_merge($this->_messages, $messages);
        else
            $this->_messages[] = $messages;
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

}
