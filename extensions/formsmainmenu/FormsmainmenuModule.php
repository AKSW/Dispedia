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
    private $_translate = null;
    
    /**
     * 
     */
    public function init(){
        parent::init();
        $this->_translate = $this->_owApp->translate;
        
        $dispediaSession = new Zend_Session_Namespace('Dispedia');
        
        if (isset($dispediaSession->menuName) && "" != $dispediaSession->menuName)
            $this->view->showMenu = $dispediaSession->menuName;
        else
            $this->view->showMenu = "";
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
        return true;
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
        $patientsResults = $this->_store->sparqlQuery (
            'SELECT ?uri ?firstName ?lastName
              WHERE {
                 ?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.dispedia.de/o/Patient>.
                 ?uri <http://www.dispedia.de/o/firstName> ?firstName.
                 ?uri <http://www.dispedia.de/o/lastName> ?lastName.
             };'
        );
        
        foreach ($patientsResults as $patient) {
            $patients[$patient['uri']] = $patient;
        }
        
        return $patients;
    }
    
    
    public function allowCaching()
    {
        // no caching
        return false;
    }
}


