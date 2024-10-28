'use strict';

//1 object == 1 popup
class Popup23 {

    constructor(data = {}) {
        if (typeof Popup23.z_index === 'undefined') {
            Popup23.z_index = 15003;
        }

        ++Popup23.z_index;
        this.create(data);
    }

    create(data = {}) {
        this.node = document.createElement('div');
        let div_id = avalon23_helper.create_id('popw-');
        this.node.setAttribute('id', div_id);
        this.node.className = 'avalon23-dynamic-popup-wrapper';
        this.node.innerHTML = document.querySelector('#avalon23-popup-template').innerHTML;
        document.querySelector('body').appendChild(this.node);
        this.node.querySelector('.avalon23-modal').style.zIndex = Popup23.z_index;
        this.node.querySelector('.avalon23-modal-backdrop').style.zIndex = Popup23.z_index - 1;

        this.node.querySelectorAll('.avalon23-modal-close, .avalon23-modal-button-large-1').forEach(item => {
            item.addEventListener('click', e => {
                e.stopPropagation();
                this.node.remove();
                return false;
            });
        });

        //***

        if (typeof data.iframe !== 'undefined' && data.iframe.length > 0) {
            let iframe = document.createElement('iframe');
            iframe.className = 'avalon23-iframe-in-popup';

            if (typeof data.height !== 'undefined') {
                iframe.height = data.height;
            } else {
                iframe.height = this.get_content_area_height();
            }

            iframe.frameborder = 0;
            iframe.allowfullscreen = '';
            iframe.allow = typeof data.allow !== 'undefined' ? data.allow : '';

            iframe.src = data.iframe;
            this.set_content('');
            this.append_content(iframe);
        }

        //***

        if (typeof data.title !== 'undefined' && data.title.length > 0) {
            this.set_title(data.title);
        }

        if (typeof data.width !== 'undefined') {
            this.node.querySelector('.avalon23-modal').style.maxWidth = data.width + 'px';
        }

        if (typeof data.height !== 'undefined') {
            this.node.querySelector('.avalon23-modal').style.maxHeight = data.height + 'px';
        }

        if (typeof data.left !== 'undefined') {
            this.node.querySelector('.avalon23-modal').style.left = data.left + '%';
        }

        if (typeof data.left !== 'undefined') {
            this.node.querySelector('.avalon23-modal').style.right = data.right + '%';
        }

        if (typeof data.action !== 'undefined' && data.action.length > 0) {
            document.dispatchEvent(new CustomEvent(data.action, {detail: {...data, ...{popup: this}}}));
        }

        if (typeof data.what !== 'undefined' && data.what) {
            
            var search_url=avalon23_helper_vars.ajax_url;
            if(location.search.substr(1)){
                search_url+="?"+location.search.substr(1);
            }
            fetch(search_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: avalon23_helper.prepare_ajax_form_data({
                    action: 'avalon23_get_smth',
                    what: data.what,
                    posted_id: data.posted_id,
                    lang: avalon23_helper_vars.selected_lang
                })
            }).then((response) => response.text()).then((content) => {
              
                this.set_content(content);
                document.dispatchEvent(new CustomEvent('popup-smth-loaded', {detail: {popup: this, content: content, posted_id: data.posted_id, what: data.what}}));
            }).catch((err) => {
                avalon23_helper.message(err, 'error', 5000);
            });
        }

        //***

        this.node.querySelector('.avalon23-modal-inner-content').addEventListener('scroll', (e) => {
            document.dispatchEvent(new CustomEvent('popup23-scrolling', {detail: {
                    top: e.srcElement.scrollTop,
                    self: this
                }}));

            //+++

            let elem = this.node.querySelector('.avalon23-data-table > .table23-wrapper');
            if (elem) {
                let flow = elem.querySelector('.table23-flow-header');

                if (flow) {
                    let box = elem.getBoundingClientRect();
                    let box2 = this.node.querySelector('.avalon23-modal-inner-header').getBoundingClientRect();
                    let first_row = elem.querySelector('table thead tr');

                    if (box.top <= Math.abs(box2.height) / 1.5) {

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

        });

        //***

        return this.node;
    }

    set_title(title) {
        this.node.querySelector('.avalon23-modal-title').innerHTML = title;
    }

    set_title_info(info) {
        this.node.querySelector('.avalon23-modal-title-info').innerHTML = info;
    }

    set_content(content) {
        this.node.querySelector('.avalon23-form-element-container').innerHTML = content;
    }

    append_content(node) {
        this.node.querySelector('.avalon23-form-element-container').appendChild(node);
    }

    get_content_area_height() {
        return this.node.querySelector('.avalon23-modal-inner-content').offsetHeight - 50;
    }
}

