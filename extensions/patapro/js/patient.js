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

var boxopen = false;

/**
* 
*/
function openProposalBox(resource, patientUri) {
    
    boxopen = true;
    
    // load the form from server
    if (1 == loadProposal ('#mainbox', resource, patientUri))
    {
        // because firefox is slow
        $('div.section-mainwindows').css('opacity', 'inherit');
        showModal('#proposalbox');
    }
}

/**
* 
*/
function showModal(id)
{
    $(id).modal({
        minWidth:750,
        persist : true,
        appendTo : '#patapro',
        onClose : function () {
                closeProposalBox('#mainbox');
        }
    });
}

/**
* 
*/
function loadProposal (context, resource, patientUri)
{
    var returnValue = 0;
    var action = 'newform';
    var data = '';
    
    if (boxopen)
    {
        action = 'report';
        data = {
            r : resource
        };
        $.ajax({
            async:false,
            dataType: "html",
            type: "GET",
            data: data,
            context: $(context),
            url: url + "formgenerator/" + action,
                // complete, no errors
            success: function ( res ) 
            {
                if ('noformularfound' == res)
                {
                    alert ('No Formular found');
                    returnValue = -1;
                }
                else
                {
                    $(this).append(res);
                    
                    returnValue = 1;
                    // set selectedResource back to the patient
                    $.ajax({
                        async:false,
                        dataType: "html",
                        type: "POST",
                        url: url + "resource/properties/?m=http%3A%2F%2Fpatients.dispedia.de%2F&r=" + patientUri,
                        
                        error: function (jqXHR, textStatus, errorThrown)
                        {
                            console.log (jqXHR);
                            console.log (textStatus);
                            console.log (errorThrown);
                        }
                    });
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
    }
    return returnValue;
}

/**
 * close boxform
 */
function closeProposalBox(id) 
{
    $(id).empty();
    $.modal.close();
    boxopen = false;
}