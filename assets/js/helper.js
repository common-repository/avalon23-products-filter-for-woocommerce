'use strict';

Element.prototype.appendBefore = function (element) {
    element.parentNode.insertBefore(this, element);
}, false;


Element.prototype.appendAfter = function (element) {
    element.parentNode.insertBefore(this, element.nextSibling);
}, false;

if (!String.prototype.format) {
    String.prototype.sprintf = function () {
        let args = arguments;
        return this.replace(/{(\d+)}/g, (match, number) => {
            return typeof args[number] !== 'undefined' ? args[number] : match;
        });
    };
}


var avalon23_helper = new (function () {
    return {
        prepare_ajax_form_data: function (data) {
            const formData = new FormData();

            Object.keys(data).forEach(function (k) {
                formData.append(k, data[k]);
            });

            return formData;
        },
        message: function (message_txt, type = 'notice', duration = 0) {
            if (duration === 0) {
                duration = 1777;
            }

            //***

            let container = null;

            if (!document.querySelectorAll('#growls').length) {
                container = document.createElement('div');
                container.setAttribute('id', 'growls');
                container.className = 'default';
                document.querySelector('body').appendChild(container);
            } else {
                container = document.getElementById('growls');
            }

            //***

            let id = this.create_id('m-');

            let wrapper = document.createElement('div');
            wrapper.className = 'growl growl-large growl-' + type;
            wrapper.setAttribute('id', id);

            let title = document.createElement('div');
            title.className = 'growl-title';
            let title_text = '';

            switch (type) {
                case 'warning':
                    title_text = avalon23_helper_vars.lang.m_warning;
                    break;

                case 'error':
                    title_text = avalon23_helper_vars.lang.m_error;
                    break;

                default:
                    title_text = avalon23_helper_vars.lang.m_notice;
                    break;
            }

            title.innerHTML = title_text;

            let message = document.createElement('div');
            message.className = 'growl-message';
            message.innerHTML = message_txt;

            //***

            //wrapper.appendChild(close);
            wrapper.appendChild(title);
            wrapper.appendChild(message);

            container.innerHTML = '';
            container.appendChild(wrapper);

            wrapper.addEventListener('click', function (e) {
                e.stopPropagation();
                this.remove();
                return false;
            });

            if (duration !== -1) {
                setTimeout(function () {
                    wrapper.style.opacity = 0;
                    setTimeout(function () {
                        wrapper.remove();
                    }, 777);
                }, duration);
        }

        },

        create_id: function (prefix = '') {
            return prefix + Math.random().toString(36).substring(7);
        },
        call_popup(command, more_data = {}, call_id = '', popup_title = '', popup_options = {}, title_info = '') {
            let popup = new Popup23(popup_options);
            popup.set_title(popup_title);
            popup.set_title_info(title_info);

            //***

            if (!call_id) {
                call_id = avalon23_helper.create_id('avalon23-');
            }

            fetch(avalon23_helper_vars.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: avalon23_helper.prepare_ajax_form_data({
                    action: 'avalon23_get_smth',
                    what: JSON.stringify({call_action: command, more_data: more_data}),
                    call_id: call_id,
                    lang: avalon23_helper_vars.selected_lang
                })
            }).then(response => response.text()).then(data => {
                if (typeof more_data.not_paste !== 'undefined' && more_data.not_paste) {
                    popup.set_content(data);
                }

                let generated_table = null;

                if (popup.node.querySelector('.avalon23-filter.avalon23-filter-self-call')) {
                    generated_table = new Avalon23_GeneratedTables(JSON.parse(popup.node.querySelector('.avalon23-table-json-data').innerText), popup.node.querySelector('.avalon23-table-json-data').getAttribute('data-table-id'));
                }

                document.dispatchEvent(new CustomEvent('avalon23-call-popup', {detail: {
                        popup: popup,
                        call_id: call_id,
                        data: data,
                        generated_table: generated_table
                    }}));
            }).catch((err) => {
                avalon23_helper.message(err, 'error', 5000);
            });

            return false;
        },

        draw_switcher(name, value, posted_id, event) {
            let id = this.create_id('sw');
            let container = document.createElement('div');

            let hidden = document.createElement('input');
            hidden.setAttribute('type', 'hidden');
            hidden.setAttribute('name', name);
            hidden.setAttribute('value', value);

            let checkbox = document.createElement('input');
            checkbox.setAttribute('type', 'checkbox');
            checkbox.setAttribute('id', id);
            checkbox.setAttribute('class', 'switcher23');
            checkbox.setAttribute('value', value);

            if (value) {
                checkbox.setAttribute('checked', true);
            }

            checkbox.setAttribute('data-post-id', posted_id);
            checkbox.setAttribute('data-event', event);

            let label = document.createElement('label');
            label.setAttribute('for', id);
            label.setAttribute('class', 'switcher23-toggle');
            label.innerHTML = '<span></span>';
	    

	    //wcag
	    let label_av23_2 = document.createElement('p');
	    label_av23_2.innerText = name;
	    label_av23_2.className = 'av23_wcag_hidden';
	    label.appendChild(label_av23_2);
	    let id_av23 = this.create_id('av23');	    
	    hidden.setAttribute('id', id_av23);	
	    let label_av23 = document.createElement('label');
	    label_av23.innerText = name;
	    label_av23.setAttribute('for', id_av23);
	    label_av23.className = 'av23_wcag_hidden';
	    container.appendChild(label_av23);

            container.appendChild(hidden);
            container.appendChild(checkbox);
            container.appendChild(label);


            return container;
        },

        init_switcher(button) {
            button.addEventListener('click', function (e) {

                e.stopPropagation();

                if (this.value > 0) {
                    this.value = 0;
                    this.previousSibling.value = 0;
                    this.removeAttribute('checked');
                } else {
                    this.value = 1;
                    this.previousSibling.value = 1;
                    this.setAttribute('checked', 'checked');
                }

                //Trigger the event
                if (this.getAttribute('data-event').length > 0) {
                    //window.removeEventListener(this.getAttribute('data-event'));

                    let data = {
                        self: this,
                        ajax_action: this.getAttribute('data-ajax-action'),
                        name: this.previousSibling.getAttribute('name'),
                        posted_id: this.getAttribute('data-post-id'),
                        value: parseInt(this.value, 10),
                        custom_data: null
                    };

                    if (this.getAttribute('data-custom-data') && this.getAttribute('data-custom-data').length > 0) {
                        data.custom_data = JSON.parse(this.getAttribute('data-custom-data'));
                    }

                    document.dispatchEvent(new CustomEvent(this.getAttribute('data-event'), {detail: data}));

                    //this.setAttribute('data-event-attached', 1);
                }



                return true;
            });
        },

        get_loader_html() {
            return `<div class="avalon23-place-loader">${avalon23_helper_vars.lang.loading}</div><br />`;
        },
        is_json(str) {
            try {
                JSON.parse(str);
            } catch (e) {
                return false;
            }
            return true;
        }        

    };
});