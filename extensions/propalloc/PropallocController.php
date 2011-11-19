<?php

require 'classes/Proposal.php';
require 'classes/Option.php';

/**
 * Controller for Propalloc.
 *
 * @category   OntoWiki
 * @package    OntoWiki_extensions_formgenerator
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */ 
class PropallocController extends OntoWiki_Controller_Component
{
    /**
     * init controller
     */     
    public function init()
    {
        parent::init();
    
    }
    
    public function indexAction ()
    {
        $option = new Option ();
        
        $option->getAllOptions ();
    }
}

