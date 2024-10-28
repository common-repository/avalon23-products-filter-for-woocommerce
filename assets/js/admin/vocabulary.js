'use strict';
class Avalon23_GeneratedVocabulary extends Avalon23_GeneratedTables {
    constructor(table_data, table_html_id) {
        super(table_data, table_html_id);
        this.save_filter_field_action = 'avalon23_save_vocabulary_field';//reinit ajax action
        this.switcher_action = 'avalon23_save_vocabulary_field';
        this.init_switchers_listener();
        this.init_json_fields_saving();
    }

    do_after_draw() {
        super.do_after_draw();
    }

    create() {
        this.message(avalon23_helper_vars.lang.creating, 'warning');
        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: this.prepare_ajax_form_data({
                action: 'avalon23_create_vocabulary_field',
                tail: avalon23_helper.create_id('a')
            })
        }).then(response => response.json()).then(data => {
            this.message(avalon23_helper_vars.lang.created);
            avalon23_vocabulary_table.settings.table_data = data;
            avalon23_vocabulary_table.draw_data(null);
        }).catch((err) => {
            this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
        });
    }

    delete(id) {
        if (confirm(avalon23_helper_vars.lang.sure)) {
            this.message(avalon23_helper_vars.lang.deleting, 'warning');
            avalon23_vocabulary_table.delete_row(id);
            fetch(this.settings.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: this.prepare_ajax_form_data({
                    action: 'avalon23_delete_vocabulary_field',
                    id: id
                })
            }).then(response => response.json()).then(data => {
                this.message(avalon23_helper_vars.lang.deleted);
                avalon23_vocabulary_table.settings.table_data = data;
            }).catch((err) => {
                this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
            });
        }
    }
}
