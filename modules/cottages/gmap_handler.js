$(document).ready(function(){
	initialize();
});
	function initialize(){    
	

	var lat = $("input[name=lat]").attr('value');
	var lng = $("input[name=lng]").attr('value');
	if(lat==0 || lat==undefined) lat = 59.93781632;
	if(lng==0 || lng==undefined) lng = 30.31179;
	var latlng = new google.maps.LatLng(lat, lng);
	var mapOptions = {
	  center: latlng,
	  zoom: 10,
	  mapTypeId: google.maps.MapTypeId.ROADMAP,
	  scrollwheel: false
	};
    var map = new google.maps.Map(document.getElementById("map_canvas"),
        mapOptions);

	
    var image = new google.maps.MarkerImage(
                    '/img/map_icons/icon_map_cottage.png',
                    new google.maps.Size(44, 46),
                    new google.maps.Point(0,0),
                    new google.maps.Point(10,42)
                );
	var marker = new google.maps.Marker({	
		position: latlng,
		map: map,
        icon: image
	});
	marker.setDraggable (true);

	google.maps.event.addListener (marker, 'drag', function (event) 
      {
	  var latlng = marker.getPosition();
      var lat = latlng.lat().toFixed(8);
      var lng = latlng.lng().toFixed(8);
	  $("input[name=lat]").attr('value',lat);
	  $("input[name=lng]").attr('value',lng);	  
      });

}