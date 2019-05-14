var customIcons = {
  'status-1': {
    icon: '/modules/invest/img/status-1.png'
  },
  'status-2': {
    icon: '/modules/invest/img/status-2.png'
  },
  'status-3': {
    icon: '/modules/invest/img/status-3.png'
  },
  'status-all': {
    icon: '/modules/invest/img/all.png'
  }
};

_object_lat = 0;
_object_lng = 0;
_radius = 800;
_init = false;
_markers = [];
markers = [];
var _items = [];
_active_el = '';
 _bounds = [];
jQuery(document).ready(function(){
    ymaps.ready(function () {
        jQuery('#map-wrapper').each(function(){
           var _element = jQuery(this);
           
           var  _text = typeof _element.data('title') != 'undefined' ? _element.data('title') : jQuery('h1').text();
           
           var myMap = new ymaps.Map(_element.attr('id'), {
                center: [59.937538, 30.309452],
                zoom: 11,
                controls: ["zoomControl"]
            });
            myMap.options.set('minZoom', 5);
            markers = new ymaps.GeoObjectCollection();

           function buildPoints(_el){
                markers.removeAll();
                markers = new ymaps.GeoObjectCollection(null);
        
                _status = _el.attr('class').replace(' active','').replace('status-','');
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', cache: false,
                    url: '/invest/list/', data: {status: _status},
                    success: function(msg){
                        if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok) {
                           
                            // Создание макета балуна на основе Twitter Bootstrap.
                            MyBalloonLayout = ymaps.templateLayoutFactory.createClass(
                                '<div class="balloon top">' +
                                    '<a class="close" href="#">&times;</a>' +
                                    '<div class="arrow"></div>' +
                                    '<div class="balloon-inner">' +
                                    '$[[options.contentLayout observeSize minWidth=1400 maxWidth=1400 maxHeight=350]]' +
                                    '</div>' +
                                    '</div>', {
                                    /**
                                     * Строит экземпляр макета на основе шаблона и добавляет его в родительский HTML-элемент.
                                     * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/layout.templateBased.Base.xml#build
                                     * @function
                                     * @name build
                                     */
                                    build: function () {
                                        this.constructor.superclass.build.call(this);
                                       
                                        this._$element = $('.balloon', this.getParentElement());

                                        this.applyElementOffset();

                                        this._$element.find('.close')
                                            .on('click', $.proxy(this.onCloseClick, this));
                                    },

                                    /**
                                     * Удаляет содержимое макета из DOM.
                                     * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/layout.templateBased.Base.xml#clear
                                     * @function
                                     * @name clear
                                     */
                                    clear: function () {
                                        this._$element.find('.close')
                                            .off('click');

                                        this.constructor.superclass.clear.call(this);
                                    },

                                    /**
                                     * Метод будет вызван системой шаблонов АПИ при изменении размеров вложенного макета.
                                     * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/IBalloonLayout.xml#event-userclose
                                     * @function
                                     * @name onSublayoutSizeChange
                                     */
                                    onSublayoutSizeChange: function () {
                                        MyBalloonLayout.superclass.onSublayoutSizeChange.apply(this, arguments);

                                        if(!this._isElement(this._$element)) {
                                            return;
                                        }

                                        this.applyElementOffset();

                                        this.events.fire('shapechange');
                                    },

                                    /**
                                     * Сдвигаем балун, чтобы "хвостик" указывал на точку привязки.
                                     * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/IBalloonLayout.xml#event-userclose
                                     * @function
                                     * @name applyElementOffset
                                     */
                                    applyElementOffset: function () {
                                        this._$element.css({
                                            left: -(this._$element[0].offsetWidth / 2),
                                            top: -(this._$element[0].offsetHeight + this._$element.find('.arrow')[0].offsetHeight)
                                        });
                                    },

                                    /**
                                     * Закрывает балун при клике на крестик, кидая событие "userclose" на макете.
                                     * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/IBalloonLayout.xml#event-userclose
                                     * @function
                                     * @name onCloseClick
                                     */
                                    onCloseClick: function (e) {
                                        e.preventDefault();
                                        myMap.setBounds(_bounds);

                                        this.events.fire('userclose');
                                    },

                                    /**
                                     * Используется для автопозиционирования (balloonAutoPan).
                                     * @see https://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/ILayout.xml#getClientBounds
                                     * @function
                                     * @name getClientBounds
                                     * @returns {Number[][]} Координаты левого верхнего и правого нижнего углов шаблона относительно точки привязки.
                                     */
                                    getShape: function () {
                                        if(!this._isElement(this._$element)) {
                                            return MyBalloonLayout.superclass.getShape.call(this);
                                        }

                                        var position = this._$element.position();

                                        return new ymaps.shape.Rectangle(new ymaps.geometry.pixel.Rectangle([
                                            [position.left, position.top], [
                                                position.left + this._$element[0].offsetWidth,
                                                position.top + this._$element[0].offsetHeight + this._$element.find('.arrow')[0].offsetHeight
                                            ]
                                        ]));
                                    },

                                    /**
                                     * Проверяем наличие элемента (в ИЕ и Опере его еще может не быть).
                                     * @function
                                     * @private
                                     * @name _isElement
                                     * @param {jQuery} [element] Элемент.
                                     * @returns {Boolean} Флаг наличия.
                                     */
                                    _isElement: function (element) {
                                        return element && element[0] && element.find('.arrow')[0];
                                    }
                                }),

                        // Создание вложенного макета содержимого балуна.
                            MyBalloonContentLayout = ymaps.templateLayoutFactory.createClass(
                                '<div class="baloon-content">$[properties.balloonContent]</div>'
                            );




                            var myBalloonContentBodyLayout = ymaps.templateLayoutFactory.createClass(
                                '<div>$[properties.body]</div>'
                            );                    
                            for(i=0; i< msg.markers_list.length; i++){
                                //добавление метки объекта


                                m = new ymaps.Placemark(
                                    [msg.markers_list[i]['lat'], msg.markers_list[i]['lng']],
                                    {
                                       hintContent: msg.markers_list[i]['name'],
                                       name : msg.markers_list[i]['id'], // Это поле нам нужно передавать в AJAX запросе к серверу
                                       body : 'Идет загрузка данных ...' // Текст для индикации процесса загрузки (будет заменен на контент когда данные загрузятся)
                                    }, {
                                        iconLayout: 'default#image',
                                        iconImageHref: customIcons['status-' + msg.markers_list[i]['status']].icon, // картинка иконки
                                        iconImageSize: [40, 51],
                                        iconImageOffset: [0, -32],
                                        balloonContentBodyLayout : myBalloonContentBodyLayout,
                                        balloonShadow: false,
                                        balloonLayout: MyBalloonLayout,
                                        balloonContentLayout: MyBalloonContentLayout,
                                        balloonPanelMaxMapArea: 0                                                                                           
                                    }
                                );  
                                markers.add(m);
                                m.events.add('click', onClick);
                              
                            }
                            myMap.geoObjects.add(markers);
                            myMap.setBounds(markers.getBounds());
                        }
                    }
                })
                    
           }
           
            jQuery('.menu li').on('click', function(){
                var _this = jQuery(this);
                _this.addClass('active').siblings('li').removeClass('active');
                buildPoints(_this)
            })
            jQuery('.menu li:first').click();  
            
            // Обработчик клика по метке.
            function onClick(e) {
                             
                var m = e.get('target'),
                map = m.getMap(), // Ссылка на карту.
                name = m.properties.get('name'); // Получаем данные для запроса из свойств метки.
                _bounds = map.getBounds(); // Область показа карты.
                _coords = m.geometry.getCoordinates();
                myMap.setCenter([_coords[0] - (map.getBounds()[1][0] - map.getBounds()[0][0])/2, _coords[1]]);    
                //myMap.setCenter([map.getBounds()[0][1], _coords[1]]);    
                console.log(map.getBounds()[0][0] +','+map.getBounds()[1][0]);
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', cache: false,
                    url: '/invest/' + name + '/',
                    success: function(msg){
                        var content = $('<div class="baloon-item">' + msg.html + '</div>')
                        // Обновляем поле "body" у properties метки
                        m.properties.set('balloonContent', msg.html);
                        jQuery('.photos a').gallery(
                        {
                            prevEffect    : 'none',
                            nextEffect    : 'none',
                            helpers    : {
                                title    : {
                                    type: 'outside'
                                },
                                thumbs    : {
                                    width    : 0,
                                    height    : 0
                                }
                            }
                        });
                        
                        if(jQuery('.text-wrap').height() + jQuery('.header').height() > 530){
                            jQuery('.slim').slimScroll({
                                height: '450px',
                                railVisible: true,
                                alwaysVisible: true
                            });
                        }
                    }
                });
        }
        })
   })
})