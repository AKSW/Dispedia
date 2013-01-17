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
	$("#responseAll").html('<div class="storeProcessDiv storeWorkingDiv">Working</div>');
	$.each(models, function(res){
			renewModel(res, mode);
	});
	$("#responseAll").html('<div class="storeProcessDiv storeFinshDiv">Finish</div>');
}
function renewModel(modelName, mode) {
    $("#response" + modelName).empty();
	$("#response" + modelName).html('<div class="storeProcessDiv storeWorkingDiv">Start</div>');
	
	if ('delete' == mode || 'all' == mode)
		deleteModel(modelName);
		
	if ('add' == mode || 'all' == mode)
		addModel(modelName);
	
	$("#response" + modelName).html('<div class="storeProcessDiv storeFinshDiv">Finish</div>');
}

function deleteModel(modelName) {
	$.ajax({
        async:false,
        dataType: "html",
        type: "GET",
        data: { modelName: modelName, do: 'remove' },
        context: $("#response" + modelName),
        url: url + 'patapro/changemodel/',
        // complete, no errors
        success: function ( res ) 
        {
            $(this).html('<div class="storeProcessDiv storeWorkingDiv">delete Model: ' + models[modelName].namespace + '</div>');
        },
        
        error: function (jqXHR, textStatus, errorThrown)
        {
            console.log (jqXHR);
            console.log (textStatus);
            console.log (errorThrown);
        }
    });
}

function addModel(modelName) {
    $.ajax({
        async:false,
        dataType: "html",
        type: "POST",
        data: { modelName: modelName, do: 'add' },
        context: $("#response" + modelName),
        url: url + 'patapro/changemodel/',
        // complete, no errors
        success: function ( res ) 
        {
            $(this).html('<div class="storeProcessDiv storeWorkingDiv">add Model: ' + models[modelName].namespace + '</div>');
			//$(this).append('<div>add File: ' + models[modelName].files[0].name + '</div>');
        },
        
        error: function (jqXHR, textStatus, errorThrown)
        {
            console.log (jqXHR);
            console.log (textStatus);
            console.log (errorThrown);
        }
    });
}
