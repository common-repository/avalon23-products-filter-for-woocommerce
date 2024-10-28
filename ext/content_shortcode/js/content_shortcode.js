function avalon23_check_content_shortcode(filters_data) {   
 
    let shortcodes_div = document.querySelectorAll('.avalon23-content');
//prepare  all  shortcodes
    for (var i = 0; i < shortcodes_div.length; i++) {
	var behavior_tmp = shortcodes_div[i].getAttribute("data-behavior");
	if (behavior_tmp == 'standard') {
	    shortcodes_div[i].classList.add('avalon23-content-hide');
	} else {
	    
	    shortcodes_div[i].classList.remove('avalon23-content-hide');
	}
    }

    if ('object' == typeof filters_data && Object.keys(filters_data).length !== 0) {

	let filter_data = {};
	for (var id in filters_data) {
	    filter_data = filters_data[id];

	    if ('object' != typeof filter_data || id < 1) {
		continue;
	    }

	    for (var j = 0; j < shortcodes_div.length; j++) {

		var behavior = shortcodes_div[j].getAttribute("data-behavior");
		for (var prop in filter_data) {
		    let show_attr = shortcodes_div[j].getAttribute("data-show-" + prop);

		    if (show_attr) {

			if (show_attr == '_any_') {
			    if (behavior == 'standard') {
				shortcodes_div[j].classList.remove('avalon23-content-hide');
			    } else {
				shortcodes_div[j].classList.add('avalon23-content-hide');
			    }
			} else {

			    var show = false;
			    let search_array = filter_data[prop].split(',');
			    let attr_array = show_attr.split(',');

			    let range_search = filter_data[prop].split(':');
			    if (range_search.length == 2 && !isNaN(parseFloat(range_search[0])) && !isNaN(parseFloat(range_search[1]))) {
				let filteredArray = attr_array.filter(function (n) {
				    return (parseFloat(range_search[0]) < parseFloat(n) && parseFloat(range_search[1]) > parseFloat(n));
				});

				if (filteredArray.length) {
				    show = true;
				}
			    } else {

				let filteredArray = search_array.filter(function (n) {
				    return attr_array.indexOf(n) !== -1;
				});

				if (filteredArray.length) {
				    show = true;
				}
				

			    }

			    if (show) {
				if (behavior == 'standard') {				    				    
				    shortcodes_div[j].classList.remove('avalon23-content-hide');
				    
				} else {
				    shortcodes_div[j].classList.add('avalon23-content-hide');
				}
			    }
			}
		    }
		}
	    }
	}
    }

}


//document.addEventListener('avalon23-filter-is-drawn', (e) => {
//    let filter_data = {};
//    filter_data[e.detail.filter_option.filter_id] = e.detail.filter_data;
//    avalon23_check_content_shortcode(filter_data);  
//}); 

avalon23_check_content_shortcode(av23_content.filter_data);

document.addEventListener('avalon23-end-redraw-page', (e) => {
   // let filter_data = Object.assign({}, e.detail.filter_data);

    if (typeof avalon23_helper.ajax_redraw_filter_data == 'undefined') {
	avalon23_helper.ajax_redraw_filter_data = {};
    }
    let filters_data = Object.assign({}, avalon23_helper.ajax_redraw_filter_data);

    let new_filters_data = {};
    for (var id in filters_data) {
	if (filters_data[id]) {
	    new_filters_data[id] = {};
	    for (var prop in filters_data[id]) {
		var regex = new RegExp("^" + av23_content.prefix + "[0-9]*_");
		let key = prop.replace(regex, '');
		new_filters_data[id][key] = filters_data[id][prop];
	    }	    
	}

    }
    
    avalon23_check_content_shortcode(new_filters_data);
 
});