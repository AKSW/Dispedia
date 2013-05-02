<?php

/**
 * Controller for Dispedia.
 *
 * @category   OntoWiki
 * @package    OntoWiki_extensions_formgenerator
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @copyright  Copyright (c) 2013
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class DispediaController extends OntoWiki_Controller_Component
{
    
    /**
     * init controller
     */     
    public function init()
    {
        parent::init();
        
    }
    
    public function indexAction()
    {
        // disable layout for Ajax requests
        $this->_helper->layout()->disableLayout();
        // disable rendering
        $this->_helper->viewRenderer->setNoRender();
    }
    
    public function downloadAction()
    {
        $ontologyName = $this->getParam('ontology', '');
        if (
            "dispediaCore" == $ontologyName
            || "dispediaCoreConditions" == $ontologyName
        )
        {
            header('Content-Type: text/xml');
            header('Content-Disposition: attachment; filename="' . $ontologyName . '.xml"');
            readfile('ontologies/' . $ontologyName . '.xml');
        }
        
        // disable layout for Ajax requests
        $this->_helper->layout()->disableLayout();
        // disable rendering
        $this->_helper->viewRenderer->setNoRender();
    }
}

