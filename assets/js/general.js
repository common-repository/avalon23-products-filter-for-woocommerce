'use strict';

window.addEventListener('load', function () {
    
    document.addEventListener('avalon23-filter-is-drawn', e => {
	let all_labels = e.detail.filter_elements.querySelectorAll('.avalon23_radio_checkbox_container');
	for (let i = 0; i < all_labels.length; i++) {
	    all_labels[i].classList.add('notranslate');
	}

    }, false);
    
    //init filters
    document.querySelectorAll('.avalon23-filter').forEach(function (container) {
        new Avalon23_Filter(container, container.id);
    });

    //***

    document.addEventListener('popup-smth-loaded', e => {

        let what = e.detail.what;

        try {
            if (typeof what === 'string') {
                what = JSON.parse(what);
            }
        } catch (e) {
            console.log(e);
        }

        //***

        if (e.detail.posted_id === -1) {
            //for [avalon23_button id=13280 title="Deus Ex" popup_title="Table in Popup23"]
            let container = e.detail.popup.node.querySelector('.avalon23-filter');
            new Avalon23_Filter(container, container.id);
        }

    });

    //***

    window.addEventListener('offline', function (e) {
        avalon23_helper.message(avalon23_helper_vars.lang.offline, 'error', -1);
    });

    window.addEventListener('online', function (e) {
        avalon23_helper.message(avalon23_helper_vars.lang.online, 'notice');
    });

    window.addEventListener('error', function (e) {
        //avalon23_helper.message(`${e.message}, ${e.filename}, #${e.lineno}`, 'error', -1);
    });

});

/************************************** additional **********************************************/

function avalon23_show_filter(self) {
    let filter = self.parentElement.querySelector('.avalon23-filter-list');
    filter.classList.toggle('avalon23-hidden');

    if (filter.classList.contains('avalon23-hidden')) {
        self.innerHTML = avalon23_helper_vars.lang.show_filter;
    } else {
        self.innerHTML = avalon23_helper_vars.lang.hide_filter;
    }

    return false;
}



//just an example for future functionality extension
//adidas - unique name to avoid interseptions
function avalon23_adidas_range_slider2() {
    //see assets\js\filter.js
    console.log('yes');
}