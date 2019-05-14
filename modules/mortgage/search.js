jQuery(document).ready(function(){
    jQuery("#banks-search-form").submit(function(){
        var _form = jQuery(this);
        var params = [];
        // собираем данные из формы
        var _regions = jQuery("#regions", _form).val();
        if(_regions) params.push('ipo_regionid='+_regions);
        // конструируем запрос
        window.location.href = '/estate/mortgage/banks/?' + params.join('&');
        return false;
    });

    jQuery("#programs-search-form").submit(function(){
        var _form = jQuery(this);
        var params = [];
        // собираем данные из формы
        var _regionid = jQuery("#regionid", _form).val();
        var _goalid = jQuery("#goalid", _form).val();
        var _bankid = jQuery("#bankid", _form).val();
        var _marketid = jQuery("#marketid", _form).val();
        var _currencyid = jQuery("#currencyid", _form).val();
        var _ratefor = parseFloat(jQuery("#ratefor", _form).val());
        var _sumcredit = parseInt(jQuery("#sumcredit", _form).val());
        var _sumcreditcurrencyid = jQuery("#sumcreditcurrencyid", _form).val();
        var _incconfirmid = jQuery("#incconfirmid", _form).val();
        var _typerateid = jQuery("#typerateid", _form).val();
        var _creditperiodmin = parseInt(jQuery("#creditperiodmin", _form).val());
        var _creditperiodmax = parseInt(jQuery("#creditperiodmax", _form).val());
        var _firstpayment = parseFloat(jQuery("#firstpayment", _form).val());
        var _age = parseInt(jQuery("#age", _form).val());
        var _registration = jQuery("#registration", _form).val();
        var _nationality = jQuery("#nationality", _form).val();
        var _paymenttypeid = jQuery("#paymenttypeid", _form).val();
        var _advrepay = parseInt(jQuery("#advrepay", _form).val());
        var _index = jQuery("#index", _form).val();

        if(_regionid) params.push('ipo_regionid='+_regionid);
        if(_goalid) params.push('ipo_goalid='+_goalid);
        if(_bankid) params.push('ipo_bankid='+_bankid);
        if(_marketid) params.push('ipo_marketid='+_marketid);
        if(_currencyid) params.push('ipo_currencyid='+_currencyid);
        if(_ratefor) params.push('ipo_ratefor='+_ratefor);
        if(_sumcredit) params.push('ipo_sumcredit='+_sumcredit);
        if(_sumcreditcurrencyid) params.push('ipo_sumcreditcurrencyid='+_sumcreditcurrencyid);
        if(_incconfirmid) params.push('ipo_incconfirmid='+_incconfirmid);
        if(_typerateid) params.push('ipo_typerateid='+_typerateid);
        if(_creditperiodmin) params.push('ipo_creditperiodmin='+_creditperiodmin);
        if(_creditperiodmax) params.push('ipo_creditperiodmax='+_creditperiodmax);
        if(_firstpayment) params.push('ipo_firstpayment='+_firstpayment);
        if(_age) params.push('ipo_age='+_age);
        if(_registration) params.push('ipo_registration='+_registration);
        if(_nationality) params.push('ipo_nationality='+_nationality);
        if(_paymenttypeid) params.push('ipo_paymenttypeid='+_paymenttypeid);
        if(_advrepay) params.push('ipo_advrepay='+_advrepay);
        if(_index) params.push('ipo_index='+_index);
        // конструируем запрос
        window.location.href = '/estate/mortgage/programs/?' + params.join('&');
        return false;
    });
    
    jQuery('.banks-list-body .row').on('click', function(e){
        var _link = jQuery(this).find("a.a_to_click");
        if (e.target === _link[0]) return false;
        _link.trigger('click');
        return false;
    });
    jQuery("a.a_to_click").click(function() { location.href = this.href; });
});
