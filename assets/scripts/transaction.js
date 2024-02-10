import $ from "jquery";

/**
 * Constants
 */
const NAME = "Transaction";
const DATA_KEY = "olix.transaction";
const EVENT_KEY = `.${DATA_KEY}`;
const JQUERY_NO_CONFLICT = $.fn[NAME];
const EVENT_LOADED = `loaded${EVENT_KEY}`;
const EVENT_OVERLAY_ADDED = `overlay.added${EVENT_KEY}`;
const EVENT_OVERLAY_REMOVED = `overlay.removed${EVENT_KEY}`;

const SELECTOR_TRIGGER = `form.filter select`;

const Default = {
    params: {},
    trigger: "select",
    content: ".card-body",
    loadOnInit: true,
    loadErrorTemplate: true,
    overlayTemplate:
        '<div class="overlay dark"><i class="fas fa-10x fa-spinner fa-spin"></i></div>',
    errorTemplate: '<span class="text-danger"></span>',
};

class Transaction {
    constructor(element, settings) {
        this._element = element;
        this._card = element.parents(".card").first();
        this._form = element.parents("form").first();
        this._settings = $.extend({}, Default, settings);
        this._overlay = $(this._settings.overlayTemplate);
    }

    load() {
        this._addOverlay();

        $.get(
            this._form.attr("action"),
            this._form.serializeArray(),
            (response) => {
                this._card.find(this._settings.content).html(response);
                this._removeOverlay();
            }
        ).fail((jqXHR, textStatus, errorThrown) => {
            this._removeOverlay();
            if (this._settings.loadErrorTemplate) {
                const msg = $(this._settings.errorTemplate).text(errorThrown);
                this._card.find(this._settings.content).empty().append(msg);
            }
        });

        $(this._element).trigger($.Event(EVENT_LOADED));
    }

    _addOverlay() {
        this._card.append(this._overlay);
        $(this._element).trigger($.Event(EVENT_OVERLAY_ADDED));
    }

    _removeOverlay() {
        this._card.find(this._overlay).remove();
        $(this._element).trigger($.Event(EVENT_OVERLAY_REMOVED));
    }

    // Private

    _init() {
        $(this)
            .find(this._settings.trigger)
            .on("change", () => {
                this.load();
            });

        if (this._settings.loadOnInit) {
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
                typeof config === "object" ? config : $(this).data()
            );

            if (!data) {
                data = new Transaction($(this), _config);
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
$(document).on("change", SELECTOR_TRIGGER, function (event) {
    if (event) {
        event.preventDefault();
    }

    Transaction._jQueryInterface.call($(this), "load");
});

$(() => {
    $(SELECTOR_TRIGGER).each(function () {
        Transaction._jQueryInterface.call($(this));
    });
});

/**
 * jQuery API
 */
$.fn[NAME] = Transaction._jQueryInterface;
$.fn[NAME].Constructor = Transaction;
$.fn[NAME].noConflict = function () {
    $.fn[NAME] = JQUERY_NO_CONFLICT;
    return Transaction._jQueryInterface;
};

export default Transaction;
