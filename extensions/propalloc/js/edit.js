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
function addEntity (entity, entityOverClass, context)
{
    $.ajax({
        async:true,
        dataType: "html",
        type: "POST",
        data: {entityOverClass : entityOverClass},
        context: $("#" + context),
        url: url + entity,
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
function removeEntity (entityHash)
{
    console.log ("remove " + entityHash);
    $("." + entityHash).remove();
}