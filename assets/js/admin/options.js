'use strict';
var avalon23_main_table = null;
var avalon23_filter_items_table = null;
var avalon23_filter_options_table = null;
var avalon23_meta_fields_table = null;
var avalon23_vocabulary_table = null;
var avalon23_settings_table = null;
var avalon23_predefinition_table = null;
var avalon23_seo_rules_table = null;
var avalon23_seo_settings_table = null;
//***

//***
//Popup with information about all shortcode possibilities
document.addEventListener('table23-html-drawn', function (e) {

    if (e.detail.otable.table_html_id === 'avalon23-admin-table') {
        /*         
         e.detail.otable.table.querySelectorAll("th[data-key='shortcode']").forEach(function (item) {
         item.addEventListener('click', function (e) {
         let answer = new Object();
         document.dispatchEvent(new CustomEvent('avalon23-table-get', {detail: {
         table_html_id: item.closest('div.avalon23-data-table').id,
         answer: answer
         }}));
         
         new Popup23({title: avalon23_helper_vars.lang.shortcodes_help, action: 'avalon23_get_smth', what: 'shortcodes_help'});
         
         }, false);
         });
         */
    }

    return true;
});

//***
//different backend popups data inits
document.addEventListener('popup-smth-loaded', e => {
    if (e.detail.what) {
        let what = e.detail.what;

        if (typeof what === 'string') {
            try {
                what = JSON.parse(what);
            } catch (e) {
                console.log(e);
            }
        }


        if (typeof what === 'object') {
            if (typeof what.call_action !== 'undefined') {
                switch (what.call_action) {
                    case 'avalon23_get_field_item_field_option':
                        let container = e.detail.popup.node.querySelector('.avalon23-table-json-data');
                        new Avalon23_FieldsOptions(JSON.parse(container.innerText), container.getAttribute('data-table-id'));
                        break;

                }
            }
        } else {
            e.detail.popup.set_content(e.detail.content);
        }
    }

});

document.addEventListener('avalon23-tabs-switch', e => {
    //fix when in one popup some tables
    Array.from(document.querySelectorAll('.table23-flow-header')).forEach(function (item) {
        item.style.display = 'none';
    });

    //***
    let help_link = document.getElementById('main-table-help-link');
    switch (e.detail.current_tab_link.getAttribute('href')) {
        case '#tabs-meta':
            help_link.setAttribute('href', 'https://avalon23.dev/document/meta-data/');
            break;
        case '#tabs-filter':
            help_link.setAttribute('href', 'https://avalon23.dev/document/filter-item/');
            break;
        case '#tabs-predefinition':
            help_link.setAttribute('href', 'https://avalon23.dev/document/predefinition/');
            break;
        case '#tabs-options':
            help_link.setAttribute('href', 'https://avalon23.dev/document/options/');
            break;

        case '#tabs-custom-css':
            help_link.setAttribute('href', 'https://avalon23.dev/document/custom-css/');

            //Custom CSS
            if (!avalon23_main_table.custom_css_editor) {
                avalon23_main_table.get_custom_css();
            }

            break;
    }
});

//overwriting CTRL+S behaviour for saving custom CSS
document.addEventListener('keydown', function (e) {
    if ((window.navigator.platform.match('Mac') ? e.metaKey : e.ctrlKey) && e.keyCode === 83) {
        if (avalon23_main_table.custom_css_editor) {
            if (window.getComputedStyle(document.getElementById('tabs-custom-css'), null).getPropertyValue('display') === 'block') {
                avalon23_main_table.save_custom_css();
                e.preventDefault();
            }
        }
    }
}, false);


window.onload = function () {

    new Avalon23_Tabs(document.querySelectorAll('.avalon23-tabs'));

    //init data tables
    document.querySelectorAll('.avalon23-table-json-data').forEach(function (container) {
        if (container.getAttribute('data-table-id') === 'avalon23-admin-table') {
            avalon23_main_table = new Avalon23_GeneratedTables(JSON.parse(container.innerText), container.getAttribute('data-table-id'));
        } else {
            new Avalon23_GeneratedTables(JSON.parse(container.innerText), container.getAttribute('data-table-id'));
        }
    });

    //+++
    //settings
    avalon23_settings_table = new Avalon23_GeneratedSettings(JSON.parse(document.querySelector('#tabs-settings .avalon23-settings-json-data').innerText), 'avalon23-settings-table');
    if (document.querySelector('.avalon23-vocabulary-json-data')) {
        avalon23_vocabulary_table = new Avalon23_GeneratedVocabulary(JSON.parse(document.querySelector('.avalon23-vocabulary-json-data').innerText), 'avalon23-vocabulary-table');
    }
    avalon23_seo_rules_table = new Avalon23_GeneratedSeoRules(JSON.parse(document.querySelector('#tabs-seo .avalon23-seo-rules-json-data').innerText), "avalon23-seo-rules-table");
    avalon23_seo_settings_table = new Avalon23_GeneratedSeoSettings(JSON.parse(document.querySelector('#tabs-seo .avalon23-seo-settings-json-data').innerText), 'avalon23-seo-settings-table');
    
    //***

    window.addEventListener('offline', function (e) {
        avalon23_helper.message(avalon23_helper_vars.lang.offline, 'error', -1);
    });

    window.addEventListener('online', function (e) {
        avalon23_helper.message(avalon23_helper_vars.lang.online, 'notice');
    });
};
document.addEventListener('avalon23-draw-settings-table', (e) => {
    let _this = e.detail.settings_table;
    
    _this.table.querySelectorAll('.avalon23-color-options').forEach(item => {
	jQuery(item).spectrum({
	    type: 'text',
	    allowEmpty: true,
	    showInput: true,
	    change: function (color) {
		item.setAttribute('value', color.toHexString());
		_this.save(0, item.dataset.field, item.value);
	    }
	});
    });    
});

//***

class Avalon23_Tabs {
    constructor(containers) {
        if (containers.length > 0) {
            for (let i = 0; i < containers.length; i++) {
                this.init(containers[i]);
            }
        }
    }

    init(container) {
        container.querySelectorAll('nav li a').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                a.parentElement.parentElement.querySelector('li.tab-current').removeAttribute('class');
                a.parentElement.className = 'tab-current';
                container.querySelector('.content-current').removeAttribute('class');
                container.querySelector('.content-wrap ' + a.getAttribute('href')).className = 'content-current';

                document.dispatchEvent(new CustomEvent('avalon23-tabs-switch', {detail: {
                        current_tab_link: a
                    }}));

                return false;
            });
        });
    }
}

function avalon23_delete_image(button){
    var field_key = button.getAttribute('data-key');
    var filter_id = button.getAttribute('data-table-id');
    var field_id = button.getAttribute('data-field-id');
    var src= button.getAttribute('data-src');
    if (typeof  button.dataset.save_input != 'undefined') {
	let input_field = document.querySelector('input[data-key="'+ field_key +'"][data-table-id="'+ filter_id +'"]');
	if (typeof  input_field != 'undefined') {
	    input_field.value = ''; 
	    input_field.dispatchEvent(new Event("change"));
	    button.parentNode.querySelector('img').setAttribute('src', src);
	    button.style.display = "none";
	}

    } else {
	fetch(ajaxurl, {
	    method: 'POST',
	    credentials: 'same-origin',
	    body: avalon23_helper.prepare_ajax_form_data({
		action: 'avalon23_save_filter_field_option',
		filter_id: filter_id,
		field_id: field_id,
		key: field_key,
		value: ""
	    })
	}).then(response => response.text()).then(data => {
	    //avalon23_image_container
	    button.parentNode.querySelector('img').setAttribute('src', src);
	    button.style.display = "none";
	    avalon23_helper.message(avalon23_helper_vars.lang.saved);
	}).catch((err) => {
	    avalon23_helper.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
	});
    }

}
function avalon23_change_image(button) {
    var field_key = button.getAttribute('data-key');
    var filter_id = button.getAttribute('data-table-id');
    var field_id = button.getAttribute('data-field-id');

    var image = wp.media({
        title: avalon23_helper_vars.lang.select_table_thumb,
        multiple: false,
        library: {
            type: ['image']
        }
    }).open()
            .on('select', function (e) {
                var uploaded_image = image.state().get('selection').first();
                uploaded_image = uploaded_image.toJSON();
                button.parentNode.querySelector('.avalon23_delete_img').style.display = "block";
                if (typeof uploaded_image.url != 'undefined') {
                    if (typeof uploaded_image.sizes.thumbnail !== 'undefined') {
                        button.querySelector('img').setAttribute('src', uploaded_image.sizes.thumbnail.url);
                    } else {
                        button.querySelector('img').setAttribute('src', uploaded_image.url);
                    }
		    if (typeof  button.dataset.save_input != 'undefined') {
			let input_field = document.querySelector('input[data-key="'+ field_key +'"][data-table-id="'+ filter_id +'"]');
			
			if (typeof  input_field != 'undefined') {
			    input_field.value = uploaded_image.id; 
			    input_field.dispatchEvent(new Event("change"));			    
			}

		    } else {
			fetch(ajaxurl, {
			    method: 'POST',
			    credentials: 'same-origin',
			    body: avalon23_helper.prepare_ajax_form_data({
				action: 'avalon23_save_filter_field_option',
				filter_id: filter_id,
				field_id: field_id,
				key: field_key,
				value: uploaded_image.id
			    })
			}).then(response => response.text()).then(data => {


			    avalon23_helper.message(avalon23_helper_vars.lang.saved);
			}).catch((err) => {
			    avalon23_helper.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
			});			
		    }

                }
            });


    return false;

}

function avalon23_toggle_content(button){   
    var toggled_class=button.getAttribute('data-toggle-id');
    document.querySelector('.'+toggled_class).classList.toggle("avalon23_show");    

}

function avalon23_change_thumbnail(button) {
    var posted_id = button.closest('tr').getAttribute('data-pid');
    var field = 'thumbnail';

    var image = wp.media({
        title: avalon23_helper_vars.lang.select_table_thumb,
        multiple: false,
        library: {
            type: ['image']
        }
    }).open()
            .on('select', function (e) {
                var uploaded_image = image.state().get('selection').first();
                uploaded_image = uploaded_image.toJSON();
                
                if (typeof uploaded_image.url != 'undefined') {
                    if (typeof uploaded_image.sizes.thumbnail !== 'undefined') {
                        button.querySelector('img').setAttribute('src', uploaded_image.sizes.thumbnail.url);
                    } else {
                        button.querySelector('img').setAttribute('src', uploaded_image.url);
                    }

                    avalon23_helper.message(avalon23_helper_vars.lang.saving, 'warning');
                    fetch(ajaxurl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: avalon23_helper.prepare_ajax_form_data({
                            action: 'avalon23_save_filter_field',
                            posted_id: posted_id,
                            field: field,
                            value: uploaded_image.id
                        })
                    }).then(response => response.text()).then(data => {
                        avalon23_helper.message(avalon23_helper_vars.lang.saved, 'notice');
                    }).catch((err) => {
                        avalon23_helper.message(err, 'error', 5000);
                    });

                }
            });


    return false;

}

function avalon23_import_options() {

    if (document.getElementById('avalon23-import-text').value) {
        let data = JSON.parse(document.getElementById('avalon23-import-text').value);

        if (typeof data === 'object') {
            if (confirm(avalon23_helper_vars.lang.sure)) {
                avalon23_helper.message(avalon23_helper_vars.lang.importing, 'warning');
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json'
                    },
                    credentials: 'same-origin',
                    body: avalon23_helper.prepare_ajax_form_data({
                        action: 'avalon23_import_data',
                        data: JSON.stringify(data)
                    })
                }).then(response => response.text()).then(data => {
                    avalon23_helper.message(avalon23_helper_vars.lang.imported, 'notice');
                    window.location.reload();
                }).catch((err) => {
                    avalon23_helper.message(err, 'error', 5000);
                });
            }
        } else {
            avalon23_helper.message(avalon23_helper_vars.lang.error, 'error', 5000);
        }
    }
}