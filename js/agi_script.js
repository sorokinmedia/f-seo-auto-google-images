/**
 * Created by F-SEO on 14.09.2016.
 */

var carPos;
var alt;

function addHtml() {
    jQuery('<a class="img_btn">G<span>Автокартинка</span></a>').insertBefore('#insert-media-button');
    jQuery('.wp-admin').append('<div class="agi_popup"></div>>');
    jQuery('.wp-admin').append('<div class="agi_preview"></div>');
    jQuery('.wp-admin').append('<div class="agi_window"></div>');

    jQuery('.agi_window').append('<div class="afi_win_left"><div id="agi_search">' +
        '<input type="text" class="agi_srch_txt" name="agi_srch_txt" id="agi_srch_txt" placeholder="Кто ищет, тот всегда найдет">' +
        '<a class="srch_btn">Find</a>' +
        '</div></div>');

    jQuery('.agi_window').append('<div class="agi_results"></div><span class="popap_cross"></span>'); 
    jQuery('.afi_win_left').append('<div class="agi_param"></div>');

    jQuery('.agi_param').append(
        '<select class="agi_select_size"><option>Любой размер</option>'
            +'<option value="i">Маленькие</option>'
            +'<option value="m">Средние</option>'
            +'<option value="l">Большие</option>'
            +'<option value="qsvga">> 400x300</option>'
            +'<option value="vga">> 640x480</option>'
            +'<option value="svga">> 800x600</option>'
            +'<option value="sga">> 1024x768</option>'
            +'<option value="2mp">> 2Мп (1600x1200)</option>'
            +'<option value="4mp">> 4Мп (2272x1704)</option>'
            +'<option value="6mp">> 6Мп (2816x2112)</option>'
            +'<option value="8mp">> 8Мп (3264x2448)</option>'
            +'<option value="10mp">> 10Мп (3648x2736)</option>'
            +'<option value="12mp">> 12Мп (4096x3072)</option>'
            +'<option value="15mp">> 15Мп (4480x3360)</option>'
            +'<option value="20mp">> 20Мп (5120x3840)</option>'
            +'<option value="40mp">> 40Мп (7216x5412)</option>'
            +'<option value="70mp">> 70Мп (9600x7200)</option>'
        +'</select>'

        +'<select class="agi_select_type"><option>Любой тип</option>'
            +'<option value="face">Лица</option>'
            +'<option value="photo">Фотографии</option>'
            +'<option value="clipart">Клип-арт</option>'
            +'<option value="lineart">Ч/б рисунок</option>'
        +'</select>'

        +'<select class="agi_select_period"><option>Любой время</option>'
            +'<option value="d">За 24 часа</option>'
            +'<option value="w">За неделю</option>'
        +'</select>'

        +'<select class="agi_select_color"><option>Любой цвет</option>'
            +'<option value="color">Цветные</option>'
            +'<option value="nocolor">Черно-белые</option>'
            +'<option value="trans">Прозрачные</option>'
            +'<option value="black">Черные</option>'
            +'<option value="blue">Синие</option>'
            +'<option value="brown">Коричневые</option>'
            +'<option value="gray">Серые</option>'
            +'<option value="green">Зеленые</option>'
            +'<option value="orange">Оранжевые</option>'
            +'<option value="pink">Розовые</option>'
            +'<option value="purple">Фиолетовые</option>'
            +'<option value="red">Красные</option>'
            +'<option value="teal">Голубые</option>'
            +'<option value="white">Белые</option>'
            +'<option value="yellow">Желтые</option>'
        +'</select>'
    );
}

jQuery(document).ready(function() {

    // Добавим  html
    addHtml();
    // Обработаем клик
    jQuery('.img_btn').click(function () {

        if(!carPos) carPos = 0;

        //вывести окно
        jQuery('.agi_popup').toggle();
        jQuery('.agi_window').toggle();
        alt = getNearestTitle(carPos);

    });
    //сделать запрос Гуглу
    // распарсить ответ
    //вывести ответ

    // Скрытие окна
    jQuery('.agi_popup').click(function () {
        jQuery('.agi_popup').toggle();
        jQuery('.agi_window').toggle();
    });

    jQuery('a.srch_btn').click(function () {
        googleImagesPage = 0;
        googleImagesSearch();
    });


    jQuery('#content').focusout(function () {
        carPos = jQuery(this)[0].selectionStart;
    });

});

function googleImagesSearch() {
    $doc = document.getElementsByClassName('agi_window')[0].firstChild;

    var $ = jQuery;

    var q = jQuery('.agi_srch_txt').val();

    var data = {
        'action': 'google_images_search',
        'q': q,
        'size': jQuery('.agi_select_size').val(),//$('#google-images-header .search-size').val(),
        'color': jQuery('.agi_select_color').val(),//$('#google-images-header .search-color').val(),
        'type': jQuery('.agi_select_type').val(),//$('#google-images-header .search-type').val(),
        'period': jQuery('.agi_select_period').val(),//$('#google-images-header .search-period').val(),
        'safety': '',//$('#google-images-header .search-safety').val(),
        'page': googleImagesPage

    };

    searchXhr = $.post(ajaxurl, data, function(items){

        if (items.length == 0)
        {
            alert( 'Не нашел');
            doc.html(objectL10n.nothing_found);
            //doc.hide();
            return;
        }
        jQuery('.agi_results').html('');
        
        for(var i = 0; i<items.length;i++){
            
            var srcFull = ajaxurl + "?action=google_images_get&full=true&url=" + items[i].imgurl + "&referer=" + items[i].imgrefurl;
            
            var html = '<div class="founded_img" mark=""><a class="agi_img_res" q="'+q+'" referer="' + items[i].imgrefurl
                + '" url="' + items[i].imgurl + '" href="' + srcFull
                + '" onclick="return false"><img src="'+items[i].thumbnail+'" /></a><span>' + items[i].w+'x'+items[i].h
                + '</span>'
                + '<div><span class="agi_img_add 300_l_close"><300</span>'
                + '<span class="agi_img_add 300_r_close">300></span>'
                + '<span class="agi_img_add 300_l"><+300</span>'
                + '<span class="agi_img_add 300_r">+300></span>'
                + '<span class="agi_img_add 600">600</span>'
                + '<span class="agi_img_add 600_close">+600</span></div>'
                +'</div>';
            
            jQuery('.agi_results').append(html);
        }
        
        jQuery('.agi_img_res').click(function () {
            
            var img = jQuery(this).attr('url');
            jQuery('.agi_preview').toggle();
            jQuery('.agi_preview').append('<img src="' + img + '" />');
        });
        
        jQuery('.agi_preview').click(function () {

            jQuery('.agi_preview').html('').hide();
        });

        jQuery('.agi_img_mark').click(function () {
            var p = jQuery(this).parent();
            if( p.attr('mark')!=''){
                p.css({
                    'border-color' : 'transparent'
                });
                p.attr('mark','');
            }
            else {
                p.attr('mark','marked');
                p.css({
                    'border-color' : '#0073aa'
                });
            }
        });

        jQuery('.agi_img_add').click(function () {
            var rem = jQuery(this).parent();
            rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
            googleImagesUpload(rem.find('.agi_img_res'));
        });
        
        jQuery('.agi_add_all_marked').click(function () {

            var originAlt = alt;
            jQuery('[mark = "marked"]').each(function (index,elem) {
                var rem = jQuery(this);
                rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
                //alert(index);
                googleImagesUpload(rem.find('.agi_img_res'),originAlt + index);
            });
        });

        searchXhr = null;

    }, "json");
}

function googleImagesUpload(item,altI) {
    var $ = jQuery;

    if(altI) var curAlt = altI;
    else var curAlt = alt;

    var data2 = {
        'action': 'google_images_upload',
        'url': item.attr('url'),
        'referer': item.attr('referer'),
        'post_id': googleImagesPostId,
        'search': item.attr('q')
    };

    $.post(ajaxurl, data2, function(response){
        var pathname = jQuery(location).attr('host');
        //alert('Добавлено! ' + response);
        item.find('.load_img').text('Добавлено');
        InsertByCaretPos(carPos,'<img src="' + 'http://' + pathname + response + '" class="' + jQuery('.img_side_float').val()
            + '" width="' + jQuery('.img_width_sel').val() + '" alt="' + curAlt + '"/>'
        );
    });

}

function InsertByCaretPos(p,value) {
    var $ = jQuery;
    var ta = $('#content');
        //p = ta[0].selectionStart;
        text = ta.val();
    if(p != undefined) {
        ta.val(text.slice(0, p) + value + text.slice(p));
        //alert("Вставил");
    }
    else{
        ta.trigger('focus');
        range = document.selection.createRange(); 
        range.text = value;
    }
}

function getNearestTitle(pos) {
    var $ = jQuery;
    var str = $('#content').val();
    var parsText = str.slice(0,pos);
    var regexp = /<h2>/gi;
    var res;var i;
    while( res = regexp.exec(parsText) ){
        i = res.index;
    }
    if( !i ) return null;
    i = i + 4;
    var alt = '';
    while( parsText.charAt(i)!='<' ){
        alt = alt + parsText.charAt(i);
        i++;
    }
    return alt;
}



