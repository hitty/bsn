jQuery(document).ready(function(){
   ///specialists/51421/answers_list/
   jQuery(document).on('click', '.card .consultant-list .paginator span', function(){
       getPendingContent(".consultant-list", window.location.href.replace(/\#.*$/,'') + "answers_list/?page="  + jQuery(this).data("link"),false,false,false,false);
       return false;
         
   })
})