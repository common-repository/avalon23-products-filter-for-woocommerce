'use strict';

class Avalon23_Filter {
    constructor(container, filter_cast_id = '') {
        if (!container.querySelector('.avalon23-filter-data')) {
            return;
        }
        this.debag_mode=0;
        this.avalon_prefix="";
        this.filter_data = {};
        this.filter_options = {};
        this.filter_cast_id = filter_cast_id;
        this.btn_pressed=0;
	this.objects={};
	this.objects['sliders']={};
        if (!this.filter_cast_id) {
            this.filter_cast_id = avalon23_helper.create_id('filter-');
        }

        this.container = container;
        this.data = JSON.parse(this.container.querySelector('.avalon23-filter-data').innerText);

        this.filter_options = this.data.filter_options;
        delete this.data.filter_options;

        this.avalon_prefix=this.filter_options.avalon_prefix;   
        /* init current search  query  */      
        if(this.filter_options.filter_data==null){
            this.filter_options.filter_data={};
        }
        this.filter_data=this.filter_options.filter_data;
	this.add_search_data_global();
        //console.log(this.filter_data);
        /**/
        //ajax 
        this.products_cont =this.filter_options.ajax_selectors.product_container;
        this.no_products_found = this.filter_options.ajax_selectors.no_products_found_container;
        this.count_cont=this.filter_options.ajax_selectors.count_container;
        this.pagination_cont=this.filter_options.ajax_selectors.pagination_container;
        this.ajax_no_redraw=this.filter_options.ajax_selectors.ajax_no_redraw;
	this.ajax_redraw=this.filter_options.ajax_selectors.ajax_redraw;
        this.sortings_cont='.woocommerce-ordering select';        
        this.debag_mode =this.filter_options.debag_mode;
	if( typeof this.filter_options.special_reset == 'undefined'){
	    this.filter_options.special_reset = false;
	}
	//

        if(this.debag_mode){
            console.log("Filter container selectors");
            console.log("Prod cont: "+this.products_cont);
            console.log("No prod cont: "+this.no_products_found);
            console.log("Count cont: "+this.count_cont);
            console.log("Pagin cont: "+this.pagination_cont);
            console.log("No redra cont: "+this.ajax_no_redraw);
            console.log("Sort cont: "+this.sortings_cont);
            console.log("Filter main data");
            console.log(this.filter_options);
            console.log("Filter current search(Init)");
            console.log(this.filter_data);
            console.log("Items DATA:");
            console.log(this.data);
        }

        if(this.filter_options.ajax_mode){
            this.wrap_all_products();
            this.init_ajax_pagination();  
        } 
        
	//action before draw
	document.dispatchEvent(new CustomEvent('avalon23-filter-cunstruct', {detail: {
	    filter_obj: this
	}}));		
	
	
       // console.log(this.filter_data);
        this.list = this.container.querySelector('.avalon23-filter-list');
        this.draw();
	this.back_btn_init();

        //to show/hide reset button
        document.addEventListener('avalon23-filter-is-changed', e => {
            if (e.detail.filter_cast_id === this.filter_cast_id) {
                let reset_visibility = 'inline';

                if (Object.keys(this.filter_data).length === 0 || (Object.keys(this.filter_data).length === 1 && this.filter_data['filter_id'])) {
                    reset_visibility = 'none';
                }

                if (this.container.querySelector('.avalon23-filter-reset')) {
                    this.draw_navigation();
                    this.container.querySelector('.avalon23-filter-reset').style.display = reset_visibility;
                }
            }
        }, false);
	//for redrawing the filter by third-party scripts
	var _this = this;
	document.addEventListener('avalon23-filter-redraw', function (e) {
	    if (Array.isArray( e.detail.filter_ids) && e.detail.filter_ids.includes(_this.filter_options.filter_id + '')) {
		_this.redraw_ajax_form();
	    }
	    return true;
	}, false);
        


    }
    back_btn_init(){
	
	var _this = this;
	window.onpopstate = function (event) {
	    try {
		if(Object.keys(_this.filter_data).length){
		    window.location.reload();
		    return false; 
		}    
		               
	    } catch (e) {
		console.log(e);
	    }
	};	
    }
    wrap_all_products(){
            let producs_cont=document.querySelectorAll(this.products_cont+","+this.no_products_found);
            let avalon_wrapper=null;
            let producs_item=null;
            let parent_node=null;
         // wrap  all  products
            for (let i = 0; i < producs_cont.length; i++) {
                avalon_wrapper = document.createElement('div');
                avalon_wrapper.className = "avalon23_redraw avalon23_"+i;
                producs_item=producs_cont[i];
                parent_node=producs_item.parentNode;
                if(!parent_node.classList.contains("avalon23_redraw")){
                    parent_node.insertBefore(avalon_wrapper, producs_item);
                    avalon_wrapper.appendChild(producs_item);
                }
            }        
    }
    check_buttons(){
        if(Object.keys(this.filter_data).length !== 0 && !(Object.keys(this.filter_data).length === 1 && this.filter_data['filter_id']) ){
                let reset_visibility = 'inline';
                if (this.container.querySelectorAll('.avalon23-filter-reset').length) {
                    
                    let all_reset=this.container.querySelectorAll('.avalon23-filter-reset');
                    for(let i=0;i<all_reset.length;i++){
                        all_reset[i].style.display = reset_visibility;
                    }
                }            
        }      
        this.draw_navigation();
    }
    reset() {

        if (!this.container.querySelector('.avalon23-filter-list')) {
            return;
        }
	
	if (this.filter_options.special_reset) {
	    let _this = this; 
	    fetch(avalon23_helper_vars.ajax_url, {
		method: 'POST',
		credentials: 'same-origin',
		body: avalon23_helper.prepare_ajax_form_data({
		    action: 'avalon23_filter_reset',
		    avalon23_action: 'do_ajax', 
		    filter_id: this.filter_options.filter_id,
		    _wpnonce: this.filter_options._wpnonce,
		    filter_mode:'yes'

		})
	    }).then(response => response.json()).then(data => {
		let url=location.protocol + '//' + location.host +_this.reset_pagination_in_link(location.pathname);
		_this.filter_data = {};
		_this.draw();
		_this.make_filtration(url);
		document.dispatchEvent(new CustomEvent('avalon23-filter-reset', {detail: {
		    filter_cast_id: _this.filter_cast_id,
		    filter_id: _this.filter_options.filter_id
		}}));	    
	    }).catch((err) => {
		console.log(err);
	    });  	
	} else {
	    let url=location.protocol + '//' + location.host +this.reset_pagination_in_link(location.pathname);
	    this.filter_data = {};
	    this.draw();
	    this.make_filtration(url);
	    document.dispatchEvent(new CustomEvent('avalon23-filter-reset', {detail: {
		filter_cast_id: this.filter_cast_id,
		filter_id: this.filter_options.filter_id
	    }}));		    
	    
	}
	
    }

    draw() {
        //console.trace();
        this.list.innerHTML = '';
        if (Object.keys(this.data).length > 0) {

            if (typeof this.filter_data !== 'object') {
                this.filter_data = {};
            }

            //***
            let _this = this;
            let count_text="";
            let option =null;
            let show_count =0;
            let hide_empty_terms=0;
            let label=null;
           
            for (let key in this.data) {
                let li = null, elem = null, label = null, value = null,is_checked=false;

                switch (this.data[key]['view']) { 
                    case 'tax_slider':
                    case 'color':
                    case 'image':
			elem = null;
                    break;
                    case 'hierarchy_dd':

                        show_count = parseInt(this.data[key]['show_count']);

                        let elem_ul=document.createElement('ul');
                        let selected_key=0;
                        let parent_key=0;
                        if (this.filter_data[key]) {
                            for(let i=0;i<this.data[key]['options'].length;i++){
                                //if (this.filter_data[key].includes(parseInt(this.data[key]['options'][i].id))) {
				if (this.filter_data[key] == parseInt(this.data[key]['options'][i].id)) {
                                    selected_key=parseInt(this.data[key]['options'][i].id);
                                }                                
                            }
                        } 
                        parent_key=selected_key
			//console.log(this.data[key]['options'])
                        if( (_this.filter_options.current_tax && _this.filter_options.current_tax['taxonomy']==key) && selected_key==0){
                            selected_key = parent_key=_this.filter_options.current_tax['term_id'];
                        }

                        elem_ul=_this.get_chine_hierarchy_dd(key,selected_key,parent_key,this.data[key]['options'],elem_ul,show_count,hide_empty_terms,0);

                        let all_select=elem_ul.querySelectorAll('select');
                        let first_option =null;
                        let titles=[];

                        if(this.data[key]['show_title']==1 && all_select.length>0){
                            label= this.get_filter_title(key,this.data[key],null,is_checked);
                        }
			if(this.data[key]['hierarchy_title'] && this.data[key]['hierarchy_title']!=1){
			    titles = this.data[key]['hierarchy_title'].split("^");
			}
			
                        if(all_select.length==0){
                            elem_ul=null;
                        }else if(titles.length && titles.length > all_select.length){

			    for(let j=all_select.length;j<titles.length;j++){
				let li_tmp= document.createElement('li');
                                let select_tmp = document.createElement('select');
				let option_tmp = document.createElement('option');
				option_tmp.setAttribute('value', 0);
				option_tmp.innerText=titles[j];
				select_tmp.appendChild(option_tmp);
				select_tmp.setAttribute('disabled', 'disabled');
				
			//wcag
			let wcag_id_select_h = 'av23_' + key + '_' + this.filter_options.filter_id +'_'+ j+1;
			let label_wcag_select_h  = document.createElement('label');
			label_wcag_select_h.setAttribute('for', wcag_id_select_h);
			label_wcag_select_h.innerText = this.data[key]['title'] + ' ' + j+1;
			label_wcag_select_h.className = 'av23_wcag_hidden';
			select_tmp.id = wcag_id_select_h;
			li_tmp.appendChild(label_wcag_select_h);				
				
				li_tmp.appendChild(select_tmp);
				elem_ul.appendChild(li_tmp);
				
				
			    }

			}
			if (all_select.length!=0){

			    elem =document.createElement('div'); 
			    
			    if(typeof this.data[key]['hierarchy_images'][all_select.length-1] != 'undefined' &&  this.data[key]['hierarchy_images'][all_select.length-1] && this.data[key]['hierarchy_images'][all_select.length-1] !=-1 ){
				let h_dd_image = document.createElement('img');
				let level_num =  all_select.length-1;
				h_dd_image.setAttribute('src', this.data[key]['hierarchy_images'][level_num]);
				h_dd_image.setAttribute('alt', this.data[key]['title'] + ' ' + level_num);
				h_dd_image.className = 'avalon23-img-hdd' + key + '-' + level_num;
				
				let all_short_hdd = document.querySelectorAll('.avalon23-h-image-' + key + '-' + this.filter_options.filter_id);

				for(let x=0;x<all_short_hdd.length;x++){
				    let h_dd_image_s = document.createElement('img');
				    h_dd_image_s.setAttribute('src', this.data[key]['hierarchy_images'][level_num]);
				    h_dd_image_s.className = 'avalon23-img-hdd' + key + '-' + level_num;
				    all_short_hdd[x].innerHTML = '';
				    all_short_hdd[x].appendChild(h_dd_image_s);

				}
				
				if(this.data[key]['show_hierarchy_images'] == 1){
				    let h_images =document.createElement('div');
				    h_images.className = 'avalon23-h-image avalon23-h-image-' + key + '-' + this.filter_options.filter_id;
				    h_images.appendChild(h_dd_image);
				    
				    elem.appendChild(h_images);
				}				
			    }
			    
			    
			    
			    
			    elem.appendChild(elem_ul);
			}

			
                        for(let i=0;i<all_select.length;i++){
                            if(titles){
                                if(typeof titles[i]!="undefined"){
                                    all_select[i].querySelector('option[value="0"]').innerText=titles[i];
                                }
                            }
                            
                            all_select[i].addEventListener('change', (e) => {
                                e.stopPropagation();
                                let key = all_select[i].getAttribute('data-key');
                                let values=all_select[i].options[all_select[i].selectedIndex].value;

                                this.filter_data[key] = values;
                                if (values.length === 0) {
                                    delete this.filter_data[key];
                                }

                                if (values.length === 1) {
                                    if (values[0] == 0) {//for single select
                                        delete this.filter_data[key];
                                    }
                                }

                                this.make_filtration();
                            });

                        }
                    break;
                    case 'labels':
   
                        elem=document.createElement('ul');
                        elem.className = 'avalon23-labels';
                        show_count = parseInt(this.data[key]['show_count']);

                        for(let i=0;i<this.data[key]['options'].length;i++){
                            let li_label=document.createElement('li');
                            let p_label=document.createElement('p');
                            p_label.className = 'avalon23-label-count';
                            let span_label=document.createElement('span');
                            span_label.className = 'avalon23-label-checkbox';
                            let input_label=document.createElement('input');
                            input_label.className = 'avalon23-label-input';
                            
                            if(parseInt(this.data[key]['multiple'])){
                                input_label.setAttribute('type', 'checkbox');
                                span_label.classList.add('avalon23_rch_checkbox');
                            }else{
                                input_label.setAttribute('type', 'radio');
                                input_label.setAttribute('name', 'radio'+key+_this.filter_options.filter_id);
                                span_label.classList.add('avalon23_rch_radio');
                            }   

                            input_label.setAttribute('value', this.data[key]['options'][i].id);
                            input_label.setAttribute('data-key', key);  
                            
                            if (this.filter_data[key]) {
				let checked_values = _this.filter_data[key].split(",");
                                if (checked_values.includes(String(this.data[key]['options'][i].id))) {
                                    input_label.checked = true;
                                    span_label.classList.add('checked');
                                    this.data[key]['options'][i].count=-1;
                                    is_checked=true;
                                }
                            }    

                              
                            span_label.innerText = this.data[key]['options'][i].title; 
                            if(show_count && this.data[key]['options'][i].count!=-1){
                                p_label.innerText= this.data[key]['options'][i].count;
                                li_label.appendChild(p_label);  
                            }  
                            if(this.data[key]['options'][i].count==0){
                                input_label.setAttribute('disabled', 'disabled');  
                                li_label.style.opacity="0.4";
                            } 
			    
			    //wcag
			    let wcag_id_label = 'av23_' + key + '_' + this.filter_options.filter_id +'_'+ this.data[key]['options'][i].id;
			    let label_wcag_label  = document.createElement('label');
			    label_wcag_label.setAttribute('for', wcag_id_label);
			    label_wcag_label.innerText = this.data[key]['options'][i].title;
			    label_wcag_label.className = 'av23_wcag_hidden';
			    input_label.id = wcag_id_label;
			    span_label.appendChild(label_wcag_label);
			
                            span_label.appendChild(input_label);
                                                                                                               
                            li_label.appendChild(span_label);

                             elem.appendChild(li_label);
                         
                            
                        }
                        let all_span=elem.querySelectorAll('.avalon23-label-checkbox');
                        
                        if(this.data[key]['show_title']==1 && all_span.length>0){
                            label= this.get_filter_title(key,this.data[key],elem,is_checked);
                        }
                        if(all_span.length==0){
                            elem=null;
                        }
                        
                        for(let i=0;i<all_span.length;i++){
                            all_span[i].addEventListener('click', (e) => {
                                if(all_span[i].querySelector('input').disabled){
                                   return; 
                                }
                                if(all_span[i].querySelector('input').checked){
                                   all_span[i].querySelector('input').checked=false; 
                                   all_span[i].classList.remove('checked');
                                }else{
                                    all_span[i].querySelector('input').checked=true;
                                    all_span[i].classList.add('checked');
                                }
                                
                                
                                let values=[];
                                Array.from(elem.querySelectorAll('.avalon23-label-checkbox')).forEach(function(item){
                                    if(item.querySelector('input').checked){
                                        values.push(item.querySelector('input').value);
                                    }else{
                                        item.classList.remove('checked');
                                    }
                                });
                                this.filter_data[key] = values.join(',');
                                if (values.length === 0) {
                                    delete this.filter_data[key];
                                }  
                                this.make_filtration(); 
                            });
                            
                        }
                        

                        
                    break;
                    case 'checkbox_radio':
                        show_count = parseInt(this.data[key]['show_count']);
   
                        let ul_list=null;
                        let parent_key_ch=0;
                        if(_this.filter_options.current_tax && _this.filter_options.current_tax['taxonomy']==key){
                            parent_key_ch=_this.filter_options.current_tax['term_id'];
                        }                        

                        if(!_this.data[key]['template'] || _this.data[key]['template']==0){
                            ul_list=this.get_parent_radio_checkbox_element(key,this.data[key]['options'],parent_key_ch,show_count,hide_empty_terms);
                        
			//add toggle			
			let all_childs=ul_list.querySelectorAll('.avalon23_children_list');
			for(let j=0;j<all_childs.length;j++){
			    let toggle_parent = all_childs[j].parentElement;
			    let ch_toggle = document.createElement('span');
			    ch_toggle.className="avalon23_toggled_child_elem";
			    ch_toggle.innerText="+";
			    
			    if(toggle_parent){
				all_childs[j].parentNode.insertBefore(ch_toggle, all_childs[j]);
			    }
			    var checked_ch = all_childs[j].querySelectorAll('.avalon23_radio_checkbox_container.checked');
			    
			    
			    if(checked_ch.length>0){
				ch_toggle.classList.add('avalon23_toggled_child_opened');
			    }else {
				all_childs[j].classList.add('avalon23_toggled_child_hide');
			    }		    
			    ch_toggle.addEventListener('click', function (e) {
				ch_toggle.classList.toggle('avalon23_toggled_child_opened');
				all_childs[j].classList.toggle('avalon23_toggled_child_hide');
			    });	
		    			    
			}
			
			}else{
                            let opt = this.sort_by_parent_option(key,this.data[key]['options'],[],parent_key_ch,0);
                            ul_list = document.createElement('ul');                        
                            ul_list.className = 'avalon23_radio_checkbox';
                            switch(_this.data[key]['template']){
                                case '1':
                                    ul_list.classList.add('avalon23_radio_checkbox_list_1');
                                break;    
                                case '2':
                                    ul_list.classList.add('avalon23_radio_checkbox_list_2');
                                break;  
                                case '3':
                                    ul_list.classList.add('avalon23_radio_checkbox_list_3');
                                break;    
                                case '4':
                                    ul_list.classList.add('avalon23_radio_checkbox_list_4');
                                break;  
                                case '5':
                                    document.addEventListener('avalon23-filter-is-drawn', e => {
                                       let col= parseInt(ul_list.offsetWidth/150);
                                      
                                       if(col>4){
                                           col=4;
                                       }else if(col<=1){
                                           col=1;
                                       }
                                       ul_list.classList.add('avalon23_radio_checkbox_list_'+col);
                                    }, false);
                                break;
                            }
                            li= null;
                            label=null;
                            let span=null;
                            let span_label=null;
                            let input=null;
                            let disable_radio=null;
                            
                            Object.values(opt).map(function (o) {
                                li= document.createElement('li');                                                                                              
                                li= document.createElement('li');
                                label=document.createElement('label');                                
                                span=document.createElement('span');
                                span_label=document.createElement('span');
                                span.className = 'avalon23_checkmark';
                                label.className = 'avalon23_radio_checkbox_container';
                                span_label.className = 'avalon23_text_label';  

                                input = document.createElement('input');
                                input.setAttribute('value', o.id);
                                input.setAttribute('data-key', key);       
                                // checked
                                if (_this.filter_data[key]) {
				    let checked_values = _this.filter_data[key].split(",");
                                    if (checked_values.includes(String(o.id))) {
                                        input.checked = true;
                                        label.classList.add('checked');

                                        o.count=-1;
                                    }
                                }                                

                                if(parseInt(_this.data[key]['multiple'])){
                                    input.setAttribute('type', 'checkbox');
                                    label.classList.add('avalon23_rch_checkbox');
                                }else{
                                    input.setAttribute('type', 'radio');
                                    input.setAttribute('name', 'radio'+key+_this.filter_options.filter_id);
                                    label.classList.add('avalon23_rch_radio');
                                }                                  
                                count_text="";
                                if(show_count && o.count!=-1){
                                    count_text=" ("+o.count+")";
                                }   

                                span_label.innerText = o.title+count_text; 

                                    label.appendChild(span_label);
                                    label.appendChild(input);
                                    if(o.count==0){
                                        input.setAttribute('disabled', 'disabled');  
                                        label.style.opacity="0.4";
                                    }                                    

                                    label.appendChild(span);
                                    li.appendChild(label);  
                                    if(!parseInt(_this.data[key]['multiple'])){
                                       disable_radio=document.createElement('span');
                                       disable_radio.className = 'avalon23_disable_radio';
                                       disable_radio.innerText=" ";
                                       li.appendChild(disable_radio);
                                    }
                                    ul_list.appendChild(li);
 

                            });                              
                        }
                        li= null;
                        label=null;
                          
                        let all_lists=ul_list.querySelectorAll('li');
                        if(ul_list.querySelectorAll('input:checked').length){
                            is_checked=true;
                        }
                        if(Object.keys(all_lists).length!==0){
                        elem = ul_list;  
                        if(this.data[key]['show_title']==1 && Object.keys(all_lists).length!==0){
                            label= this.get_filter_title(key,this.data[key],elem,is_checked);
                        }                            

                        
                           for(let i=0;i<all_lists.length;i++){
                               let disable=all_lists[i].querySelector('.avalon23_disable_radio');
                               let check=all_lists[i].querySelector('input');                               
                               check.addEventListener('click', (e) => {
                                   e.stopPropagation();
                                   if(check.checked){
                                       all_lists[i].querySelector('label').classList.add('checked');
                                   }else{
                                       all_lists[i].querySelector('label').classList.remove("checked");                                       
                                   }
                                   let values=[];
                                   for(let j=0;j<all_lists.length;j++){
                                       if(all_lists[j].querySelector('input').checked){
                                           values.push(all_lists[j].querySelector('input').value);
                                           all_lists[j].querySelector('label').classList.add('checked');
                                       }else{
                                           all_lists[j].querySelector('label').classList.remove("checked");
                                       }
                                   }
                                    _this.filter_data[key] = values.join(',');

                                    if (values.length === 0) {
                                        delete _this.filter_data[key];
                                    }

                                    this.make_filtration();                                      

                               });
                               if(disable!=null){                                   
                                    disable.addEventListener('click', (e) => {
                                        e.stopPropagation();
                                        if(check.checked){
                                            all_lists[i].querySelector('label').classList.remove("checked");      
                                            check.checked=false;
                                            let values=[];
                                            for(let j=0;j<all_lists.length;j++){
                                                if(all_lists[j].querySelector('input').checked){
                                                    values.push(all_lists[j].querySelector('input').value);
                                                    all_lists[j].querySelector('label').classList.add('checked');
                                                }else{
                                                    all_lists[j].querySelector('label').classList.remove("checked");
                                                }
                                            }
                                             _this.filter_data[key] = values.join(',');

                                             if (values.length === 0) {
                                                 delete _this.filter_data[key];
                                             }

                                             this.make_filtration();                                             
                                        }
                                    });
                                    
                               }
                               
                               
                           }                         
                        
                        }

                        break;
                    case 'select':
                    case 'mselect':

                        let select_elem = document.createElement('select');
                        select_elem.setAttribute('data-key', key);
                        option = document.createElement('option');
                        show_count = parseInt(this.data[key]['show_count']);
                        option.setAttribute('value', 'reset');
                        option.innerText = this.data[key]['title'];
                        select_elem.appendChild(option);
                        let parent_key_select=0;
                        if(_this.filter_options.current_tax && _this.filter_options.current_tax['taxonomy']==key){
                            parent_key_select=_this.filter_options.current_tax['term_id'];
                        }

                        select_elem=this.get_parent_select_element(key,this.data[key]['options'],parent_key_select,select_elem,show_count,hide_empty_terms,0);


                        if(select_elem.options.length-1==0){
                            elem = null;
			    select_elem = null;
                            break;
                        }
                        let is_multi = parseInt(this.data[key]['multiple']);  

                        //***
                        //for non-multiselect drop-down
                        select_elem.addEventListener('change', (e) => {
                            e.stopPropagation();
                            let key = select_elem.getAttribute('data-key');
                            //let values = Array.from(select_elem.querySelectorAll('option:checked')).map(el => Number(el.value));

                            let values=[];
                            if(is_multi){
                                Array.from(select_elem.querySelectorAll('option')).forEach(function(item){
                                    if(item.hasAttribute('selected')){
                                        values.push(item.value);
                                    }
                                });                                
                            }else{
                               values.push( select_elem.options[select_elem.selectedIndex].value);
                            }

                            this.filter_data[key] = values.join(',');

                            if (values.length === 0) {
                                delete this.filter_data[key];
                            }

                            if (values.length === 1) {
                                if (values[0] === 0 || values[0]=='reset') {//for single select
                                    delete this.filter_data[key];
                                }
                            }

                            this.make_filtration();
                        });

                        //***

                       

                        if (is_multi && typeof SelectM23 === 'function') {
                            elem = select_elem;
                            setTimeout(() => {

                                //fix for strange behaviour of multi selects with first option selection while redrawing
                                select_elem.querySelector('option').setAttribute('value', 'selectm23-exclude');//first option
                                select_elem.setAttribute('multiple', 'multiple');
                                new SelectM23(select_elem, false, this.data[key]['title']);//wrapping of <select>

                            }, 111);
                        } else {
			   elem =  document.createElement('div');
			   elem.className = 'avalon23_select_wraper';
			   elem.appendChild(select_elem);

			    //wcag
			    let wcag_id_select = 'av23_' + key + '_' + this.filter_options.filter_id;
			    let label_wcag_select  = document.createElement('label');
			    label_wcag_select.setAttribute('for', wcag_id_select);
			    label_wcag_select.innerText = this.data[key]['title'];
			    label_wcag_select.className = 'av23_wcag_hidden';
			    select_elem.id = wcag_id_select; 
			    elem.appendChild(label_wcag_select);			    
			   
			}
	
                        break;

                    case 'range_slider':

                        elem = document.createElement('div');
                        elem.setAttribute('data-key', key);
                        elem.className = 'ranger23-track avalon23-slider';
                        elem.setAttribute('data-min', this.data[key]['min']);
                        elem.setAttribute('data-max', this.data[key]['max']);
                        elem.setAttribute('data-selected-min', this.data[key]['min']);
                        elem.setAttribute('data-selected-max', this.data[key]['max']);
                        if(this.data[key]['min']==0 && this.data[key]['max']==0){
                            elem=null;
                            break;
                        }
                        
                        
                        count_text="";

                        if(this.data[key]['show_count'] && this.data[key]['count']!=-1 ){
                            count_text=" ("+this.data[key]['count']+")";
                        }
                        

                        this.data[key]['title']=this.data[key]['title']+count_text
                        label= this.get_filter_title(key,this.data[key],elem,is_checked);
  

                        //+++

                        if (_this.filter_data[key]) {
                            let val = _this.filter_data[key].split(':');
                            elem.setAttribute('data-selected-min', val[0]);
                            elem.setAttribute('data-selected-max', val[1]);
                        }

                        //+++

                        let slider = new Ranger23(elem, avalon23_helper.create_id('slider-'));

                        //if slider generated in float containers coordinates are wrong
                        setTimeout(() => {
                            slider.resize();
                        }, 125);
			this.objects['sliders'][key] = slider;
                        document.addEventListener('ranger23-update', (e) => {
                            if (e.detail.cast_id === slider.cast_id) {

                                let key = slider.track.getAttribute('data-key');
                                let from = parseInt(e.detail.from, 10);
                                let to = parseInt(e.detail.to, 10);

                                _this.filter_data[key] = from + ':' + to;

                                if (slider.min === from && slider.max === to) {
                                    delete _this.filter_data[key];
                                }

                                if (Object.keys(_this.filter_data).length === 0) {
                                    _this.make_filtration(null);//because another way no reaction if filter_data empty
                                } else {
                                    _this.make_filtration();
                                }
                            }
                        });

                        break;


                    case 'textinput':

			elem = document.createElement('div');
                        elem.setAttribute('data-key', key);	
                        elem.className = 'avalon23_text_search_wrapper';			
				
			let search_icon = document.createElement('img');
			search_icon.className = 'avalon23_search-icon';
			search_icon.setAttribute('src', 'data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDU2Ljk2NiA1Ni45NjYiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDU2Ljk2NiA1Ni45NjY7IiB4bWw6c3BhY2U9InByZXNlcnZlIiB3aWR0aD0iMTZweCIgaGVpZ2h0PSIxNnB4Ij4KPHBhdGggZD0iTTU1LjE0Niw1MS44ODdMNDEuNTg4LDM3Ljc4NmMzLjQ4Ni00LjE0NCw1LjM5Ni05LjM1OCw1LjM5Ni0xNC43ODZjMC0xMi42ODItMTAuMzE4LTIzLTIzLTIzcy0yMywxMC4zMTgtMjMsMjMgIHMxMC4zMTgsMjMsMjMsMjNjNC43NjEsMCw5LjI5OC0xLjQzNiwxMy4xNzctNC4xNjJsMTMuNjYxLDE0LjIwOGMwLjU3MSwwLjU5MywxLjMzOSwwLjkyLDIuMTYyLDAuOTIgIGMwLjc3OSwwLDEuNTE4LTAuMjk3LDIuMDc5LTAuODM3QzU2LjI1NSw1NC45ODIsNTYuMjkzLDUzLjA4LDU1LjE0Niw1MS44ODd6IE0yMy45ODQsNmM5LjM3NCwwLDE3LDcuNjI2LDE3LDE3cy03LjYyNiwxNy0xNywxNyAgcy0xNy03LjYyNi0xNy0xN1MxNC42MSw2LDIzLjk4NCw2eiIgZmlsbD0iIzAwMDAwMCIvPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8L3N2Zz4K');
			search_icon.setAttribute('alt', avalon23_helper_vars.lang.search);
			
			let clear_icon = document.createElement('img');
			clear_icon.className = 'avalon23_clear-icon';
			clear_icon.setAttribute('src', 'data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDUxLjk3NiA1MS45NzYiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDUxLjk3NiA1MS45NzY7IiB4bWw6c3BhY2U9InByZXNlcnZlIiB3aWR0aD0iMTZweCIgaGVpZ2h0PSIxNnB4Ij4KPGc+Cgk8cGF0aCBkPSJNNDQuMzczLDcuNjAzYy0xMC4xMzctMTAuMTM3LTI2LjYzMi0xMC4xMzgtMzYuNzcsMGMtMTAuMTM4LDEwLjEzOC0xMC4xMzcsMjYuNjMyLDAsMzYuNzdzMjYuNjMyLDEwLjEzOCwzNi43NywwICAgQzU0LjUxLDM0LjIzNSw1NC41MSwxNy43NCw0NC4zNzMsNy42MDN6IE0zNi4yNDEsMzYuMjQxYy0wLjc4MSwwLjc4MS0yLjA0NywwLjc4MS0yLjgyOCwwbC03LjQyNS03LjQyNWwtNy43NzgsNy43NzggICBjLTAuNzgxLDAuNzgxLTIuMDQ3LDAuNzgxLTIuODI4LDBjLTAuNzgxLTAuNzgxLTAuNzgxLTIuMDQ3LDAtMi44MjhsNy43NzgtNy43NzhsLTcuNDI1LTcuNDI1Yy0wLjc4MS0wLjc4MS0wLjc4MS0yLjA0OCwwLTIuODI4ICAgYzAuNzgxLTAuNzgxLDIuMDQ3LTAuNzgxLDIuODI4LDBsNy40MjUsNy40MjVsNy4wNzEtNy4wNzFjMC43ODEtMC43ODEsMi4wNDctMC43ODEsMi44MjgsMGMwLjc4MSwwLjc4MSwwLjc4MSwyLjA0NywwLDIuODI4ICAgbC03LjA3MSw3LjA3MWw3LjQyNSw3LjQyNUMzNy4wMjIsMzQuMTk0LDM3LjAyMiwzNS40NiwzNi4yNDEsMzYuMjQxeiIgZmlsbD0iIzAwMDAwMCIvPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+Cjwvc3ZnPgo=');
			clear_icon.setAttribute('alt', avalon23_helper_vars.lang.clear);			
			
                        let text_seach = document.createElement('input');
                        text_seach.setAttribute('data-key', key);
                        text_seach.setAttribute('type', 'text');
			

			//wcag
			let wcag_id_text = 'av23_' + key + '_' + this.filter_options.filter_id;
			let label_wcag_text = document.createElement('label');
			label_wcag_text.setAttribute('for', wcag_id_text);
			label_wcag_text.innerText = this.data[key]['placeholder'];
			label_wcag_text.className = 'av23_wcag_hidden';
			text_seach.id = wcag_id_text;
			elem.appendChild(label_wcag_text);
			
			
			elem.appendChild(search_icon); 
			elem.appendChild(text_seach);
			elem.appendChild(clear_icon);


                        let minlength = 1;
                        if (parseInt(this.data[key]['minlength']) > 0) {
                            minlength = this.data[key]['minlength'];
                        }
                        let livesearch = 0;
			
                        if (parseInt(this.data[key]['livesearch']) > 0) {
                            livesearch = 1;
                        }			

                        text_seach.setAttribute('livesearch', livesearch);
			text_seach.setAttribute('minlength', minlength);
                        text_seach.className = 'avalon23-text-search';
			text_seach.setAttribute('placeholder', this.data[key]['placeholder']);

                        if (_this.filter_data[key]) {
                            text_seach.value = _this.filter_data[key];
			    clear_icon.style.visibility = "visible";
                        }
			clear_icon.addEventListener("click", () => {
			  text_seach.value = "";
			  clear_icon.style.visibility = "hidden";
			  _this.get_products_data(text_seach);
			});
			search_icon.addEventListener("click", () => {

			    if (text_seach.value.length === 0) {
				    if (typeof _this.filter_data[key] !== 'undefined') {
					text_seach.classList.remove('avalon23-not-ready-text-search');
					delete _this.filter_data[key];
					_this.btn_pressed=1;
					_this.make_filtration();
				    }

			    } else {
				    text_seach.classList.add('avalon23-not-ready-text-search');
				    _this.filter_data[key] = text_seach.value;
				    _this.btn_pressed=1;
				    _this.make_filtration();
			    }


			});			
			
			/*text  live search*/
			let ul_text_search = document.createElement('ul');
			ul_text_search.className = 'avalon23_text_ajax_result';
			let li_start = document.createElement('li');
			li_start.className = 'avalon23_text_ajax_result_load av23_lds-dual-ring';
			li_start.style.display = 'none';
			ul_text_search.appendChild(li_start);
			text_seach.parentNode.appendChild(ul_text_search);
//			text_seach.addEventListener('focusin', (event) => {
//			    ul_text_search.style.display = 'block'
//			});
//
//			text_seach.addEventListener('focusout', (event) => {
//			    ul_text_search.style.display = 'none';
//			});
	

                        this.attach_keyup_event(text_seach,clear_icon);
                        this.attach_mouseup_event(text_seach);



                        break;

                    case 'switcher':

                        value = 0;

                        if (_this.filter_data[key]) {
                            value = _this.filter_data[key];
                        }

                        
                        elem = avalon23_helper.draw_switcher(key, value, 0, '');
                        avalon23_helper.init_switcher(elem.querySelector('.switcher23'));
                        
                        if(parseInt(this.data[key]['count'])==0){
                            elem.querySelector(".switcher23").setAttribute('disabled', 'disabled');  
                            elem.style.opacity="0.4";                                        
                        }                        


                        elem.querySelector('.switcher23').addEventListener('change', function (e) {

                            if (Number(this.value)) {
                                _this.filter_data[key] = Number(this.value);
                            } else {
                                delete _this.filter_data[key];
                            }

                            _this.make_filtration();
                            return true;
                        });

                        label = document.createElement('div');
                        label.className = 'avalon23-slider-label';					
                      
                        count_text="";
                        if(parseInt(this.data[key]['count'])!=-1 && parseInt(this.data[key]['show_count'])){
                            count_text=" ("+this.data[key]['count']+")";
                        }
                        
                        label.innerText = this.data[key]['title'] +count_text+':';

                        break;

                    case 'calendar':

                        elem = document.createElement('div');
                        elem.className = 'calendar23-selector';
                        if(this.data[key]['show_title']==1){
                            label= this.get_filter_title(key,this.data[key],elem,is_checked);
                        }
                        if (_this.filter_data[key]) {
                            value = _this.filter_data[key];
                        }

                        let selector = new Calendar23_Selector(elem, value, this.data[key]['title'], avalon23_helper_vars.lang.calendar23_names);

                        selector.selected = () => {
                            this.filter_data[key] = selector.unix_time_stamp;
                            this.make_filtration();
                        };

                        break;

                    case 'html':

                        elem = document.createElement('div');
                        //elem.innerHTML = atob(this.data[key]['html']);
			elem.innerHTML =decodeURIComponent(escape(window.atob(this.data[key]['html'])));

                        break;

                    default:
                        //js extension
                        if (typeof window[this.data[key]['view']] === 'function') {
                            let answer = window[this.data[key]['view']](key, this);
			    if (answer != null && typeof answer['elem'] !='undefined' && typeof answer['label'] !='undefined'){
				elem = answer['elem'];
				label = answer['label'];
			    } else {
				elem = answer;
			    }
                        }
                        break;
                }

                if (elem && this.data[key]) {
                    li = document.createElement('div');
                    li.className = 'avalon23-filter-cell-' + key + ' avalon23-filter-cell-type-' + this.data[key]['view'];

                    /*   GRID    */ 
                    if(this.list.offsetWidth<299){
                        let w_grid='12';
                        if(this.data[key]['width_sm']=='hide show@sm'){
                            w_grid+=' hide show@sm'; 
                        }
                        if(this.data[key]['width_md']=='hide show@md'){
                            w_grid+=" hide show@md"; 
                        }
                        li.setAttribute('bp',w_grid); 
                    }else{
                        li.setAttribute('bp', this.data[key]['width_sm']+" "+this.data[key]['width_md']+" "+this.data[key]['width_lg']);
                    }
                    /*+++++++*/
                    
                    li.appendChild(elem);
                    this.list.appendChild(li);

                    if (label) {
                        label.appendBefore(elem);
                    }
                }
                if(this.debag_mode){
                    console.log("---------------------------");
                    console.log("Draw current item: "+key);
                    console.log(this.data[key]);
                    console.log("---------------------------");
                }
            }

            //submit area
            let li_submit = document.createElement('div');
            li_submit.className = 'avalon23-filter-submit-container';
            li_submit.setAttribute('bp','12');
            
            //add reset button
            let reset = document.createElement('a');
            reset.setAttribute('href', '#');
            reset.className = 'avalon23-filter-reset avalon23-btn';
            if(this.filter_options.reset_text==''){
                reset.innerText = avalon23_helper_vars.lang.reset;
            }else{
                reset.innerText = this.filter_options.reset_text;
            }
            reset.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                _this.btn_pressed=1;
                _this.reset();
                _this.filter_data = {};
                _this.draw_navigation();

                return false;
            });

            let li = document.createElement('div');
            li.className = 'avalon23-filter-reset-container';
            li.appendChild(reset);
            li_submit.appendChild(li);
            
            if(_this.filter_options.autosubmit=='ajax_redraw' || _this.filter_options.autosubmit=='no'){
                //add filter button
                let filter= document.createElement('a');
                filter.setAttribute('href', '#');
                filter.className = 'avalon23-filter-filter avalon23-btn';
                if(this.filter_options.filter_text==''){
                    filter.innerText = avalon23_helper_vars.lang.filter;
                }else{
                    filter.innerText = this.filter_options.filter_text;
                }
                filter.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    _this.btn_pressed=1;
                    _this.make_filtration();
                    return false;
                });

                let li_f = document.createElement('div');
                li_f.className = 'avalon23-filter-filter-container';
                li_f.appendChild(filter);
                li_submit.appendChild(li_f);
            }
            
            // submit btn position

            this.list.appendChild(li_submit);
    
            
            /*************/
            
            
            document.dispatchEvent(new CustomEvent('avalon23-filter-is-drawn', {detail: {
                filter_cast_id: this.filter_cast_id,
                filter_elements: this.list,
		filter_data: this.filter_data,
		filter_option: this.filter_options
            }}));   
            this.check_buttons();
        }
    }
    get_filter_title(key,data,toggled_elem,is_checked){
        let element=document.createElement('span');
        element.className = 'avalon23_filter_title';
        element.classList.add("avalon23_title_"+key);
        element.innerText = data['title'];  

        if(typeof data.toggle!="undefined" && data.toggle!='none'  && toggled_elem!=null ){
            toggled_elem.classList.add('avalon23_toggled_box');
            let icon=document.createElement('div');
            let span=document.createElement('span');
            icon.className="avalon23_toggle_el";
            span.className="avalon23_toggle_icon";
            icon.appendChild(span);
            element.appendChild(icon);

            if(is_checked){
               data.toggle='opened';                
            }
           
            if(data.toggle=='opened'){
                element.classList.add('avalon23_toggled_opened');
            }else if(data.toggle=='closed'){
                toggled_elem.classList.add('avalon23_toggled_hide');
            }
               
            element.addEventListener('click', function (e) {
                element.classList.toggle('avalon23_toggled_opened');
                toggled_elem.classList.toggle('avalon23_toggled_hide');
            });

        }    
        return element;
    }
    get_chine_hierarchy_dd(key,selected_key,parent_key,options,element,show_count,hide_empty_terms, level){
        let prev_parent=-1;
        let prev_selected=selected_key;
        let li= document.createElement('li');
        let select= document.createElement('select');
        let option=null;
        let options_count=0;
        let count_text="";
        select.setAttribute('data-key', key);
	select.setAttribute('data-level', level++);
        let first_option = document.createElement('option');            
        first_option.setAttribute('value', 0);
        first_option.innerText = this.data[key]['title'];
        select.appendChild(first_option);	

        Object.values(options).map(function (o) {
            if(o.parent==parent_key){
                option=document.createElement('option');
                option.setAttribute('value', o.id);
                if(selected_key==o.id){
                    prev_selected=o.parent;
                    option.setAttribute('selected', '');
                    o.count=-1;
                }
                count_text="";
                if(show_count && o.count!=-1){
                    count_text=" ("+o.count+")";
                }
                option.innerText = o.title+count_text;
                //add to select

                    if(o.count==0){
                        option.setAttribute('disabled', 'disabled');  
                        option.style.opacity="0.4";
                    }  
                    select.appendChild(option);
                    options_count++;
               
                
            }
            if(o.id==parent_key){
                prev_parent=o.parent;
            }
            
        });
        if(options_count){
	    
	//wcag
	let wcag_id_select = 'av23_' + key + '_' + this.filter_options.filter_id +'_'+ level;
	let label_wcag_select = document.createElement('label');
	label_wcag_select.setAttribute('for', wcag_id_select);
	label_wcag_select.innerText = this.data[key]['title'] + ' ' + level;
	label_wcag_select.className = 'av23_wcag_hidden';
	select.id = wcag_id_select;
	li.appendChild(label_wcag_select);	    
	    	    
            //add to  element
            li.appendChild(select);
            element.insertBefore(li, element.firstChild);

        }
        if(prev_parent!=-1){
            this.get_chine_hierarchy_dd(key,prev_selected,prev_parent,options,element,show_count,hide_empty_terms,level);
        }
        
        return element;
    }
    get_parent_radio_checkbox_element(key,options,parent_key,show_count,hide_empty_terms){
        let _this = this;
        let ul_list = document.createElement('ul');                        
        ul_list.className = 'avalon23_radio_checkbox';
        ul_list.classList.add("avalon23_rch_"+parent_key);
        let li=null;
        let span=null;
        let span_label=null;
        let disable_radio=null;
        let label = null;
        let input=null;
        let count_text="";
        let children=null;
        Object.values(options).map(function (o) {
            if (typeof o.parent == 'undefined') {
                o.parent=0;
            }
            if(o.parent==parent_key){
                li= document.createElement('li');
                label=document.createElement('label');
                span=document.createElement('span');
                span_label=document.createElement('span');
                span.className = 'avalon23_checkmark';
                label.className = 'avalon23_radio_checkbox_container';
                span_label.className = 'avalon23_text_label';  

                input = document.createElement('input');
                input.setAttribute('value', o.id);
                input.setAttribute('data-key', key);       
                // checked
                if (_this.filter_data[key]) {
		    let checked_values = _this.filter_data[key].split(",");
                    if (checked_values.includes(String(o.id))) {
                        input.checked = true;
                        label.classList.add('checked');
                       
                        o.count=-1;
                    }
                }                                

                if(parseInt(_this.data[key]['multiple'])){
                    input.setAttribute('type', 'checkbox');
                    label.classList.add('avalon23_rch_checkbox');
                }else{
                    input.setAttribute('type', 'radio');
                    input.setAttribute('name', 'radio'+key+_this.filter_options.filter_id);
                    label.classList.add('avalon23_rch_radio');
                }                                  
                count_text="";
                if(show_count && o.count!=-1){
                    count_text=" ("+o.count+")";
                }   
		
                span_label.innerHTML = o.title+count_text; 
                    label.appendChild(span_label);
                    label.appendChild(input);
                    if(o.count==0){
                        input.setAttribute('disabled', 'disabled');  
                        label.style.opacity="0.4";
                    } 
                   // label.appendChild(input);
                    label.appendChild(span);
                    li.appendChild(label);  
                    if(!parseInt(_this.data[key]['multiple'])){
                       disable_radio=document.createElement('span');
                       disable_radio.className = 'avalon23_disable_radio';
                       disable_radio.innerText=" ";
                       li.appendChild(disable_radio);
                    }
                    children=_this.get_parent_radio_checkbox_element(key,options,o.id,show_count,hide_empty_terms);                                         

                    if(Object.keys(children.querySelectorAll('li')).length!==0){
                        children.classList.add('avalon23_children_list');
                        li.appendChild(children);
                    }
                    ul_list.appendChild(li);

                               

            }

        });    
        return ul_list;
    }
    sort_by_parent_option(key,options,sorted_options,parent_key,level){
        let _this=this;
        let children=null;

        let lev=level;
        
        Object.values(options).map(function (o) {
            //+++
            if (typeof o.parent == 'undefined') {
                o.parent=0;
            } 
            if(o.parent==0){
                level=0;
            }

            if(o.parent==parent_key){
                sorted_options.push(o);
                sorted_options=_this.sort_by_parent_option(key,options,sorted_options,o.id,lev+1);
            }

        });     
        return sorted_options;        
    }
    get_parent_select_element(key,options,parent_key,elem,show_count,hide_empty_terms,level){
        let _this=this;
        let option=null;
        let count_text="";
        let children=null;

        let lev=level;

        Object.values(options).map(function (o) {
            option = document.createElement('option');
            option.setAttribute('value', o.id);
            //+++

            if (typeof o.parent == 'undefined') {
                o.parent=0;
            } 
            if(o.parent==0){
                level=0;
            }

            if(o.parent==parent_key){
                if (_this.filter_data[key]) {
		    let checked_values = _this.filter_data[key].split(",");
                    if (checked_values.includes(String(o.id))) {
                        option.setAttribute('selected', '');
                        o.count=-1;
                    }
                }
                count_text="";
                if(show_count && o.count!=-1){
                    count_text=" ("+o.count+")";
                }
		var spices="";
		for(let h=0;h<lev;h++){
		    spices+="\xa0\xa0\xa0";
		}
                option.innerText = spices+o.title+count_text;


                    option.setAttribute('data-parent', parent_key);
                    option.setAttribute('data-level', lev);

		    
                    if(o.count==0){
                        option.setAttribute('disabled', 'disabled');  
                        option.style.opacity="0.4";
                    }  
                    elem.appendChild(option);                     
                    elem=_this.get_parent_select_element(key,options,o.id,elem,show_count,hide_empty_terms,lev+1);
  

            }

        });     
        return elem;
    }
    draw_navigation() {

        if (this.navigation) {
          this.navigation.remove();
        }
        if(this.filter_options.filter_navigation!='n'){
            this.navigation=this.init_navigation_items('avalon23-filter-navigation');
            if(this.filter_options.filter_navigation=='t'){
               this.container.insertBefore(this.navigation, this.container.querySelector('.avalon23-filter-list')); 
            }else{
               this.container.querySelector('.avalon23-filter-list').after(this.navigation);
            }            
        }
        /*additional  navigation*/
        if(this.filter_options.filter_navigation_additional){
            let blocks = document.querySelectorAll(this.filter_options.filter_navigation_additional);        
            let old_blocks=document.querySelectorAll('.avalon23-filter-navigation.avalon23-filter-navigation-additional');
	    
            if(old_blocks.length>0){
                 for(let i=0;i<old_blocks.length;i++){
                     let nav_labels =old_blocks[i].querySelectorAll('.avalon23-filter-navigation-label[data-id="' + this.filter_options.filter_id + '"]');
		     for(let j=0;j<nav_labels.length;j++){
			 nav_labels[j].remove();
		     }
		     this.add_nav_items(old_blocks[i]);
		    
                 }
		
            }  else {
		if(blocks.length>0){
		     for(let i=0;i<blocks.length;i++){
			 blocks[i].after(this.init_navigation_items('avalon23-filter-navigation avalon23-filter-navigation-additional'));
		     }
		}		
	    }     

        }
          
    }
    redraw_objects() {

	if (typeof this.objects['sliders'] != 'undefined') {
	    let sliders = this.objects['sliders'];
	    for (var slider_key in sliders) {
		sliders[slider_key].resize();
	    }
	}
    }
    init_navigation_items(classes){
        let nav = document.createElement('div');
        nav.className = classes;
        this.add_nav_items(nav);  
        return nav;
    }
    add_nav_items(nav){
        let keys = Object.keys(this.data);

        if (keys.length > 0) {
            let label = null;

            for (let i in keys) {

                let key = keys[i];

                if (!this.filter_data[key]) {
                    continue;
                }
                label = document.createElement('span');
                label.className = 'avalon23-filter-navigation-label';
		label.setAttribute('data-id', this.filter_options.filter_id);
                label.innerText = this.data[key]['title'];
                nav.appendChild(label);

                //***
                label.addEventListener('click', () => {
                    label.remove();
                    delete this.filter_data[key];
                    this.draw();
                    this.make_filtration();
                    return true;
                });

            }
        } 	
    }   
    //use this for class filter also
    attach_keyup_event(input,clear) {
        let _this = this;

        input.addEventListener('keyup', function (e) {

            let add = {};
            let do_search = false;
            let key = this.getAttribute('data-key');

            if(clear){
		if (input.value.length === 0) {
		    clear.style.visibility = "hidden"; 
		}else{
		    clear.style.visibility = "visible";
		}
	    }
	    if (input.value.length === 0) {
		if (input.classList.contains('avalon23-not-ready-text-search')) {
		    if (typeof _this.filter_data[key] !== 'undefined') {
			input.classList.remove('avalon23-not-ready-text-search');
			delete _this.filter_data[key];

		    }
		}

	    } else {

		if (input.value.length >= Number(input.getAttribute('minlength'))) {
		    input.classList.add('avalon23-not-ready-text-search');
		    _this.filter_data[key] = input.value;
		    
		}
	    }
	    
	    _this.get_products_data(input);
	    
            if (e.keyCode === 13 || typeof e.detail.woo_text_search !== 'undefined') {

		 do_search = true;

            }

            if (e.keyCode === 27) {
                delete _this.filter_data[key];
                do_search = true;
                input.classList.remove('avalon23-not-ready-text-search');
            }


            if (do_search) {
                _this.btn_pressed=1;
                _this.make_filtration();
            }

        });
    }

    //use this for class filter also
    attach_mouseup_event(input) {
        let _this = this;

        //click on cross
        input.addEventListener('mouseup', function (e) {
            e.stopPropagation();

            if (input.value.length > 0) {
                setTimeout(() => {
                    if (input.value.length === 0) {
                        input.classList.remove('avalon23-not-ready-text-search');
                        delete _this.filter_data[this.getAttribute('data-key')];
                        _this.make_filtration();
                    }
                }, 5);
            }
        });
    }
    get_get_values() {
	const searchParams  = new URLSearchParams(window.location.search);
	let get = {};
	for(var pair of searchParams.entries()) {
	    get[pair[0]] = pair[1];
	}
	
	return get;
    }
    get_all_filter_data() {

	let all_filter_data = this.get_search_data_global_array();
	if (Object.keys(all_filter_data).length == 0) {
	    let all_filter_data = this.get_get_values();
	}	
	return all_filter_data;
    }    
    generate_query_url(additional_get){

        let hash = window.location.hash;
        let vars = window.location.search.match(new RegExp('[^&?]+', 'gm'));
        let result = {};
        if(vars){
            for (let i=0; i < vars.length; i++) {
                let r = vars[i].split('=');

                if (r[0].indexOf(this.avalon_prefix) !== 0) {
                        result[r[0]] = r[1];
                }
            }
        }

        result=this.reset_pagination_in_get(result);
        let filter_data={};
               
        for (let j in this.filter_data){
          filter_data[this.avalon_prefix+j] = this.filter_data[j];
        }  

        if(Object.keys(this.filter_data).length !== 0){
            filter_data[this.avalon_prefix+"filter_id"]=this.filter_options.filter_id; 
        }
        if(Object.keys(filter_data).length === 1 && filter_data[this.avalon_prefix+"filter_id"]){
	    if (!this.filter_options.special_reset) {
		delete filter_data[this.avalon_prefix+"filter_id"]; 
	    }
	  
        }        
        
        
        let result_link= Object.assign({}, result,filter_data);
        
        if(additional_get && Array.isArray(additional_get)){
            for (let item in additional_get){
                result_link[item]=additional_get[item];
            }             
        }
        let ret = [];
        for (let d in result_link){
          ret.push(encodeURIComponent(d) + '=' + result_link[d]);
        }

        let get ="";
        if(Object.keys(ret).length !== 0){
            get="?"+ ret.join('&') ;
        }
        get=get+hash;
        return get;
    }
    generate_query_url_parser(){
        let hash = window.location.hash;
        let vars = window.location.search;
	let url = this.generate_base_url();
	
	//multi-filter compatibility
	let new_url = url;
	for (let i_url in this.filter_options['all_search_urls']){	    
	    let tmp_url = new_url.split('/' + this.filter_options['all_search_urls'][i_url] + '/');
	    new_url = tmp_url[0];
	}
	
	if(new_url.slice(-1)!= '/'){
	    new_url+='/';
	}
	
	let url_array=[];

	const ordered_data = Object.keys(this.filter_data).sort().reduce(
	  (obj, key) => { 
	    obj[key] = this.filter_data[key]; 
	    return obj;
	  }, 
	  {}
	);
	for (let j in ordered_data){
	    
	    if(typeof this.data[j] != 'undefined'){
		
		if(typeof this.data[j]['type'] != 'undefined' && this.data[j]['type'] == 'taxonomy'){
		    let curr_url = j.replace(/^pa_/, '');
		    let request_array=[];
		    let request = this.filter_data[j].split(',');

		    for (let i in this.data[j].options){

			if(request.includes(this.data[j].options[i]['id']+"")){
			    request_array.push(this.data[j].options[i]['slug']);
			}
			
		    }
		    request_array.sort();
		    url_array.push(curr_url + "-" + request_array.join('-and-'));
		}else if(typeof this.data[j]['view'] != 'undefined' && this.data[j]['view'] == 'textinput'){
		    
		    let request = encodeURI(this.filter_data[j]);
		    url_array.push(j + '-' + request);
		    
		}else if(typeof this.data[j]['view'] != 'undefined' && this.data[j]['view'] == 'switcher'){

		    url_array.push(j);
		    
		}else{
		    let request = this.filter_data[j]+'';
		    request = request.replace(',','-and-');
		    request = request.replace(':','-to-');
		    request = request.replace(/\s+/g, '+');
		    url_array.push(j + '-' + request);
		    
		}
		
	    }
	    
        }  
	let search_request_url ="";
	
	if(url_array.length){
	    search_request_url = this.filter_options.search_url + '/' + url_array.join('/') + '/';
	} else {
	    if (this.filter_options.special_reset && Object.keys(this.filter_data).length === 1 && this.filter_data["filter_id"]) {
		search_request_url = this.filter_options.search_url + '/';
	    }
	}
		
	return new_url + search_request_url + vars + hash;
    }
    generate_base_url(){
        let base_url=location.protocol + '//' + location.host +this.reset_pagination_in_link(location.pathname);//reset pagination
        //redirect  to shop page if doesn't exist  product template
        let producs_cont=document.querySelectorAll(this.products_cont+","+this.no_products_found+","+this.ajax_no_redraw);//WOOT compatibility
        
        if(producs_cont.length==0 || document.querySelector('body.single-product')){
            base_url=this.filter_options.shop_page;
            this.filter_options.ajax_mode=0;            
        }
        
        return base_url;
    }
    
    get_url(link) {
	let url = this.generate_base_url(); 
	if(link){
	   url=link;
	}	
	
	if(this.filter_options.init_url){	    
	    url = this.generate_query_url_parser();	    
	}else{
	    url = url + this.generate_query_url();
	}
	
	return url;
    }
    make_filtration(link) {
        
        avalon23_helper.message(avalon23_helper_vars.lang.filtering + ' ...', 'warning', -1);

        let url = this.get_url(link);
	if (typeof url == 'undefined') {
                if(this.debag_mode){
                    console.log("Empty URL!");
                }	    	    
	    return false;
	}

        if(this.filter_options.autosubmit=='no' && this.btn_pressed==0){
            avalon23_helper.message(avalon23_helper_vars.lang.done, 'notice', 100);
            return false;
        }else if(this.filter_options.autosubmit=='ajax_redraw' && this.btn_pressed==0){
	    this.start_redraw_page();
	    
	    let get_vars = this.get_all_filter_data();

            fetch(avalon23_helper_vars.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: avalon23_helper.prepare_ajax_form_data({
                    action: 'avalon23_form_filter_redraw',
		    avalon23_action: 'do_ajax', 
		    get_vars: JSON.stringify(Object.assign({}, get_vars)),
                    filter_id: this.filter_options.filter_id,
		    current_tax: JSON.stringify(Object.assign({},this.filter_options.current_tax)),
                    filter_data: JSON.stringify(Object.assign({}, this.filter_data)),
                    _wpnonce: this.filter_options._wpnonce,
                    filter_mode:'yes',
		    show_btn_count: 1

                })
            }).then(response => response.json()).then(data => {
                
                if(this.debag_mode){
                    console.log("---------------------------");
                    console.log("Redraw form: ");
                    console.log(data);
                    console.log("---------------------------");
                }       


                this.data=JSON.parse(data.filter);
                this.draw();
		
		// draw  count for filter button only on ajax redraw
		if (typeof data.product_count != 'undefined') {
		    let filter_btns = document.querySelectorAll('#'+ this.filter_cast_id + ' .avalon23-filter-filter');
		    for(let i=0;i<filter_btns.length;i++){
			filter_btns[i].innerHTML += " (" + data.product_count + ")";
		    }	    
		}		
		
		
		this.end_redraw_page();

		
		//no need to do this until the search is done
		// add data to global
		//this.add_search_data_global();
		//this.check_filter_to_redraw();

		
               // this.do_after_form_redraw();
                avalon23_helper.message(avalon23_helper_vars.lang.done, 'notice', 100);
            }).catch((err) => {
		
                    if(this.debag_mode){
                        avalon23_helper.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
                    }
            });  
            avalon23_helper.message(avalon23_helper_vars.lang.done, 'notice', 100);
             return false;
        }
        
        this.btn_pressed=0;
        
        //ajax mode 
        if(this.filter_options.ajax_mode==1){
            let _this = this;
            _this.start_redraw_page();
            history.pushState({}, "", url);   
            jQuery.ajax({
                url: url,
		data: {  avalon23_action: 'do_ajax',
			 _wpnonce: this.filter_options._wpnonce
		},
                success: function (response) {

                    var parser = new DOMParser();
                    var doc = parser.parseFromString(response, "text/html");
                    
                    //redraw products
                    _this.redraw_ajax_page(doc,response);
                    
                    let data = JSON.parse(doc.querySelector('.avalon23-filter-data[data-filter_id="' + _this.filter_options.filter_id + '"]').innerText);
                    _this.data = data;
                    _this.draw();
                    _this.after_ajax_products_redraw(doc);
		    _this.add_search_data_global();
		    _this.check_filter_to_redraw();
                    avalon23_helper.message(avalon23_helper_vars.lang.done, 'notice', 100);
                    document.dispatchEvent(new CustomEvent('avalon23-filter-ajax-search', {detail: {
                            filter_cast_id: this.filter_cast_id,
                            filter_data: this.filter_data,
                            link:url
                        }}));                       
                }
            });
            
         
        }else{
            this.start_redraw_page();
            //redirect mode                   
            window.location =url;
            //if any events for another parts is nessesary subscribed to filter_cast_id
            document.dispatchEvent(new CustomEvent('avalon23-filter-is-changed', {detail: {
                    filter_cast_id: this.filter_cast_id,
                    filter_data: this.filter_data
                }}));            
            
        }

    }
    add_search_data_global(){
	
	if (typeof avalon23_helper.ajax_redraw_filter_data == 'undefined') {
	    avalon23_helper.ajax_redraw_filter_data = {};
	}
	
	let filter_data = {};
        for (let j in this.filter_data){
          filter_data[this.avalon_prefix+j] = this.filter_data[j];
        }

	if(Object.keys(filter_data).length !== 0){
	    filter_data[this.avalon_prefix+'filter_id'] = this.filter_options.filter_id;
	    avalon23_helper.ajax_redraw_filter_data[this.filter_options.filter_id] = filter_data;
	} else {
	    avalon23_helper.ajax_redraw_filter_data[this.filter_options.filter_id] = false;
	}	
	
    } 
    get_search_data_global_array(){

	if (typeof avalon23_helper.ajax_redraw_filter_data == 'undefined') {
	    avalon23_helper.ajax_redraw_filter_data = {};
	}
	
	let filter_data = {};
        for (let j in avalon23_helper.ajax_redraw_filter_data){
	    
	    if (j != this.filter_options.filter_id) {
		Object.assign(filter_data, avalon23_helper.ajax_redraw_filter_data[j]);
	    }

        } 

	return filter_data;
    }
    get_products_data(input){
	
	if(parseInt(input.getAttribute('livesearch'))<1){
	    return false;
	}
	
	let ul_result = input.parentNode.querySelector('ul.avalon23_text_ajax_result');
	let li_start = ul_result.querySelector('.avalon23_text_ajax_result_load.av23_lds-dual-ring');
	li_start.style.display = 'block';
	if (input.value.length < Number(input.getAttribute('minlength'))) {
	    let old_li = ul_result.querySelectorAll('li.avalon23_text_ajax_result_item');
	    for(let i=0;i<old_li.length;i++){
		old_li[i].remove();
		li_start.style.display = 'none';
	    }
	    li_start.style.display = 'none';
	    return false;
	}	
	if (input.value.length == 0){
	    li_start.style.display = 'none';
	    return false;
	} 
	

	fetch(avalon23_helper_vars.ajax_url, {
	    method: 'POST',
	    credentials: 'same-origin',
	    body: avalon23_helper.prepare_ajax_form_data({
		action: 'avalon23_get_products_data',
		avalon23_action: 'do_ajax', 
		filter_id: this.filter_options.filter_id,
		current_tax: JSON.stringify(Object.assign({},this.filter_options.current_tax)),
		filter_data: JSON.stringify(Object.assign({}, this.filter_data)),
		_wpnonce: this.filter_options._wpnonce,
		filter_mode:'yes'

	    })
	}).then(response => response.json()).then(data => {

	    if(this.debag_mode){
		console.log("---------------------------");
		console.log("get_products_data: ");
		console.log(data);
		console.log("---------------------------");
	    } 
	    li_start.style.display = 'none';
	    let old_li = ul_result.querySelectorAll('li.avalon23_text_ajax_result_item');
	    for(let i=0;i<old_li.length;i++){
		old_li[i].remove();
	    }	    
	    if (data.length) {
		for(let j=0;j<data.length;j++){
		    let li = document.createElement('li');
		    li.className = 'avalon23_text_ajax_result_item';
		    let img = document.createElement('img');
		    let a = document.createElement('a');
		    a.setAttribute('href' ,data[j]['img']);
		    
		    let title = document.createElement('span');
		    title.innerHTML = data[j]['title'];
		    
		    if (data[j]['url']) {
			a.setAttribute('href' ,data[j]['url']);
		    }
		    a.appendChild(title);
		    if (data[j]['img']) {
			img.setAttribute('src' ,data[j]['img']);
			img.className = 'avalon23_text_ajax_img';
			li.appendChild(img);
		    }
		    li.appendChild(a);
		    ul_result.appendChild(li);
		}
	    }
	     
	    
	}).catch((err) => {

		if(this.debag_mode){
		    avalon23_helper.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
		}
	});  

    }    
    redraw_ajax_form() {

	let get_vars = this.get_all_filter_data();

	fetch(avalon23_helper_vars.ajax_url, {
	    method: 'POST',
	    credentials: 'same-origin',
	    body: avalon23_helper.prepare_ajax_form_data({
		action: 'avalon23_form_filter_redraw',
		avalon23_action: 'do_ajax',
		get_vars: JSON.stringify(Object.assign({}, get_vars)),
		filter_id: this.filter_options.filter_id,
		current_tax: JSON.stringify(Object.assign({}, this.filter_options.current_tax)),
		filter_data: JSON.stringify(Object.assign({}, this.filter_data)),
		_wpnonce: this.filter_options._wpnonce,
		filter_mode: 'yes'

	    })
	}).then(response => response.json()).then(data => {

	    if (this.debag_mode) {
		console.log("---------------------------");
		console.log("Additional redraw form: ");
		console.log(data);
		console.log("---------------------------");
	    }

	    this.data = JSON.parse(data.filter);
	    this.draw();
	    document.dispatchEvent(new CustomEvent('avalon23-end-redraw-page', {detail: {
		    filter_cast_id: this.filter_cast_id,
		    filter_data: this.filter_data,
		    filter_option: this.filter_options
		}}));
	}).catch((err) => {
	    if (this.debag_mode) {
		avalon23_helper.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
	    }
	});
    } 
    init_ajax_pagination(){
        let _this=this;
        let paginations=document.querySelectorAll(this.pagination_cont+' a');

        let parser = new DOMParser();
        let url=null;
        for (let i = 0; i < paginations.length; i++) {
            paginations[i].addEventListener('click', function (e) {
                avalon23_helper.message(avalon23_helper_vars.lang.filtering + ' ...', 'warning', -1);
                e.preventDefault();
                e.stopPropagation();
                url=this.getAttribute("href");
                //redirect mode                   
                //window.location =url;
                //return;
                _this.start_redraw_page();
                history.pushState({}, "", url);  
                fetch(url,{
                    method: 'POST',
                    credentials: 'same-origin',
                    body: avalon23_helper.prepare_ajax_form_data({
                        filter_mode:'yes'
                    })
                }).then(response => response.text()).then(data => {
                    //console.log(data);                
                    _this.redraw_ajax_page(parser.parseFromString(data, "text/html"),data);               
                    
                }).catch((err) => {
                    if(this.debag_mode){
                        avalon23_helper.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
                    }
                });  
                   avalon23_helper.message(avalon23_helper_vars.lang.done, 'notice', 100);
            });           
        }
    }
    redraw_ajax_page(doc,response){     
        
                    let products_reponse=doc.querySelectorAll(this.products_cont+","+this.no_products_found);
                    let products=null;                    
                    
                    let new_children =null;
                    let old_children=null;
                    let parser = new DOMParser();
                    let redraw_selectors=[];
                    redraw_selectors.push(this.count_cont);
                    redraw_selectors.push(this.pagination_cont);
                    
                    
                    let element_list=[];
                    let element_current_list=[];                    

                    for (let i = 0; i < products_reponse.length; i++) {
   
                        products=document.querySelector(".avalon23_redraw.avalon23_"+i);

                        if(products==null || products.querySelector(this.ajax_no_redraw)){ //WOOT compatibility
                            continue;
                        }

                        new_children=products_reponse[i].parentNode.children;
                        old_children=products.parentNode.children;
                        
                        //redraw counting and  pagination
                        for (let x = 0; x < redraw_selectors.length; x++){
                            element_list=[];
                            element_current_list=[]; 

                            for (let j = 0; j < old_children.length; j++){
                                if(old_children[j].querySelector(this.products_cont+","+this.no_products_found)==null){ //exclude  product  cont
                                    if(old_children[j].querySelector(redraw_selectors[x])!=null){
                                        //check  if  current page  have  element  for  redraw
                                        element_current_list.push(old_children[j].querySelector(redraw_selectors[x])); 

                                    }else if(old_children[j].matches(redraw_selectors[x])){
                                        element_current_list.push(old_children[j]);
                                    }                                    
                                    
                                }

                            }

                            for (let j = 0; j < new_children.length; j++){
                                if(new_children[j].querySelector(this.products_cont+","+this.no_products_found)==null){//exclude  product  cont
                                    //check  if  response page  have  element  for  redraw
                                     if(new_children[j].querySelector(redraw_selectors[x])!=null ){
                                        element_list.push(new_children[j].querySelector(redraw_selectors[x])); 
                                     }else if(new_children[j].matches(redraw_selectors[x])){
                                         element_list.push(new_children[j]);
                                     }                                    
                                }

                            }

                            if(element_list.length==0){                           
                                for (let j = 0; j < element_current_list.length; j++){
                                    //if response no_products_found
                                    element_current_list[j].style.display = "none";
                                }
                            }else if(element_current_list.length==0 && element_list.length!=0 ){
                                //if old page no_products_found
                                products.parentNode.replaceWith(parser.parseFromString(response, "text/html").querySelectorAll(this.products_cont+","+this.no_products_found)[i].parentNode);
                                this.wrap_all_products();

                            }else if(element_list.length!=0 && element_current_list.length!=0){

                                for (let j = 0; j < element_current_list.length; j++){                                                            
                                    if(typeof element_list[j]!='undefined'){
                                        //redraw  pagination  &  count
                                        element_current_list[j].replaceWith(element_list[j]);
                                    }
                                }         
                            }                            
                                                        
                        }        
                    //Redraw products 
            products.replaceChild(products_reponse[i], products.querySelector(this.products_cont+","+this.no_products_found));

        }
	
	//redraw  additional  elements
	if(typeof this.ajax_redraw != 'undefined'){
	    for (let y = 0; y < this.ajax_redraw.length; y++) {
		let elements_new = doc.querySelectorAll(this.ajax_redraw[y]);
		let elements_old = document.querySelectorAll(this.ajax_redraw[y]);
		for (let z = 0; z < this.ajax_redraw.length; z++) {
		    if( typeof elements_old[z] != 'undefined'){
			elements_old[z].replaceWith(elements_new[z]);
		    }
		}
	    }
	}
	
	 

        this.after_ajax_products_redraw(doc);
        
    }   

    init_ajax_sortings(){
        let _this=this;
        let sortings=document.querySelectorAll(this.sortings_cont);
        let url=null;
        let val=null;
        let additional_get=[];

        for (let i = 0; i < sortings.length; i++) {
            sortings[i].addEventListener('change', function (e) {
                e.preventDefault();
                e.stopPropagation();
                
                val = this.options[this.selectedIndex].value;
                additional_get[this.name]=val;
                               
                url = location.protocol + '//' + location.host +location.pathname +_this.generate_query_url(additional_get);
                //redirect mode                   
                //window.location =url;
                //return;
                _this.start_redraw_page();
                let parser = new DOMParser();
                history.pushState({}, "", url);  
                fetch(url,{
                    method: 'POST',
                    credentials: 'same-origin',
                    body: avalon23_helper.prepare_ajax_form_data({
                        filter_mode:'yes'
                    })
                }).then(response => response.text()).then(data => {
                    //console.log(data);                
                    _this.redraw_ajax_page(parser.parseFromString(data, "text/html"),data);               
                    
                }).catch((err) => {
                    if(this.debag_mode){
                        avalon23_helper.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
                    }
                });  
                   avalon23_helper.message(avalon23_helper_vars.lang.done, 'notice', 100);                

            });

            if(document.querySelectorAll('.product.product-category').length){
                sortings[i].style.display = "none";
            }else{
                sortings[i].style.display = "block";
            }
        }
        
        
    }
    after_ajax_products_redraw(response){
        this.init_ajax_pagination();
        this.init_ajax_sortings();

        if (typeof avalon23_after_page_redraw == 'function') {
            avalon23_after_page_redraw(this,response);
        }
        this.end_redraw_page();
    }
    do_after_form_redraw(){
        
    }
    start_redraw_page(){
        let products=document.querySelectorAll(".avalon23_redraw");
        for (let j = 0; j < products.length; j++){
            products[j].style.filter="blur(3px)";
        }   
        document.dispatchEvent(new CustomEvent('avalon23-start-redraw-page', {detail: {
            filter_cast_id: this.filter_cast_id,
            filter_data: this.filter_data,
	    filter_option: this.filter_options
        }}));   
        
    }
    end_redraw_page(){        
        let products=document.querySelectorAll(".avalon23_redraw");
        for (let j = 0; j < products.length; j++){
            products[j].style.filter="unset";
        }  

        document.dispatchEvent(new CustomEvent('avalon23-end-redraw-page', {detail: {
            filter_cast_id: this.filter_cast_id,
            filter_data: this.filter_data,
	    filter_option: this.filter_options
        }}));          

    }
    check_filter_to_redraw() {
	let products=document.querySelectorAll(".avalon23-filter-data");
	let filter_ids = [];
	for (let j = 0; j < products.length; j++){
            if (products[j].dataset.filter_id && products[j].dataset.filter_id != this.filter_options.filter_id){
		filter_ids.push(products[j].dataset.filter_id);
	    }
        }
	var url_data = this.generate_query_url();

        document.dispatchEvent(new CustomEvent('avalon23-filter-redraw', {detail: {
            source: 'avalon23-' + this.filter_options.filter_id,
	    filter_ids: filter_ids
        }})); 	

	return true;
    }

    reset_pagination_in_link(link) {       
        let l=link.split("/page/")[0];       
        return l;       
    }
    reset_pagination_in_get(result) {  
        if(result['product-page']){
            delete result['product-page'];
        }
    
        return result;       
    }
    
}

