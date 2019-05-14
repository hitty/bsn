jQuery(document).ready(function(){
    var _tgbIds = [];
    var _tgbIds1 = [];
    index = '';
    function carouselScrollItemsLog(itemslist,_index){
        var _statsArray = [];
        var _pseudoArray = [];
        if(_index==1) _pseudoArray = _tgbIds;
        else _pseudoArray = _tgbIds1;
        itemslist.each(function(index){
            var _this = jQuery(this);
            var _inarray_flag = false;
            for(k=0; k<_pseudoArray.length; k++){
                if(_pseudoArray[k]['type']==_this.attr('data-type') && _pseudoArray[k]['id']==_this.attr('data-id')) { _inarray_flag = true; }
            }
            if(_inarray_flag == false) {
               _pseudoArray.push({type: _this.attr('data-type'), id: _this.attr('data-id')}); 
               _statsArray.push({type: _this.attr('data-type'), id: _this.attr('data-id')}); 
            }
        })
        if(_statsArray.length>0){
            var _params = {ajax:true, offers:_statsArray}
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', cache: true,
                url: '/spec_offers/show/', data: _params,
                success: function(msg){},
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log('XMLHttpRequest: '+XMLHttpRequest+', textStatus: '+textStatus+', errorThrown: '+errorThrown+'; Не возможно выполнить операцию!');
                }
            });
        }
        if(_index==1) _tgbIds = _pseudoArray;
        else _tgbIds1 = _pseudoArray;
    }
    function carouselInit(_index){
        jQuery('#sp-carousel'+_index).carouFredSel({
            width: '100%',
            align       : "left",
            onCreate: function(map){carouselScrollItemsLog(map.items, _index)},
            scroll: {
                items: 2,
                pauseOnHover: true,
                onAfter: function(map){carouselScrollItemsLog(map.items.visible, _index)}
            },
            auto: {
                timeoutDuration: 7000,
                delay: 3000,
                progress: '.sp-slider-progressbar'+_index 
            },
            prev: {
                button: '.sp-slider .carousel-btn-prev'+_index,
                pauseOnHover: true
            },
            next: {
                button: '.sp-slider .carousel-btn-next'+_index,
                pauseOnHover: true
            }
        });
    }
    function carouselRemove(_index){
        jQuery('#sp-carousel'+_index).trigger("destroy");
    }
    jQuery('.sp-carousel').each(function(){
        if(index=='') {
            carouselInit('');
            jQuery('#sp-carousel-remove').click(function(){
                var elem = jQuery(this);
                if(elem.hasClass('fullview')) {
                    carouselInit('');
                    elem.removeClass('fullview');
                } else {
                    carouselRemove('');
                    carouselScrollItemsLog(jQuery('#sp-carousel li'),'');
                    elem.addClass('fullview');
                }
                return false;
            }); 
            index = 1;
        }
        else{
            var _this = jQuery(this);
            _this.attr('id','sp-carousel'+index)
            .siblings('.carousel-btn-prev').attr('class','carousel-btn-prev'+index)
            .siblings('.carousel-btn-next').attr('class','carousel-btn-next'+index)
            .siblings('.sp-slider-progressbar').attr('class','sp-slider-progressbar'+index);
            _this.parents('.sp-slider').siblings('#sp-carousel-remove').attr('id','sp-carousel-remove'+index);
            carouselInit(1);
            jQuery('#sp-carousel-remove1').click(function(){
                var elem = jQuery(this);
                if(elem.hasClass('fullview')) {
                    carouselInit(1);
                    elem.removeClass('fullview');
                } else {
                    carouselRemove(1);
                    carouselScrollItemsLog(jQuery('#sp-carousel li'),1);
                    elem.addClass('fullview');
                }
                return false;
            }); 
            
        }
                
            

    })
    
});