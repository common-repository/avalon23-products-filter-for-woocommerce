'use strict';
//created by RealMag777
class Select23 {
    constructor(container, data, connect_id = '', attributes = {}) {

        this.connect_id = connect_id;
        this.select = document.createElement('select');
        this.container=container;

        if (Object.keys(attributes).length) {
            for (let key in attributes) {
                this.select.setAttribute(key, attributes[key]);
            }
        }

        if (Object.keys(data).length) {
            for (let key in data) {
                let option = document.createElement('option');
                option.value = key;
                option.innerText = data[key];
                this.select.appendChild(option);
            }
        }

        //***
        container.innerHTML = '';
        container.appendChild(this.select);
        this.init();

    }

    init() {
        this.select.addEventListener('change', (e) => {
            document.dispatchEvent(new CustomEvent('select23-change', {detail: {
                    connect_id: this.connect_id,
                    value: this.select.value
                }}));
        });
    }
}
