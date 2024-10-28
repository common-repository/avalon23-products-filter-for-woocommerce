function avalon23_change_side_filter_image(button) {
    let posted_id = button.getAttribute('data-post_id');
   
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
                    fetch(ajaxurl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: avalon23_helper.prepare_ajax_form_data({
                            action: 'avalon23_save_filter_item_option_field',
			    field: 'side_img',
			    posted_id: posted_id,
                            value: uploaded_image.id
                        })
                    }).then(response => response.text()).then(data => {
                        

                        avalon23_helper.message(avalon23_helper_vars.lang.saved);
                    }).catch((err) => {
                        avalon23_helper.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
                    });

                }
            });


    return false;

}

function avalon23_delete_side_filter_image(button){
    var src= button.getAttribute('data-src');
    let posted_id = button.getAttribute('data-post_id');
    console.log(posted_id)
    fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: avalon23_helper.prepare_ajax_form_data({
	    action: 'avalon23_save_filter_item_option_field',
	    field: 'side_img',
	    posted_id: posted_id,
            value: 0
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

