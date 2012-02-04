/**
 * Javascript stuff for form action
 * 
 * @category   OntoWiki
 * @package    OntoWiki_extensions_formgenerator
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
 
/**
* 
*/
function addInformation (informationOverClass)
{
    $.ajax({
        async:true,
        dataType: "html",
        type: "POST",
        data: {informationOverClass : informationOverClass},
        context: $("#editProposalTable"),
        url: url + "information",
            // complete, no errors
        success: function ( res ) 
        {
            $(this).append(res);
        },
        
        error: function (jqXHR, textStatus, errorThrown)
        {
            console.log (jqXHR);
            console.log (textStatus);
            console.log (errorThrown);
        }
    });
}

/**
* 
*/
function removeInformation (informationHash)
{
    console.log ("remove " + informationHash);
    $("." + informationHash).remove();
}