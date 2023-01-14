import $ from "jquery";

/**
 * Constants
 */
const NAME = "Reconciliation";
const DATA_KEY = "olix.reconciliation";
const JQUERY_NO_CONFLICT = $.fn[NAME];

const SELECTOR_TRIGGER = `input[name='reconciliation']`;
const BUTTON_VALID = "#btnGab";

const Default = {
    params: {},
    trigger: SELECTOR_TRIGGER,
};

class Reconciliation {
    constructor(element, settings) {
        this._element = element;
        this._settings = $.extend({}, Default, settings);
    }

    /**
     * Fait le rapprochement
     */
    doCheck() {
        console.log("doCheck", this._settings.remote);
        $.getJSON(this._settings.remote, (response) => {
            console.log(response);
            var gab = this._calculate(response.amount);
            this._print(gab);
            this._toggleButton(gab);
        });
    }

    /**
     * Calcule le nouvel écart
     * @param {*} amount 
     * @returns 
     */
    _calculate(amount) {
        var gab = 0;
        var $gab = $(this._settings.gab).find("input").first();
        gab = parseFloat($gab.val()) + parseFloat(amount);
        gab = parseFloat(gab).toFixed(2);
        $gab.val(gab);
        return gab;
    }

    /**
     * Affiche l'écart restant
     * @param {String} gab
     */
    _print(gab) {
        var $gab = $(this._settings.gab).find("strong").first();
        $gab.text(gab.toLocaleString(undefined, { minimumFractionDigits: 2 }));
    }

    /**
     * Active ou désactive le boutton de validation
     * @param {*} gab 
     */
    _toggleButton(gab) {
        if (gab == 0 || gab == -0) {
            $(BUTTON_VALID).attr("disabled", false);
        } else {
            $(BUTTON_VALID).attr("disabled", true);
        }
    }

    // Private

    _init() {
        this._element.on("click", () => {
            this.doCheck();
        });
    }

    // Static
    static _jQueryInterface(config) {
        return this.each(function () {
            let data = $(this).data(DATA_KEY);
            const _config = $.extend(
                {},
                Default,
                typeof config === "object" ? config : $(this).data()
            );

            if (!data) {
                data = new Reconciliation($(this), _config);
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
$(() => {
    $(SELECTOR_TRIGGER).each(function () {
        Reconciliation._jQueryInterface.call($(this));
    });
});

/**
 * jQuery API
 */
$.fn[NAME] = Reconciliation._jQueryInterface;
$.fn[NAME].Constructor = Reconciliation;
$.fn[NAME].noConflict = function () {
    $.fn[NAME] = JQUERY_NO_CONFLICT;
    return Reconciliation._jQueryInterface;
};

export default Reconciliation;
