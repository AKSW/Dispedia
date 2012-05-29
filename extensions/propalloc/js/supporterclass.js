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

var globalPropertyNumber = 0;
var globalSubPropertyNumber = new Array();

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
    
    elementStr = "<div id=\"property" + globalPropertyNumber + "\"><input type=\"text\" name=\"currentSupporterClass[properties][" + globalPropertyNumber + "][label]\" value=\"" + propertyLabel + "\" />";
    elementStr += "<input type=\"hidden\" name=\"currentSupporterClass[properties][" + globalPropertyNumber + "][uri]\" value=\"" + propertyUri+ "\" />";
    elementStr += "<a id=\"removePropoerty\" class=\"button\" onclick=\"javascript:removeSupporterClassProperty(" + globalPropertyNumber + ");\">-</a>";
    elementStr += "<a id=\"addSubPropoerty\" class=\"button\" onclick=\"javascript:addSupporterClassSubProperty(" + globalPropertyNumber + ");\">+</a><div>";
    
    $("#propertiesDiv").append(elementStr);
    
    globalSubPropertyNumber[globalPropertyNumber] = 0;
    if (property != null && property['subproperties'] != null)
    {
        $.each(property['subproperties'], function(res){
            addSupporterClassSubProperty(globalPropertyNumber, property['subproperties'][res]);
        });
    }
    globalPropertyNumber++;
    console.log(globalSubPropertyNumber);
}

/**
* function remove a field of a property
*/
function removeSupporterClassProperty (propertyNumber)
{
    $("#property" + propertyNumber).remove();
}

/**
* function add a field to set a subproperty
*/
function addSupporterClassSubProperty (propertyNumber, subProperty)
{
    if (subProperty == null)
    {
        subPropertyLabel = "";
        subPropertyUri = "";
    }
    else
    {
        subPropertyLabel = subProperty['label'];
        subPropertyUri = subProperty['uri'];
    }
    
    elementStr = "<div class=\"subProperty\" id=\"subPropertyp" + propertyNumber + "s" + globalSubPropertyNumber[propertyNumber] + "\"><input type=\"text\" name=\"currentSupporterClass[properties][" + propertyNumber + "][subproperties][" + globalSubPropertyNumber[propertyNumber] + "][label]\" value=\"" + subPropertyLabel + "\" />";
    elementStr += "<input type=\"hidden\" name=\"currentSupporterClass[properties][" + propertyNumber + "][subproperties][" + globalSubPropertyNumber[propertyNumber] + "][uri]\" value=\"" + subPropertyUri+ "\" />";
    elementStr += "<a id=\"removePropoerty\" class=\"button\" onclick=\"javascript:removeSupporterClassSubProperty('p" + propertyNumber + "s" + globalSubPropertyNumber[propertyNumber] + "');\">-</a></div>";
    $("#property" + propertyNumber).append(elementStr);
    globalSubPropertyNumber[propertyNumber]++;
}

/**
* function remove a field of a subproperty
*/
function removeSupporterClassSubProperty (subPropertyNumber)
{
    $("#subProperty" + subPropertyNumber).remove();
}

$(document).ready(function() {
    if (undefined !==  window.currentSupporterClass)
        $.each(currentSupporterClass.properties, function(res){
            addSupporterClassProperty(currentSupporterClass.properties[res]);
        });
});
    