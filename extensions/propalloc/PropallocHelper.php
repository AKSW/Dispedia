<?php

/**
 * @category   OntoWiki
 * @package    OntoWiki_extensions_propalloc
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class PropallocHelper extends OntoWiki_Component_Helper
{
    public function __construct()
    {
        OntoWiki_Navigation::disableNavigation();
    }
}