<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Rdf2cdaModule extends OntoWiki_Module
{
    /**
     * Label of article resource type
     */
    protected $_model;
    protected $_newArticleResourceTypeLabel = '';

    public function init ()
    {
        parent::init();
        
        $this->_store = Erfurt_App::getInstance()->getStore();
        
        // get all models
        $this->_ontologies = $this->_config->ontologies->toArray();
        $this->_ontologies = $this->_ontologies['models'];
        $this->_model = $this->_store->getModel($this->_ontologies['dispediaPN']['namespace']);
        
        // include javascript files
        $basePath = $this->_config->staticUrlBase . 'extensions/dispedia/';
        $baseCSSPath = $basePath .'public/css/';
        $baseJavascriptPath = $basePath .'public/js/';
        
        $this->view->headScript()
            ->appendFile($baseJavascriptPath. 'rdf2cda.js', 'text/javascript');
        
        $this->view->headLink()   
            ->appendStylesheet(
                $baseCSSPath . 'rdf2cda.css',
                'screen',
                true,
                array()
            );
    }
    
    /**
     * @return string Title of the module container
     */
    public function getTitle()
    {
        return $this->_owApp->translate->_('getPatientFile');
    }

    /**
     * Show tab only if model is selected and editable
     */
    public function shouldShow()
    {
        if (true == $this->_model->isEditable()) {
            return true;
        }

        return false;
    }

    public function getContents()
    {
        return $this->render(
            'public/templates/dispedia/modules/rdf2cda'
        );
    }
}


