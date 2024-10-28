'use strict';
class Avalon23_GeneratedSeoRules extends Avalon23_GeneratedTables {
    constructor(table_data, table_html_id) {
        super(table_data, table_html_id);
        this.save_filter_field_action = 'avalon23_save_seo_rules_field';//reinit ajax action
        this.switcher_action = 'avalon23_save_seo_rules_field';
        this.init_switchers_listener();
        this.init_json_fields_saving();
    }

    do_after_draw() {
        super.do_after_draw();
    }

    create() {
        this.message(avalon23_helper_vars.lang.creating, 'warning');
	let url = document.querySelector('.avalon23_seo_prefix').value;
	let request = document.querySelector('.avalon23_seo_search_link').value;
	document.querySelector('.avalon23_seo_search_link').value = "";
	request = request.trim().replace(/^\/|\/$/g, '');
	if (request){
	    request +=  '/';
	}
	url = '/' + url + '/' + request;

        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: this.prepare_ajax_form_data({
                action: 'avalon23_create_seo_rules_field',
                url: url
            })
        }).then(response => response.json()).then(data => {
            this.message(avalon23_helper_vars.lang.created);
            avalon23_seo_rules_table.settings.table_data = data;
            avalon23_seo_rules_table.draw_data(null);
        }).catch((err) => {
            this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
        });
    }

    delete(id) {
        if (confirm(avalon23_helper_vars.lang.sure)) {
            this.message(avalon23_helper_vars.lang.deleting, 'warning');
            avalon23_seo_rules_table.delete_row(id);

            fetch(this.settings.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: this.prepare_ajax_form_data({
                    action: 'avalon23_delete_seo_rules_field',
                    id: id
                })
            }).then(response => response.json()).then(data => {
                this.message(avalon23_helper_vars.lang.deleted);
                avalon23_seo_rules_table.settings.table_data = data;
            }).catch((err) => {
                this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
            });
        }
    }
}


