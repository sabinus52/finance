var protoTransactions = {
    /**
     * Chargement des transactions suite à un changement du filtre
     * @returns
     */
    loadListTransactions: function () {
        var table = $("#tableTransactions");
        var form = $("#formFilter");
        var isDisabled = form.find("select").attr("disabled");
        console.log(form.find("select").attr("disabled"));
        if (isDisabled == "disabled") {
            return;
        }

        $.ajax({
            type: form.attr("method"),
            url: form.attr("action"),
            data: new FormData(form.get(0)),
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                table.html(
                    '<p class="text-center"><img alt="" src="/bundles/olixbackoffice/images/spinner-rectangle.gif"></p>'
                );
            },
            success: function (data) {
                table.html(data);
            },
            error: function (data) {
                alert(
                    "Une erreur est survenue lors de validation du formulaire."
                );
            },
        });
    },
};

(function () {
    /**
     * Changement du formulaire de filtre
     */
    $("#formFilter select").on("change", function (event) {
        var widget = $(this);
        var table = $("#tableTransactions");
        console.log(this.value);
        protoTransactions.loadListTransactions();
    });

    /**
     * Affiche le modal du montant à rapprocher
     */
    $("#modalRecon").on("submit", "form", $("#modalRecon"), function (event) {
        var form = $(this);
        var modal = event.data;

        $.ajax({
            type: form.attr("method"),
            url: form.attr("action"),
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                modal
                    .find(".modal-body")
                    .html(
                        '<p class="text-center"><img alt="" src="/bundles/olixbackoffice/images/spinner-rectangle.gif"></p>'
                    );
                modal.find(".modal-footer").hide();
            },
            success: function (data) {
                console.log(data);
                location.replace(data);
            },
            error: function (data) {
                if (data.status == 422) {
                    modal.find(".modal-content").html(data.responseText);
                } else {
                    alert(
                        "Une erreur est survenue lors de validation du formulaire."
                    );
                }
            },
        });
        return false;
    });

    /**
     * Click sur chaque transaction à rapprocher
     */
    $("input[name='reconciliation']").on("click", function (event) {
        var check = $(this);
        var route = check.data("remote");
        console.log(route);
        $.getJSON(route, function (data) {
            console.log(data);
            var gab = parseFloat($("#gab").val());
            console.log(gab);
            gab = parseFloat(gab + data.amount).toFixed(2);
            console.log(gab);
            //gab = parseFloat(gab).toFixed(2);
            //console.log(gab);
            if (gab == 0 || gab == -0) {
                $("#btGab").attr("disabled", false);
            } else {
                $("#btGab").attr("disabled", true);
            }
            $("#gab").val(gab);
            $("#txtGab").text(
                gab.toLocaleString(undefined, { minimumFractionDigits: 2 })
            );
        });
    });

    protoTransactions.loadListTransactions();
})();
