'use strict';
class Avalon23_FilterMetaTable extends Avalon23_GeneratedTables {
    constructor(table_data, table_html_id) {
        super(table_data, table_html_id);
	this.filter_id_es = table_data.request_data.posted_id;
        this.save_filter_field_action = 'avalon23_save_filter_meta_field';//reinit ajax action
        this.delete_action = 'avalon23_delete_filter_meta';//ajax action for deleting

        this.init_json_fields_saving();
        document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-modal-inner-content').addEventListener('scroll', (e) => {
            this.add_scroll_action(document.getElementById('tabs-meta').querySelector('.avalon23-meta-filters-table-zone'));
        });
    }

    init_json_fields_saving() {
        super.init_json_fields_saving();

        document.addEventListener('after_' + this.save_filter_field_action, function (e) {
            e.stopPropagation();
            if (e.detail.field === 'title' || e.detail.field === 'meta_key') {
                avalon23_filter_items_table.refresh();
            }
        });

    }
    save(posted_id, field, value, ajax_action = null, additional = '', custom_data = null) {
        this.message(avalon23_helper_vars.lang.saving + ' ...', 'warning');
        let action = this.save_filter_field_action;

        if (ajax_action) {
            action = ajax_action;
        }

        let form_data = {
            action: action,
            field: field,
            posted_id: posted_id,
            value: value,
            additional: additional
        };

        if (custom_data) {
            form_data = {...form_data, ...custom_data};
        }

        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin', // 'include', default: 'omit'
            body: this.prepare_ajax_form_data(form_data)
        }).then(response => response.text()).then(data => {
            this.message(avalon23_helper_vars.lang.saved);
            if(!avalon23_helper.is_json(data)){
                data = atob(data);
            }
            document.dispatchEvent(new CustomEvent('after_' + this.save_filter_field_action, {detail: {
                    self: this,
                    posted_id: posted_id,
                    field: field,
                    value: value
                }}));
	    let item_field = document.querySelector('#avalon23-filter-items-meta-table .avalon23_td_cell[data-key="'+field+'"]');
	    if (typeof item_field.querySelector("[data-redraw='1']") == 'object') {
		fetch(this.settings.ajax_url, {
		    method: 'POST',
		    credentials: 'same-origin',
		    body: this.prepare_ajax_form_data({
			action: 'avalon23_get_filter_meta',
			filter_id: this.filter_id_es 
		    })
		}).then(response => response.text()).then(data => {                            
		    if(!avalon23_helper.is_json(data)){
			data = atob(data);
		    }
		    data=JSON.parse(data);
		    data.request_data.posted_id = this.filter_id_es;
		    new Avalon23_FilterMetaTable(data, 'avalon23-filter-items-meta-table');
		    document.querySelector('#avalon23-filter-items-meta-table .table23-wrapper').remove();//first as it empty
		    this.message(avalon23_helper_vars.lang.saved);

		}).catch((err) => {
		    this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
		});
	    }	    
	   

        }).catch((err) => {
            console.log(err);
            this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
        });
    }    

    do_after_draw() {
        super.do_after_draw();
    }

    create() {
        this.message(avalon23_helper_vars.lang.creating + ' ...', 'warning');
        let meta_f = document.querySelectorAll('.avalon23-meta-filters-table-zone tbody tr');
        if(meta_f.length>1){
            alert(avalon23_helper_vars.lang.free_meta);
            return false;
        }
        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin', // 'include', default: 'omit'                   
            body: this.prepare_ajax_form_data({
                action: 'avalon23_create_meta',
                filter_id: this.request_data.posted_id
            })
        }).then(response => response.json()).then(data => {
            this.message(avalon23_helper_vars.lang.created);
            this.settings.table_data = data;
            this.draw_data(null);
        }).catch((err) => {
            this.message(err, 'error', 5000);
        });
    }

}