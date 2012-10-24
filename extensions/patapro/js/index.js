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
            $('#spanHealthStateTypeLabel').empty();
            $('#spanHealthStateTypeLabel').html(jQuery.parseJSON(healthstates)[uri]['typeLabel']);
        },
        
        error: function (jqXHR, textStatus, errorThrown)
        {
            console.log (jqXHR);
            console.log (textStatus);
            console.log (errorThrown);
        }
    });
}

function loadProposalBoxCheck(proposalUri, patientUri, proposalMd5, status)
{
    if($('#input' + proposalMd5).is(':checked'))
        loadProposalBox(proposalUri, patientUri, proposalMd5, status)
}

function loadProposalBox(proposalUri, patientUri, proposalMd5, status)
{
    if ('new' == status)
    {
        $('#input' + proposalMd5).attr('checked', true);
    }
    
    $.ajax({
        async:false,
        dataType: "html",
        type: "GET",
        data: {
            proposalUri : proposalUri,
            patientUri : patientUri,
            status : status
        },
        context: $('#box'),
        url: url + "patapro/proposaldata",
            // complete, no errors
        success: function ( res ) 
        {
            if ('noproposaluri' == res)
            {
                alert ('No Proposal Uri found');
            }
            else
            {
                $(this).append(res);
                showProposalBox();
            }
        },
        
        error: function (jqXHR, textStatus, errorThrown)
        {
            console.log (jqXHR);
            console.log (textStatus);
            console.log (errorThrown);
        }
    });
}

function showProposalBox()
{
    $('div.section-mainwindows').css('opacity', 'inherit');
    $('#box').modal({
        minWidth:750,
        persist : true,
        appendTo : '.content'
    });
}

/**
 * close proposal box
 */
function submitProposalBox() 
{
    // send formulas to submit action on server
    $.ajax({
        async:true,
        data: $('#descriptionReceivedStatus').serialize() + "&do=save",
        dataType: "json",
        type: "POST",
        url: url + "patapro/proposaldata/",
    
        // complete, no errors
        success: function ( res ) 
        {
            console.log ("response");
            console.log ( res );
    
            proposalboxdata = {};
            
            closeProposalBox();
            
        },
        
        error: function (jqXHR, textStatus, errorThrown)
        {
            console.log (jqXHR);
            console.log (textStatus);
            console.log (errorThrown);
        },
        
        complete: function ()
        {
            console.log ( "complete" );
        }
    });
}

/**
 * close proposal box
 */
function closeProposalBox() 
{
    $('#box').empty();
    $.modal.close();
}