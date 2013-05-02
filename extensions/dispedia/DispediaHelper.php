<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_formgenerator
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @copyright  Copyright (c) 2013
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class DispediaHelper extends OntoWiki_Component_Helper
{
    public function __construct()
    {
        $moduleRegistry = OntoWiki_Module_Registry::getInstance();
        //$moduleRegistry->disableModule('navigation');
    }
}
