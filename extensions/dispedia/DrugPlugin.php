<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2006-2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * OntoWiki Plugin – drug
 *
 * Write a descripten please
 *
 * @category   OntoWiki
 * @package    Extensions_Dispedia
 * @author     Markus Nitzschke
 * @copyright  Copyright (c) 2006-2013, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class DrugPlugin extends OntoWiki_Plugin
{
    public function onDrugCheck($event)
    {
        $owApp = OntoWiki::getInstance();
        $owApp->appendMessage(
            new OntoWiki_Message(
                'Drug failure in Resource: ' .
                $event->currentResource .
                ' from Model: ' .
                $event->form->getTargetModel(),
                //this->_owApp->translate->_('noformularfound'),
                OntoWiki_Message::ERROR
            )
        );
    }
}