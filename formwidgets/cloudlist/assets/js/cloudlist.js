/*
 * List Widget
 *
 * Dependences:
 * - Modification de listWidget j'ai pas compris grand chose...
 */
+function ($) {
    "use strict";

    var ListWidget2 = function (element, options) {
        var $el = this.$el = $(element);
        this.options = options || {};
        this.update()
    }

    ListWidget2.DEFAULTS = {
    }

    ListWidget2.prototype.update = function () {

        var list = this.$el


        /*
         * Bind check boxes
         */
        $('.list-checkbox input[type="checkbox"]', list).each(function () {
            var $el = $(this)
            if ($el.is(':checked')) {
                $el.closest('tr').addClass('active')
            }
        })

        list.on('change', '.list-checkbox input[type="checkbox"]', function () {
            var $el = $(this),
                checked = $el.is(':checked')

            if (checked) {
                $el.closest('tr').addClass('active')
            }
            else {
                //$('.list-checkbox input[type="checkbox"]', head).prop('checked', false)
                $el.closest('tr').removeClass('active')
            }
        })
    }

    ListWidget2.prototype.getChecked = function () {

        var list = this.$el

        return $('.list-checkbox input[type="checkbox"]', list).map(function () {
            var $el = $(this)
            if ($el.is(':checked')) {
                return $el.val()
            }
        }).get();
    }

    // ListWidget2.prototype.toggleChecked = function (el) {
    //     console.log('toggleChecked')
    //     var $checkbox = $('.list-checkbox input[type="checkbox"]', $(el).closest('tr'))
    //     $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change')
    // }

    // LIST WIDGET PLUGIN DEFINITION
    // ============================

    var old = $.fn.listWidget2

    $.fn.listWidget2 = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result

        this.each(function () {
            var $this = $(this)
            var data = $this.data('oc.listwidget2')
            var options = $.extend({}, ListWidget2.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) {
                $this.data('oc.listwidget2', (data = new ListWidget2(this, options)))
                if (typeof option == 'string') {
                    result = data[option].apply(data, args)
                    if (typeof result != 'undefined') {
                        return false
                    }
                }
            }
        })

        return result ? result : this
    }

    $.fn.listWidget2.Constructor = ListWidget2

    // LIST WIDGET NO CONFLICT
    // =================

    $.fn.listWidget2.noConflict = function () {
        $.fn.listWidget2 = old
        return this
    }

    // LIST WIDGET HELPERS
    // =================

    if ($.oc === undefined) {
        $.oc = {}

    // $.oc.listToggleChecked = function (el) {
    //     $(el)
    //         .closest('[data-control="listwidget2"]')
    //         .listWidget2('toggleChecked', el)
    // }

    // $.oc.listGetChecked = function (el) {
    //     return $(el)
    //         .closest('[data-control="listwidget2"]')
    //         .listWidget2('getChecked')
    // }

    // LIST WIDGET DATA-API
    // ==============

        $(document).render(function () {
            $('[data-control="listwidget2"]').listWidget2();
        })
    }
}(window.jQuery);