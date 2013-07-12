$().ready(function() {
    $('#dispedia-Cda2rdfModule-file-input').change(function() {
        if ("" == $('#dispedia-Cda2rdfModule-file-input').val()) {
            $('#dispedia-Cda2rdfModule-buttons').hide();
        }
        else {
            $('#dispedia-Cda2rdfModule-buttons').show();
        }
    });
    $('#dispedia-Cda2rdfModule-resetButton').click(function() {
        $('#dispedia-Cda2rdfModule-file-input').val();
        $('#dispedia-Cda2rdfModule-buttons').hide();    
    });
    $('#dispedia-Cda2rdfModule-addPatientFileButton').click(function() {
        $('#dispedia-Cda2rdfModule-form').submit();    
    });
});