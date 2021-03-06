
// Инициализация опций
option_init = function(){
	$("#options input.option_name").keyup(function(event){
		$(this).parents('tr.content').prev('tr.option_header').find('h3.title').text($(this).val());
	});
};

// Инициализация комплектаций
bundle_init = function(){
	$("#bundles input.bundle_name").keyup(function(event){
		$(this).parents('tr.content').prev('tr.bundle_header').find('h3.title').text($(this).val());
	});
};

// Реинициализация аккордиона
option_reinit = function() {
	option_init();
};

// Реинициализация аккордиона
bundle_reinit = function() {
	bundle_init();
};

$(document).ready(function(){
	// обработка клика на кнопку добавления новой опции
	$("#new_option_add").click(function(){
		/**
		 * Клонируем шаблон опции
		 */
		a = $(".option_template .option").clone();
		a.appendTo('#options .fastlist');
		/**
		 * Изменяем заголовок на указанный
		 */
		if($('#new_option_name').val() != '') {
			title = $('#new_option_name').val();
			$('#new_option_name').val('');
		} else {
			title = option_name;
		}
		a.find("h3.title").text(title);
		a.find('.option_name').val(title);
		a.find('input.option_del').click(function(){
			$(this).parents('tr.option').remove();
			option_reinit();
			return false;
		});
		a.find('h3.title').click(function(){
			elem = $(this).parents('tr.option_header').next();
			if(elem.hasClass('visible')) {
				elem.removeClass('visible');
			} else {
				elem.addClass('visible');
			}
		});
		$('#options .sorttable').tableDnD({
			dragHandle: "dragHandle"
		});
		option_reinit();
		return false;
	});

	$('.option_del').click(function(){
		/**
		 * Основное элемент
		 */
		$(this).parents('tr.option').remove();
		option_reinit();
		return false;
	});

	$('.option_extend').click(function(){
		var parent = $(this).parents('tr.content');
		if(parent.find('.extend').hasClass('visible')) {
			parent.find('.extend').removeClass('visible');
		} else {
			parent.find('.extend').addClass('visible');
			parent.find('div.values .extend').removeClass('visible');
		}
		return false;
	});

	$('button.option_value_add').click(function(){
		id = $(this).val();
		a = $('.value_template').clone();
		a.appendTo('#values_' + id);
		a.removeClass('value_template');
		/**
		 * идентификатор в скрытом поле
		 */
		a.find('input.option_value_ids').attr('name','option_value_ids[' + id + '][]').removeClass('option_value_ids');
		a.find('input.option_values_title').attr('name','option_values_title[' + id + '][]').removeClass('option_values_title');
		a.find('input.option_values_value').attr('name','option_values_value[' + id + '][]').removeClass('option_values_value');
		a.find('input.option_values_class').attr('name','option_values_class[' + id + '][]').removeClass('option_values_class');
		a.find('input.option_values_image').attr('name','option_values_image[' + id + '][]').removeClass('option_values_image');
		a.find('input.option_value_del').click(function(){
			$(this).parents('div.values').remove();
			return false;
		});
		a.find('input.option_value_extend').click(function(){
			if($(this).parents('div.values').find('.extend').hasClass('visible')) {
				$(this).parents('div.values').find('.extend').removeClass('visible');
			} else {
				$(this).parents('div.values').find('.extend').addClass('visible');
			}
			return false;
		});
		return false;
	});

	$('.option_value_del').click(function(){
		$(this).parents('div.values').remove();
		return false;
	});

	$('.option_value_extend').click(function(){
		if($(this).parents('div.values').find('.extend').hasClass('visible')) {
			$(this).parents('div.values').find('.extend').removeClass('visible');
		} else {
			$(this).parents('div.values').find('.extend').addClass('visible');
		}
		return false;
	});

	// обработка клика на кнопку добавления новой комплектации
	$("#new_bundle_add").click(function(){
		/**
		 * Клонируем шаблон комплектации
		 */
		a = $(".bundle_template .bundle").clone();
		a.appendTo('#bundles .fastlist');
		/**
		 * Изменяем заголовок на указанный
		 */
		if($('#new_bundle_name').val() != '') {
			title = $('#new_bundle_name').val();
			$('#new_bundle_name').val('');
		} else {
			title = bundle_name;
		}
		a.find("h3.title").text(title);
		a.find('.bundle_name').val(title);
		a.find('input.bundle_del').click(function(){
			$(this).parents('tr.bundle').remove();
			bundle_reinit();
			return false;
		});
		a.find("h3.title").click(function(){
			elem = $(this).parents('tr.bundle_header').next();
			if(elem.hasClass('visible')) {
				elem.removeClass('visible');
			} else {
				elem.addClass('visible');
			}
		});
		$('#bundles .sorttable').tableDnD({
			dragHandle: "dragHandle"
		});
		bundle_reinit();
		return false;
	});

	$('.bundle_del').click(function(){
		$(this).parents('tr.bundle').remove();
		bundle_reinit();
		return false;
	});

	$('.new_attribute_add').click(function(){
		/**
		 * Определяем контейнер для параметров
		 */
		attr = $(this).parents('div.attribute_group').find('div.attribute_group_outer');
		/**
		 * Клонируем шаблон параметра
		 */
		a = $(".attribute_template").clone();
		/**
		 * Добавляем шаблон параметра в контейнер
		 */
		attr.append(a);

		a.removeClass('attribute_template');
		a.css('display','block');

		a.find('input.attribute_del').click(function(){
			$(this).parents('div.attribute').remove();
			return false;
		});
		return false;
	});

	$('.attribute_del').click(function(){
		$(this).parents('div.attribute').remove();
		return false;
	});

	$('#docManagerPane .tab').click(function(){
		if($('#tabAttributes').css('display') == 'block') {
			$('#attributes').css('visibility', 'visible');
		} else if($('#tabOptions').css('display') == 'block') {
			option_reinit();
			$('#options').css('visibility', 'visible');
		} else if ($('#tabBundles').css('display') == 'block') {
			bundle_reinit();
			$('#bundles').css('visibility', 'visible');
		}
	});

	if($('#tabAttributes').css('display') == 'block') {
		$('#attributes').css('visibility', 'visible');
	} else if($('#tabOptions').css('display') == 'block') {
		option_init();
		$('#options').css('visibility', 'visible');
	} else if ($('#tabBundles').css('display') == 'block') {
		bundle_init();
		$('#bundles').css('visibility', 'visible');
	}

	/**
	 * Работа с подсказками
	 */
	$('button.tipclear').click(function(){
		tipId = $(this).attr('name');
		tipVal = $(this).text();
		$('#' + tipId).val('');
		$('#info_' + tipId).html(tipVal);
		return false;
	});
	
	$('#options .option_header h3').click(function(){
		elem = $(this).parents('tr.option_header').next();
		if(elem.hasClass('visible')) {
			elem.removeClass('visible');
		} else {
			elem.addClass('visible');
		}
	});

	$('#bundles .bundle_header h3').click(function(){
		elem = $(this).parents('tr.bundle_header').next();
		if(elem.hasClass('visible')) {
			elem.removeClass('visible');
		} else {
			elem.addClass('visible');
		}
	});

	$('.sorttable').tableDnD({
		dragHandle: "dragHandle"
	});

});


/**
 * Выбор изображения. Код взят из MODX от виджета Image
 */
var lastImageCtrl;
var lastFileCtrl;
function OpenServerBrowser(url, width, height ) {
	var iLeft = (screen.width  - width) / 2 ;
	var iTop  = (screen.height - height) / 2 ;

	var sOptions = 'toolbar=no,status=no,resizable=yes,dependent=yes' ;
	sOptions += ',width=' + width ;
	sOptions += ',height=' + height ;
	sOptions += ',left=' + iLeft ;
	sOptions += ',top=' + iTop ;

	var oWindow = window.open( url, 'FCKBrowseWindow', sOptions ) ;
}
function BrowseServer(ctrl) {
	lastImageCtrl = ctrl;
	var w = screen.width * 0.7;
	var h = screen.height * 0.7;
	OpenServerBrowser('/manager/media/browser/mcpuk/browser.html?Type=images&Connector=/manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath=/', w, h);
}

function BrowseFileServer(ctrl) {
	lastFileCtrl = ctrl;
	var w = screen.width * 0.7;
	var h = screen.height * 0.7;
	OpenServerBrowser('/manager/media/browser/mcpuk/browser.html?Type=files&Connector=/manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath=/', w, h);
}

function SetUrl(url, width, height, alt){
	if(lastFileCtrl) {
		var c = document.mutate[lastFileCtrl];
		if(c) c.value = url;
		lastFileCtrl = '';
	} else if(lastImageCtrl) {
		var c = document.mutate[lastImageCtrl];
		if(c) c.value = url;
		lastImageCtrl = '';
	} else {
		return;
	}
}