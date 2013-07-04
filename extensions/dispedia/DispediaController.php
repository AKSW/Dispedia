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
        $arrFiles = scandir('ontologies');
        if (false !== array_search($ontologyName . '.xml', $arrFiles))
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
    
    public function downloadpdfAction()
    {
        $pdfName = $this->getParam('name', '');
        $arrFiles = scandir('htdocs/ehealthservices2013/data');
        
        if (false !== array_search($pdfName . '.pdf', $arrFiles))
        {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $pdfName . '.pdf"');
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header("Content-Description: File Transfer");            
            header("Content-Length: " . filesize('htdocs/ehealthservices2013/data/' . $pdfName . '.pdf'));
            readfile('htdocs/ehealthservices2013/data/' . $pdfName . '.pdf');
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
       $zieladresse = 'lars.eidam@studserv.uni-leipzig.de';
       
       // Welche Adresse soll als Absender angegeben werden?
       // (Manche Hoster lassen diese Angabe vor dem Versenden der Mail ueberschreiben)
       $absenderadresse = 'anmeldung@ehealth-services-2013.de';
       
       // Welcher Absendername soll verwendet werden?
       $absendername = 'eHealth Service 2013 Anmeldungs Formular';
       
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
}

