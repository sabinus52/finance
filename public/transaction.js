var protoTransactions = {
    loadListTransactions: function () {
        var table = $("#tableTransactions");
        var form = $("#formFilter");
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
    $("#formFilter select").on("change", function (event) {
        var widget = $(this);
        var table = $("#tableTransactions");
        console.log(this.value);
        protoTransactions.loadListTransactions();
    });

    protoTransactions.loadListTransactions();
})();
