'use strict';

document.addEventListener('avalon23-call-popup', function (e) {
    let call_id = 'avalon23_filter_options_table';
    if (e.detail.call_id === call_id) {
        new Avalon23_FieldsOptions(JSON.parse(document.querySelector(`[data-table-id="${call_id}"]`).innerText), call_id);
    }
});

class Avalon23_FieldsOptions extends Avalon23_GeneratedTables {
    constructor(table_data, table_html_id) {
        super(table_data, table_html_id);
        this.save_filter_field_action = 'avalon23_save_filter_field_option';//reinit ajax action
        this.switcher_action = 'avalon23_save_filter_field_option';
        this.init_switchers_listener();
    }

    do_after_draw() {
        super.do_after_draw();
        //as we can have different elements in field value elements actons should be inited here
        this.init_html_items_action('select', 'change');
        this.init_html_items_action('input[type="text"]', 'change');
	this.init_html_items_action('textarea', 'change');

        //https://seballot.github.io/spectrum/#toc5
        this.table.querySelectorAll('.avalon23-color-field').forEach(item => {
            jQuery(item).spectrum({
                type: 'text',
                allowEmpty: true,
                showInput: true,
                change: function (color) {
                    item.setAttribute('value', color.toHexString());
                    item.dispatchEvent(new Event('change'));
                }
            });
        });

    }

    init_html_items_action(html_item_type, action) {
        this.table.querySelectorAll(`.avalon23_td_value ${html_item_type}.avalon23-filter-field-option`).forEach(item => {
            item.addEventListener(action, e => {
                e.stopPropagation();

                let filter_id = item.getAttribute('data-table-id');
                let field_id = item.getAttribute('data-field-id');
                let key = item.getAttribute('data-key');
                let redraw_table = item.getAttribute('data-redraw');
                this.message(avalon23_helper_vars.lang.saving, 'warning');

                fetch(this.settings.ajax_url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: this.prepare_ajax_form_data({
                        action: this.save_filter_field_action,
                        filter_id: filter_id,
                        field_id: field_id,
                        key: key,
                        value: item.value
                    })
                }).then(response => response.text()).then(data => {
                    if (typeof item.dataset.redraw != 'undefined') {

                        this.message(avalon23_helper_vars.lang.saving, 'warning');
                        fetch(this.settings.ajax_url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: this.prepare_ajax_form_data({
                                action: 'avalon23_form_redraw',
                                filter_id: filter_id,
                                field_id: field_id
                            })
                        }).then(response => response.text()).then(data => {                            
                            if(!avalon23_helper.is_json(data)){
                                data = atob(data);
                            }
                            data=JSON.parse(data);
                            new Avalon23_FieldsOptions(data, 'avalon23_filter_options_table');
                            document.querySelector('#avalon23_filter_options_table .table23-wrapper').remove();//first as it empty
                            this.message(avalon23_helper_vars.lang.saved);

                        }).catch((err) => {
                            this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
                        });

                    }
		    document.dispatchEvent(new CustomEvent('after_' + this.save_filter_field_action, {detail: {
			    self: this,
			    posted_id: field_id,
			    filter_id: filter_id,
			    field: key,
			    value: item.value
			}}));		    

                    this.message(avalon23_helper_vars.lang.saved);
                }).catch((err) => {
                    this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
                });

                return true;
            });
        });
    }

}