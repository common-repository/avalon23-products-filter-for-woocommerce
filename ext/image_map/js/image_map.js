function image_map(key, filter) {

    let main_image = filter.data[key]['main_image'];
    let marker_image = filter.data[key]['marker'];
    let marker_checked_image = filter.data[key]['marker_checked'];
    let args = {
	multiple: filter.data[key]['multiple'],
	show_count: parseInt(filter.data[key]['show_count']),
	hide_empty_terms: filter.data[key]['hide_empty_terms'],
	filter_id: filter.filter_options.filter_id
    }
   
    //init  object
    let image_map = new Av23ImageMap(key, filter.data[key]['options'], filter.filter_data, main_image, marker_image, marker_checked_image, args);
    image_map.do_filter = function(){
	filter.filter_data = this.filter_data;
	filter.make_filtration(); 
      }
    let elem = image_map.draw();
    let label = null;
    let is_checked = ( typeof filter.filter_data[key] != 'undefined' );
    if(filter.data[key]['show_title']==1 && elem){
	label = filter.get_filter_title(key,filter.data[key],elem,is_checked);
    }        
    
    let all_shortcodes = document.querySelectorAll('.avalon23-image-map-' + key + '-' + filter.filter_options.filter_id);
    for(let x=0;x<all_shortcodes.length;x++){
	all_shortcodes[x].innerHTML = '';
	if (label) {
	    all_shortcodes[x].appendChild(label);
	}
	if (elem) {
	    all_shortcodes[x].appendChild(elem);
	}
	
	return {'elem':null,'label':null};
    }
    
    return {'elem':elem,'label':label};
}

