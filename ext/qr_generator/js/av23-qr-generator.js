'use strict';

function avalon23_generate_qr(show) {   
    let qr_div = document.querySelectorAll('.avalon23-qr-generator-item');
     for (var i = 0; i < qr_div.length; i++) {
	
	var current_link = qr_div[i].getAttribute("data-fixed-link");

	if (!current_link) {
	    if (!show) {
		qr_div[i].innerHTML = "";
		//continue;
	    } else {
		 current_link = window.location.href;
	    }    

	}
	if (current_link) {
	    var qr_prev_link = qr_div[i].getAttribute("data-prev-link");
	    if (qr_prev_link != current_link) {
		qr_div[i].innerHTML = "";
		/*title*/
		var qr_title = qr_div[i].getAttribute("data-title");
		let title = document.createElement('h5');
		title.textContent = qr_title;

		let qr_img = document.createElement('img');
		var qr_size = qr_div[i].getAttribute("data-size");
		var qr_link_api = qr_div[i].getAttribute("data-link");

		let qr_link = qr_link_api + 'size=' + qr_size + '&data=' + encodeURIComponent(current_link);

		qr_img.setAttribute('src',qr_link);
		qr_div[i].appendChild(title);
		qr_div[i].appendChild(qr_img);
		qr_div[i].setAttribute('data-prev-link',qr_link);
		//console.log('Draw QR!');
	    }
	}
    }   
}


document.addEventListener('avalon23-filter-is-drawn', (e) => {
   // let filter_data = Object.assign({}, e.detail.filter_data);
    if (typeof avalon23_helper.ajax_redraw_filter_data == 'undefined') {
	avalon23_helper.ajax_redraw_filter_data = {};
    }
    let filters_data = Object.assign({}, avalon23_helper.ajax_redraw_filter_data);
    let show = false;
    for (var id in filters_data) {
	if (filters_data[id] && Object.keys(filters_data[id]).length > 1) {
	    show = true;
	}
    }   
    avalon23_generate_qr(show);
 
});