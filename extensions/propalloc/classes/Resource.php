<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_proppalloc
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class Resource
{
    private $_coreModel;
    private $_dispediaModel;
    private $_patientsModel;
    private $_lang;
    private $_titleHelper;
    
    public function __construct ($lang, $coreModel, $patientsModel, $dispediaModel)
    {
        $this->_lang = $lang;
        $this->_coreModel = $coreModel;
        $this->_dispediaModel = $dispediaModel;
        $this->_patientsModel = $patientsModel;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
        $this->_titleHelper = new OntoWiki_Model_TitleHelper ($dispediaModel);
    }
    
        
    /**
    * 
    */
    public function getNewInstance($class)
    {
        $result = array();
        $result['hash'] = substr ( md5 (rand(0,rand(500,2000))), 0, 8 );
        $result['uri'] = "http://als.dispedia.de/i/" . $result['hash'] . "/" . $class;
        $result['status'] = "new";
        $result['label'] = "";
        return $result;
    }
    
    /**
     * 
     */
    public function getLabel ($uri)
    {
        $this->_titleHelper->addResource ($uri);
        return $this->_titleHelper->getTitle($uri, $this->_lang);
    }
    
        /**
     * adds a triple to datastore
     */
    public function addStmt($s, $p, $o, $lang = '')
    {
        // set type(uri or literal)
        $type = true == Erfurt_Uri::check($o) 
            ? 'uri'
            : 'literal';
        
        // add a triple to datastore
        if ( '' == $lang )
            return $this->_patientsModel->addStatement(
                $s,
                $p, 
                array('value' => $o, 'type' => $type)
            );
        else
            return $this->_patientsModel->addStatement(
                $s,
                $p, 
                array('value' => $o, 'type' => $type, 'lang' => $lang)
            );
    }
    
    
    /**
     *
     */
    public function removeStmt($s, $p, $o)
    {
        $options = array();
        // set subjecttype(uri or literal)
        if (isset($s))
            $options['subject_type'] = true == Erfurt_Uri::check($s)
            ? Erfurt_Store::TYPE_IRI
            : Erfurt_Store::TYPE_LITERAL;
            // set type(uri or literal)
        if (isset($o) && !is_array($o))
        {
            $options['object_type'] = true == Erfurt_Uri::check($o)
            ? Erfurt_Store::TYPE_IRI
            : Erfurt_Store::TYPE_LITERAL;
            // TODO: Fehler im Erfurt, Übergabe eines Arrays für das Object ist laut doc nicht vorgesehen wird aber vom Backend benötigt
            // set type(uri or literal)
            $type = Erfurt_Store::TYPE_IRI == $options['object_type'] 
                ? 'uri'
                : 'literal';
            $o = array('value' => $o, 'type' => $type);
        }
        // aremove a triple form datastore
        return $this->_store->deleteMatchingStatements(
            (string) $this->_dispediaModel,
            $s,
            $p,
            $o,
            $options
       );
    }
}