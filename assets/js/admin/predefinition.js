'use strict';
class Avalon23_Predefinition extends Avalon23_GeneratedTables {
    constructor(table_data, table_html_id) {
        super(table_data, table_html_id);
        this.save_filter_field_action = 'avalon23_save_table_predefinition_field';//reinit ajax action
        this.switcher_action = 'avalon23_save_table_predefinition_field';
        this.init_switchers_listener();

        document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-modal-inner-content').addEventListener('scroll', (e) => {
            this.add_scroll_action(document.getElementById('tabs-predefinition').querySelector('.avalon23-predefinition-table-zone'));
        });
    }

    do_after_draw() {
        super.do_after_draw();
    }

}