'use strict';
//version 1.1
class Av23ImageMap {

    constructor(filter_key = '', options={}, filter_data, main_img='', marker_img = '', marker_checked_img = '', args = {}) {
	this.prefix = 'avalon23-';
	this.is_empty = true;
	this.unique_id = this.create_id();
	this.filter_key = filter_key; //slug
	this.filter_data = filter_data; // search query (what checked)
	this.main_img = main_img;
	this.marker_img = marker_img;
	this.marker_checked_img = marker_checked_img;
	this.options = options; // [id,title,top,left,count]
	//args [multiple,show_count,hide_empty_terms,show_scale_menu,filter_id]
	this.multiple = 1;// checkbox,radio
	if (typeof args.multiple != 'undefined') {
	    this.multiple = args.multiple;
	}
	this.show_count = 1;
	if (typeof args.show_count != 'undefined') {
	    this.show_count = args.show_count;
	}
	this.hide_empty_terms = 0;
	
	this.show_scale_menu = 1;
	if (typeof args.show_scale_menu != 'undefined') {
	    this.show_scale_menu = args.show_scale_menu;
	}
	this.show_expand_menu = 1;
	if (typeof args.show_expand_menu != 'undefined') {
	    this.show_expand_menu = args.show_expand_menu;
	}	
	this.filter_id = 'av23' + this.create_id(); // to share many elements
	if (typeof args.filter_id != 'undefined') {
	    this.filter_id = args.filter_id;
	}
	
	this.map = this.create_map();
	this.container = this.create_container();
    }
    create_container(){
	let elem = document.createElement('div');
	elem.className =  this.prefix + 'image-map-container';
	return elem;
    }
    create_map(){
	let map = document.createElement('div');
	map.className = this.prefix + 'image-map-main';
	
	let main_image_el = document.createElement('img');
	main_image_el.setAttribute('src', this.main_img);
	main_image_el.setAttribute('alt', this.filter_key);
	main_image_el.className = this.prefix + 'image-map-main-img';
	
	map.appendChild(main_image_el);	
	
	return map;
    }
    create_id(){
	let m = "_"+Math.random().toString(36).substr(2, 4);
	return m;
    }
    
    draw(){
	if (!this.main_img) {
	    return null;
	}
	let map_wraper = document.createElement('div');
	map_wraper.className = this.prefix + 'image-map-wraper';
	
	this.add_markers();
	if ( this.is_empty ){
	    return null;
	}
	let menu = this.create_menu();
	if (menu){
	    this.container.appendChild(menu);
	} 
	
	map_wraper.appendChild(this.map);
	this.container.appendChild(map_wraper);
	
	this.init_filter();
	
	return this.container;
	
    }
    init_filter(){
	
	let all_marker_imput = this.container.querySelectorAll('.avalon23-marker-input');
	// init  filter
	
	for(let i=0;i<all_marker_imput.length;i++){
	    
	    let checked_bef=all_marker_imput[i].checked;
	    all_marker_imput[i].addEventListener('click', (e) => {
		if(all_marker_imput[i].disabled){
		   return; 
		}                          
		let values=[]; 

		if(checked_bef && this.multiple == 0){
		    all_marker_imput[i].checked=false;
		}

		Array.from(all_marker_imput).forEach(function(item){
		    if(item.checked){
			values.push(item.value);
		    }
		});

		this.filter_data[this.filter_key] = values.join(',');
		if (values.length === 0) {
		    delete this.filter_data[this.filter_key];
		}  
		this.do_filter();
	    });

	}		
    }
    do_filter(){
	console.log(this.filter_data);
    }
    add_markers(){
	this.is_empty = true;
	for(let i=0; i < this.options.length; i++){
	    let marker = document.createElement('div');
	    marker.className = this.prefix + 'image-map-marker avalon23-image-map-' + this.options[i]['id'];
	    
	    if (this.options[i]['top'] && this.options[i]['left']){
		marker.style.top = this.options[i]['top'] + "%";
		marker.style.left = this.options[i]['left'] + "%";
		// add  tooltip
		marker.setAttribute(this.prefix + 'data-tooltip', this.options[i].title);
		if (this.options[i]['top']< 23 ){
		    marker.setAttribute(this.prefix + 'data-tooltip-location', 'bottom');
		} else if(this.options[i]['left']<7 ){
		    marker.setAttribute(this.prefix + 'data-tooltip-location', 'right');
		} else if(this.options[i]['left']>93){
		    marker.setAttribute(this.prefix + 'data-tooltip-location', 'left');
		}

		let label_marker=document.createElement('label');
		let input_marker=document.createElement('input');

		input_marker.className = this.prefix + 'marker-input';
		input_marker.id= this.prefix + 'marker_'+ this.options[i].id + this.unique_id;
		label_marker.setAttribute('for', this.prefix + 'marker_'+ this.options[i].id + this.unique_id);
		if(parseInt(this.multiple)){
		    input_marker.setAttribute('type', 'checkbox');
		}else{
		    input_marker.setAttribute('type', 'radio');
		    input_marker.setAttribute('name', 'radio' + this.filter_key + this.filter_id);                              
		} 

		input_marker.setAttribute('value', this.options[i].id);
		input_marker.setAttribute('data-key', this.filter_key); 

		if (this.filter_data[this.filter_key]) {
		    let checked_values = this.filter_data[this.filter_key].split(",");
		    if (checked_values.includes(String(this.options[i].id))) {
			input_marker.checked = true;
			this.options[i].count=-1;

		    }
		}  	    

		let img_marker = document.createElement('img');
		img_marker.className = this.prefix + 'image-map-img-marker';
		img_marker.setAttribute('src', this.marker_img);
		img_marker.setAttribute('alt', this.options[i].title);

		let img_marker_checked = document.createElement('img');
		img_marker_checked.className = this.prefix + 'image-map-img-marker-checked';
		img_marker_checked.setAttribute('src', this.marker_checked_img);
		img_marker_checked.setAttribute('alt', this.options[i].title);

		label_marker.appendChild(img_marker);
		label_marker.appendChild(img_marker_checked);


		if(this.show_count && this.options[i].count!=-1){
		    let image_count = document.createElement('p');
		    image_count.className = this.prefix + 'image-map-count';		    
		    image_count.innerText= this.options[i].count;
		    label_marker.appendChild(image_count);
		} 
		marker.appendChild(input_marker);
		marker.appendChild(label_marker);

                              
		    if(this.options[i].count==0){
			input_marker.setAttribute('disabled', 'disabled');  
			img_marker.style.opacity="0.4";
		    } 
		    this.is_empty = false;
		    this.map.appendChild(marker);
 

	    }
	}	
    }
    create_menu(){
	if( !this.show_expand_menu && !this.show_scale_menu) {
	    return null;
	}
	
	let menu = document.createElement('div');
	menu.className = this.prefix + 'image-map-menu';
	
	if(this.show_scale_menu) {
	    let scale_p = document.createElement('div');
	    //scale_p.setAttribute('onclick',"avalon23_image_map_scale(this,'+')");
	    scale_p.addEventListener('click', (e) => {
		this.image_map_scale('+');
	    });	    
	    scale_p.className = this.prefix + 'image-map-menu-item ' + this.prefix + 'image-map-scale-plus';
	    let icon_p = document.createElement('span');
	    icon_p.className = 'dashicons dashicons-insert';
	    scale_p.appendChild(icon_p);

	    let scale_m = document.createElement('div');
	   // scale_m.setAttribute('onclick',"avalon23_image_map_scale(this,'-')");
	    scale_m.addEventListener('click', (e) => {
		this.image_map_scale('-');
	    });		    
	    scale_m.className = this.prefix + 'image-map-menu-item ' + this.prefix + 'image-map-scale-minus';
	    let icon_m = document.createElement('span');
	    icon_m.className = 'dashicons dashicons-remove';
	    scale_m.appendChild(icon_m);  
	    
	    menu.appendChild(scale_p);
	    menu.appendChild(scale_m);
	}

	if(this.show_expand_menu){
	    let expand = document.createElement('div');
	    //expand.setAttribute('onclick',"avalon23_image_map_expand(this)");
	    expand.addEventListener('click', (e) => {
		this.image_map_expand();
	    });
	    expand.className = this.prefix + 'image-map-menu-item ' + this.prefix + 'image-map-scale-expand';
	    let icon_expand = document.createElement('span');
	    icon_expand.className = 'dashicons dashicons-editor-expand';
	    let icon_contract = document.createElement('span');
	    icon_contract.className = 'dashicons dashicons-editor-contract';
	    expand.appendChild(icon_expand); 
	    expand.appendChild(icon_contract);	    
	    
	    menu.appendChild(expand); 
	}

	return menu;
    }
    image_map_scale(scale){
	let img_wrap = this.map;
	if('+' == scale){
	    let plus_w = img_wrap.clientWidth * 1.2;
	    let plus_h = img_wrap.clientHeight * 1.2;
	    img_wrap.style.width = plus_w + "px";
	    img_wrap.style.height = plus_h + "px";
	}else{
	    let minus_w = img_wrap.clientWidth * 0.8;
	    let minus_h = img_wrap.clientHeight * 0.8;
	    if (minus_w > 175){ 
		img_wrap.style.width = minus_w + "px";
		img_wrap.style.height = minus_h + "px";
	    }
	}
	this.image_map_resize();
    }
    image_map_resize(){
	
	//resize count text
	let counts = this.map.querySelectorAll('.' + this.prefix + 'image-map-count');
	for(let i=0;i<counts.length;i++){
	    let font_size = counts[i].clientHeight * 0.4;
	    counts[i].style.fontSize = font_size + "px";
	}
    }
    image_map_expand(){
	 let container = this.container;
	 container.classList.toggle(this.prefix + 'image-map-expanded');
	 this.image_map_resize();
    }    
}

