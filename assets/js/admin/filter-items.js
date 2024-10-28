'use strict';
class Avalon23_FilterItems extends Avalon23_GeneratedTables {
    constructor(table_data, table_html_id) {
        super(table_data, table_html_id);
	
        this.save_filter_field_action = 'avalon23_save_filter_item_field';//reinit ajax action
        this.delete_action = 'avalon23_delete_filter_field';//ajax action for deleting
        this.switcher_action = 'avalon23_save_filter_item_field';

        //call it here because name of the action here is another and no init applied after parent constructor init (super)
        this.init_switchers_listener();
        this.init_json_fields_saving();

        document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-modal-inner-content').addEventListener('scroll', (e) => {
            this.add_scroll_action(document.getElementById('tabs-filter-items').querySelector('.avalon23-filters-table-zone'));
        });
    }

    //destructor
    destructor() {
        console.log('destructor here');
        if (this.change_cell_ev_handler) {
            document.removeEventListener('after_' + this.save_filter_field_action, this.change_cell_ev_handler);
        }
    }

    do_after_draw() {
        super.do_after_draw();
        let _this = this;

        setTimeout(() => {
            jQuery('.avalon23-filters-table-zone table tbody').sortable({
                items: 'tr',
                update: function (event, ui) {
                    let tr_pids = [];
                    _this.table.parentElement.querySelectorAll('tbody > tr').forEach(function (tr) {
                        tr_pids.push(parseInt(tr.getAttribute('data-pid'), 10));
                    });

                    if (tr_pids.length > 1) {
                        _this.save(_this.request_data.posted_id, 'pos_num', tr_pids);
                    }
                },
                opacity: 0.8,
                cursor: 'crosshair',
                handle: '.avalon23-tr-drag-and-drope',
                placeholder: 'avalon23-tr-highlight'
            });

        }, 333);
	
        document.addEventListener('after_avalon23_save_filter_field_option', function (e) {
            e.stopPropagation();
	    if(e.detail.field.indexOf('front-view') != -1){
		_this.refresh();
	    }
        });	

    }

    create(prepend = true) {
        this.message(avalon23_helper_vars.lang.creating + ' ...', 'warning');

        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: this.prepare_ajax_form_data({
                action: 'avalon23_create_filter_field',
                posted_id: this.settings.posted_id,
                prepend: Number(prepend)
            })
        }).then(response => response.json()).then(data => {
            this.message(avalon23_helper_vars.lang.created);
            this.settings.table_data = data;
            this.request_data.orderby = 'pos_num';//to allow new row appear on its position
            this.request_data.order = 'asc';
            this.draw_data(null);
        }).catch((err) => {
            this.message(err, 'error', 5000);
        });
    }
    do_after_save(field, value, posted_id){
	if(field == 'field_key'){
	    this.refresh();
	}
    }
    refresh() {
        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: this.prepare_ajax_form_data({
                action: 'avalon23_refresh_filter_items_table',
                posted_id: this.settings.posted_id
            })
        }).then(response => response.json()).then(data => {
            this.settings.table_data = data;
            this.draw_data(null);
        }).catch((err) => {
            this.message(err, 'error', 5000);
        });
    }

    close_popup() {
        document.getElementById('avalon23-popup-filters-template').style.display = 'none';
    }
}