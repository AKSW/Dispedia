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
	$.each(source, function(res){
			renewModel(res, mode);
	});
}
function renewModel(modelName, mode) {
    $("#response" + modelName).empty();
	$("#response" + modelName).append('<div>Start</div>');
	
	if ('delete' == mode || 'all' == mode)
		deleteModel(modelName);
		
	if ('add' == mode || 'all' == mode)
		addModel(modelName);
	
	$("#response" + modelName).append('<div>Finish</div>');
}

function deleteModel(modelName) {
	$.ajax({
        async:false,
        dataType: "html",
        type: "GET",
        data: { model: source[modelName].modeluri },
        context: $("#response" + modelName),
        url: deleteUrl,
        // complete, no errors
        success: function ( res ) 
        {
            $(this).append('<div>delete Model: ' + source[modelName].modeluri + '</div>');
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
        data: { modelUri: source[modelName].modeluri , activeForm: "paste", "filetype-paste": "rdfxml", paste: source[modelName].files[0] },
        context: $("#response" + modelName),
        url: createUrl,
        // complete, no errors
        success: function ( res ) 
        {
            $(this).append('<div>add Model: ' + source[modelName].modeluri + '</div>');
			$(this).append('<div>add File to: ' + source[modelName].modeluri + '</div>');
        },
        
        error: function (jqXHR, textStatus, errorThrown)
        {
            console.log (jqXHR);
            console.log (textStatus);
            console.log (errorThrown);
        }
    });
	
	for (var i = 1; i < source[modelName].files.length; i++)
	{
		$.ajax({
			async:false,
			dataType: "html",
			type: "POST",
			data: { modelUri: source[modelName].modeluri , activeForm: "paste", "filetype-paste": "rdfxml", paste: source[modelName].files[i] },
			context: $("#response" + modelName),
			url: addUrl + '?m=' + source[modelName].modeluri,
			// complete, no errors
			success: function ( res ) 
			{
				$(this).append('<div>add File to: ' + source[modelName].modeluri + '</div>');
			},
			
			error: function (jqXHR, textStatus, errorThrown)
			{
				console.log (jqXHR);
				console.log (textStatus);
				console.log (errorThrown);
			}
		});
	}
}
