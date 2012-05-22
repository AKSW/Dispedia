/**
 * Javascript stuff for supportclass action
 * 
 * @category   OntoWiki
 * @package    OntoWiki_extensions_formgenerator
 * @author     Lars Eidam <larseidam@googlemail.com>
 * @author     Konrad Abicht <konrad@inspirito.de>
 * @copyright  Copyright (c) 2011
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

globalProbertyNumber = 0;
globalSubProbertyNumber = 0;

/**
* function add a field to set a property
*/
function addSupporterClassProperty (property)
{
    if (property == null)
    {
        propertyLabel = "";
        propertyUri = "";
    }
    else
    {
        propertyLabel = property['label'];
        propertyUri = property['uri'];
    }
    
    elementStr = "<div id=\"property" + globalProbertyNumber + "\"><input type=\"text\" name=\"currentSupporterClass[properties][" + globalProbertyNumber + "][label]\" value=\"" + propertyLabel+ "\" />";
    elementStr += "<input type=\"hidden\" name=\"currentSupporterClass[properties][" + globalProbertyNumber + "][uri]\" value=\"" + propertyUri+ "\" />";
    elementStr += "<a id=\"removePropoerty\" class=\"button\" onclick=\"javascript:removeSupporterClassProperty(" + globalProbertyNumber + ");\">-</a>";
    elementStr += "<a id=\"addSubPropoerty\" class=\"button\" onclick=\"javascript:addSupporterClassSubProperty(" + globalProbertyNumber + ");\">+</a><div>";
    $("#propertiesDiv").append(elementStr);
    globalProbertyNumber++;
}

/**
* function remove a field to set a property
*/
function removeSupporterClassProperty (probertyNumber)
{
    $("#property" + probertyNumber).remove();
}

/**
* function add a field to set a property
*/
function addSupporterClassSubProperty (probertyNumber)
{
    elementStr = "<div><input type=\"text\" name=\"currentSupporterClass[properties][" + probertyNumber + "][subproperties][]\" value=\"\" /></div>";
    $("#property" + probertyNumber).append(elementStr);
    globalSubProbertyNumber++;
}

$(document).ready(function() {
    if (undefined !==  window.currentSupporterClass)
        $.each(currentSupporterClass.properties, function(res){
            addSupporterClassProperty(currentSupporterClass.properties[res]);
        });
});
    