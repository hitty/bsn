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
    if( typeof ymaps != "object" ) return false;
    ymaps.ready(function () {
        jQuery('#map-search-results').each(function(){
            var _element = jQuery(this);

            YMSR = new ymaps.Map(_element.attr('id'), {
                center: [59.937538, 30.309452],
                zoom: 11,
                controls: []
            });
            // пользовательский макет ползунка масштаба.
            ZoomLayout = ymaps.templateLayoutFactory.createClass('<div class="custom-controls zoom-control transition"><div class="in" data-icon="add"></div><div class="out" data-icon="remove"></div></div>', {

                // Переопределяем методы макета, чтобы выполнять дополнительные действия
                // при построении и очистке макета.
                build: function () {
                    // Вызываем родительский метод build.
                    ZoomLayout.superclass.build.call(this);

                    // Привязываем функции-обработчики к контексту и сохраняем ссылки
                    // на них, чтобы потом отписаться от событий.
                    this.zoomInCallback = ymaps.util.bind(this.zoomIn, this);
                    this.zoomOutCallback = ymaps.util.bind(this.zoomOut, this);

                    // Начинаем слушать клики на кнопках макета.
                    $('.zoom-control .in').bind('click', this.zoomInCallback);
                    $('.zoom-control .out').bind('click', this.zoomOutCallback);
                },

                clear: function () {
                    // Снимаем обработчики кликов.
                    $('.zoom-control .in').unbind('click', this.zoomInCallback);
                    $('.zoom-control .out').unbind('click', this.zoomOutCallback);

                    // Вызываем родительский метод clear.
                    ZoomLayout.superclass.clear.call(this);
                },

                zoomIn: function () {
                    var map = this.getData().control.getMap();
                    // Генерируем событие, в ответ на которое
                    // элемент управления изменит коэффициент масштабирования карты.
                    this.events.fire('zoomchange', {
                        oldZoom: map.getZoom(),
                        newZoom: map.getZoom() + 1
                    });
                },

                zoomOut: function () {
                    var map = this.getData().control.getMap();
                    this.events.fire('zoomchange', {
                        oldZoom: map.getZoom(),
                        newZoom: map.getZoom() - 1
                    });
                }
            }),
              
            zoomControl = new ymaps.control.ZoomControl({
                options: {
                    layout: ZoomLayout
                }
            });

            YMSR.controls.add(zoomControl, {
                float: 'none', 
                position: { 
                    top: 200, 
                    left: 20
                }
            })
            
            // пользовательский макет полноэкранного режима.
            FullscreenLayout = ymaps.templateLayoutFactory.createClass('<div class="custom-controls fullscreen-control transition"><div data-icon="fullscreen"></div></div>', {

                // Переопределяем методы макета, чтобы выполнять дополнительные действия
                // при построении и очистке макета.
                build: function () {
                    // Вызываем родительский метод build.
                    FullscreenLayout.superclass.build.call(this);   
                    fullscreenControl.events.add('fullscreenenter', function(){  
                        jQuery('.fullscreen-control div').toggleClass('active');
                        jQuery('body').toggleClass('estate-list');
                        YMSR.behaviors.enable('scrollZoom'); 
                    });
                    fullscreenControl.events.add('fullscreenexit', function(){  
                        jQuery('.fullscreen-control div').toggleClass('active');
                        jQuery('body').toggleClass('estate-list');
                        YMSR.behaviors.disable('scrollZoom'); 
                    });
                }
            }),
              
            fullscreenControl = new ymaps.control.FullscreenControl({
                options: {
                    layout: FullscreenLayout
                }
            });            
            YMSR.controls.add(fullscreenControl, {
                float: 'none', 
                position: { 
                    top: 20, 
                    left: 20
                }
            })            
            
            YMSR.behaviors.disable('scrollZoom'); 
            markers = new ymaps.GeoObjectCollection();
        })

        // Создание макета балуна на основе Twitter Bootstrap.
        MyBalloonLayout = ymaps.templateLayoutFactory.createClass(
            '<div class="balloon br3">' +
                '<a class="close" href="#" data-icon="clear"></a>' +
                '<div class="arrow"></div>' +
                '<div class="balloon-inner br3">' +
                '$[[options.contentLayout observeSize minWidth=560 maxWidth=560 maxHeight=200 minHeight=200]]' +
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
            '<div class="balloon-content br3">$[properties.balloonContent]</div>'
        );




        myBalloonContentBodyLayout = ymaps.templateLayoutFactory.createClass(
            '<div>$[properties.body]</div>'
        );        
    })
})

function pendingMapPoints(_url, _params){
    ymaps.ready(function () {
        markers.removeAll();
        markers = new ymaps.GeoObjectCollection(null);
        jQuery.ajax({     
            type: "POST", async: true,
            dataType: 'json', cache: false,
            url: _url, data: _params,
            success: function(msg){
                if( typeof(msg)=='object' && typeof(msg.ok)!='undefined' && msg.ok) {
                    
                    for(i=0; i< msg.points.length; i++){
                        //добавление метки объекта

                        
                        m = new ymaps.Placemark(
                            [msg.points[i]['lat'], msg.points[i]['lng']],
                            {
                               hintContent: msg.points[i]['title'],
                               name : msg.points[i]['id'], // Это поле нам нужно передавать в AJAX запросе к серверу
                               balloonContent: msg.points[i]['html']
                            }, {
                                iconLayout: 'default#imageWithContent',
                                iconImageHref: '/img/layout/bsn-map-tag.svg',
                                iconImageSize: [30, 30],
                                iconImageOffset: [-15, -28],
                                iconShadow: true,
                                iconShadowImageHref: '/img/layout/bsn-map-tag-shadow.png',
                                iconShadowImageSize: [21, 23],
                                iconShadowImageOffset: [-1, -20], 
                                balloonContentBodyLayout : myBalloonContentBodyLayout,
                                balloonLayout: MyBalloonLayout,
                                balloonContentLayout: MyBalloonContentLayout,
                                balloonPanelMaxMapArea: 0                                                                                            
                            }
                        );  
                        markers.add(m);
                        //m.events.add('click', onClick);
                      
                    }
                    YMSR.geoObjects.add(markers);
                    YMSR.setBounds(markers.getBounds());
                }
            }
        })
    })    
}              
