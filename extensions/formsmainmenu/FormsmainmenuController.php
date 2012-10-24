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