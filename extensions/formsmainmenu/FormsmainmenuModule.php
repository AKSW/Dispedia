<?php

/**
 * Forms - Main Menu
 *
 * @category   OntoWiki
 * @package    OntoWiki_extensions_formgenerator
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class FormsMainMenuModule extends OntoWiki_Module
{   
    protected $_translate = null;
    protected $_titleHelper;
    protected $_lang;
    protected $_ontologies;
    protected $_shouldShow = false;
    
    /**
     * 
     */
    public function init(){
        parent::init();
        $this->_translate = $this->_owApp->translate;
        
        $this->_ontologies = $this->_config->ontologies->toArray();
        $this->_ontologies = $this->_ontologies['models'];
        if (
            $this->_store->isModelAvailable($this->_ontologies['dispediaCore']['namespace']) &&
            $this->_store->isModelAvailable($this->_ontologies['dispediaPatient']['namespace'])
            )
        {
            $this->view->urlBase = $this->_config->urlBase;
            
            // include CSS files
            $this->view->headLink()->appendStylesheet($this->_config->urlBase . 'extensions/formsmainmenu/css/formsmainmenu.css');
            $this->_shouldShow = true;

            $dispediaSession = new Zend_Session_Namespace('Dispedia');
            
            if (isset($dispediaSession->menuName) && "" != $dispediaSession->menuName)
                $this->view->showMenu = $dispediaSession->menuName;
            else
                $this->view->showMenu = "";
            
            $this->_titleHelper = new OntoWiki_Model_TitleHelper();
            
            $this->_lang = OntoWiki::getInstance()->config->languages->locale;
        } else {
            $this->_shouldShow = false;
        }
        
        // add Home button in main navi
        $registry = OntoWiki_Menu_Registry::getInstance();
        $menu = OntoWiki_Menu_Registry::getInstance()->getMenu('application');
        $menu->prependEntry('Home', $this->_owApp->getUrlBase() . 'Site/Home');
    }

    /**
     * Returns the title of the module
     *
     * @return string
     */
    public function getTitle()
    {
        return 'Dispedia Menu';
    }

    /**
     * Maybe we should disable the app module in some case?
     *
     * @return string
     */
    public function shouldShow()
    {
        return $this->_shouldShow;
    }

    /**
     * Returns the menu of the module
     *
     * @return string
     */
    public function getMenu()
    {
        /*$menuRegistry = OntoWiki_Menu_Registry::getInstance();
        $menuRegistry->getMenu('formsmainmenu')->getSubMenu('View')->setEntry('Hide Knowledge Bases Box', '#');
        
        return OntoWiki_Menu_Registry::getInstance()->getMenu('application');*/
        
        // No Menu
        return new OntoWiki_Menu ();
    }
    
    /**
     * Returns the content for the model list.
     */
    public function getContents()
    {
        $data ['url'] = $this->_config->urlBase;
        $data ['applicationUrl'] = $this->_config->urlBase . 'application/';
        $data ['imagesUrl'] = $this->_config->urlBase . 'extensions/formsmainmenu/resources/images/';
            
        $this->view->patients = $this->getAllPatients();
        $dispediaSession = new Zend_Session_Namespace('Dispedia');
        $selectedResource = $this->_owApp->__get("selectedResource");
        if(isset($selectedResource))
            $selectedResourceUri = $selectedResource->getIri();

        if (isset($selectedResourceUri) && "" != $selectedResourceUri)
            if (in_array($selectedResourceUri, array_keys($this->view->patients)))
                $this->view->currentPatientUri = $selectedResourceUri;
            else
                $this->view->currentPatientUri = "";
        else
        {
            $this->view->currentPatientUri = "";
        }
        
        if (isset($dispediaSession->menuName) && "" != $dispediaSession->menuName)
            $this->view->menuName = $dispediaSession->menuName;
        else
            $this->view->menuName = "";
        
        if (!$this->_owApp->user || $this->_owApp->user->isAnonymousUser()) {
            $data ['loggedIn'] = false;
        } else {
            $data ['loggedIn'] = true;
        }
        
        return $this->render('formsmainmenu', $data);
    }
    
    /**
     * 
     */
    private function getAllPatients ()
    {
        $patients = array();
        
        $closureResults = $this->_store->getTransitiveClosure('http://www.dispedia.de/', 'http://www.w3.org/2000/01/rdf-schema#subClassOf', array('http://www.dispedia.de/o/Patient'));
        
        $closureFilter = 'FILTER (';
        
        foreach ($closureResults as $closureUri => $closureResult)
        {
            $closureFilter .= '?classUri = <'. $closureUri .'> OR ';
        }
        
        $closureFilter .= 'FALSE)';
        
        $patientsResults = $this->_store->sparqlQuery (
            'SELECT ?uri
              WHERE {
                 ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?classUri .' .
                 $closureFilter .
             '};'
        );
        
        $this->_titleHelper->reset();
        $this->_titleHelper->addResources($patientsResults, 'uri');
        
        foreach ($patientsResults as $patient) {
            $patients[$patient['uri']] = $this->_titleHelper->getTitle($patient['uri'], $this->_lang);;
        }
        
        return $patients;
    }
    
    
    public function allowCaching()
    {
        // no caching
        return false;
    }
}


