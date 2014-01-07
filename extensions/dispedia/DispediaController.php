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
    
    private $_store;
    private $_translate;
    private $_ontologies;
    
    /**
     * init controller
     */     
    public function init()
    {
        parent::init();
        
        $this->_store = Erfurt_App::getInstance()->getStore();
        
        $this->_translate = $this->_owApp->translate;
        
        // get all models
        $this->_ontologies = $this->_config->ontologies->toArray();
        $this->_ontologies = $this->_ontologies['models'];
        
        // make model instances
        foreach ($this->_ontologies as $modelName => $model) {
            if ($this->_store->isModelAvailable($model['namespace'])) {
                $this->_ontologies[$modelName]['instance'] = $this->_store->getModel($model['namespace']);
            }
            $namespaces[$model['namespace']] = $modelName;
        }
    }
    
    public function indexAction()
    {
        $this->_redirect('dispedia/demo', array());
    }
    
    public function downloadAction()
    {
        $ontologyFolder = $this->_config->ontologies->toArray();
        $ontologyFolder = $ontologyFolder['folder'];
        $ontologyName = $this->getParam('ontology', '');
        $arrFiles = scandir($ontologyFolder);
        if (false !== array_search($ontologyName . '.xml', $arrFiles))
        {
            header('Content-Type: text/xml');
            header('Content-Disposition: attachment; filename="' . $ontologyName . '.xml"');
            readfile($ontologyFolder . '/' . $ontologyName . '.xml');
        }
        
        // disable layout for Ajax requests
        $this->_helper->layout()->disableLayout();
        // disable rendering
        $this->_helper->viewRenderer->setNoRender();
    }
    
    public function sendregistrationmailAction()
    {
        // disable layout for Ajax requests
        $this->_helper->layout()->disableLayout();
        // disable rendering
        $this->_helper->viewRenderer->setNoRender();
        /**
        * Konfiguration 
        *
        * Bitte passen Sie die folgenden Werte an, bevor Sie das Script benutzen!
        * 
        * Das Skript bitte in UTF-8 abspeichern (ohne BOM).
        */

       // An welche Adresse sollen die Mails gesendet werden?
       $zieladresse = $this->_privateConfig->registrationmail->destAddress;
       
       // Welche Adresse soll als Absender angegeben werden?
       // (Manche Hoster lassen diese Angabe vor dem Versenden der Mail ueberschreiben)
       $absenderadresse = 'anmeldung@ehealth-services.com';
       
       // Welcher Absendername soll verwendet werden?
       $absendername = 'eHealth Service Anmeldungs Formular';
       
       // Welchen Betreff sollen die Mails erhalten?
       $betreff = 'Anmeldung';
       
       // Zu welcher Seite soll als "Danke-Seite" weitergeleitet werden?
       // Wichtig: Sie muessen hier eine gueltige HTTP-Adresse angeben!
       $urlDankeSeite = '/Danke.html';
       
       // Welche(s) Zeichen soll(en) zwischen dem Feldnamen und dem angegebenen Wert stehen?
       $trenner = ":\t"; // Doppelpunkt + Tabulator
       
       /**
        * Ende Konfiguration
        */
       
       if ($_SERVER['REQUEST_METHOD'] === "POST") {
       
           $header = array();
           $header[] = "From: ".mb_encode_mimeheader($absendername, "utf-8", "Q")." <".$absenderadresse.">";
           $header[] = "MIME-Version: 1.0";
           $header[] = "Content-type: text/plain; charset=utf-8";
           $header[] = "Content-transfer-encoding: 8bit";
           
           $mailtext = "";
       
           foreach ($_POST as $name => $wert) {
               if (is_array($wert)) {
                   foreach ($wert as $einzelwert) {
                       $mailtext .= $name.$trenner.$einzelwert."\n";
                   }
               } else {
                   $mailtext .= $name.$trenner.$wert."\n";
               }
           }
       
           mail(
               $zieladresse, 
               mb_encode_mimeheader($betreff, "utf-8", "Q"), 
               $mailtext,
               implode("\n", $header)
           ) or die("Die Mail konnte nicht versendet werden.");
           header("Location: $urlDankeSeite");
           exit;
       }
    }
    
    public function cda2rdfAction()
    {
        // disable rendering
        $this->_helper->viewRenderer->setNoRender();
        
        if ($this->_erfurt->getAc()->isActionAllowed('cda2rdf')) {
            // check if user can edit model
            if (
                !isset($this->_ontologies['dispediaPN']['instance'])
                || false == $this->_ontologies['dispediaPN']['instance']->isEditable()
             ) {
                $message = new OntoWiki_Message($this->_translate->_('noModelEdit'), OntoWiki_Message::ERROR);
                $this->_owApp->appendMessage($message);
            } else {
                // disable layout for Ajax requests
                $this->_helper->layout()->disableLayout();
                
                $model = $this->_ontologies['dispediaPN']['instance'];
                $upload = new Zend_File_Transfer();
                if ("application/xml" == $upload->getMimeType()) {
                    $upload->receive();
                    $fileName = $upload->getFileName();
                    
                    $xmlFileContent = simplexml_load_file($fileName);
                    $xmlFileContentStr = $xmlFileContent->asXML();
                    exec("java -jar " . APPLICATION_PATH . "../extensions/dispedia/libraries/cda2rdf/cda2rdf-0.1-jar-with-dependencies.jar --cda2rdf --input='$xmlFileContentStr'", $output);
                    
                    $data = implode($output);
                    $locator = Erfurt_Syntax_RdfParser::LOCATOR_DATASTRING;
                    $filetype = 'auto';
                    $parser = Erfurt_Syntax_RdfParser::rdfParserWithFormat('rdfxml');
                    $retVal = $parser->parse($data, $locator, $this->_ontologies['dispediaPN']['namespace']);
                    
                    foreach ($retVal as $patientUri => $patientProperties) {
                        $patientExists = $this->_store->sparqlAsk("ASK  { <$patientUri> ?p  ?o }");
                        if (false == $patientExists) {
                            if (isset($patientProperties['http://schema.org/familyName'])
                                && isset($patientProperties['http://schema.org/givenName'])) {
                                $label = $patientProperties['http://schema.org/givenName'][0]['value'] . ' '
                                         . $patientProperties['http://schema.org/familyName'][0]['value'];
                                $retVal[$patientUri]['http://www.w3.org/2000/01/rdf-schema#label'][] = array(
                                    'value' => $label,
                                    'type' => 'literal'
                                );
                            }
                            $model->addMultipleStatements(array($patientUri => $retVal[$patientUri]));
                            // add log message
                            $messageStr = $this->_translate->_('patientFile') . " <a href=\"/resource/properties/?r=" . urlencode($patientUri) . "\">" . $label . "</a> " . $this->_translate->_('added');
                            $message = new OntoWiki_Message(
                                $messageStr,
                                OntoWiki_Message::SUCCESS,
                                array('escape' => false)
                            );
                            $this->_owApp->appendMessage($message);
                        }
                    }
                    
                }
            }
            $this->_redirect("list?init&m=http%3A%2F%2Fpatients.dispedia.de%2F&instancesconfig=%7B%22filter%22%3A%5B%7B%22rdfsclass%22%3A%22http%3A%5C%2F%5C%2Fwww.dispedia.de%5C%2Fo%5C%2FPatient%22%2C%22mode%22%3A%22rdfsclass%22%7D%5D%7D");
        } else {
            // add log message
            $messageStr = $this->_translate->_('actionNotAllowed');
            $message = new OntoWiki_Message(
                $messageStr,
                OntoWiki_Message::ERROR
            );
            $this->_owApp->appendMessage($message);
        }
    }
    
    public function rdf2cdaAction()
    {
        // disable rendering
        $this->_helper->viewRenderer->setNoRender();
        if ($this->_erfurt->getAc()->isActionAllowed('rdf2cda')) {
            // check if user can edit model
            if (
                !isset($this->_ontologies['dispediaPN']['instance'])
                || false == $this->_ontologies['dispediaPN']['instance']->isEditable()
             ) {
                $message = new OntoWiki_Message($this->_translate->_('noModelEdit'), OntoWiki_Message::ERROR);
                $this->_owApp->appendMessage($message);
            } else {
                // disable layout for Ajax requests
                $this->_helper->layout()->disableLayout();
                
                $model = $this->_ontologies['dispediaPN']['instance'];
                $selectedResource = $this->_owApp->__get("selectedResource");
                $modelResource = $model->getResource($selectedResource->getIri());
                $modelResourceRdfStr = $modelResource->serialize();
                
                // get label for filename
                $titleHelper = new OntoWiki_Model_TitleHelper();
                $titleHelper->reset();
                $titleHelper->addResource($modelResource->getIri());
                $modelResourceLabel = $titleHelper->getTitle($modelResource->getIri());
                
                // transform rdf to cda
                exec("java -jar " . APPLICATION_PATH . "../extensions/dispedia/libraries/cda2rdf/cda2rdf-0.1-jar-with-dependencies.jar --rdf2cda --input='$modelResourceRdfStr'", $output);
    
                if (false !== isset($selectedResource))
                {
                    header('Content-Type: text/xml');
                    header('Content-Disposition: attachment; filename="' . $modelResourceLabel . '.xml"');
                    echo implode("\n",$output);
                }
            }
        } else {
            // add log message
            $messageStr = $this->_translate->_('actionNotAllowed');
            $message = new OntoWiki_Message(
                $messageStr,
                OntoWiki_Message::ERROR
            );
            $this->_owApp->appendMessage($message);
        }
    }
    
    public function demoAction()
    {
        $this->_owApp->getNavigation()->disableNavigation();
    }
}

