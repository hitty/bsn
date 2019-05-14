jQuery(document).ready(function(){
    jQuery('.expand-button').on('click', function(){
        jQuery(this).hide(0).siblings('.expand').fadeIn(300);
    });
    if(jQuery('.corner-consultant').length==0){
        jQuery('.lz_cbl').addClass('align-right');
    }

    jQuery('.tab-offers').on('click', function(){
        jQuery('.dashed-link-blue[data-tab-ref=".objects"]').click();
    })
    if(jQuery('.slide-photogallery .thumbs-list a').length > 0){
        jQuery('.payed-format .offices-info .media-box .photos-count i').on('click', function(){
            jQuery('.slide-photogallery .thumbs-list a:first-child').click();
        })
    } 
    
    jQuery('.offices-info .infrastructure').on('click', function(){
        jQuery('.row.infrastructure').toggleClass('active');
        jQuery(this).toggleClass('active');
        
    }) 
    
    jQuery('.expand-row').on('click', function(){
        jQuery(this).toggleClass('active')
    })
    
});   





     