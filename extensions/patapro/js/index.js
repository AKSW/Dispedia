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

function loadProposalBoxCheck(proposalUri, patientUri, proposalMd5)
{
    if($('#input' + proposalMd5).is(':checked')) {
        loadProposalBox(proposalUri, patientUri, proposalMd5)
    } else {
        $("tr#" + proposalMd5 + " td#proposalStatus span").attr("data-status", "delete");
        submitProposalBox(proposalMd5, patientUri,proposalUri);
        $("tr#" + proposalMd5 + " td#proposalStatus span").attr("data-status", "new");
        updateStatus(proposalMd5);
    }
}

function loadProposalBox(proposalUri, patientUri, proposalMd5)
{
    status = $("tr#" + proposalMd5 + " td#proposalStatus span").attr("data-status");
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
        appendTo : '#patapro'
    });
}

/**
 * close proposal box
 */
function submitProposalBox(proposalMd5, patientUri, proposalUri) 
{
    status = $("tr#" + proposalMd5 + " td#proposalStatus span").attr("data-status");
    if ("delete" == status) {
        postData = "patientUri=" + patientUri + "&proposalUri=" + proposalUri;
    } else {
        postData = $('#descriptionReceivedStatus').serialize();
    }
    // send formulas to submit action on server
    $.ajax({
        async:false,
        data: postData + "&do=save&status=" + status,
        dataType: "json",
        type: "POST",
        url: url + "patapro/proposaldata/",
    
        // complete, no errors
        success: function ( res )
        {
            proposalboxdata = {};
            
            if (0 == res.error && 0 < res.messages.length) {
                if (0 < messages.length) {
                    messages = messages.concat(res.messages);
                } else {
                    messages = messages.concat(res.messages);
                    showMessage();
                }
            }
            closeProposalBox();
            status = $("tr#" + proposalMd5 + " td#proposalStatus span").attr("data-status");
            if ('new' == status) {
                $("tr#" + proposalMd5 + " td#proposalStatus span").attr("data-status", "isPending");
                updateStatus(proposalMd5);
            }
            
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

function showMessage()
{
    if (0 < messages.length) {
        message = messages.shift();
        $('#messageBox').append('<div class="messagebox ' + message.type + '"> ' + message.text + '</div>');
        $('#messageBox').fadeIn(700).delay(2000).slideUp(700, function() {hideMessage();});
    }
}
function hideMessage()
{
    $('#messageBox').empty();
    showMessage();
}

/**
 * close proposal box
 */
function closeProposalBox(proposalMd5) 
{
    $('#box').empty();
    $.modal.close();
    
    status = $("tr#" + proposalMd5 + " td#proposalStatus span").attr("data-status");
    if ('new' == status) {
        $('#input' + proposalMd5).attr('checked', false);
    }
}

/**
 * set a new status for a proposal
 */
function updateStatus(proposalMd5)
{
    status = $("tr#" + proposalMd5 + " td#proposalStatus span").attr("data-status");
    $("tr#" + proposalMd5 + " td#proposalStatus span").html(statusArray[status]);
}