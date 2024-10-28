'use strict';
class Avalon23_GeneratedTables extends DataTable23 {
    constructor(table_data, table_html_id) {
        super(table_data, table_html_id);

        this.use_cache = false;

        this.save_filter_field_action = 'avalon23_save_filter_field';//ajax action for saving
        this.delete_action = 'avalon23_delete_filter';//ajax action for deleting
        this.clone_action = 'avalon23_clone_filter';//ajax action for deleting
        this.switcher_action = 'avalon23_save_filter_field';
        this.custom_css_editor = null;

        //***

        let _this = this;

        this.wrapper.parentElement.querySelectorAll('.avalon23-text-search').forEach(function (input) {
            input.addEventListener('keyup', function (e) {

                e.stopPropagation();

                let data_key = input.getAttribute('data-key');

                let add = {};
                let do_search = false;

                switch (e.keyCode) {
                    case 13:
                        add[data_key] = input.value;
                        do_search = true;
                        break;

                    case 27:
                        delete _this.request_data.filter_data[data_key];
                        do_search = true;
                        break;
                }

                if (do_search) {
                    _this.request_data.current_page = 0;
                    if (typeof _this.request_data.filter_data !== 'object' && _this.request_data.filter_data.length > 0) {
                        _this.request_data.filter_data = JSON.parse(_this.request_data.filter_data);
                    }
                    _this.request_data.filter_data = _this.extend(_this.request_data.filter_data, add);
                    _this.draw_data();
                }

            });

            //click on cross
            input.addEventListener('mouseup', function (e) {
                e.stopPropagation();
                if (input.value.length > 0) {
                    let data_key = input.getAttribute('data-key');
                    setTimeout(function () {
                        if (input.value.length === 0) {
                            delete _this.request_data.filter_data[data_key];
                            _this.request_data.current_page = 0;
                            _this.draw_data();
                        }
                    }, 5);
                }
            });

        });

        //for switchers actions casting
        this.init_switchers_listener();
        this.init_json_fields_saving();
    }

    init_switchers_listener() {
        //With inheriting this js class custom events adds multiple times, so var flags is uses here to avoid it, and now in js no way to get all attached actions to the document
        if (avalon23_helper_vars.flags.indexOf(this.switcher_action) === -1) {
            document.addEventListener(this.switcher_action, e => {
                //e.preventDefault();
                this.save(e.detail.posted_id, e.detail.name, e.detail.value, null, '', e.detail.custom_data);
            });
        }
        avalon23_helper_vars.flags.push(this.switcher_action);
    }

    do_after_draw() {
        super.do_after_draw();
        //fade out loader
        if (document.querySelector('.avalon23-admin-preloader')) {
            document.querySelector('.avalon23-admin-preloader').classList.add('hide-opacity');
            setTimeout(function () {
                document.querySelector('.avalon23-admin-preloader').style.display = 'none';
            }, 777);
        }

        //***

        let _this = this;
        _this.table.querySelectorAll('.table23-td-editable').forEach(function (td) {
            let type = td.getAttribute('data-field-type');
            let field = td.getAttribute('data-field');
            let posted_id = td.getAttribute('data-pid');

            //fix for tables as options, where on different rows needs different type of editing
            if (type === 'textinput') {
                if (td.querySelectorAll('select').length > 0) {
                    type = 'select';
                    td.setAttribute('data-field-type', type);
                }

                if (td.querySelectorAll('input[type="checkbox"]').length > 0) {
                    type = 'checkbox';
                    td.setAttribute('data-field-type', type);
                }
                if (td.querySelectorAll('.avalon23_override_field_type').length > 0) {
                    type = 'custom';
                    td.setAttribute('data-field-type', type);
                }		
            }

            switch (type) {
                case 'textinput':

                    td.addEventListener('click', function (e) {
                        e.stopPropagation();
                        if (!td.querySelector('textarea')) {
                            let input = document.createElement('textarea');
                            //input.setAttribute('type', 'text');
                            input.className = 'avalon23-editable-textarea';

                            let prev_value = input.value = td.innerHTML;
                            td.innerHTML = '';

                            input.addEventListener('keydown', function (e) {

                                e.stopPropagation();

                                if (e.keyCode === 13) {
                                    e.preventDefault();

                                    td.innerHTML = input.value.trim();

                                    if (input.value !== prev_value) {
                                        _this.save(posted_id, field, input.value);
                                    }

                                    //return false;
                                }

                                if (e.keyCode === 27) {//escape
                                    td.innerHTML = prev_value;
                                }

                            });

                            td.appendChild(input);
                            input.focus();
                        }

                        return true;
                    });

                    break;

                case 'select':
                    let select = td.querySelector('select');

                    select.addEventListener('change', function (e) {
                        e.stopPropagation();

                        let values = Array.from(this.querySelectorAll('option:checked')).map(elem => elem.value).join(',');

                        _this.save(posted_id, field, values, this.getAttribute('data-action'), this.getAttribute('data-additional'));
                        return true;
                    });


                    if (typeof SelectM23 === 'function') {
                        new SelectM23(select, true);//wrapping of <select>

                        select.addEventListener('selectm23-reorder', function (e) {
                            _this.save(posted_id, field, e.detail.values, this.getAttribute('data-action'), this.getAttribute('data-additional'));
                        });
                    }

                    break;
            }

        });

        //init switchers
        Array.from(this.table.querySelectorAll('.switcher23')).forEach((button) => {
            avalon23_helper.init_switcher(button);
        });


        document.dispatchEvent(new CustomEvent('table23_do_after_draw', {detail: {
                table_html_id: this.table_html_id
            }}));

    }

    //**********************************************************************************************

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
        }).then(response => response.json()).then(data => {
            this.message(avalon23_helper_vars.lang.saved);

            document.dispatchEvent(new CustomEvent('after_' + this.save_filter_field_action, {detail: {
                    self: this,
                    posted_id: posted_id,
                    field: field,
                    value: value
                }}));
	    this.do_after_save(field, value, posted_id);
        }).catch((err) => {
            console.log(err);
            this.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
        });
    }

    /***************************************/

    create() {
        this.message(avalon23_helper_vars.lang.creating + ' ...', 'warning');

        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin', // 'include', default: 'omit'
            body: this.prepare_ajax_form_data({
                action: 'avalon23_create_filter'
            })
        }).then(response => response.json()).then(table_data => {
            this.message(avalon23_helper_vars.lang.created);
            this.request_data.orderby = 'id';
            this.request_data.order = 'desc';
            this.settings.table_data = table_data;
            this.draw_data(null);
        }).catch((err) => {
            console.log(err);
            this.message(err, 'error', 5000);
        });
    }

    delete(id) {
        if (confirm(avalon23_helper_vars.lang.sure)) {
            this.message(avalon23_helper_vars.lang.deleting + ' ...', 'warning');
            this.delete_row(id);
            fetch(this.settings.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: this.prepare_ajax_form_data({
                    action: this.delete_action,
                    id: id
                })
            }).then(response => response.json()).then(data => {
                this.message(avalon23_helper_vars.lang.deleted);
            }).catch((err) => {
                console.log(err);
                this.message(err, 'error', 5000);
            });
        }
    }

    clone(id) {
        alert(avalon23_helper_vars.lang.free);
    }

    //popup of table options
    call_popup(filter_id) {
        this.filter_id = filter_id;
        document.getElementById('avalon23-popup-filters-template').style.display = 'block';

        if (document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-filters-table-zone table')) {
            document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-filters-table-zone table').remove();
        }

        document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-filters-table-zone').innerHTML = avalon23_helper.get_loader_html();
        document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-options-filters-table-zone').innerHTML = avalon23_helper.get_loader_html();
        document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-meta-filters-table-zone').innerHTML = avalon23_helper.get_loader_html();
        document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-modal-title').innerHTML = '#' + filter_id + '. ' + this.table.querySelector('.avalon23_td_title[data-pid="' + filter_id + '"]').innerHTML;

        this.custom_css_editor = null;
        document.querySelector('.avalon23-options-custom-css-zone').innerHTML = '';
        if (window.getComputedStyle(document.getElementById('tabs-custom-css'), null).getPropertyValue('display') === 'block') {
            this.get_custom_css();
        }

        //avalon23_get_filter_item_data   
        let table_html_id = avalon23_helper.create_id('t');
        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin', // 'include', default: 'omit'
            body: this.prepare_ajax_form_data({
                action: 'avalon23_get_filter_item_data',
                filter_id: filter_id,
                table_html_id: table_html_id
            })
        }).then(response => response.text()).then(html => {
            document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-filters-table-zone').innerHTML = html;

            if (avalon23_filter_items_table) {
                avalon23_filter_items_table.destructor();//detach avalon23_save_filter_field
            }

            avalon23_filter_items_table = new Avalon23_FilterItems(JSON.parse(document.querySelector(`[data-table-id="${table_html_id}"]`).innerText), table_html_id);
        }).catch((err) => {
            console.log(err);
            this.message(err, 'error', 5000);
        });


        //get_options_data
        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: this.prepare_ajax_form_data({
                action: 'avalon23_get_filter_item_options',
                filter_id: filter_id
            })
        }).then(response => response.text()).then(data => {
            document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-options-filters-table-zone').innerHTML = '<div class="avalon23-data-table" id="avalon23-filter-item-options-table"><table></table></div>';
            if(!avalon23_helper.is_json(data)){
                data = atob(data);
            }
            let d = JSON.parse(data);
            d.request_data.posted_id = filter_id;
            avalon23_filter_options_table = new Avalon23_FilterOptions(d, 'avalon23-filter-item-options-table');
        }).catch((err) => {
            console.log(err);
            this.message(err, 'error', 5000);
        });


        //avalon23_get_filter_meta
	
        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: this.prepare_ajax_form_data({
                action: 'avalon23_get_filter_meta',
                filter_id: filter_id
            })
         }).then(response => response.text()).then(data => {
            document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-meta-filters-table-zone').innerHTML = '<div class="avalon23-data-table" id="avalon23-filter-items-meta-table"><table></table></div>';
            if(!avalon23_helper.is_json(data)){
                data = atob(data);
            }
            let d = JSON.parse(data);
	    
            d.request_data.posted_id = filter_id;
	    
            avalon23_meta_fields_table = new Avalon23_FilterMetaTable(d, 'avalon23-filter-items-meta-table');
        }).catch((err) => {
            console.log(err);
            this.message(err, 'error', 5000);
        });

        //***

        //get predefinition data table        
        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: this.prepare_ajax_form_data({
                action: 'avalon23_get_predefinition_table',
                filter_id: filter_id
            })
        }).then(response => response.text()).then(html => {
            document.getElementById('tabs-predefinition').querySelector('.avalon23-predefinition-table-zone').innerHTML = html;
            avalon23_predefinition_table = new Avalon23_Predefinition(JSON.parse(document.querySelector('[data-table-id="avalon23-predefinition-table"]').innerText), 'avalon23-predefinition-table');
        }).catch((err) => {
            console.log(err);
            this.message(err, 'error', 5000);
        });

    }

    save_custom_css() {
        this.message(avalon23_helper_vars.lang.saving + ' ...', 'warning');
        fetch(this.settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: this.prepare_ajax_form_data({
                action: 'avalon23_save_table_custom_css',
                filter_id: this.filter_id,
                value: this.custom_css_editor.codemirror.getValue()
            })
        }).then(response => response.text()).then(data => {
            this.message(avalon23_helper_vars.lang.saved);
        }).catch((err) => {
            console.log(err);
            this.message(err, 'error', 5000);
        });
    }

    get_custom_css() {
        if (this.custom_css_editor !== 1) {
            this.custom_css_editor = 1;//flag to avoid double requesting
            let zone = document.querySelector('.avalon23-options-custom-css-zone');
            zone.innerHTML = avalon23_helper.get_loader_html();

            fetch(this.settings.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: this.prepare_ajax_form_data({
                    action: 'avalon23_get_table_custom_css',
                    filter_id: this.filter_id
                })
            }).then(response => response.text()).then(data => {

                zone.innerHTML = '';
                let custom_css_textarea = document.createElement('textarea');
                custom_css_textarea.setAttribute('id', 'table-custom-css-textarea');
                custom_css_textarea.value = data;
                zone.appendChild(custom_css_textarea);
                this.custom_css_editor = wp.codeEditor.initialize(custom_css_textarea, custom_css_settings);

            }).catch((err) => {
                console.log(err);
                this.message(err, 'error', 5000);
            });
        }
    }

    message(text, type = 'notice', duration = 0) {
        avalon23_helper.message(text, type, duration);
    }

    //for popup with a table settings
    add_scroll_action(node) {
        let elem = node.querySelector('.avalon23-data-table > .table23-wrapper');
        if (elem) {
            let flow = elem.querySelector('.table23-flow-header');

            if (flow) {
                let box = elem.getBoundingClientRect();
                let box2 = document.getElementById('avalon23-popup-filters-template').querySelector('.avalon23-modal-inner-header').getBoundingClientRect();
                let first_row = elem.querySelector('table thead tr');


                if (box.top <= 5) {

                    flow.style.display = 'block';
                    flow.style.width = (elem.querySelector('table').offsetWidth + 10) + 'px';
                    flow.style.top = 2 * Math.abs(box2.height) + Math.abs(box.top) + 'px';

                    Array.from(first_row.querySelectorAll('th')).forEach((th, index) => {
                        flow.querySelectorAll('div')[index].style.width = th.offsetWidth + 1 + 'px';
                        flow.querySelectorAll('div')[index].innerHTML = th.innerText;
                    });

                } else {
                    flow.style.display = 'none';
                }
            }
        }
    }
    do_after_save(){
	
    }

}

