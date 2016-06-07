/*
 * Controller
 *
 * Copyright (C) 2011 Nethesis S.r.l.
 */
(function( $ ) {
    var SUPER = $.nethgui.Component;
    $.widget('nethgui.Controller', SUPER, {
        _create: function () {
            SUPER.prototype._create.apply(this);
            this.element.children('ul.ActionList').remove();
            this.element.bind('nethguishow.' + this.namespace, $.proxy(this._onShow, this));
            this.element.children('.Action:eq(0)').trigger('nethguishow');
        },
        _onShow: function (e) {
            if(this.element.get(0) === e.target) {
                // redirect to first Action:
                e.stopPropagation();
                this.element.getChildren().first().trigger('nethguishow');
            } else {
                this.getChildren().filter(function (idx, action) {
                    return action !== e.target && $(action).find(e.target).length === 0;
                    }).trigger('nethguihide');
                }
            }
    });
}( jQuery ));
/*
 * Action
 *
 * Copyright (C) 2011 Nethesis S.r.l.
 */
(function( $ ) {
    var SUPER = $.nethgui.Component;
    $.widget('nethgui.Action', SUPER, {
        _create: function () {
            SUPER.prototype._create.apply(this);
            var behaviour = this.element.hasClass('Dialog') ? 'Dialog' : 'Default';
            this._form = this.element.children('form').on('submit.' + this.namespace, $.proxy(this._onSubmit, this));
            this.element.bind('nethguishow.' + this.namespace, $.proxy(this['_onShow' + behaviour], this));
            this.element.bind('nethguihide.' + this.namespace, $.proxy(this['_onHide' + behaviour], this));            
            this.element.bind('nethguireloaddata.' + this.namespace, $.proxy(this._onReloadData, this));
            this.element.bind('nethguisendquery.' + this.namespace, $.proxy(this._onSendQuery, this));
            this.element.bind('nethguisetmandatoryfields.' + this.namespace, $.proxy(this._onSetMandatoryFields, this))
        },
        _onSetMandatoryFields: function(e, fields) {
            if( ! $.isPlainObject(fields)) {
                return;
            }
            e.stopPropagation();
            $.each(fields, function(index, value) {
                $('#' + index).triggerHandler('nethguimandatory', [value ? true : false]);
            });
        },
        _onReloadData: function (e, delay) {
            var url = this._form.attr('action');
            var self = this;

            delay = parseInt(delay)
            if(delay < 1000) {
                delay = 1000;
            } else if ( delay > 10000) {
                delay = 10000;
            }
            window.setTimeout(function() {
                self._sendQuery(url, undefined, false)
            }, delay);            
            e.stopPropagation();
        },
        _onSendQuery: function(e, url, delay, freezeUi) {
            var self = this;
            
            if(freezeUi === undefined) {
                freezeUi = true;
            }

            if(delay === undefined || parseInt(delay) === 0) {
                this._sendQuery(url, undefined, freezeUi);
            } else if(parseInt(delay) > 0) {
                window.setTimeout(function() {
                    self._sendQuery(url, undefined, freezeUi)
                }, delay);
            }
            e.stopPropagation();
        },
        _onSubmit: function (e, additionalData) {
            e.preventDefault();
            e.stopPropagation();            
            var form = e.target.tagName.toLowerCase() === 'form' ? $(e.target) : $(e.target).closest('form');
            var formData = form.attr('enctype') === 'multipart/form-data' ? new FormData(form[0]) : form.serializeArray();
            if($.isArray(additionalData) && $.isArray(formData)) {
                $.merge(formData, additionalData);
            }

            if(form.attr('method').toUpperCase() === 'POST') {
                this._sendMutation(form.attr('action'), formData, true);
            } else {
                var url = form.attr('action');
                if($.isArray(formData) && formData.length > 0) {
                    url += '?' + $.param(formData);
                }
                this._sendQuery(url, undefined, true);
            }
            return false;
        },
        _onHideDefault: function (e) {
            this.element.hide();
        },
        _onShowDefault: function (e) {
            this.element.show();
        },
        _onHideDialog: function (e) {
            if(this._dialog === undefined) {
                this._initDialog();
            }
            if(this._dialog.dialog('isOpen')) {
                this._dialog.dialog("close");                
            }
            e.stopPropagation();
        },
        _onShowDialog: function (e) {
            if(this._dialog === undefined) {
                this._initDialog();
            }
            if(!this._dialog.dialog('isOpen')) {
                this._dialog.dialog("open");             
            }
            e.stopPropagation();
        },
        _onResizeDialog: function (e) {
            if(this._dialog === undefined) {
                return;
            }

            this._dialog.dialog('option', 'width', this._dialog.dialog('option', 'height', 'auto'));

            e.stopPropagation();
        },
        _initDialog: function() {
            var self = this;
            
            var titleNode = this.element.find('h1, h2, h3').first().bind('nethguichanged.' + this.namespace, function(e, value) {
                self._dialog.dialog('option', 'title', value);
            }).hide();

            var content = this.element.children().detach();

            this._dialog = $('<div class="Dialog"></div>');
            this.element.append(this._dialog).hide();
            this._dialog.append(content).dialog({
                modal: true,
                autoOpen: false,
                position: ['center', 50],
                title: titleNode.text()
            }).bind('nethguicancel.' + this.namespace, $.proxy(this._onHideDialog, this))
            .bind('nethguiresizeend.' + this.namespace, $.proxy(this._onResizeDialog, this));
        }
    });
}( jQuery ));
/*
 * Form
 *
 * Copyright (C) 2011 Nethesis S.r.l.
 */
(function( $ ) {
    var SUPER = $.nethgui.Component;
    $.widget('nethgui.Form', SUPER, {
        _updateView: function (value) {
            this.element.attr('action', value);
        }
    });
}( jQuery ));
