'use strict';

document.addEventListener('avalon23-filter-cunstruct', (e) => {

    let image_url = "";

    let btn_selector = ''; //'.woocommerce-products-header';
    let min_client_width = 0;
    let mobile_display_type = 'left_sidebar';
    

    if (e.detail.filter_obj.filter_options.side_img_url) {
	image_url = e.detail.filter_obj.filter_options.side_img_url;
    }
    if (e.detail.filter_obj.filter_options.mobile_display_type) {
	mobile_display_type = e.detail.filter_obj.filter_options.mobile_display_type;
    }
    if (e.detail.filter_obj.filter_options.min_client_width) {
	min_client_width = e.detail.filter_obj.filter_options.min_client_width;
    }
    if (e.detail.filter_obj.filter_options.mob_behavior_selector) {
	btn_selector = e.detail.filter_obj.filter_options.mob_behavior_selector;
    }

    let top_offset = 0;
    let client_width = document.body.clientWidth;

    if (min_client_width && (min_client_width > client_width)) {

	if (document.querySelector('#wpadminbar') != null) {
	    top_offset = document.querySelector('#wpadminbar').offsetHeight;
	}

	let filter_wrapper = document.querySelector('.avalon23_filter_wrapper[data-filter_id="' + e.detail.filter_obj.filter_options.filter_id + '"]');
	let wrapper = null;
	if (btn_selector) {
	    wrapper = document.querySelector(btn_selector);
	}

	if (mobile_display_type == 'content' && wrapper != null) {
	    if (filter_wrapper.parentNode.classList.contains('avalon23_filter_widget')) {
		filter_wrapper.parentNode.style.display = 'none';
	    }
	    wrapper.appendChild(filter_wrapper);
	} else if (mobile_display_type == 'left_sidebar' || mobile_display_type == 'right_sidebar') {
	    if (filter_wrapper.parentNode.classList.contains('avalon23_filter_widget')) {
		filter_wrapper.parentNode.style.display = 'none';
	    }
	    let side_s = document.createElement('div');
	    let side_container = document.createElement('div');
	    let show_btn = document.createElement('div');
	    let img_btn = document.createElement('img');

	    let close_btn = document.createElement('span');

	    let side_header = document.createElement('div');
	    side_s.className = 'avalon23-side-sidebar-wrapper';
	    if (mobile_display_type == 'right_sidebar') {
		side_s.classList.add("avalon23-side-right-sidebar-wrapper");
	    }
	    side_header.className = 'avalon23-side-sidebar-header';
	    close_btn.className = 'dashicons dashicons-no-alt avalon23-side-close-btn';

	    if (client_width > 600) {
		side_s.classList.add("avalon23-side-sidebar-wrapper-wide");
	    }

	    img_btn.setAttribute('src', image_url);

	    side_header.appendChild(close_btn);
	    show_btn.appendChild(img_btn);
	    side_container.appendChild(side_header);
	    show_btn.setAttribute('data-filter_id', e.detail.filter_obj.filter_options.filter_id);
	    side_s.setAttribute('data-filter_id', e.detail.filter_obj.filter_options.filter_id);
	    side_container.className = 'avalon23-side-sidebar-container';
	    side_container.style.paddingTop = top_offset + "px";

	    show_btn.className = 'avalon23-side-sidebar-btn';
	    side_container.appendChild(filter_wrapper);
	    side_s.appendChild(side_container);
	    document.querySelector('body').appendChild(side_s);

	    if (btn_selector && document.querySelector(btn_selector) != null) {
		document.querySelector(btn_selector).appendChild(show_btn);
	    } else {
		side_container.appendChild(show_btn);
	    }
	    let filter_obj = e.detail.filter_obj
	    show_btn.addEventListener('click', function (e) {
		show_btn.classList.toggle('avalon23_side_btn_opened');
		side_s.classList.toggle('avalon23_side_opened');
	        setTimeout(() => {
		    filter_obj.redraw_objects();
		}, 350);
	    });
	    close_btn.addEventListener('click', function (e) {
		show_btn.classList.remove('avalon23_side_btn_opened');
		side_s.classList.remove('avalon23_side_opened');
	    });
	}
	

    }

});
