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
class MainMenuModule extends OntoWiki_Module
{   
    private $_translate = null;
    
    /**
     * 
     */
    public function init(){
        parent::init();
        $this->_translate = $this->_owApp->translate;
        
        // Request variables
        $this->view->showMenu = $this->_request->getParam ( 'showMenu' );
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
        
        if (!$this->_owApp->user || $this->_owApp->user->isAnonymousUser()) {
            $data ['loggedIn'] = false;
        } else {
            $data ['loggedIn'] = true;
        }
        
        return $this->render('mainmenu', $data);
    }
    
    public function allowCaching()
    {
        // no caching
        return false;
    }
}


