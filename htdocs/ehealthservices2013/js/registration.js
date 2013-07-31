$().ready(function() {
    $("#registrationForm").validate({ 
        rules: { 
            Vorname: "required",
            Nachname: "required",
            Email: {
                required: true, 
                email: true
            },
            Email2: {
                required: true, 
                email: true,
				equalTo: "#Email"
            },
            StrasseHausnummer: "required",
            PLZ: "required",
            Ort: "required"
        }, 
        messages: {
            Vorname: "Bitte Vorname eintragen.",
            Nachname: "Bitte Nachname eintragen.",
            Email: {
				required: "Bitte eine E-Mail-Adresse eintragen",
				email: "Bitte eine korrekte E-Mail-Adresse eintragen"
			},
            Email2: {
				required: "Bitte eine E-Mail-Adresse eintragen",
				email: "Bitte eine korrekte E-Mail-Adresse eintragen",
				equalTo: "E-Mail-Adressen nicht identisch"
			},
            Telefonnummer: "Bitte Telefonnummer eintragen.",
            Institution: "Bitte Institution eintragen.",
            StrasseHausnummer: "Bitte Straße und Hausnummer eintragen.",
            PLZ: "Bitte Postleitzahl eintragen.",
            Ort: "Bitte Ort eintragen."
        } 
    }); 
}); 
