   
   
var rates = [];    
var Calculator = {
  toggleContentNotFound: function(toggle) {
    $('.credit-wrap .output .not-found').toggle(toggle);
    $('.credit-wrap .output .content').toggle(!toggle);
  },

  setCalculatorInfo: function(index) {
    var self = this;

    var initialContribution = self.getContribution();
    var loanAmount = self.currentAmount - initialContribution;
    $('#loan-amount').text(self.formatAmount(loanAmount));

    self.currentIndex = index;
    var rate = self.currentCalculatorRates[self.currentIndex];
    self.toggleContentNotFound(!rate);
    if (!rate) return;
    
    //ставка возрастает, если первоначальный взнос меньше 20% 
    if( rate.market < 3 && ( initialContribution/self.currentAmount) * 100 < 20 ) var rateInit = 9.2;
    else rateInit = rate.rate;
    console.log( rate.rate )

    $('#rate').text(rateInit.toFixed(2) + '%');

    var months = self.durationTextInput.val() * 12;
    var mrate = rateInit / 1200;
    var monthlyPayment = loanAmount * (mrate / (1 - Math.pow(1 + mrate, -(months-1))));
    monthlyPayment = Math.round(monthlyPayment);

    var minSalary = parseInt(monthlyPayment * 100 / 50);

    var totalCost = monthlyPayment * months;
    totalCost = Math.round(totalCost);

    if (!totalCost) {
      self.toggleContentNotFound(true);
      return;
    }

    $('#total-cost').text(self.formatAmount(totalCost));
    $('#monthly-payment').text(self.formatAmount(monthlyPayment));
    $('#min-salary').text(self.formatAmount(minSalary));
    if (rate.url)
      $('#product-name').html('<a href="/retail/mortgage/' + rate.url + '">' + rate.name + '</a>');
    else
      $('#product-name').text(rate.name);
    $('.credit-wrap .currency').text(rate.currency);
  },

  formatAmount: function(amount) {
    return formattedNumber( amount.toString() );
  },

  normalizeInputValue: function(input, slider) {
    var val = input.val().replace(/[ \u00A0]/g, '');
    if (!val || !$.isNumeric(val)) {
      input.val('');
      return {valid: false};
    }
    if (val > slider.data('slider').max) {
      val = slider.data('slider').max;
      input.val(val);
    }
    return {valid: true, value: val};      
  },
  getMatchingRates: function() {
    var self = this;
    var amountNormalization = self.normalizeInputValue(self.amountInput, self.amountSlider);
    var durationNormalization = self.normalizeInputValue(self.durationTextInput, self.durationSlider);
    if (!amountNormalization.valid || !durationNormalization.valid) return [];

    self.currentAmount = amountNormalization.value;
    var currency = $('input[name=currency]:checked').val();
    var years = self.durationTextInput.val();
    var contribution = self.contributionSlider.data('slider').getValue();
    var market = $('.type-objects span.active').data('market');
    var loanAmount = self.currentAmount * (1 - contribution/100);
    
    var object = $('.type-objects span.active').data('object');

 
    
    var matchingRates = _.filter(self.allRates, function(rate) {
      return rate.currency == currency && years >= rate.minYears && years <= rate.maxYears && contribution >= rate.minContribution
          && loanAmount >= rate.minAmount && loanAmount <= rate.maxAmount
           && rate.market == market && rate.object == object
          ;
    });
    matchingRates = _.sortBy(matchingRates, function(rate) {
      return rate.rate;
    });
    var usedNames = [];
    return _.filter(matchingRates, function(rate) {
      var result = !_.contains(usedNames, rate.name);
      if (result) usedNames.push(rate.name);
      return result;
    });
  },

  Update: function() {
    this.currentCalculatorRates = this.getMatchingRates();
    this.setCalculatorInfo(0);
  },

  getAmount: function() {
    return parseInt(this.amountInput.val().replace(/[ \u00A0]/g, ''));
  },

  getContribution: function() {
    return parseInt(this.contributionInput.val().replace(/[ \u00A0]/g, ''));
  },

  initAmountSlider: function(data) {
    var self = this;
    var slider = $('#amount-slider-wrapper > .slider').slider({min: data.min, max: data.max, step: data.step, tooltip: 'hide'});
    slider.on('slide', function() {
      var amount = $(this).data('slider').getValue();
      self.amountInput.val(self.formatAmount(amount));
      self.contributionSlider.trigger('slide');
    });
    slider.on('slideStop', function() {self.amountInput.change()});
    slider.slider('setValue', self.getAmount());
    return slider;
  },

  initAmountInput: function() {
    var self = this;
    var amountInput = $('input[name=amount]');
    amountInput.keypress(function(e) {
      var keycode, keyChar; if(!e) var e = window.event; if (e.keyCode) keycode = e.keyCode;    else if(e.which) keycode = e.which;
      if((keycode != 8 && keycode != 9 && keycode != 13 && keycode != 27 && keycode < 35) || (keycode+e.charCode != 46 && keycode > 40 && keycode < 48) || keycode > 57) return false;
    });
    amountInput.on('keyup', function() {
      self.amountSlider.slider('setValue', $(this).val());
    });
    amountInput.on('focus', function() {$(this).val('')});
    amountInput.on('blur', function() {$(this).val(self.formatAmount($(this).val()))});
    amountInput.trigger('blur');
    return amountInput;
  },

  initContributionSlider: function() {
    var self = this;
    var slider = $('#contribution-slider').slider({min: 10, max: 99, tooltip: 'hide'});
    slider.on('slide', function() {
      self.contributionInput.val(self.formatAmount(self.getAmount() * $(this).data('slider').getValue() / 100));
    });
    slider.on('slideStop', function() {self.contributionInput.change()});
    return slider;
  },

  setContributionLimits: function(min, max) {
    var slider = this.contributionSlider.data('slider');
    slider.min = min;
    slider.max = max;
    slider.setValue(slider.getValue());
    $('#contribution-min').text(min + '%');
    $('#contribution-max').text(max + '%');
  },

  initDurationSlider: function() {
    var self = this;
    var slider = $('#amount-slider-duration').slider({min: 1, max: 30, tooltip: 'hide'});
    var handleDuration = function() {
      self.durationTextInput.val($(this).data('slider').getValue());
    };
    slider.on('slide', handleDuration);
    slider.on('slideStop', function() {self.durationTextInput.change()});
    slider.slider('setValue', self.durationTextInput.val());
    $('#duration-min').text(slider.data('slider').min + ' год');
    $('#duration-max').text(slider.data('slider').max + ' лет');
    return slider;
  },

  initTextInput: function(selector) {
    var self = this;
    var input = $(selector);
    input.on('focus', function() {$(this).val('')});
    return input;
  },

  getSelectedCurrency: function() {
    return $('input[name=currency]:checked');
  },

  setAmountLegend: function(data) {
    var slider = this.amountSlider.data('slider');
    if (data.step) slider.step = data.step;
    slider.max = Math.round(data.max / slider.step) * slider.step;
    slider.min = Math.round(data.min / slider.step) * slider.step;
    slider.setValue(slider.getValue());
    $('#amount-min').text(this.formatAmount(slider.min));
    $('#amount-max').text(this.formatAmount(slider.max));
  },

  durationTextInput: null,
  contributionInput: null,
  amountInput: null,
  amountSlider: null,
  durationSlider: null,
  currentCalculatorRates: null,
  currentIndex: 0,
  currentAmount: 0,

  formatNumberData: function(value) {
    return value.toString().replace(/[ \u00A0]/g, '');
  },

  allRates: [],
  getData: function() {
         
  },
  init: function() {
    
    self = this;   
    getPending( '/credit_calculator/', false, false, 
        function( data ){
            self.amountInput = self.initAmountInput();
            const maxAmount = self.amountInput.attr('value') * ( self.amountInput.attr('value') < 10000000 ? 3 : ( self.amountInput.attr('value') < 20000000 ? 1.8 : 1.2 ) ); 
            var rubData = $('input[value=Р]').data();
            self.amountSlider = self.initAmountSlider(rubData);
            self.allRates = [
              {"market":1, "object":"apartment", "currency":"Р", "minAmount":  500000, "maxAmount": maxAmount, "minContribution":parseInt( data.list[2].first_payment ), "minYears": 1, "maxYears":30, "rate": parseFloat( data.list[2].percent ) },
              {"market":2, "object":"apartment", "currency":"Р", "minAmount":  500000, "maxAmount": maxAmount, "minContribution":parseInt( data.list[1].first_payment ), "minYears": 1, "maxYears":30, "rate": parseFloat( data.list[1].percent ) },

              {"market":2, "object":"house",     "currency":"Р", "minAmount":  500000, "maxAmount": maxAmount, "minContribution":parseInt( data.list[4].first_payment ), "minYears": 1, "maxYears":30, "rate": parseFloat( data.list[4].percent )},
              {"market":2, "object":"land",      "currency":"Р", "minAmount":  500000, "maxAmount": maxAmount, "minContribution":parseInt( data.list[4].first_payment ), "minYears": 1, "maxYears":30, "rate": parseFloat( data.list[4].percent )},
            ]

            self.setAmountLegend(rubData);
            $('input[name=currency]').change(function() {
              self.setAmountLegend($(this).data());
              self.amountSlider.trigger('slide');
            });

            self.contributionInput = self.initTextInput('input[name=initialContribution]').keyup(function() {
              self.contributionSlider.slider('setValue', $(this).val() / self.getAmount() * 100);
            });

            self.contributionInput.keypress(function(e) {
              var keycode, keyChar; if(!e) var e = window.event; if (e.keyCode) keycode = e.keyCode;    else if(e.which) keycode = e.which;
              if((keycode != 8 && keycode != 9 && keycode != 13 && keycode != 27 && keycode < 35) || (keycode+e.charCode != 46 && keycode > 40 && keycode < 48) || keycode > 57) return false;
            });

            self.contributionInput.blur(function() {
              $(this).val(self.formatAmount($(this).val()));
            });
            self.contributionSlider = self.initContributionSlider().trigger('slide');

            self.durationTextInput = self.initTextInput('input[name=duration]').keyup(function() {
              self.durationSlider.slider('setValue', $(this).val());
            });

            self.durationTextInput.keypress(function(e) {
              var keycode, keyChar; if(!e) var e = window.event; if (e.keyCode) keycode = e.keyCode;    else if(e.which) keycode = e.which;
              if((keycode != 8 && keycode != 9 && keycode != 13 && keycode != 27 && keycode < 35) || (keycode+e.charCode != 46 && keycode > 40 && keycode < 48) || keycode > 57) return false;
            });

            self.durationSlider = self.initDurationSlider();
            self.Update();

            $('.credit-box_item').attr('data-id', data.id );
            $('.credit-box_item').attr('data-type', data.type );
            $('.type-objects span').on( 'click', function(){
                var market = $(this).data('market')
                var object = $(this).data('object') 
                var currency = $('input[name=currency]:checked').val();   

                var rates = _.filter(self.allRates, function(rate) {
                  return rate.currency == currency && rate.market == market && rate.object == object;
                });
                var maxAmount = _.max(_.pluck(rates, 'maxAmount'));
                var minAmount = _.min(_.pluck(rates, 'minAmount'));
                var minContribution = _.min(_.pluck(rates, 'minContribution'));
                self.setContributionLimits(minContribution, 99);  
                
                var amountFactor = $(this).data('max-loan');
                self.setAmountLegend({max: maxAmount / amountFactor * 100, min: minAmount / amountFactor * 100});
                jQuery('.contribution-slider .slider-selection').css({'left':'0%', 'width':'0%'});
                jQuery('.contribution-slider .slider-handle.round').css({'left':'0%'});
                self.contributionInput.val( ( self.currentAmount*minContribution ) / 100 );
                Calculator.Update();
            });   
            
            $('.credit-wrap .input').find('input').on('keyup change blur', function() {self.Update()});
            $('.type-objects span.active').click();        
        }   
      ) 
  }
};

$(function() {if(jQuery('#amount-slider-wrapper').length > 0 ){Calculator.init();}});             
  
  
  
!function(t){var i=function(i,e){this.element=t(i),this.picker=t('<div class="slider"><div class="slider-track"><div class="slider-selection"></div><div class="slider-handle"></div><div class="slider-handle"></div></div><div class="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div></div>').insertBefore(this.element).append(this.element),this.id=this.element.data("slider-id")||e.id,this.id&&(this.picker[0].id=this.id),"undefined"!=typeof Modernizr&&Modernizr.touch&&(this.touchCapable=!0);var s=this.element.data("slider-tooltip")||e.tooltip;switch(this.tooltip=this.picker.find(".tooltip"),this.tooltipInner=this.tooltip.find("div.tooltip-inner"),this.orientation=this.element.data("slider-orientation")||e.orientation,this.orientation){case"vertical":this.picker.addClass("slider-vertical"),this.stylePos="top",this.mousePos="pageY",this.sizePos="offsetHeight",this.tooltip.addClass("right")[0].style.left="100%";break;default:this.picker.addClass("slider-horizontal").css("width",this.element.outerWidth()),this.orientation="horizontal",this.stylePos="left",this.mousePos="pageX",this.sizePos="offsetWidth",this.tooltip.addClass("top")[0].style.top=-this.tooltip.outerHeight()-14+"px"}switch(this.min=this.element.data("slider-min")||e.min,this.max=this.element.data("slider-max")||e.max,this.step=this.element.data("slider-step")||e.step,this.value=this.element.data("slider-value")||e.value,this.value[1]&&(this.range=!0),this.selection=this.element.data("slider-selection")||e.selection,this.selectionEl=this.picker.find(".slider-selection"),"none"===this.selection&&this.selectionEl.addClass("hide"),this.selectionElStyle=this.selectionEl[0].style,this.handle1=this.picker.find(".slider-handle:first"),this.handle1Stype=this.handle1[0].style,this.handle2=this.picker.find(".slider-handle:last"),this.handle2Stype=this.handle2[0].style,this.element.data("slider-handle")||e.handle){case"round":this.handle1.addClass("round"),this.handle2.addClass("round");break;case"triangle":this.handle1.addClass("triangle"),this.handle2.addClass("triangle")}this.range?(this.value[0]=Math.max(this.min,Math.min(this.max,this.value[0])),this.value[1]=Math.max(this.min,Math.min(this.max,this.value[1]))):(this.value=[Math.max(this.min,Math.min(this.max,this.value))],this.handle2.addClass("hide"),"after"==this.selection?this.value[1]=this.max:this.value[1]=this.min),this.diff=this.max-this.min,this.percentage=[100*(this.value[0]-this.min)/this.diff,100*(this.value[1]-this.min)/this.diff,100*this.step/this.diff],this.offset=this.picker.offset(),this.size=this.picker[0][this.sizePos],this.formater=e.formater,this.layout(),this.touchCapable?this.picker.on({touchstart:t.proxy(this.mousedown,this)}):this.picker.on({mousedown:t.proxy(this.mousedown,this)}),"show"===s?this.picker.on({mouseenter:t.proxy(this.showTooltip,this),mouseleave:t.proxy(this.hideTooltip,this)}):this.tooltip.addClass("hide")};i.prototype={constructor:i,over:!1,inDrag:!1,showTooltip:function(){this.tooltip.addClass("in"),this.over=!0},hideTooltip:function(){!1===this.inDrag&&this.tooltip.removeClass("in"),this.over=!1},layout:function(){this.handle1Stype[this.stylePos]=this.percentage[0]+"%",this.handle2Stype[this.stylePos]=this.percentage[1]+"%","vertical"==this.orientation?(this.selectionElStyle.top=Math.min(this.percentage[0],this.percentage[1])+"%",this.selectionElStyle.height=Math.abs(this.percentage[0]-this.percentage[1])+"%"):(this.selectionElStyle.left=Math.min(this.percentage[0],this.percentage[1])+"%",this.selectionElStyle.width=Math.abs(this.percentage[0]-this.percentage[1])+"%"),this.range?(this.tooltipInner.text(this.formater(this.value[0])+" : "+this.formater(this.value[1])),this.tooltip[0].style[this.stylePos]=this.size*(this.percentage[0]+(this.percentage[1]-this.percentage[0])/2)/100-("vertical"===this.orientation?this.tooltip.outerHeight()/2:this.tooltip.outerWidth()/2)+"px"):(this.tooltipInner.text(this.formater(this.value[0])),this.tooltip[0].style[this.stylePos]=this.size*this.percentage[0]/100-("vertical"===this.orientation?this.tooltip.outerHeight()/2:this.tooltip.outerWidth()/2)+"px")},mousedown:function(i){this.touchCapable&&"touchstart"===i.type&&(i=i.originalEvent),this.offset=this.picker.offset(),this.size=this.picker[0][this.sizePos];var e=this.getPercentage(i);if(this.range){var s=Math.abs(this.percentage[0]-e),h=Math.abs(this.percentage[1]-e);this.dragged=s<h?0:1}else this.dragged=0;this.percentage[this.dragged]=e,this.layout(),this.touchCapable?t(document).on({touchmove:t.proxy(this.mousemove,this),touchend:t.proxy(this.mouseup,this)}):t(document).on({mousemove:t.proxy(this.mousemove,this),mouseup:t.proxy(this.mouseup,this)}),this.inDrag=!0;var a=this.calculateValue();return this.element.trigger({type:"slideStart",value:a}).trigger({type:"slide",value:a}),!1},mousemove:function(t){this.touchCapable&&"touchmove"===t.type&&(t=t.originalEvent);var i=this.getPercentage(t);this.range&&(0===this.dragged&&this.percentage[1]<i?(this.percentage[0]=this.percentage[1],this.dragged=1):1===this.dragged&&this.percentage[0]>i&&(this.percentage[1]=this.percentage[0],this.dragged=0)),this.percentage[this.dragged]=i,this.layout();var e=this.calculateValue();return this.element.trigger({type:"slide",value:e}).data("value",e).prop("value",e),!1},mouseup:function(i){this.touchCapable?t(document).off({touchmove:this.mousemove,touchend:this.mouseup}):t(document).off({mousemove:this.mousemove,mouseup:this.mouseup}),this.inDrag=!1,0==this.over&&this.hideTooltip(),this.element;var e=this.calculateValue();return this.element.trigger({type:"slideStop",value:e}).data("value",e).prop("value",e),!1},calculateValue:function(){var t;return this.range?(t=[this.min+Math.round(this.diff*this.percentage[0]/100/this.step)*this.step,this.min+Math.round(this.diff*this.percentage[1]/100/this.step)*this.step],this.value=t):(t=this.min+Math.round(this.diff*this.percentage[0]/100/this.step)*this.step,this.value=[t,this.value[1]]),t},getPercentage:function(t){this.touchCapable&&(t=t.touches[0]);var i=100*(t[this.mousePos]-this.offset[this.stylePos])/this.size;return i=Math.round(i/this.percentage[2])*this.percentage[2],Math.max(0,Math.min(100,i))},getValue:function(){return this.range?this.value:this.value[0]},setValue:function(t){this.value=t,this.range?(this.value[0]=Math.max(this.min,Math.min(this.max,this.value[0])),this.value[1]=Math.max(this.min,Math.min(this.max,this.value[1]))):(this.value=[Math.max(this.min,Math.min(this.max,this.value))],this.handle2.addClass("hide"),"after"==this.selection?this.value[1]=this.max:this.value[1]=this.min),this.diff=this.max-this.min,this.percentage=[100*(this.value[0]-this.min)/this.diff,100*(this.value[1]-this.min)/this.diff,100*this.step/this.diff],this.layout()}},t.fn.slider=function(e,s){return this.each(function(){var h=t(this),a=h.data("slider"),o="object"==typeof e&&e;a||h.data("slider",a=new i(this,t.extend({},t.fn.slider.defaults,o))),"string"==typeof e&&a[e](s)})},t.fn.slider.defaults={min:0,max:10,step:1,orientation:"horizontal",value:5,selection:"before",tooltip:"show",handle:"round",formater:function(t){return t}},t.fn.slider.Constructor=i}(window.jQuery);
!function(){var n=this,t=n._,r={},e=Array.prototype,u=Object.prototype,i=Function.prototype,a=e.push,o=e.slice,c=e.concat,l=u.toString,f=u.hasOwnProperty,s=e.forEach,p=e.map,v=e.reduce,h=e.reduceRight,d=e.filter,g=e.every,m=e.some,y=e.indexOf,b=e.lastIndexOf,x=Array.isArray,_=Object.keys,w=i.bind,j=function(n){return n instanceof j?n:this instanceof j?(this._wrapped=n,void 0):new j(n)};"undefined"!=typeof exports?("undefined"!=typeof module&&module.exports&&(exports=module.exports=j),exports._=j):n._=j,j.VERSION="1.5.1";var A=j.each=j.forEach=function(n,t,e){if(null!=n)if(s&&n.forEach===s)n.forEach(t,e);else if(n.length===+n.length){for(var u=0,i=n.length;i>u;u++)if(t.call(e,n[u],u,n)===r)return}else for(var a in n)if(j.has(n,a)&&t.call(e,n[a],a,n)===r)return};j.map=j.collect=function(n,t,r){var e=[];return null==n?e:p&&n.map===p?n.map(t,r):(A(n,function(n,u,i){e.push(t.call(r,n,u,i))}),e)};var E="Reduce of empty array with no initial value";j.reduce=j.foldl=j.inject=function(n,t,r,e){var u=arguments.length>2;if(null==n&&(n=[]),v&&n.reduce===v)return e&&(t=j.bind(t,e)),u?n.reduce(t,r):n.reduce(t);if(A(n,function(n,i,a){u?r=t.call(e,r,n,i,a):(r=n,u=!0)}),!u)throw new TypeError(E);return r},j.reduceRight=j.foldr=function(n,t,r,e){var u=arguments.length>2;if(null==n&&(n=[]),h&&n.reduceRight===h)return e&&(t=j.bind(t,e)),u?n.reduceRight(t,r):n.reduceRight(t);var i=n.length;if(i!==+i){var a=j.keys(n);i=a.length}if(A(n,function(o,c,l){c=a?a[--i]:--i,u?r=t.call(e,r,n[c],c,l):(r=n[c],u=!0)}),!u)throw new TypeError(E);return r},j.find=j.detect=function(n,t,r){var e;return O(n,function(n,u,i){return t.call(r,n,u,i)?(e=n,!0):void 0}),e},j.filter=j.select=function(n,t,r){var e=[];return null==n?e:d&&n.filter===d?n.filter(t,r):(A(n,function(n,u,i){t.call(r,n,u,i)&&e.push(n)}),e)},j.reject=function(n,t,r){return j.filter(n,function(n,e,u){return!t.call(r,n,e,u)},r)},j.every=j.all=function(n,t,e){t||(t=j.identity);var u=!0;return null==n?u:g&&n.every===g?n.every(t,e):(A(n,function(n,i,a){return(u=u&&t.call(e,n,i,a))?void 0:r}),!!u)};var O=j.some=j.any=function(n,t,e){t||(t=j.identity);var u=!1;return null==n?u:m&&n.some===m?n.some(t,e):(A(n,function(n,i,a){return u||(u=t.call(e,n,i,a))?r:void 0}),!!u)};j.contains=j.include=function(n,t){return null==n?!1:y&&n.indexOf===y?n.indexOf(t)!=-1:O(n,function(n){return n===t})},j.invoke=function(n,t){var r=o.call(arguments,2),e=j.isFunction(t);return j.map(n,function(n){return(e?t:n[t]).apply(n,r)})},j.pluck=function(n,t){return j.map(n,function(n){return n[t]})},j.where=function(n,t,r){return j.isEmpty(t)?r?void 0:[]:j[r?"find":"filter"](n,function(n){for(var r in t)if(t[r]!==n[r])return!1;return!0})},j.findWhere=function(n,t){return j.where(n,t,!0)},j.max=function(n,t,r){if(!t&&j.isArray(n)&&n[0]===+n[0]&&n.length<65535)return Math.max.apply(Math,n);if(!t&&j.isEmpty(n))return-1/0;var e={computed:-1/0,value:-1/0};return A(n,function(n,u,i){var a=t?t.call(r,n,u,i):n;a>e.computed&&(e={value:n,computed:a})}),e.value},j.min=function(n,t,r){if(!t&&j.isArray(n)&&n[0]===+n[0]&&n.length<65535)return Math.min.apply(Math,n);if(!t&&j.isEmpty(n))return 1/0;var e={computed:1/0,value:1/0};return A(n,function(n,u,i){var a=t?t.call(r,n,u,i):n;a<e.computed&&(e={value:n,computed:a})}),e.value},j.shuffle=function(n){var t,r=0,e=[];return A(n,function(n){t=j.random(r++),e[r-1]=e[t],e[t]=n}),e};var F=function(n){return j.isFunction(n)?n:function(t){return t[n]}};j.sortBy=function(n,t,r){var e=F(t);return j.pluck(j.map(n,function(n,t,u){return{value:n,index:t,criteria:e.call(r,n,t,u)}}).sort(function(n,t){var r=n.criteria,e=t.criteria;if(r!==e){if(r>e||r===void 0)return 1;if(e>r||e===void 0)return-1}return n.index<t.index?-1:1}),"value")};var k=function(n,t,r,e){var u={},i=F(null==t?j.identity:t);return A(n,function(t,a){var o=i.call(r,t,a,n);e(u,o,t)}),u};j.groupBy=function(n,t,r){return k(n,t,r,function(n,t,r){(j.has(n,t)?n[t]:n[t]=[]).push(r)})},j.countBy=function(n,t,r){return k(n,t,r,function(n,t){j.has(n,t)||(n[t]=0),n[t]++})},j.sortedIndex=function(n,t,r,e){r=null==r?j.identity:F(r);for(var u=r.call(e,t),i=0,a=n.length;a>i;){var o=i+a>>>1;r.call(e,n[o])<u?i=o+1:a=o}return i},j.toArray=function(n){return n?j.isArray(n)?o.call(n):n.length===+n.length?j.map(n,j.identity):j.values(n):[]},j.size=function(n){return null==n?0:n.length===+n.length?n.length:j.keys(n).length},j.first=j.head=j.take=function(n,t,r){return null==n?void 0:null==t||r?n[0]:o.call(n,0,t)},j.initial=function(n,t,r){return o.call(n,0,n.length-(null==t||r?1:t))},j.last=function(n,t,r){return null==n?void 0:null==t||r?n[n.length-1]:o.call(n,Math.max(n.length-t,0))},j.rest=j.tail=j.drop=function(n,t,r){return o.call(n,null==t||r?1:t)},j.compact=function(n){return j.filter(n,j.identity)};var R=function(n,t,r){return t&&j.every(n,j.isArray)?c.apply(r,n):(A(n,function(n){j.isArray(n)||j.isArguments(n)?t?a.apply(r,n):R(n,t,r):r.push(n)}),r)};j.flatten=function(n,t){return R(n,t,[])},j.without=function(n){return j.difference(n,o.call(arguments,1))},j.uniq=j.unique=function(n,t,r,e){j.isFunction(t)&&(e=r,r=t,t=!1);var u=r?j.map(n,r,e):n,i=[],a=[];return A(u,function(r,e){(t?e&&a[a.length-1]===r:j.contains(a,r))||(a.push(r),i.push(n[e]))}),i},j.union=function(){return j.uniq(j.flatten(arguments,!0))},j.intersection=function(n){var t=o.call(arguments,1);return j.filter(j.uniq(n),function(n){return j.every(t,function(t){return j.indexOf(t,n)>=0})})},j.difference=function(n){var t=c.apply(e,o.call(arguments,1));return j.filter(n,function(n){return!j.contains(t,n)})},j.zip=function(){for(var n=j.max(j.pluck(arguments,"length").concat(0)),t=new Array(n),r=0;n>r;r++)t[r]=j.pluck(arguments,""+r);return t},j.object=function(n,t){if(null==n)return{};for(var r={},e=0,u=n.length;u>e;e++)t?r[n[e]]=t[e]:r[n[e][0]]=n[e][1];return r},j.indexOf=function(n,t,r){if(null==n)return-1;var e=0,u=n.length;if(r){if("number"!=typeof r)return e=j.sortedIndex(n,t),n[e]===t?e:-1;e=0>r?Math.max(0,u+r):r}if(y&&n.indexOf===y)return n.indexOf(t,r);for(;u>e;e++)if(n[e]===t)return e;return-1},j.lastIndexOf=function(n,t,r){if(null==n)return-1;var e=null!=r;if(b&&n.lastIndexOf===b)return e?n.lastIndexOf(t,r):n.lastIndexOf(t);for(var u=e?r:n.length;u--;)if(n[u]===t)return u;return-1},j.range=function(n,t,r){arguments.length<=1&&(t=n||0,n=0),r=arguments[2]||1;for(var e=Math.max(Math.ceil((t-n)/r),0),u=0,i=new Array(e);e>u;)i[u++]=n,n+=r;return i};var M=function(){};j.bind=function(n,t){var r,e;if(w&&n.bind===w)return w.apply(n,o.call(arguments,1));if(!j.isFunction(n))throw new TypeError;return r=o.call(arguments,2),e=function(){if(!(this instanceof e))return n.apply(t,r.concat(o.call(arguments)));M.prototype=n.prototype;var u=new M;M.prototype=null;var i=n.apply(u,r.concat(o.call(arguments)));return Object(i)===i?i:u}},j.partial=function(n){var t=o.call(arguments,1);return function(){return n.apply(this,t.concat(o.call(arguments)))}},j.bindAll=function(n){var t=o.call(arguments,1);if(0===t.length)throw new Error("bindAll must be passed function names");return A(t,function(t){n[t]=j.bind(n[t],n)}),n},j.memoize=function(n,t){var r={};return t||(t=j.identity),function(){var e=t.apply(this,arguments);return j.has(r,e)?r[e]:r[e]=n.apply(this,arguments)}},j.delay=function(n,t){var r=o.call(arguments,2);return setTimeout(function(){return n.apply(null,r)},t)},j.defer=function(n){return j.delay.apply(j,[n,1].concat(o.call(arguments,1)))},j.throttle=function(n,t,r){var e,u,i,a=null,o=0;r||(r={});var c=function(){o=r.leading===!1?0:new Date,a=null,i=n.apply(e,u)};return function(){var l=new Date;o||r.leading!==!1||(o=l);var f=t-(l-o);return e=this,u=arguments,0>=f?(clearTimeout(a),a=null,o=l,i=n.apply(e,u)):a||r.trailing===!1||(a=setTimeout(c,f)),i}},j.debounce=function(n,t,r){var e,u=null;return function(){var i=this,a=arguments,o=function(){u=null,r||(e=n.apply(i,a))},c=r&&!u;return clearTimeout(u),u=setTimeout(o,t),c&&(e=n.apply(i,a)),e}},j.once=function(n){var t,r=!1;return function(){return r?t:(r=!0,t=n.apply(this,arguments),n=null,t)}},j.wrap=function(n,t){return function(){var r=[n];return a.apply(r,arguments),t.apply(this,r)}},j.compose=function(){var n=arguments;return function(){for(var t=arguments,r=n.length-1;r>=0;r--)t=[n[r].apply(this,t)];return t[0]}},j.after=function(n,t){return function(){return--n<1?t.apply(this,arguments):void 0}},j.keys=_||function(n){if(n!==Object(n))throw new TypeError("Invalid object");var t=[];for(var r in n)j.has(n,r)&&t.push(r);return t},j.values=function(n){var t=[];for(var r in n)j.has(n,r)&&t.push(n[r]);return t},j.pairs=function(n){var t=[];for(var r in n)j.has(n,r)&&t.push([r,n[r]]);return t},j.invert=function(n){var t={};for(var r in n)j.has(n,r)&&(t[n[r]]=r);return t},j.functions=j.methods=function(n){var t=[];for(var r in n)j.isFunction(n[r])&&t.push(r);return t.sort()},j.extend=function(n){return A(o.call(arguments,1),function(t){if(t)for(var r in t)n[r]=t[r]}),n},j.pick=function(n){var t={},r=c.apply(e,o.call(arguments,1));return A(r,function(r){r in n&&(t[r]=n[r])}),t},j.omit=function(n){var t={},r=c.apply(e,o.call(arguments,1));for(var u in n)j.contains(r,u)||(t[u]=n[u]);return t},j.defaults=function(n){return A(o.call(arguments,1),function(t){if(t)for(var r in t)n[r]===void 0&&(n[r]=t[r])}),n},j.clone=function(n){return j.isObject(n)?j.isArray(n)?n.slice():j.extend({},n):n},j.tap=function(n,t){return t(n),n};var S=function(n,t,r,e){if(n===t)return 0!==n||1/n==1/t;if(null==n||null==t)return n===t;n instanceof j&&(n=n._wrapped),t instanceof j&&(t=t._wrapped);var u=l.call(n);if(u!=l.call(t))return!1;switch(u){case"[object String]":return n==String(t);case"[object Number]":return n!=+n?t!=+t:0==n?1/n==1/t:n==+t;case"[object Date]":case"[object Boolean]":return+n==+t;case"[object RegExp]":return n.source==t.source&&n.global==t.global&&n.multiline==t.multiline&&n.ignoreCase==t.ignoreCase}if("object"!=typeof n||"object"!=typeof t)return!1;for(var i=r.length;i--;)if(r[i]==n)return e[i]==t;var a=n.constructor,o=t.constructor;if(a!==o&&!(j.isFunction(a)&&a instanceof a&&j.isFunction(o)&&o instanceof o))return!1;r.push(n),e.push(t);var c=0,f=!0;if("[object Array]"==u){if(c=n.length,f=c==t.length)for(;c--&&(f=S(n[c],t[c],r,e)););}else{for(var s in n)if(j.has(n,s)&&(c++,!(f=j.has(t,s)&&S(n[s],t[s],r,e))))break;if(f){for(s in t)if(j.has(t,s)&&!c--)break;f=!c}}return r.pop(),e.pop(),f};j.isEqual=function(n,t){return S(n,t,[],[])},j.isEmpty=function(n){if(null==n)return!0;if(j.isArray(n)||j.isString(n))return 0===n.length;for(var t in n)if(j.has(n,t))return!1;return!0},j.isElement=function(n){return!(!n||1!==n.nodeType)},j.isArray=x||function(n){return"[object Array]"==l.call(n)},j.isObject=function(n){return n===Object(n)},A(["Arguments","Function","String","Number","Date","RegExp"],function(n){j["is"+n]=function(t){return l.call(t)=="[object "+n+"]"}}),j.isArguments(arguments)||(j.isArguments=function(n){return!(!n||!j.has(n,"callee"))}),"function"!=typeof/./&&(j.isFunction=function(n){return"function"==typeof n}),j.isFinite=function(n){return isFinite(n)&&!isNaN(parseFloat(n))},j.isNaN=function(n){return j.isNumber(n)&&n!=+n},j.isBoolean=function(n){return n===!0||n===!1||"[object Boolean]"==l.call(n)},j.isNull=function(n){return null===n},j.isUndefined=function(n){return n===void 0},j.has=function(n,t){return f.call(n,t)},j.noConflict=function(){return n._=t,this},j.identity=function(n){return n},j.times=function(n,t,r){for(var e=Array(Math.max(0,n)),u=0;n>u;u++)e[u]=t.call(r,u);return e},j.random=function(n,t){return null==t&&(t=n,n=0),n+Math.floor(Math.random()*(t-n+1))};var I={escape:{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#x27;","/":"&#x2F;"}};I.unescape=j.invert(I.escape);var T={escape:new RegExp("["+j.keys(I.escape).join("")+"]","g"),unescape:new RegExp("("+j.keys(I.unescape).join("|")+")","g")};j.each(["escape","unescape"],function(n){j[n]=function(t){return null==t?"":(""+t).replace(T[n],function(t){return I[n][t]})}}),j.result=function(n,t){if(null==n)return void 0;var r=n[t];return j.isFunction(r)?r.call(n):r},j.mixin=function(n){A(j.functions(n),function(t){var r=j[t]=n[t];j.prototype[t]=function(){var n=[this._wrapped];return a.apply(n,arguments),z.call(this,r.apply(j,n))}})};var N=0;j.uniqueId=function(n){var t=++N+"";return n?n+t:t},j.templateSettings={evaluate:/<%([\s\S]+?)%>/g,interpolate:/<%=([\s\S]+?)%>/g,escape:/<%-([\s\S]+?)%>/g};var q=/(.)^/,B={"'":"'","\\":"\\","\r":"r","\n":"n","    ":"t","\u2028":"u2028","\u2029":"u2029"},D=/\\|'|\r|\n|\t|\u2028|\u2029/g;j.template=function(n,t,r){var e;r=j.defaults({},r,j.templateSettings);var u=new RegExp([(r.escape||q).source,(r.interpolate||q).source,(r.evaluate||q).source].join("|")+"|$","g"),i=0,a="__p+='";n.replace(u,function(t,r,e,u,o){return a+=n.slice(i,o).replace(D,function(n){return"\\"+B[n]}),r&&(a+="'+\n((__t=("+r+"))==null?'':_.escape(__t))+\n'"),e&&(a+="'+\n((__t=("+e+"))==null?'':__t)+\n'"),u&&(a+="';\n"+u+"\n__p+='"),i=o+t.length,t}),a+="';\n",r.variable||(a="with(obj||{}){\n"+a+"}\n"),a="var __t,__p='',__j=Array.prototype.join,"+"print=function(){__p+=__j.call(arguments,'');};\n"+a+"return __p;\n";try{e=new Function(r.variable||"obj","_",a)}catch(o){throw o.source=a,o}if(t)return e(t,j);var c=function(n){return e.call(this,n,j)};return c.source="function("+(r.variable||"obj")+"){\n"+a+"}",c},j.chain=function(n){return j(n).chain()};var z=function(n){return this._chain?j(n).chain():n};j.mixin(j),A(["pop","push","reverse","shift","sort","splice","unshift"],function(n){var t=e[n];j.prototype[n]=function(){var r=this._wrapped;return t.apply(r,arguments),"shift"!=n&&"splice"!=n||0!==r.length||delete r[0],z.call(this,r)}}),A(["concat","join","slice"],function(n){var t=e[n];j.prototype[n]=function(){return z.call(this,t.apply(this._wrapped,arguments))}}),j.extend(j.prototype,{chain:function(){return this._chain=!0,this},value:function(){return this._wrapped}})}.call(this);
