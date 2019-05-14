var _points = [];
var i=0;
function pendingMapPoints(_element, _url, _params){
    if(typeof(_params) == 'undefined' || !_params) _params = {ajax: true};
    var elem = _element;
    if(typeof(_element) == 'string') elem = jQuery(_element);
    _cached = false;
    
    jQuery.ajax({
        type: "POST", async: true,
        dataType: 'json', cache: _cached,
        url: _url, data: _params,
        success: function(msg){
            if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok) {
                ymaps.ready(function(){
                    var _id = elem.selector;
                    _id = _id.substr(1);
                    var _lat = 59.938014; 
                    var _lng = 30.307489;
                    var _zoom = 10;
                    var myMap = new ymaps.Map(
                            _id, {
                            zoom: _zoom,
                            center: [_lat, _lng]
                    });
                    myMap.controls
                        // Кнопка изменения масштаба
                        .add('zoomControl')
                        // Список типов карты
                        .add('typeSelector')      
                        // Кнопка изменения масштаба - компактный вариант
                        // Расположим её справа
                        .add('smallZoomControl', { right: 5, top: 75 })
                        // Стандартный набор кнопок
                        .add('mapTools');                    
                    _points = msg.points;
                    // Создаем кластеризатор
                    if (msg.cluster){
                        cluster = new ymaps.Clusterer();
                        myPlacemarks = []
                    }
                    //  Создаем экземпляр класса коллекции геообъектов.
                    myCollection = new ymaps.GeoObjectCollection();
                    myAdvancedCollection = new ymaps.GeoObjectCollection();
                    //добавление меток
                    for(i=0; i<Object.keys(_points).length; i++){
                        //icon style
                        if(_points[i].icon_url != undefined) iconHref = _points[i].icon_url;
                        else iconHref = msg.icon_url;
                        myPlacemark = new ymaps.Placemark([_points[i].lat, _points[i].lng],{
                                name: _points[i].title,    
                                balloonContentBody: _points[i].html,
                                link: _points[i].link
                            }, {
                                iconImageHref: iconHref,
                                iconImageSize: [44, 46],
                                iconImageOffset: [-7, -38], 
                                iconShadow: true,
                                iconShadowImageHref: '/img/map_icons/icon_shadow.png',
                                iconShadowImageSize: [53, 25],
                                iconShadowImageOffset: [2, -22] 
                            }
                        );
                        myPlacemark.options.set('hintContentLayout', 'my#superlayout');
                        //добавление точек в кластер
                        if (msg.cluster ) {
                            myPlacemarks[i] = myPlacemark;
                        }
                        //добавление точек в коллекцию
                        if(typeof _points[i].advanced == 'undefined' || _points[i].advanced == 2 || !msg.cluster) myCollection.add(myPlacemark); //обычные объекты
                        else myAdvancedCollection.add(myPlacemark);
                        if(typeof _points[i].link != 'undefined'){
                            myPlacemark.events.add('click', function (e) {
                                window.open(e.get('target').properties.get('link'));
                                });                        
                        }
                    }
                    //вывод точек из коллекции (в случае кластеризации Ya.Api пока не позволяет выводить границы точек, поэтому сначала необходимо создать коллекцию, а потом в случае кластеризации удалить её)
                    myMap.geoObjects.add(myAdvancedCollection);
                    myMap.geoObjects.add(myCollection);
                    //установка центра карты в зависимости от маркеров
                    myMap.setBounds(myCollection.getBounds());

                    if (msg.cluster){ //вывод маркеров при кластеризации
                        //при кластеризации необходимо удалить коллекцию точек
                        myCollection.removeAll();
                        cluster.options.set({
                            gridSize: 64
                        });
                        cluster.add(myPlacemarks);
                        // Добавляем кластер на карту.
                        myMap.geoObjects.add(cluster);                        
                    } 
                    //определение стиля для всплывающего title
                    myHintLayout = ymaps.templateLayoutFactory.createClass('<b>$[properties.name]</b>');                        
                    ymaps.layout.storage.add('my#superlayout', myHintLayout);
                });
                
            } else console.log("Ошибка данных");
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
                console.log("Error: "+textStatus+" "+errorThrown);
        },
        complete: function(){
        }
    });
    return true;
}