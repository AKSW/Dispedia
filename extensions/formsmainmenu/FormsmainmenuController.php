<?php
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
class FormsMainMenuController extends OntoWiki_Controller_Component
{
    /**
     * init controller
     */     
    public function init()
    {
        parent::init();
    }
    
    /**
     * Action change patient in session
     */
     public function changepatientAction()
     {
        // disable rendering
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout()->disableLayout();
        
        $currentPatientUri = urldecode($this->getParam('curentPatientUri'));
        if ("" == $currentPatientUri)
        {
            unset($this->_owApp->currentResource);
        }
        else
        {
            $currentResource = new Ontowiki_Resource($currentPatientUri, $this->_owApp->selectedModel);
            $this->_owApp->currentResource = $currentResource;
        }
     }
    
    /**
     * Action to view the Proposal overview
     */
    public function setmenuAction()
    {
        
        $menuName = urldecode($this->getParam ('menuName'));
        
        $dispediaSession = new Zend_Session_Namespace('Dispedia');
        
        if (isset($menuName) && "" != $menuName)
            if ($dispediaSession->menuName != $menuName)
                $dispediaSession->menuName = $menuName;
            else
                unset($dispediaSession->menuName);
        else
            unset($dispediaSession->menuName);
    }
}