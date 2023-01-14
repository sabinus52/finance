import $ from "jquery";
import "bootstrap/js/src/modal.js";

/**
 * Constants
 */
const NAME = "OlixModal";
const DATA_KEY = "olix.modal";
const EVENT_KEY = `.${DATA_KEY}`;
const JQUERY_NO_CONFLICT = $.fn[NAME];
const EVENT_LOADED = `loaded${EVENT_KEY}`;
const EVENT_OVERLAY_ADDED = `overlay.added${EVENT_KEY}`;
const EVENT_OVERLAY_REMOVED = `overlay.removed${EVENT_KEY}`;

const SELECTOR_TRIGGER = `[data-toggle="olix-modal"]`;

const Default = {
    params: {},
    id: "",
    isInit: false,
    target: "",
    urlLoad: "", // A remplir pour charger la page dans le modal (mode édition)
    urlValid: "", // A remplir pour uniquement la validation (mode suppression)
    overlayTemplate:
        '<p class="text-center"><i class="fas fa-3x fa-spinner fa-spin"></i></p>',
    onLoadDone(response) {
        console.log("done", response);
        if (response == "OK") {
            location.reload();
        } else {
            location.replace(response);
        }
    },
};

class OlixModal {
    /**
     * Constructeur
     * @param {*} element
     * @param {*} settings
     */
    constructor(element, settings) {
        this._element = element;
        this._settings = $.extend({}, Default, settings);

        // Vérification des paramètres
        if (this._settings.target == "")
            throw new Error("'data-target' is not defined.");
        if (this._settings.urlLoad == "") {
            let href = this._element.attr("href");
            if (href != undefined && href != "") this._settings.urlLoad = href;
        }

        // Détermination des objets
        this._modal = $(this._settings.target);
        this._content = this._modal.find(".modal-content");
    }

    /**
     * Charge la page de formulaire
     */
    load() {
        this._modal.modal("show");
        this._modal.data("id", this._settings.id);

        console.log(this._settings.urlLoad);
        if (this._settings.urlLoad != "") {
            this._content.load(this._settings.urlLoad, () => {
                olixBackOffice.initForm(); // FIXME
                // Evenement sur la validation du formulaire
                this._modal.on("submit", "form", () => {
                    this.valid();
                    return false;
                });
            });
        }
    }

    /**
     * Valide le formulaire
     * @returns bool
     */
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
                    olixBackOffice.initForm(); // FIXME
                } else {
                    alert(
                        "Une erreur est survenue lors de validation du formulaire."
                    );
                }
            });

        return false;
    }

    // Private

    _init() {
        console.log("init", this);
        // On doit détruire les évenements pour éviter la multiplication
        this._modal.on("hide.bs.modal", () => {
            this._modal.off("submit");
        });
        // Flag pour savoir si l'objet est init au chargement de la page ou bien suite à un chargement ajax
        if (!this._settings.isInit) {
            this.load();
        }
    }

    // Static
    static _jQueryInterface(config) {
        return this.each(function () {
            let data = $(this).data(DATA_KEY);
            const _config = $.extend(
                {},
                Default,
                $(this).data(),
                typeof config === "object" ? config : {}
            );

            if (!data) {
                data = new OlixModal($(this), _config);
                $(this).data(DATA_KEY, data);
                data._init();
            } else if (typeof config === "string") {
                if (typeof data[config] === "undefined") {
                    throw new TypeError(`No method named "${config}"`);
                }

                data[config]();
            } else if (typeof config === "undefined") {
                data._init();
            }
        });
    }
}

/**
 * Data API
 */
$(document).on("click", SELECTOR_TRIGGER, function (event) {
    if (event) {
        event.preventDefault();
    }

    OlixModal._jQueryInterface.call($(this), "load");
});

$(() => {
    $(SELECTOR_TRIGGER).each(function () {
        OlixModal._jQueryInterface.call($(this), { isInit: true });
    });
});

/**
 * jQuery API
 */
$.fn[NAME] = OlixModal._jQueryInterface;
$.fn[NAME].Constructor = OlixModal;
$.fn[NAME].noConflict = function () {
    $.fn[NAME] = JQUERY_NO_CONFLICT;
    return OlixModal._jQueryInterface;
};

export default OlixModal;
