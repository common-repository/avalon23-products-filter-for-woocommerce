'use strict';
class Avalon23_GeneratedSettings extends Avalon23_GeneratedTables {
    constructor(table_data, table_html_id) {
        super(table_data, table_html_id);
        this.save_filter_field_action = 'avalon23_save_settings_field';//reinit ajax action
        this.switcher_action = 'avalon23_save_settings_field';
        this.init_switchers_listener();

        document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-modal-inner-content').addEventListener('scroll', (e) => {
            this.add_scroll_action(document.getElementById('tabs-options').querySelector('.avalon23-options-filters-table-zone'));
        });
    }

    do_after_draw() {
        super.do_after_draw();
	document.dispatchEvent(new CustomEvent('avalon23-draw-settings-table', {detail: {
	    settings_table: this
	}})); 
    }
    clear_transient_cache(){
        this.message(avalon23_helper_vars.lang.clear + ' ...', 'warning');
        let form_data={
            action:'avalon23_optimize_clear_transient'
        };
        
        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin', // 'include', default: 'omit'
            body: this.prepare_ajax_form_data(form_data)
        }).then(response => response.json()).then(data => {
            this.message(avalon23_helper_vars.lang.cleared);
        }).catch((err) => {
            console.log(err);
            this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
        });
    }
    clear_recount_cache(){
        this.message(avalon23_helper_vars.lang.clear + ' ...', 'warning');
        let form_data={
            action:'avalon23_optimize_clear_cache'
        };
        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin', // 'include', default: 'omit'
            body: this.prepare_ajax_form_data(form_data)
        }).then(response => response.json()).then(data => {
            this.message(avalon23_helper_vars.lang.cleared);
        }).catch((err) => {
            console.log(err);
            this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
        });
    }
}
