'use strict';
class Avalon23_FilterOptions extends Avalon23_GeneratedTables {
    constructor(table_data, table_html_id) {
        super(table_data, table_html_id);
        this.save_filter_field_action = 'avalon23_save_filter_item_option_field';//reinit ajax action
        this.switcher_action = 'avalon23_save_filter_item_option_field';
        this.init_switchers_listener();
    }

    do_after_draw() {
        super.do_after_draw();
    }

}