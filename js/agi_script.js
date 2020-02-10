/**
 * основной js скрипт работы плагина
 */
var carPos;
var alt;
var agi_width_big;
var agi_width;
var onSelected;
var textArea;
var textAreaJs;
var agi_width_thmb;
var LastQuery;
var content;


function addHtml() {
    jQuery('<a class="img_btn">G<span>Автокартинка</span></a>').insertAfter('.wp-media-buttons');
    jQuery('<a class="thmb_btn">G<span>Автоминиатюра</span></a>').insertAfter('.img_btn');
    jQuery('<span class="after_img_btn"></span>').insertAfter('.thmb_btn');
    jQuery('.wp-admin').append('<div class="agi_popup"></div>');
    jQuery('.wp-admin').append('<div class="agi_preview"></div>');
    jQuery('.wp-admin').append('<div class="agi_window"></div>');

    jQuery('.agi_window').append('<div class="afi_win_left"><div id="agi_search">' +
        '<input type="text" class="agi_srch_txt" name="agi_srch_txt" id="agi_srch_txt" placeholder="Введите запрос для поиска">' +
        '<a class="srch_btn">Найти</a>' +
        '</div></div>');

    /*jQuery('.agi_window').append('<div class="agi_results"></div><div class="agi_pagination"><span class="agi_pages prev_p"></span><span class="numpage"></span><span class="agi_pages next_p">след→</span></div><span class="popap_cross"></span>');*/
    jQuery('.afi_win_left').append('<div class="agi_param"></div>');

    jQuery('.btns_instruct').append('"+"  -  добавить картинку и остаться на странице попап (если нажать на кнопку без плюса, окно закоется)' +
        '/n"<"  -  добавит картинку слева' +
        '/n">"  -  добавит картинку справа' +
        '/n300, 600 или другое число - ширина картинки на сайте');

    jQuery('.agi_param').append(
        '<select class="agi_select_size">'
            +'<option value="qsvga">> 400x300</option>'
            +'<option value="i">Маленькие</option>'
            +'<option value="m">Средние</option>'
            +'<option value="l">Большие</option>'
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

        +'<label for="circle_only"><input type="checkbox" id="circle_only" name="circle_only"/> Только квадратные</label>'
    );
}

jQuery(document).ready(function() {

        //Подготовка необходимых объектов
        var url = jQuery(location).attr('href');
        if (url.indexOf('term.php?taxonomy=category') + 1) {
            googleImagesPostId = jQuery("[name = 'tag_ID']").val();
            jQuery('.thmb_btn').hide();
        }
        jQuery("#cat_top_description").focus(function () {
            textArea = jQuery("#cat_top_description");
            textAreaJs = document.getElementById('cat_top_description');
        });
        jQuery("#cat_bottom_description").focus(function () {
            textArea = jQuery("#cat_bottom_description");
            textAreaJs = document.getElementById('cat_bottom_description');
        });

        if ((url.indexOf('post.php') + 1 && url.indexOf('action=edit') + 1) || (url.indexOf('post-new.php') + 1)) {
            textArea = jQuery("#content");
            textAreaJs = document.getElementById('content');
        }


        // Добавим  html
        addHtml();

        // Скроем в категориях Добавление миниатюры
        if (jQuery("h1:first").text() == 'Изменить рубрику') {
            jQuery('.thmb_btn').hide();
        }

        // Обработаем клики
        jQuery('.img_btn').click(function () {
            jQuery('.agi_window').attr('win', 'img');
            AgiWinOpen();
        });

        jQuery('.thmb_btn').click(function () {
            //if(content!=jQuery("#content").val()) jQuery("form#post").submit();
            jQuery('.agi_window').attr('win', 'thmb');
            AgiWinOpen();
        });

        //ОБработаем выделение текста
        jQuery("#content").select(function () {
            OnSelect();
        });
        jQuery("#cat_top_description").select(function () {
            OnSelect();
        });
        jQuery("#cat_bottom_description").select(function () {
            OnSelect();
        });

        function OnSelect() {
            googleImagesPage = 0;
            var stxt = ShowSelection();
            if (2 < stxt.length && stxt.length < 50) {
                onSelected = stxt;
                jQuery('.after_img_btn').html(onSelected);
            }
        }

        //сделать запрос Гуглу
        // распарсить ответ
        //вывести ответ

        // Скрытие окна и показ окна
        jQuery('.agi_popup').click(function () {
            jQuery('.agi_popup').toggle();
            jQuery('.agi_window').toggle();
        });
        jQuery('.popap_cross').click(function () {
            jQuery('.agi_popup').toggle();
            jQuery('.agi_window').toggle();
        });

        // Поиск
        jQuery('a.srch_btn').click(function () {
            googleImagesPage = 0;
            jQuery('.prev_p').html('');
            agi_googleImagesSearch();
        });
        jQuery('.prev_p').click(function () {
            if (googleImagesPage > 0) googleImagesPage--;
            jQuery('.numpage').text(googleImagesPage + 1);
            if (googleImagesPage == 0) {
                jQuery('.prev_p').html('');
                jQuery('.numpage').text('');
            }
            agi_googleImagesSearch();
        });
        jQuery('.next_p').click(function () {
            googleImagesPage++;
            if (googleImagesPage == 1) jQuery('.prev_p').html('←пред');
            jQuery('.numpage').text(googleImagesPage + 1);
            agi_googleImagesSearch();
        });

        // Мониторим положение курсора в редакторе
        jQuery("#content").focusout(function () {
            carPos = jQuery(this)[0].selectionStart;
        });
        jQuery("#cat_top_description").focusout(function () {
            carPos = jQuery(this)[0].selectionStart;
        });
        jQuery("#cat_bottom_description").focusout(function () {
            carPos = jQuery(this)[0].selectionStart;
        });

        try{
            window.eeCommon = new EventEmitter();
        }catch( window) {
            console.log( 'Window.eeCommon=' + window.eeCommon)
        }
        // Горячие клавиши для показа окна
        if(window.eeCommon) window.eeCommon.addListener('commonKeyDown', function(e) {
            e = e || window.event;
            if (e.ctrlKey && e.keyCode == 71) { //ctrl+i
                e.preventDefault();
                if (window.getSelection) {
                    carPos = textArea[0].selectionStart; // Мониторим положение курсора в редакторе
                }
                AgiWinOpen();
                window.getSelection().removeAllRanges();
                jQuery('.agi_srch_txt').focus();

            }
            if (e.keyCode == 27) {                    //esc
                if (window.getSelection) {
                    var selection = window.getSelection();
                    selection.removeAllRanges();
                }
                if (jQuery('.agi_preview').css('display') == 'block') {
                    jQuery('.agi_preview').html('').hide();
                } else {
                    jQuery('.agi_popup').hide();
                    jQuery('.agi_window').hide();
                }
            }
            if (e.keyCode == 13 && jQuery('.agi_srch_txt').is(":focus")) {  //search on enter
                if (window.getSelection) {
                    var selection = window.getSelection();
                    selection.removeAllRanges();
                }
                jQuery('a.srch_btn').trigger('click');
            }
        })

        //Ф-я открытия окна

        function AgiWinOpen() {
            if (!carPos) carPos = 0;

            //вывести окно
            jQuery('.agi_popup').toggle();
            jQuery('.agi_window').toggle();
            //jQuery('.agi_results').html('');

            alt = getNearestTitle(carPos);

            var selectText = onSelected;//ShowSelection();
            if (selectText != '' && selectText != LastQuery) {
                jQuery('.agi_srch_txt').val(selectText);
                jQuery('a.srch_btn').trigger('click');
                LastQuery = selectText;
            }

            jQuery('.agi_srch_txt').focus();
        }

        // Ф-я чтения выделенного
        function ShowSelection() {
            var textComponent = textAreaJs;
            var selectedText;
            // IE version
            if (document.selection != undefined) {
                textComponent.focus();
                var sel = document.selection.createRange();
                selectedText = sel.text;
                document.selection.empty();
            }
            // Mozilla version
            else if (textComponent.selectionStart != undefined) {
                var startPos = textComponent.selectionStart;
                var endPos = textComponent.selectionEnd;
                selectedText = textComponent.value.substring(startPos, endPos)
            }
            return selectedText;
        }

        // Задаем ширину из настроек
        setOptionWidth();
});

//поиск и вывод превью картинок
function agi_googleImagesSearch() {
    $doc = document.getElementsByClassName('agi_window')[0].firstChild;
    console.log($doc);

    var $ = jQuery;

    var q = jQuery('.agi_srch_txt').val().replace('"','');

    var search_orient = '';

    if(jQuery('.agi_param').find('#circle_only').prop('checked')) search_orient = 'square';

    var data = {
        'action': 'agi_google_images_search',
        'q': q,
        'size': jQuery('.agi_select_size').val(),//$('#google-images-header .search-size').val(),
        'color': jQuery('.agi_select_color').val(),//$('#google-images-header .search-color').val(),
        'type': jQuery('.agi_select_type').val(),//$('#google-images-header .search-type').val(),
        'period': jQuery('.agi_select_period').val(),//$('#google-images-header .search-period').val(),
        'safety': '',//$('#google-images-header .search-safety').val(),
        'page': googleImagesPage,
        'search_orient': search_orient

    };

    searchXhr = $.post(ajaxurl, data, function(chunk){
        var dataFunc;
        var code = 'var AF_initDataCallback = function(val) { dataFunc = val.data };' + chunk;
        eval(code);
        var items = dataFunc()[31][0][12][2];
	    console.log(q, items)
        items = items.map(img => {
        	if (!img || !img[1] || !img[1][3]) return {};
        	return {
		        w: img[1][3][2],
		        h: img[1][3][1],
		        imgurl: img[1][3][0],
		        thumbnail: img[1][3][0],
		        imgrefurl: img[1][9][2003][2],
	        }
        });

        if (items.length == 0)
        {
            alert( 'Не нашел');
            doc.html(objectL10n.nothing_found);
            //doc.hide();
            return;
        }

        jQuery('.agi_results').html('');

        for(var i = 0; i < items.length; i++){

            var srcFull = ajaxurl + "?action=google_images_get&full=true&url=" + items[i].imgurl + "&referer=" + items[i].imgrefurl;

            if(items[i].churl == 'b9ka') continue;

            var onclick = '';
            var deny_click = 'click';
            if(Number(items[i].w) < Number(agi_width_big)) {
                onclick = 'style="background-color:#ccc; border-color:#ccc;" onclick="return null" ';
                deny_click = 'deny';
            }

            var orientation;
            if(Number(items[i].w) > Number(items[i].h)) orientation = 'horizontal';
            else if(Number(items[i].w) < Number(items[i].h)) orientation = 'vertical';
            else orientation = 'square';

            var propor = items[i].w / items[i].h;//пропорциональность картинки - ширина на длину

            var circle;
            if(orientation === 'square') {
                circle = '<div class="circle_img"><form>'
                    + '<label for="circle_img' + i + '"><input type="checkbox" id="circle_img' + i + '" name="circle_img' + i + '"/> Cirlce</label>'
                    +'</form></div>';
            }
            else circle = '';

            var html = '<div class="founded_img" mark="" orient="'
                +orientation+'" proportion="'+propor+'"><a class="agi_img_res" q="' + q + '" referer="' + items[i].imgrefurl
                + '" url="' + items[i]['imgurl']
                + '" href="' + srcFull
                + '" onclick="return false"><img src="'+items[i].thumbnail+'" /></a><div class="agi_img_propor">' + items[i].w+'x'+items[i].h
                + '</div>'
                +  circle;

            var html_img = '<div id="founded_images_btns"><span class="agi_img_add l_300_close" data-toggle="tooltip" title="Добавить слева (w:число) и закрыть" data-delay="{'+'"show": 100, "hide": 1000}">←'
                    +agi_width+'</span>'
                + '<span class="agi_img_add r_300_close" data-toggle="tooltip" title="Добавить справа (w:число) и закрыть" data-delay="{'+'"show": 100, "hide": 1000}">'
                    +agi_width+'→</span>'
                + '<span class="agi_img_add close_600" '+ ' deny="' + deny_click + '" '+onclick+' data-toggle="tooltip" title="Добавить в центр (w:число) и закрыть" data-delay="{'+'"show": 100, "hide": 1000}">'
                    +agi_width_big+'</span>'
                + '<span class="agi_img_add l_300 yellow_btn" data-toggle="tooltip" title="Добавить слева (w:число) и продолжить" data-delay="{'+'"show": 100, "hide": 1000}">←'
                    +agi_width+'+</span>'
                + '<span class="agi_img_add r_300 yellow_btn" data-toggle="tooltip" title="Добавить справа (w:число) и продолжить" data-delay="{'+'"show": 100, "hide": 1000}">→'
                    +agi_width+'+</span>'
                + '<span class="agi_img_add add_600 yellow_btn" ' + ' deny="' + deny_click + '" '+onclick+' data-toggle="tooltip" title="Добавить в центр (w:число) и продолжить" data-delay="{'+'"show": 100, "hide": 1000}">+'
                    +agi_width_big+'</span>'
                +'</div></div>';

            var html_thmb = '<div id="founded_images_btns"><span class="create_thmb_btn">Сделать миниатюрой</span></div></div>';

            if(jQuery('.agi_window').attr('win') == 'img'
                && Number(items[i].w) > Number(agi_width)) jQuery('.agi_results').append(html + html_img);
            if(jQuery('.agi_window').attr('win') == 'thmb'
                && Number(items[i].w) > Number(agi_width_thmb)) jQuery('.agi_results').append(html + html_thmb);
        }

        jQuery('.agi_img_res').click(function () {

            var img = jQuery(this).attr('url');
            jQuery('.agi_preview').toggle();
            jQuery('.agi_preview').append('<img src="' + img + '" />');
        });

        jQuery('.agi_preview').click(function () {

            jQuery('.agi_preview').html('').hide();
        });

        jQuery('.create_thmb_btn').click(function () { //добавляем миниатюру
            var rem = jQuery(this).parent();
            rem = rem.parent();
            rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
            agi_googleThumbnailUpload(rem.find('.agi_img_res'),rem.attr('orient'));
            setTimeout(function(){
                jQuery('.agi_popup').toggle();
                jQuery('.agi_window').toggle();
            }, 3000);
        });

        var left = 'alignleft'; var right = 'alignright'; var cen = 'aligncenter'; var photoNum = 0;

        jQuery('.l_300_close').click(function () {
            var rem = jQuery(this).parent();
            rem = rem.parent();
            rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
            agi_googleImagesUpload(rem.find('.agi_img_res'),left,'medium',rem.attr('orient'), 0, rem.attr('proportion'));
            jQuery('.agi_popup').toggle();
            jQuery('.agi_window').toggle();
        });
        jQuery('.r_300_close').click(function () {
            var rem = jQuery(this).parent();
            rem = rem.parent();
            rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
            agi_googleImagesUpload(rem.find('.agi_img_res'),right,'medium',rem.attr('orient'), 0, rem.attr('proportion'));
            jQuery('.agi_popup').toggle();
            jQuery('.agi_window').toggle();
        });
        jQuery('.l_300').click(function () {
            photoNum++;
            var rem = jQuery(this).parent();
            rem = rem.parent();
            rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
            agi_googleImagesUpload(rem.find('.agi_img_res'),left,'medium',rem.attr('orient'),'Фото ' + photoNum ,rem.attr('proportion'));
        });
        jQuery('.r_300').click(function () {
            photoNum++;
            var rem = jQuery(this).parent();
            rem = rem.parent();
            rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
            agi_googleImagesUpload(rem.find('.agi_img_res'),right,'medium',rem.attr('orient'),'Фото ' + photoNum ,rem.attr('proportion'));
        });
        jQuery('.add_600').click(function () {
            photoNum++;
            if(jQuery(this).attr('deny') == 'click') {
                var rem = jQuery(this).parent();
                rem = rem.parent();
                rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
                agi_googleImagesUpload(rem.find('.agi_img_res'), cen, 'large',rem.attr('orient'),'Фото ' + photoNum ,rem.attr('proportion'));
            }
        });
        jQuery('.close_600').click(function () {
            if(jQuery(this).attr('deny') == 'click') {
                var rem = jQuery(this).parent();
                rem = rem.parent();
                rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
                agi_googleImagesUpload(rem.find('.agi_img_res'), cen, 'large',rem.attr('orient'), 0, rem.attr('proportion'));
                jQuery('.agi_popup').toggle();
                jQuery('.agi_window').toggle();
            }
        });

        //jQuery('.agi_results').html(items);

        searchXhr = null;

    }, "json");
}

function agi_googleImagesUpload(item, side, width, orientation, altI, proportion) {
    var $ = jQuery;
    /*if(altI) var curAlt = altI;
    else var curAlt = alt;*/
    var curAlt = '';
    var data2 = {
        'action': 'agi_google_images_upload',
        'url': item.attr('url'),
        'referer': item.attr('referer'),
        'post_id': googleImagesPostId,
        'search': item.attr('q'),
        'width': width,
        'orientation':orientation,
        'proportion': proportion
    };
	console.log(data2);
    $.post(ajaxurl, data2, function(response){
        console.log(response);
        if(response === 'WP_Error') alert('Картинка недоступна');
        else {
            var circle = '';
            var pathname = jQuery(location).attr('host');
            //alert('Добавлено! ' + response);
            item.find('.load_img').text('Добавлено');
            if(item.parent().find('.circle_img input').prop("checked")) circle = ' img_rounded';
            var img = '<img src="' + '//' + pathname + response + '" class="' + side
                + circle  + '"' +' alt="' + curAlt + '"/>';
            InsertByCaretPos(carPos, img);
            carPos = carPos + img.length;
        }
    });
}

function agi_googleThumbnailUpload(item, orientation) {
    var $ = jQuery;

    var data_thmb = {
        'action': 'agi_google_images_upload',
        'url': item.attr('url'),
        'referer': item.attr('referer'),
        'post_id': googleImagesPostId,
        'search': item.attr('q'),
        'thumb': true,
        'orientation':orientation
    };
    $.post(ajaxurl, data_thmb, function(response){
        if(response === 'WP_Error') alert('Картинка недоступна');
        else {
            //console.log(response)
            var pathname = jQuery(location).attr('host');
            item.find('.load_img').text('Добавлено');
            jQuery('#_thumbnail_id').val(response[0]);
            jQuery('#set-post-thumbnail').html('<img src="//' + pathname + response[1] + '" />');
        }
    },"json");

}

function InsertByCaretPos(p,value) {
    var $ = jQuery;
    var ta = textArea;
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
    var str = textArea.val();
    var parsText = str.slice(0,pos);
    var regexp = /<h2>/gi;
    var res; var i=-1;
    while( res = regexp.exec(parsText) ){
        i = res.index;
    }
    if( i == -1 ) return '';

    // для р3
    var regexp = /<h3>/gi;
    var res3; var i3=-1;
    while( res3 = regexp.exec(parsText) ){
        i3 = res3.index;
    }
    if( i3 >= 0){
        if(i3 > i) i = i3;
    }

    i = i + 4;
    var alt = '';
    while( parsText.charAt(i)!='<' && parsText.charAt(i)){
        alt = alt + parsText.charAt(i);
        i++;
    }
    return alt;
}

function setOptionWidth(){
    var $ = jQuery;
    var data3 = {
        'action': 'get_agi_img_width_option'
    };
    $.post(ajaxurl, data3, function(response){
        agi_width_big = response[1];
        agi_width = response[0];
        agi_width_thmb = response[2];
    },"json");
}
