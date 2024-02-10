import $ from "jquery";


const Project = {

    valid() {
        let button = this._modal.find(`[type="submit"]`);
        let form = this._modal.find("form");

        // Overlay
        button.attr("disabled", true);
        this._content.html(this._settings.overlayTemplate);

        // Quelle URL utilisée
        let url = "";
        if (this._settings.urlValid != "") url = this._settings.urlValid;
        else url = this._settings.urlLoad;

        console.log("valid", this._settings.urlLoad);

        $.ajax({
            type: form.attr("method"),
            url: url,
            data: new FormData(form[0]),
            cache: false,
            contentType: false,
            processData: false,
        })
            .done((data) => {
                this._settings.onLoadDone.call($(this), data);
            })
            .fail((jqXHR) => {
                if (jqXHR.status == 422) {
                    console.log("error 422");
                    this._content.html(jqXHR.responseText);
                    Olix.initForms();
                } else {
                    alert(
                        "Une erreur est survenue lors de validation du formulaire."
                    );
                }
            });

        return false;
    },

    test(event) {
        let button = $(event.currentTarget);
        let modal = event.data;
        let form = modal.find("form");
        console.log(form);
        button.attr("disabled", true);


        $.ajax({
            type: form.attr("method"),
            url: form.attr("action"),
            data: new FormData(form[0]),
            cache: false,
            contentType: false,
            processData: false,
        })
            .done((data) => {
                location.reload();
            })
            .fail(() => {
                alert("Une erreur est survenue lors de validation du formulaire.");
            });


        return false;
    }
}


$('#modalProject [type="submit"]').on('click', $('#modalProject'), function (event) {
    return Project.test(event);
})

export default Project;