<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_proppalloc
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class Action
{
    private $_patientsModel;
    private $_lang;
    
    public function __construct ($lang, $patientsModel)
    {
        $this->_lang = $lang;
        $this->_patientsModel = $patientsModel;
        $this->_store = $this->_store = Erfurt_App::getInstance()->getStore();
    }
    
    
    /**
     * Add an action
     */
    public function add($proposalUri, $title, $text)
    {
        $actionUri = 'http://patients.dispedia.de/'. 
            str_replace (' ', '', $title) .
            substr ( hash ( 'sha384', mt_rand (0, 20) * time() ), 0, 5);
        
        $this->addStmt ( 
            $proposalUri, 
            'http://www.dispedia.de/o/containsAction', 
            $actionUri
        );
        
        // ---------
        
        $this->addStmt ( 
            $actionUri, 
            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
            'http://www.dispedia.de/o/Action'
        );
        
        $this->addStmt ( 
            $actionUri, 
            'http://www.w3.org/2000/01/rdf-schema#label', 
            $title
        );
        
        $this->addStmt ( 
            $actionUri, 
            'http://www.dispedia.de/o/linkedToActionInfo', 
            $text
        );
    }
    
    
    /**
     * adds a triple to datastore
     */
    public function addStmt($s, $p, $o, $lang = 'de')
    {
        // set type(uri or literal)
        $type = true == Erfurt_Uri::check($o) 
            ? 'uri'
            : 'literal';
        
        // add a triple to datastore
        return $this->_patientsModel->addStatement(
            $s,
            $p, 
            array('value' => $o, 'type' => $type, 'lang' => $lang)
       );
    }
}

