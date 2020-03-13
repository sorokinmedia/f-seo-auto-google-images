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
var pagination = 1;
var googleImagesPage = 1;
var paginationStep = {prev: 0, next: 0};

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

	jQuery('.agi_window').append('<div class="agi_results"></div><div class="agi_pagination"><span class="agi_pages prev_p"></span><span class="numpage"></span><span class="agi_pages next_p">след→</span></div><span class="popap_cross"></span>');
	jQuery('.afi_win_left').append('<div class="agi_param"></div>');

	jQuery('.btns_instruct').append('"+"  -  добавить картинку и остаться на странице попап (если нажать на кнопку без плюса, окно закоется)' +
		'/n"<"  -  добавит картинку слева' +
		'/n">"  -  добавит картинку справа' +
		'/n300, 600 или другое число - ширина картинки на сайте');

	jQuery('.agi_param').append(
		'<select class="agi_select_size">'
		+ '<option value="large">Большие</option>'
		+ '<option value="icon">Иконки</option>'
		+ '<option value="small">Маленькие</option>'
		+ '<option value="medium">Средние</option>'
		+ '<option value="xlarge">Очень Большие</option>'
		+ '<option value="xlarge">Еще Больше</option>'
		+ '<option value="huge">Огромные</option>'
		+ '</select>'

		+ '<select class="agi_select_type"><option value="0">Любой тип</option>'
		+ '<option value="face">Лица</option>'
		+ '<option value="photo">Фотографии</option>'
		+ '<option value="clipart">Клип-арт</option>'
		+ '<option value="lineart">Ч/б рисунок</option>'
		+ '</select>'

		+ '<select class="agi_select_color"><option value="0">Любой цвет</option>'
		+ '<option value="black">Черные</option>'
		+ '<option value="blue">Синие</option>'
		+ '<option value="brown">Коричневые</option>'
		+ '<option value="gray">Серые</option>'
		+ '<option value="green">Зеленые</option>'
		+ '<option value="orange">Оранжевые</option>'
		+ '<option value="pink">Розовые</option>'
		+ '<option value="purple">Фиолетовые</option>'
		+ '<option value="red">Красные</option>'
		+ '<option value="teal">Голубые</option>'
		+ '<option value="white">Белые</option>'
		+ '<option value="yellow">Желтые</option>'
		+ '</select>'
		// +'<label for="circle_only"><input type="checkbox" id="circle_only" name="circle_only"/> Только квадратные</label>'
	);
}

jQuery(document).ready(function () {

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
		pagination = 0;
		jQuery('.prev_p').html('');
		agi_googleImagesSearch();
	});
	jQuery('.prev_p').click(function () {
		if (pagination > 0) pagination--;
		jQuery('.numpage').text(pagination + 1);
		if (pagination == 0) {
			jQuery('.prev_p').html('');
			jQuery('.numpage').text('');
		}
		googleImagesPage = paginationStep.prev
		agi_googleImagesSearch();
	});
	jQuery('.next_p').click(function () {
		pagination++;
		if (pagination == 1) jQuery('.prev_p').html('←пред');
		jQuery('.numpage').text(pagination + 1);
		googleImagesPage = paginationStep.next
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

	try {
		window.eeCommon = new EventEmitter();
	} catch (window) {
		console.log('Window.eeCommon=' + window.eeCommon)
	}
	// Горячие клавиши для показа окна
	if (window.eeCommon) window.eeCommon.addListener('commonKeyDown', function (e) {
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

	var q = jQuery('.agi_srch_txt').val().replace('"', '');

	// var search_orient = '';

	// if(jQuery('.agi_param').find('#circle_only').prop('checked')) search_orient = 'square';

	var data = {
		'action': 'agi_google_images_search',
		'q': q,
		'imgSize': jQuery('.agi_select_size').val(),//$('#google-images-header .search-size').val(),
		'imgDominantColor': jQuery('.agi_select_color').val(),//$('#google-images-header .search-color').val(),
		'imgType': jQuery('.agi_select_type').val(),//$('#google-images-header .search-type').val(),
		'safe': '',//$('#google-images-header .search-safety').val(),
		'start': googleImagesPage,
		// 'search_orient': search_orient

	};

	searchXhr = $.post(ajaxurl, data, function (chunk) {

		// delete
		const testData = {
			"kind": "customsearch#search",
			"url": {
				"type": "application/json",
				"template": "https://www.googleapis.com/customsearch/v1?q={searchTerms}&num={count?}&start={startIndex?}&lr={language?}&safe={safe?}&cx={cx?}&sort={sort?}&filter={filter?}&gl={gl?}&cr={cr?}&googlehost={googleHost?}&c2coff={disableCnTwTranslation?}&hq={hq?}&hl={hl?}&siteSearch={siteSearch?}&siteSearchFilter={siteSearchFilter?}&exactTerms={exactTerms?}&excludeTerms={excludeTerms?}&linkSite={linkSite?}&orTerms={orTerms?}&relatedSite={relatedSite?}&dateRestrict={dateRestrict?}&lowRange={lowRange?}&highRange={highRange?}&searchType={searchType}&fileType={fileType?}&rights={rights?}&imgSize={imgSize?}&imgType={imgType?}&imgColorType={imgColorType?}&imgDominantColor={imgDominantColor?}&alt=json"
			},
			"queries": {
				"request": [
					{
						"title": "Google Custom Search - pencil",
						"totalResults": "2200000000",
						"searchTerms": "pencil",
						"count": 10,
						"startIndex": 1,
						"inputEncoding": "utf8",
						"outputEncoding": "utf8",
						"safe": "off",
						"cx": "007742504625441601031:acf65gyd015",
						"searchType": "image",
						"imgSize": "large"
					}
				],
				"nextPage": [
					{
						"title": "Google Custom Search - pencil",
						"totalResults": "2200000000",
						"searchTerms": "pencil",
						"count": 10,
						"startIndex": 11,
						"inputEncoding": "utf8",
						"outputEncoding": "utf8",
						"safe": "off",
						"cx": "007742504625441601031:acf65gyd015",
						"searchType": "image",
						"imgSize": "large"
					}
				]
			},
			"context": {
				"title": "who"
			},
			"searchInformation": {
				"searchTime": 0.750972,
				"formattedSearchTime": "0.75",
				"totalResults": "2200000000",
				"formattedTotalResults": "2,200,000,000"
			},
			"items": [
				{
					"kind": "customsearch#result",
					"title": "Amazon.com : AmazonBasics Wood-cased Bulk Pencils - #2 HB Pencil ...",
					"htmlTitle": "Amazon.com : AmazonBasics Wood-cased Bulk <b>Pencils</b> - #2 HB <b>Pencil</b> ...",
					"link": "https://images-na.ssl-images-amazon.com/images/I/61TrT6dIUlL._AC_SX466_.jpg",
					"displayLink": "www.amazon.com",
					"snippet": "Amazon.com : AmazonBasics Wood-cased Bulk Pencils - #2 HB Pencil ...",
					"htmlSnippet": "Amazon.com : AmazonBasics Wood-cased Bulk <b>Pencils</b> - #2 HB <b>Pencil</b> ...",
					"mime": "image/jpeg",
					"fileFormat": "image/jpeg",
					"image": {
						"contextLink": "https://www.amazon.com/AmazonBasics-Wood-cased-Pencils-Box-144/dp/B0188A3QRM",
						"height": 455,
						"width": 466,
						"byteSize": 11599,
						"thumbnailLink": "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSFGUJ0pzEwI4xr4QQNnHr8aDTIbbE948SMoOSz-vD-IuP14g9NUA5uNX8&s",
						"thumbnailHeight": 125,
						"thumbnailWidth": 128
					}
				},
				{
					"kind": "customsearch#result",
					"title": "JetPens Mechanical Pencil Sampler",
					"htmlTitle": "JetPens Mechanical <b>Pencil</b> Sampler",
					"link": "https://static2.jetpens.com/images/a/000/170/170526.jpg?auto=format&ba=middle%2Ccenter&balph=3&blend64=aHR0cDovL3d3dy5qZXRwZW5zLmNvbS9pbWFnZXMvYXNzZXRzL3dhdGVybWFyazIucG5n&bm=difference&bs=inherit&chromasub=444&fm=jpg&h=400&mark64=aHR0cDovL3d3dy5qZXRwZW5zLmNvbS9pbWFnZXMvYXNzZXRzL3dhdGVybWFyazEucG5n&markalign=top%2Cright&markalpha=30&markscale=16&q=90&usm=20&w=600&s=70feb4a2e6253775283e98d2d509c639",
					"displayLink": "www.jetpens.com",
					"snippet": "JetPens Mechanical Pencil Sampler",
					"htmlSnippet": "JetPens Mechanical <b>Pencil</b> Sampler",
					"mime": "image/jpeg",
					"fileFormat": "image/jpeg",
					"image": {
						"contextLink": "https://www.jetpens.com/JetPens-Mechanical-Pencil-Sampler/pd/27617",
						"height": 400,
						"width": 600,
						"byteSize": 43144,
						"thumbnailLink": "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQhizY6YGmtMHlqML9Kwsuvo7nRgyuV1seF-L3dDZQ8yVHJgl-t1qNwk4A&s",
						"thumbnailHeight": 90,
						"thumbnailWidth": 135
					}
				},
				{
					"kind": "customsearch#result",
					"title": "Ticonderoga Laddie Oversized Pencil With Latex Free Eraser, 11/32 ...",
					"htmlTitle": "Ticonderoga Laddie Oversized <b>Pencil</b> With Latex Free Eraser, 11/32 ...",
					"link": "https://target.scene7.com/is/image/Target/GUEST_41bdc4d2-b67b-4935-8dc1-ae8ef04881fb?wid=488&hei=488&fmt=pjpeg",
					"displayLink": "www.target.com",
					"snippet": "Ticonderoga Laddie Oversized Pencil With Latex Free Eraser, 11/32 ...",
					"htmlSnippet": "Ticonderoga Laddie Oversized <b>Pencil</b> With Latex Free Eraser, 11/32 ...",
					"mime": "image/",
					"fileFormat": "image/",
					"image": {
						"contextLink": "https://www.target.com/p/ticonderoga-laddie-oversized-pencil-with-latex-free-eraser-11-32-inch-pk-of-12/-/A-76168249",
						"height": 488,
						"width": 488,
						"byteSize": 7470,
						"thumbnailLink": "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSy483hhLbJRpxrJQk7-YnxOkCmY9zKNS3-3WR3HH8aLHSyEf_VBYMVnyEb&s",
						"thumbnailHeight": 130,
						"thumbnailWidth": 130
					}
				},
				{
					"kind": "customsearch#result",
					"title": "Amazon.com : Tombow MONO Drawing Pencil, H, Graphite 12-Pack ...",
					"htmlTitle": "Amazon.com : Tombow MONO Drawing <b>Pencil</b>, H, Graphite 12-Pack ...",
					"link": "https://images-na.ssl-images-amazon.com/images/I/6133DDGnOoL._AC_SY355_.jpg",
					"displayLink": "www.amazon.com",
					"snippet": "Amazon.com : Tombow MONO Drawing Pencil, H, Graphite 12-Pack ...",
					"htmlSnippet": "Amazon.com : Tombow MONO Drawing <b>Pencil</b>, H, Graphite 12-Pack ...",
					"mime": "image/jpeg",
					"fileFormat": "image/jpeg",
					"image": {
						"contextLink": "https://www.amazon.com/Tombow-Drawing-Pencil-Graphite-12-Pack/dp/B00AQENNGS",
						"height": 355,
						"width": 353,
						"byteSize": 7908,
						"thumbnailLink": "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ8hNhi7LtX2Uui6S9szBoJyNlhqDtnquiF9f3euWkH46WZA6vohXnTZw&s",
						"thumbnailHeight": 121,
						"thumbnailWidth": 120
					}
				},
				{
					"kind": "customsearch#result",
					"title": "BIC Mechanical Pencils Xtra Strong 0.9 mm Assorted Barrel Colors ...",
					"htmlTitle": "BIC Mechanical <b>Pencils</b> Xtra Strong 0.9 mm Assorted Barrel Colors ...",
					"link": "https://officedepot.scene7.com/is/image/officedepot/292475_o01_bic_xtra_mechanical_pencils?$OD%2DLarge$&wid=450&hei=450",
					"displayLink": "www.officedepot.com",
					"snippet": "BIC Mechanical Pencils Xtra Strong 0.9 mm Assorted Barrel Colors ...",
					"htmlSnippet": "BIC Mechanical <b>Pencils</b> Xtra Strong 0.9 mm Assorted Barrel Colors ...",
					"mime": "image/",
					"fileFormat": "image/",
					"image": {
						"contextLink": "https://www.officedepot.com/a/products/292475/BIC-Mechanical-Pencils-Xtra-Strong-09/",
						"height": 450,
						"width": 450,
						"byteSize": 33698,
						"thumbnailLink": "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQdIbkdH4jt9kY9FVfxZgNHuAGKPN7pJPFZixpX0iC3MBeDeOvlm7hr3A&s",
						"thumbnailHeight": 127,
						"thumbnailWidth": 127
					}
				},
				{
					"kind": "customsearch#result",
					"title": "Amazon.com : BIC Xtra Sparkle Mechanical Pencil, Colorful Barrel ...",
					"htmlTitle": "Amazon.com : BIC Xtra Sparkle Mechanical <b>Pencil</b>, Colorful Barrel ...",
					"link": "https://images-na.ssl-images-amazon.com/images/I/81KWwjaqgQL._AC_SX466_.jpg",
					"displayLink": "www.amazon.com",
					"snippet": "Amazon.com : BIC Xtra Sparkle Mechanical Pencil, Colorful Barrel ...",
					"htmlSnippet": "Amazon.com : BIC Xtra Sparkle Mechanical <b>Pencil</b>, Colorful Barrel ...",
					"mime": "image/jpeg",
					"fileFormat": "image/jpeg",
					"image": {
						"contextLink": "https://www.amazon.com/BIC-Sparkle-Mechanical-Colorful-15-Count/dp/B015HJGBHE",
						"height": 581,
						"width": 466,
						"byteSize": 48409,
						"thumbnailLink": "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTLwIh-Kq6AD5KcKLeDiyA7lKy3nPp17hsL0Jes_9cPL4-uee4LoprgAoc&s",
						"thumbnailHeight": 134,
						"thumbnailWidth": 107
					}
				},
				{
					"kind": "customsearch#result",
					"title": "Have we all underrated the humble pencil? - BBC News",
					"htmlTitle": "Have we all underrated the humble <b>pencil</b>? - BBC News",
					"link": "https://ichef.bbci.co.uk/news/410/cpsprodpb/C6DC/production/_107080905_gettyimages-654239286.jpg",
					"displayLink": "www.bbc.com",
					"snippet": "Have we all underrated the humble pencil? - BBC News",
					"htmlSnippet": "Have we all underrated the humble <b>pencil</b>? - BBC News",
					"mime": "image/jpeg",
					"fileFormat": "image/jpeg",
					"image": {
						"contextLink": "https://www.bbc.com/news/business-48383050",
						"height": 230,
						"width": 410,
						"byteSize": 9525,
						"thumbnailLink": "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRQ1AwRAMyPtI7wqE-bBEdMo6niOLgG1iginXkgikS_f-tnpoF6UrByHDM&s",
						"thumbnailHeight": 70,
						"thumbnailWidth": 125
					}
				},
				{
					"kind": "customsearch#result",
					"title": "Amazon.com : ban.do Women's Write On Graphite Pencil Set of 10 ...",
					"htmlTitle": "Amazon.com : ban.do Women&#39;s Write On Graphite <b>Pencil</b> Set of 10 ...",
					"link": "https://images-na.ssl-images-amazon.com/images/I/81ZezY8-kxL._AC_SY355_.jpg",
					"displayLink": "www.amazon.com",
					"snippet": "Amazon.com : ban.do Women's Write On Graphite Pencil Set of 10 ...",
					"htmlSnippet": "Amazon.com : ban.do Women&#39;s Write On Graphite <b>Pencil</b> Set of 10 ...",
					"mime": "image/jpeg",
					"fileFormat": "image/jpeg",
					"image": {
						"contextLink": "https://www.amazon.com/ban-do-Womens-Graphite-Pencil-Compliments/dp/B01HWVLMQA",
						"height": 355,
						"width": 308,
						"byteSize": 23857,
						"thumbnailLink": "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQfNndbaoZwzo1cGH9_K6Bprqsg3uvwWyEMpEEzhBKn2OZgb8W7QAP2M50&s",
						"thumbnailHeight": 121,
						"thumbnailWidth": 105
					}
				},
				{
					"kind": "customsearch#result",
					"title": "Beginner's Guide to Pencils - Pencil Grades | CW Pencil Enterprise",
					"htmlTitle": "Beginner&#39;s Guide to <b>Pencils</b> - <b>Pencil</b> Grades | CW <b>Pencil</b> Enterprise",
					"link": "https://cdn.shopify.com/s/files/1/0644/8811/articles/image1_3_large.JPG?v=1466789031",
					"displayLink": "cwpencils.com",
					"snippet": "Beginner's Guide to Pencils - Pencil Grades | CW Pencil Enterprise",
					"htmlSnippet": "Beginner&#39;s Guide to <b>Pencils</b> - <b>Pencil</b> Grades | CW <b>Pencil</b> Enterprise",
					"mime": "image/jpeg",
					"fileFormat": "image/jpeg",
					"image": {
						"contextLink": "https://cwpencils.com/blogs/news/131980803-beginners-guide-to-pencil-shopping-a-guide-to-grades",
						"height": 264,
						"width": 479,
						"byteSize": 25831,
						"thumbnailLink": "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQQg8BDNdTbeKW1c8lJ4TkskQweuH1Ulp5UXzmWX2OBa-rn2ZH7iVbvAkQ&s",
						"thumbnailHeight": 71,
						"thumbnailWidth": 129
					}
				},
				{
					"kind": "customsearch#result",
					"title": "Pencil Painted Wood Shape | Hobby Lobby | 1227362",
					"htmlTitle": "<b>Pencil</b> Painted Wood Shape | Hobby Lobby | 1227362",
					"link": "https://imgprod65.hobbylobby.com/4/fe/ed/4feed470b25786878c7c41a9e5d201e26d9d669d/350Wx350H-1227362-0719-px.jpg",
					"displayLink": "www.hobbylobby.com",
					"snippet": "350Wx350H-1227362-0719-px.jpg",
					"htmlSnippet": "350Wx350H-1227362-0719-px.jpg",
					"mime": "image/jpeg",
					"fileFormat": "image/jpeg",
					"image": {
						"contextLink": "https://www.hobbylobby.com/Crafts-Hobbies/Wood-Crafting/Painted-Wood/Pencil-Painted-Wood-Shape/p/80744807",
						"height": 350,
						"width": 350,
						"byteSize": 64265,
						"thumbnailLink": "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQoAz9SPSPznVHPh1gK1zwI5bJD5EC4CVS0ZyJlloTqaf0qS81z8yktHg&s",
						"thumbnailHeight": 120,
						"thumbnailWidth": 120
					}
				}
			]
		}

		var items = chunk.items
		paginationStep = {
			prev: chunk.queries.previousPage ? chunk.queries.previousPage[0].startIndex : 0,
			next: chunk.queries.nextPage ? chunk.queries.nextPage[0].startIndex : 0
		}
		items = items.map(img => {
			if (!img) return {};
			const desc = img.image
			return {
				w: desc.width,
				h: desc.height,
				imgurl: img.link,
				thumbnail: desc.thumbnailLink,
				imgrefurl: img.title,
			}
		});

		if (items.length == 0) {
			alert('Не нашел');
			doc.html(objectL10n.nothing_found);
			//doc.hide();
			return;
		}

		jQuery('.agi_results').html('');

		for (var i = 0; i < items.length; i++) {

			var srcFull = items[i].thumbnail

			var onclick = '';
			var deny_click = 'click';
			if (Number(items[i].w) < Number(agi_width_big)) {
				onclick = 'style="background-color:#ccc; border-color:#ccc;" onclick="return null" ';
				deny_click = 'deny';
			}

			var orientation;
			if (Number(items[i].w) > Number(items[i].h)) orientation = 'horizontal';
			else if (Number(items[i].w) < Number(items[i].h)) orientation = 'vertical';
			else orientation = 'square';

			var propor = items[i].w / items[i].h;//пропорциональность картинки - ширина на длину

			var circle;
			if (orientation === 'square') {
				circle = '<div class="circle_img"><form>'
					+ '<label for="circle_img' + i + '"><input type="checkbox" id="circle_img' + i + '" name="circle_img' + i + '"/> Cirlce</label>'
					+ '</form></div>';
			} else circle = '';

			var html = '<div class="founded_img" mark="" orient="'
				+ orientation + '" proportion="' + propor + '"><a class="agi_img_res" q="' + q + '" referer="' + items[i].imgrefurl
				+ '" url="' + items[i]['imgurl']
				+ '" href="' + srcFull
				+ '" onclick="return false"><img src="' + items[i].thumbnail + '" /></a><div class="agi_img_propor">' + items[i].w + 'x' + items[i].h
				+ '</div>'
				+ circle;

			var html_img = '<div id="founded_images_btns"><span class="agi_img_add l_300_close" data-toggle="tooltip" title="Добавить слева (w:число) и закрыть" data-delay="{' + '"show": 100, "hide": 1000}">←'
				+ agi_width + '</span>'
				+ '<span class="agi_img_add r_300_close" data-toggle="tooltip" title="Добавить справа (w:число) и закрыть" data-delay="{' + '"show": 100, "hide": 1000}">'
				+ agi_width + '→</span>'
				+ '<span class="agi_img_add close_600" ' + ' deny="' + deny_click + '" ' + onclick + ' data-toggle="tooltip" title="Добавить в центр (w:число) и закрыть" data-delay="{' + '"show": 100, "hide": 1000}">'
				+ agi_width_big + '</span>'
				+ '<span class="agi_img_add l_300 yellow_btn" data-toggle="tooltip" title="Добавить слева (w:число) и продолжить" data-delay="{' + '"show": 100, "hide": 1000}">←'
				+ agi_width + '+</span>'
				+ '<span class="agi_img_add r_300 yellow_btn" data-toggle="tooltip" title="Добавить справа (w:число) и продолжить" data-delay="{' + '"show": 100, "hide": 1000}">→'
				+ agi_width + '+</span>'
				+ '<span class="agi_img_add add_600 yellow_btn" ' + ' deny="' + deny_click + '" ' + onclick + ' data-toggle="tooltip" title="Добавить в центр (w:число) и продолжить" data-delay="{' + '"show": 100, "hide": 1000}">+'
				+ agi_width_big + '</span>'
				+ '</div></div>';

			var html_thmb = '<div id="founded_images_btns"><span class="create_thmb_btn">Сделать миниатюрой</span></div></div>';

			if (jQuery('.agi_window').attr('win') == 'img'
				&& Number(items[i].w) > Number(agi_width)) {
				jQuery('.agi_results').append(html + html_img);
			}
			if (jQuery('.agi_window').attr('win') == 'thmb'
				&& Number(items[i].w) > Number(agi_width_thmb)) {
				jQuery('.agi_results').append(html + html_thmb);
			}
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
			agi_googleThumbnailUpload(rem.find('.agi_img_res'), rem.attr('orient'));
			setTimeout(function () {
				jQuery('.agi_popup').toggle();
				jQuery('.agi_window').toggle();
			}, 3000);
		});

		var left = 'alignleft';
		var right = 'alignright';
		var cen = 'aligncenter';
		var photoNum = 0;

		jQuery('.l_300_close').click(function () {
			var rem = jQuery(this).parent();
			rem = rem.parent();
			rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
			agi_googleImagesUpload(rem.find('.agi_img_res'), left, 'medium', rem.attr('orient'), 0, rem.attr('proportion'));
			jQuery('.agi_popup').toggle();
			jQuery('.agi_window').toggle();
		});
		jQuery('.r_300_close').click(function () {
			var rem = jQuery(this).parent();
			rem = rem.parent();
			rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
			agi_googleImagesUpload(rem.find('.agi_img_res'), right, 'medium', rem.attr('orient'), 0, rem.attr('proportion'));
			jQuery('.agi_popup').toggle();
			jQuery('.agi_window').toggle();
		});
		jQuery('.l_300').click(function () {
			photoNum++;
			var rem = jQuery(this).parent();
			rem = rem.parent();
			rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
			agi_googleImagesUpload(rem.find('.agi_img_res'), left, 'medium', rem.attr('orient'), 'Фото ' + photoNum, rem.attr('proportion'));
		});
		jQuery('.r_300').click(function () {
			photoNum++;
			var rem = jQuery(this).parent();
			rem = rem.parent();
			rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
			agi_googleImagesUpload(rem.find('.agi_img_res'), right, 'medium', rem.attr('orient'), 'Фото ' + photoNum, rem.attr('proportion'));
		});
		jQuery('.add_600').click(function () {
			photoNum++;
			if (jQuery(this).attr('deny') == 'click') {
				var rem = jQuery(this).parent();
				rem = rem.parent();
				rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
				agi_googleImagesUpload(rem.find('.agi_img_res'), cen, 'large', rem.attr('orient'), 'Фото ' + photoNum, rem.attr('proportion'));
			}
		});
		jQuery('.close_600').click(function () {
			if (jQuery(this).attr('deny') == 'click') {
				var rem = jQuery(this).parent();
				rem = rem.parent();
				rem.find('img').after('<div class="load_img">Загружаю...</div>').remove();
				agi_googleImagesUpload(rem.find('.agi_img_res'), cen, 'large', rem.attr('orient'), 0, rem.attr('proportion'));
				jQuery('.agi_popup').toggle();
				jQuery('.agi_window').toggle();
			}
		});

		//jQuery('.agi_results').html(items);

		searchXhr = null;

	}, "json");
}

function agi_googleImagesUpload(item, side, width, orientation, altI, proportion) {
	console.log(arguments)
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
		'orientation': orientation,
		'proportion': proportion
	};
	console.log(data2);
	$.post(ajaxurl, data2, function (response) {
		console.log(response);
		if (response === 'WP_Error') alert('Картинка недоступна');
		else {
			var circle = '';
			console.log(response, '@!')
			var pathname = jQuery(location).attr('host');
			//alert('Добавлено! ' + response);
			item.find('.load_img').text('Добавлено');
			if (item.parent().find('.circle_img input').prop("checked")) circle = ' img_rounded';
			var img = '<img src="' + '//' + pathname + response + '" class="' + side
				+ circle + '"' + ' alt="' + curAlt + '"/>';
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
		'orientation': orientation
	};
	$.post(ajaxurl, data_thmb, function (response) {
		if (response === 'WP_Error') alert('Картинка недоступна');
		else {
			//console.log(response)
			var pathname = jQuery(location).attr('host');
			item.find('.load_img').text('Добавлено');
			jQuery('#_thumbnail_id').val(response[0]);
			jQuery('#set-post-thumbnail').html('<img src="//' + pathname + response[1] + '" />');
		}
	}, "json");

}

function InsertByCaretPos(p, value) {
	var $ = jQuery;
	var ta = textArea;
	//p = ta[0].selectionStart;
	text = ta.val();
	if (p != undefined) {
		ta.val(text.slice(0, p) + value + text.slice(p));
		//alert("Вставил");
	} else {
		ta.trigger('focus');
		range = document.selection.createRange();
		range.text = value;
	}
}

function getNearestTitle(pos) {
	var $ = jQuery;
	var str = textArea.val();
	var parsText = str.slice(0, pos);
	var regexp = /<h2>/gi;
	var res;
	var i = -1;
	while (res = regexp.exec(parsText)) {
		i = res.index;
	}
	if (i == -1) return '';

	// для р3
	var regexp = /<h3>/gi;
	var res3;
	var i3 = -1;
	while (res3 = regexp.exec(parsText)) {
		i3 = res3.index;
	}
	if (i3 >= 0) {
		if (i3 > i) i = i3;
	}

	i = i + 4;
	var alt = '';
	while (parsText.charAt(i) != '<' && parsText.charAt(i)) {
		alt = alt + parsText.charAt(i);
		i++;
	}
	return alt;
}

function setOptionWidth() {
	var $ = jQuery;
	var data3 = {
		'action': 'get_agi_img_width_option'
	};
	$.post(ajaxurl, data3, function (response) {
		agi_width_big = response[1];
		agi_width = response[0];
		agi_width_thmb = response[2];
	}, "json");
}
