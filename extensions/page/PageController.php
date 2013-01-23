<?php

require_once 'OntoWiki/Controller/Component.php';

/**
 * Controller for OntoWiki
 *
 * @category   OntoWiki
 * @package    OntoWiki_extensions_components_page
 * @author     Sebastian Dietzold <dietzold@informatik.uni-leipzig.de>
 * @author     Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class PageController extends OntoWiki_Controller_Component
{
    /**
     * Default action. Forwards to get action.
     */
    public function __call($action, $params)
    {
        $owApp = OntoWiki::getInstance();
        $owNavigation = $owApp->getNavigation();
        $owNavigation->disableNavigation();

        $pagename = str_replace('Action', '', $action);
        if ($pagename === 'index') {
            // no action given... check config for a mapping of URL to static file
            $mappings = array();
            if (isset($this->_privateConfig->mappings)) {
                $mappings = $this->_privateConfig->mappings->toArray();
            }

            $urlBase = $this->_owApp->getUrlBase();
            foreach ($mappings as $key=>$mappingSpec) {
                if ($mappingSpec['uri'] === $urlBase) {
                    $pagename = $mappingSpec['page'];
                    break;
                }
            }
        }
        $pageTitles = $this->_privateConfig->titles->toArray();

        if (isset($pageTitles[$pagename])) {
            $this->view->placeholder('main.window.title')->set($this->_owApp->translate->_($pageTitles[$pagename]));
        } else {
            $this->view->placeholder('main.window.title')->set($this->_owApp->translate->_($pagename));
        }

        if (!file_exists($this->_componentRoot.'page/'.$pagename.'.phtml')) {
            $this->render('default');
            return;
        }

        $this->render($pagename);
    }
}
