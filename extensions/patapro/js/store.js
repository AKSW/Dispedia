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

function renewAllModel(mode) {
    var result = 0;
	$("#responseAll").html('<div class="storeProcessDiv storeWorkingDiv">Working</div>');
	$.each(models, function(res){
        if ('namespaces' != res)
            result += renewModel(res, mode);
	});
    if (0 == result)
        $("#responseAll").html('<div class="storeProcessDiv storeFinshDiv">Finish</div>');
    else
        $("#responseAll").html('<div class="storeProcessDiv storeErrorDiv">Error (' + result + ')</div>');
    
}
function renewModel(modelName, mode) {
    var result = 0;
    $("#response" + modelName).empty();
	$("#response" + modelName).html('<div class="storeProcessDiv storeWorkingDiv">Start</div>');
	
	if ('delete' == mode || 'all' == mode)
		result += deleteModel(modelName);
		
	if ('add' == mode || 'all' == mode)
		result += addModel(modelName);
	
    if (0 == result)
        $("#response" + modelName).html('<div class="storeProcessDiv storeFinshDiv">Finish</div>');
    else
        $("#response" + modelName).html('<div class="storeProcessDiv storeErrorDiv">Error (' + result + ')</div>');
    
    return result;
}

function deleteModel(modelName) {
    var returnValue = 0;
	$.ajax({
        async:false,
        dataType: "json",
        type: "POST",
        data: {
            modelName: modelName,
            do: 'remove',
            backup: $('#storeBackup').is(":checked") ? $('#storeBackup').val() : 'false'
        },
        context: $("#response" + modelName),
        url: url + 'patapro/changemodel/',
        // complete, no errors
        success: function ( res ) 
        {
            $(this).html('<div class="storeProcessDiv storeWorkingDiv">delete Model: ' + models[modelName].namespace + '</div>');
            try {
                if (undefined != res.error) {
                    returnValue = -1;
                }
                else {
                    changeButtons('removed', modelName);
                }
            }
            catch (err) {
                returnValue = -1;
            }
        },
        
        error: function (jqXHR, textStatus, errorThrown)
        {
            console.log (jqXHR);
            console.log (textStatus);
            console.log (errorThrown);
            returnValue = -1;
        }
    });
    return returnValue;
}

function addModel(modelName) {
    var returnValue = 0;
    $.ajax({
        async:false,
        dataType: "json",
        type: "POST",
        data: {
            modelName: modelName,
            do: 'add',
            hidden: $('#model' + modelName + 'Hidden').is(":checked") ? $('#model' + modelName + 'Hidden').val() : 'false'
        },
        context: $("#response" + modelName),
        url: 'changemodel/',
        // complete, no errors
        success: function ( res ) 
        {
            $(this).html('<div class="storeProcessDiv storeWorkingDiv">add Model: ' + models[modelName].namespace + '</div>');
            try {
                if (undefined != res.error) {
                    returnValue = -1;
                }
                else {
                    changeButtons('added', modelName);
                }
            }
            catch (err) {
                returnValue = -2;
            }
        },
        
        error: function (jqXHR, textStatus, errorThrown)
        {
            console.log (jqXHR);
            console.log (textStatus);
            console.log (errorThrown);
            returnValue = -2;
        }
    });
    return returnValue;
}

function changeButtons(mode, modelName) {
    console.log(mode, modelName);
    
    if ('added' == mode) {
        $('#model' + modelName + 'RenewA').removeClass('storeHidden');
        $('#model' + modelName + 'RenewDiv').addClass('storeHidden');
        $('#model' + modelName + 'AddA').addClass('storeHidden');
        $('#model' + modelName + 'AddDiv').removeClass('storeHidden');
        $('#model' + modelName + 'DeleteA').removeClass('storeHidden');
        $('#model' + modelName + 'DeleteDiv').addClass('storeHidden');
        $('#model' + modelName + 'StatusA').removeClass('storeHidden');
        $('#model' + modelName + 'StatusNA').addClass('storeHidden');
        
    } else if ('removed' == mode) {
        $('#model' + modelName + 'RenewA').addClass('storeHidden');
        $('#model' + modelName + 'RenewDiv').removeClass('storeHidden');
        $('#model' + modelName + 'AddA').removeClass('storeHidden');
        $('#model' + modelName + 'AddDiv').addClass('storeHidden');
        $('#model' + modelName + 'DeleteA').addClass('storeHidden');
        $('#model' + modelName + 'DeleteDiv').removeClass('storeHidden');
        $('#model' + modelName + 'StatusA').addClass('storeHidden');
        $('#model' + modelName + 'StatusNA').removeClass('storeHidden');
    }
}
