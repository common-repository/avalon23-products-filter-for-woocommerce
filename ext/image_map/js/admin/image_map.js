function avalon23_dragstart_handler(ev) {

 ev.dataTransfer.setData("text/plain", ev.target.id);
 ev.dataTransfer.effectAllowed = "move";
 document.querySelector(".avalon23-image-map-delete").style.display = "block";
}
function avalon23_dragover_handler(ev) {
 ev.preventDefault();
 ev.dataTransfer.dropEffect = "move";
 
}
function avalon23_drop_handler(ev) {
    ev.preventDefault();
    const data = ev.dataTransfer.getData("text/plain");

    if(data) {
	var cX = ev.layerX;
	var cY = ev.layerY;
	var img = document.getElementById(data);
	var input_left = document.querySelector(".coordinates_left_" + data);
	var input_top = document.querySelector(".coordinates_top_" + data);
	var wraper = document.querySelector(".avalon23-image-map-wrap");
	if (img && wraper) {
	    var img_w = img.clientWidth;
	    var img_h = img.clientHeight;	
	    var wraper_w = wraper.clientWidth;
	    var wraper_h = wraper.clientHeight;
	    //cY = cY - (img_h / 2);
	   // cX = cX - (img_w / 2);
	    var pr_t = cY / wraper_h * 100;
	    var pr_l = cX / wraper_w * 100;
	   
	    if (!ev.target.classList.contains('avalon23-image-draggable')) {
		wraper.appendChild(img);
		img.style.top = pr_t + "%";
		img.style.left = pr_l + "%";
		input_left.value = pr_l;
		input_top.value = pr_t;
		input_left.dispatchEvent(new Event("change"));
		input_top.dispatchEvent(new Event("change"));
	     }
	        
	}
	
    }
    setTimeout(function(){
	document.querySelector(".avalon23-image-map-delete").style.display = "none";
    }, 500);
}
function avalon23_dragover_delete(ev){
    ev.preventDefault();   
    ev.dataTransfer.dropEffect = "move";
}
function avalon23_drop_delete(ev){
    ev.preventDefault();
    const data = ev.dataTransfer.getData("text/plain");
    var img = document.getElementById(data);
    var input_left = document.querySelector(".coordinates_left_" + data);
    var input_top = document.querySelector(".coordinates_top_" + data);
    if(data) {
	var li = document.querySelector('li[data-place="'+ data +'"]');
	if (!ev.target.classList.contains('avalon23-image-map-remove') && li) {
	    img.style.top = "0%";
	    img.style.left = "0%";
	    li.insertBefore(img, li.firstChild);
	    input_left.value = 0;
	    input_top.value = 0;
	    input_left.dispatchEvent(new Event("change"));
	    input_top.dispatchEvent(new Event("change"));	    
	}
    }
    setTimeout(function(){
	document.querySelector(".avalon23-image-map-delete").style.display = "none";
    }, 500);
    
}

function avalon23_image_map_scale(_this, scale){
    let img_wrap = _this.closest('.avalon23-image-map-scale').querySelector('.avalon23-image-map-wrap');
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
}