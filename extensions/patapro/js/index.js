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
    
function getHealthstate(selectHealthstate)
{
    var uri = "";
    uri =  $("#" + selectHealthstate + " option:selected").val();
    $.ajax({
        async:true,
        dataType: "html",
        type: "POST",
        data: {healthstateUri : uri},
        context: $("#healthstate"),
        url: url + "patapro/healthstate",
        // complete, no errors
        success: function ( res ) 
        {
            $(this).empty();
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

function loadProposalBox(proposalUri, patientUri)
{
    $.ajax({
        async:false,
        dataType: "html",
        type: "GET",
        data: {
            proposalUri : proposalUri,
            patientUri : patientUri,
        },
        context: $('#box'),
        url: url + "patapro/proposaldata",
            // complete, no errors
        success: function ( res ) 
        {
            if ('noproposaluri' == res)
            {
                alert ('No Proposal Uri found');
                returnValue = -1;
            }
            else
            {
                $(this).append(res);
                showProposalBox();
                returnValue = 1;
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

function showProposalBox()
{
    $('div.section-mainwindows').css('opacity', 'inherit');
    $('#box').modal({
        minWidth:750,
        persist : true,
        appendTo : '.content',
        animation : 'fade'
    });
}