<?php

/**
 * @name SBShop
 * @author Mukharev Maxim
 * @version 0.1a
 * 
 * @desription
 * 
 * Электронный магазин для MODx
 * 
 * Экшен сниппета электронного магазина: Вывод товара
 * 
 */

class product_mode {
	
	protected $aTemplates; // Массив с набором шаблонов
	
	/**
	 * Конструктор
	 */
	public function __construct($sMode) {
		global $modx;
		/**
		 * Задаем набор шаблонов
		 */
		$this->aTemplates = $modx->sbshop->getSnippetTemplate('product');
		/**
		 * Вывод списка товаров
		 */
		$modx->setPlaceholder('sb.product',$this->outputProduct());
		
	}
	
	/**
	 * Вывод списка товаров для текущей категории
	 */
	public function outputProduct() {
		global $modx;
		/**
		 * Инициализируем переменную для вывода результата
		 */
		$sOutput = '';
		/**
		 * Получение данных товара
		 */
		$aProduct = $modx->sbshop->oGeneralProduct->getAttributes();
		/**
		 * Если не установлен расширенный заголовок
		 */
		if($aProduct['longtitle'] == '') {
			$aProduct['longtitle'] = $aProduct['title'];
		}
		/**
		 * Установка заголовков
		 */
		$modx->setPlaceholder('sb.product.title',$aProduct['title']);
		$modx->setPlaceholder('sb.product.longtitle',$aProduct['longtitle']);
		/**
		 * Подготавливаем плейсхолдеры
		 */
		$aRepl = $modx->sbshop->arrayToPlaceholders($aProduct);
		/**
		 * Если установлен параметр "Есть в наличии"
		 */
		if($modx->sbshop->oGeneralProduct->getAttribute('existence')) {
			/**
			 * Устанавливаем заголовок "есть в наличии" из языкового файла
			 */
			$aRepl['[+sb.existence+]'] = $modx->sbshop->lang['product_existence_title'];
		} else {
			/**
			 * Устанавливаем заголовок "нет в наличии" из языкового файла
			 */
			$aRepl['[+sb.existence+]'] = $modx->sbshop->lang['product_notexistence_title'];
		}
		/**
		 * Обрабатываем описание и делим на блоки
		 */
		$aReplBlocks = $modx->sbshop->multiarrayToPlaceholders(explode('<!-- ### -->',$modx->sbshop->oGeneralProduct->getAttribute('description')),'num','sb.description.');
		/**
		 * Добавляем изображения
		 */
		$aReplBlocks = array_merge($aReplBlocks,$modx->sbshop->multiarrayToPlaceholders($modx->sbshop->oGeneralProduct->getAllImages(),'num','sb.image.'));
		/**
		 * Подготавливает массив миниатюры
		 */
		$aImages = $modx->sbshop->oGeneralProduct->getImagesByKey('x104');
		/**
		 * Переменная для блока миниатюр
		 */
		$sImages = '';
		/**
		 * Обрабатываем каждую картинку
		 */
		foreach ($aImages as $sImage) {
			/**
			 * Вставляем линк
			 */
			$sImages .= str_replace('[+sb.image+]', $sImage, $this->aTemplates['thumbs_row']);
		}
		/**
		 * Вставляем картинки в контейнер
		 */
		$sImages = str_replace('[+sb.wrapper+]', $sImages, $this->aTemplates['thumbs_outer']);
		/**
		 * Добавляем в плейсхолдеры блок с миниатюрами
		 */
		$aReplBlocks['[+sb.thumbs+]'] = $sImages;
		/**
		 * Переменная для опций
		 */
		$sOptions = '';
		/**
		 * Получаем опции товара
		 */
		$aOptions = $modx->sbshop->oGeneralProduct->getOptionNames();
		/**
		 * Опции раздела
		 */
		$aGeneralOptions = $modx->sbshop->oGeneralCategory->getOptionNames();
		/**
		 * Обрабатываем каждую опцию
		 */
		foreach ($aOptions as $aOption) {
			/**
			 * Если опция не является скрытой
			 */
			if(!$aOption['hidden']) {
				/**
				 * Значения
				 */
				$sOptRaw = '';
				/**
				 * Если есть изображение
				 */
				if($aOption['image']) {
					$aOption['image.url'] = $aOption['image'];
					$aOption['image'] = '<img src="' . $aOption['image'] . '">';
				} elseif($aGeneralOptions[$aOption['title']]['image']) {
					/**
					 * Наследование изображения от раздела
					 */
					$aOption['image.url'] = $aGeneralOptions[$aOption['title']]['image'];
					$aOption['image'] = '<img src="' . $aGeneralOptions[$aOption['title']]['image'] . '">';
				}
				/**
				 * Если не указан класс и есть данные у раздела
				 */
				if(!$aOption['class'] && $aGeneralOptions[$aOption['title']]['class']) {
					$aOption['class'] = $aGeneralOptions[$aOption['title']]['class'];
				}
				/**
				 * Массив значений
				 */
				$aValues = $modx->sbshop->oGeneralProduct->getValuesByOptionName($aOption['title']);
				/**
				 * Если есть только одно значение
				 */
				if(count($aValues) == 1) {
					/**
					 * Получаем первую запись
					 */
					$aValue = current($aValues);
					/**
					 * Если значение находится в списке исключаемых
					 */
					if(in_array($aValue['id'], $modx->sbshop->config['hide_option_values'])) {
						$aValue['title'] = '';
					}
					/**
					 * Если значение равно null, то устанавливаем цену опции
					 */
					if($aValue['value'] != 'null') {
						$aValue['price'] = $aValue['value'];
						$aValue['price'] = $modx->sbshop->setPriseIncrement($aValue['price'], $aValue['price_add']);
					} else {
						$aValue['price'] = '';
					}
					/**
					 * Создаем плейсхолдеры
					 */
					$aReplVal = $modx->sbshop->arrayToPlaceholders($aValue);
					/**
					 * Вставляем данные в шаблон
					 */
					$sOptRaw = str_replace(array_keys($aReplVal), array_values($aReplVal), $this->aTemplates['single_option_row']);
				} else {
					/**
					 * Обрабатываем значения
					 */
					foreach ($aValues as $aValue) {
						if($aValue['value'] != 'null') {
							$aValue['price'] = $aValue['value'];
							$aValue['price'] = $modx->sbshop->setPriseIncrement($aValue['price'], $aValue['price_add']);
						} else {
							$aValue['price'] = '';
						}
						$aReplVal = $modx->sbshop->arrayToPlaceholders($aValue);
						/**
						 * Вставляем данные в шаблон
						 */
						$sOptRaw .= str_replace(array_keys($aReplVal), array_values($aReplVal), $this->aTemplates['multi_option_row']);
					}
				}
				/**
				 * Плейсхолдеры для опции
				 */
				$aReplOpt['[+sb.wrapper+]'] = $sOptRaw;
				$aReplOpt = array_merge($aReplOpt,$modx->sbshop->arrayToPlaceholders($aOption,'sb.option.'));
				/**
				 * Если есть подсказка
				 */
				if($aOption['tip']) {
					$aReplTip = array(
						'[+sb.id+]' => $aOption['tip'],
					);
					$sReplOpt = str_replace(array_keys($aReplTip), array_values($aReplTip), $this->aTemplates['option_tip']);
				} elseif ($aGeneralOptions[$aOption['title']]['tip']) {
					$aReplTip = array(
						'[+sb.id+]' => $aGeneralOptions[$aOption['title']]['tip'],
					);
					$sReplOpt = str_replace(array_keys($aReplTip), array_values($aReplTip), $this->aTemplates['option_tip']);
				} else {
					$sReplOpt = "";
				}
				$aReplOpt['[+sb.option.tip+]'] = $sReplOpt;
				/**
				 * Если значение одно
				 */
				if(count($aValues) == 1) {
					/**
					 * Используем контейнер для одного значения
					 */
					$sOptions .= str_replace(array_keys($aReplOpt), array_values($aReplOpt), $this->aTemplates['single_option_outer']);
				} else {
					/**
					 * Используем контейнер для нескольких значений
					 */
					$sOptions .= str_replace(array_keys($aReplOpt), array_values($aReplOpt), $this->aTemplates['multi_option_outer']);
				}
			}
		}
		/**
		 * Если опции есть
		 */
		if($sOptions != '') {
			/**
			 * Вставляем в общий контейнер
			 */
			$sOptions = str_replace('[+sb.wrapper+]',$sOptions,$this->aTemplates['options_outer']);
		}
		/**
		 * Добавляем плейсхолдер
		 */
		$aReplBlocks['[+sb.options+]'] = $sOptions;
		/**
		 * Переменная для комплектаций
		 */
		$sBundles = '';
		/**
		 * Получаем список комплектаций
		 */
		$aBundles = $modx->sbshop->oGeneralProduct->getBundleList();
		/**
		 * Если комплектации существуют
		 */
		if(count($aBundles) > 0) {
			/**
			 * Обрабатываем каждую запись
			 */
			foreach ($aBundles as $iBundleId => $aBundle) {
				/**
				 * Если это индивидуальная комплектация
				 */
				if($aBundle['title'] === 'personal') {
					$iBundleId = 'personal';
					$aBundle = array (
						'title' => $modx->sbshop->lang['bundle_personal_title'],
						'price' => $modx->sbshop->oGeneralProduct->getAttribute('price_full'),
						'options' => array(),
						'description' => $modx->sbshop->lang['bundle_personal_description'],
					);
				}
				/**
				 * Плейсхолдеры для замены
				 */
				/**
				 * Добавляем идентификатор в плейсхолдеры
				 */
				$aBundle['id'] = $iBundleId;
				/**
				 * Массив опций в комплектации
				 */
				$aBundleOptions = array();
				/**
				 * Обрабатываем список опций в комплектации
				 */
				foreach ($aBundle['options'] as $sOptionKey => $sOptionVal) {
					/**
					 * Если опция не является скрытой
					 */
					if(!$modx->sbshop->oGeneralProduct->isOptionHidden($sOptionKey)) {
						$aBundleOptions[$sOptionKey] = $sOptionVal;
					}
				}
				/**
				 * Подготовка массива опций в JSON
				 */
				$aBundle['options.js'] = json_encode($aBundleOptions);
				/**
				 * Если стоимость пустая
				 */
				if($aBundle['price'] === '') {
					/**
					 * Определяем стоимость по факту - товар + опции
					 */
					$aBundle['price_full'] = $modx->sbshop->oGeneralProduct->getAttribute('price_full') + $modx->sbshop->oGeneralProduct->getPriceByOptions($aBundle['options']);
				} elseif(substr($aBundle['price'],0,1) === '+') {
					/**
					 * Стоимость задана и нам нужно определить правило ее формирования.
					 * Если первый символ - "+"
					 */
					$aBundle['price_full'] = $modx->sbshop->oGeneralProduct->getAttribute('price_full') + substr($aBundle['price'], 1);
				} else {
					$aBundle['price_full'] = $aBundle['price'];
				}
				/**
				 * Обработка надбавки
				 */
				$aBundle['price_full'] = $modx->sbshop->setPriseIncrement($aBundle['price_full'], $aBundle['price_add']);
				/**
				 * Делаем набор плейсхолдеров
				 */
				$aBundleRepl = $modx->sbshop->arrayToPlaceholders($aBundle,'sb.bundle.');
				/**
				 * Если опции в комплектации есть
				 */
				if(count($aBundle['options']) > 0) {
					/**
					 * Обрабатываем каждую опцию
					 */
					$aBundlOptions = array();
					foreach ($aBundle['options'] as $iOptKey => $iOptVal) {
						/**
						 * Плейсхолдеры для опций в коплектации
						 */
						$aOptionRepl = $modx->sbshop->arrayToPlaceholders($modx->sbshop->oGeneralProduct->getNamesByNameIdAndValId($iOptKey,$iOptVal));
						/**
						 * Разделитель между опцией и значением
						 */
						$aOptionRepl['[+sb.separator+]'] = $modx->sbshop->config['option_separator'];
						/**
						 * Если значение находится в списке скрываемых
						 */
						if(in_array($iOptVal, $modx->sbshop->config['hide_option_values'])) {
							/**
							 * Очищаем разделитель и значение
							 */
							$aOptionRepl['[+sb.value+]'] = '';
							$aOptionRepl['[+sb.separator+]'] = '';
						}
						/**
						 * Создаем ряд
						 */
						$aBundlOptions[] = str_replace(array_keys($aOptionRepl), array_values($aOptionRepl), $this->aTemplates['bundle_option_row']);
					}
					/**
					 * Если опции есть
					 */
					if(count($aBundlOptions) > 0) {
						/**
						 *
						 */
						$sBundlOptions = str_replace('<br>','',implode('',$aBundlOptions));
					} else {
						$sBundlOptions = '';
					}
					/**
					 * Устанавливаем
					 */
					$aBundleRepl['[+sb.bundle.options+]'] = str_replace('[+sb.wrapper+]', $sBundlOptions, $this->aTemplates['bundle_option_outer']);
				} else {
					$aBundleRepl['[+sb.bundle.options+]'] = '';
				}
				/**
				 * Добавляем ряд
				 */
				$sBundles .= str_replace(array_keys($aBundleRepl), array_values($aBundleRepl), $this->aTemplates['bundle_row']);
			}
			/**
			 * Вставляем в контейнер и добавляем плейсхолдер
			 */
			$aReplBlocks['[+sb.bundles+]'] = str_replace('[+sb.wrapper+]', $sBundles, $this->aTemplates['bundle_outer']);
		} else {
			$aReplBlocks['[+sb.bundles+]'] = '';
		}
		/**
		 * Получаем информацию по базовой комплектации
		 */
		$aBaseBundle = $modx->sbshop->oGeneralProduct->getBaseBundle();
		/**
		 * Если есть опции
		 */
		if(count($aBaseBundle) > 0) {
			/**
			 * Обрабатываем каждую опцию
			 */
			$aBaseBundleOptions = array();
			foreach ($aBaseBundle as $iOptKey => $iOptVal) {
				/**
				 * Плейсхолдеры для опций в коплектации
				 */
				$aOptionRepl = $modx->sbshop->arrayToPlaceholders($modx->sbshop->oGeneralProduct->getNamesByNameIdAndValId($iOptKey,$iOptVal));
				/**
				 * Разделитель между опцией и значением
				 */
				$aOptionRepl['[+sb.separator+]'] = $modx->sbshop->config['option_separator'];
				/**
				 * Если значение находится в списке скрываемых
				 */
				if(in_array($iOptVal, $modx->sbshop->config['hide_option_values'])) {
					/**
					 * Очищаем разделитель и значение
					 */
					$aOptionRepl['[+sb.value+]'] = '';
					$aOptionRepl['[+sb.separator+]'] = '';
				}
				/**
				 * Создаем ряд с опцией
				 */
				$aBaseBundleOptions[] = str_replace(array_keys($aOptionRepl), array_values($aOptionRepl), $this->aTemplates['bundle_option_row']);
			}
			/**
			 * Делаем вставку в контенер для опций
			 */
			$sBaseBundleRepl = str_replace('[+sb.wrapper+]', implode('',$aBaseBundleOptions), $this->aTemplates['bundle_option_outer']);
		} else {
			$sBaseBundleRepl = '';
		}
		/**
		 * Если нет дополнительных комплектаций
		 */
		if(count($aBundles) == 0) {
			/**
			 * Вставляем в контенер и выводим
			 */
			$aReplBlocks['[+sb.base_bundle+]'] = str_replace('[+sb.bundle.options+]', $sBaseBundleRepl, $this->aTemplates['single_bundle_base']);
		} else {
			$aReplBlocks['[+sb.base_bundle+]'] = str_replace('[+sb.bundle.options+]', $sBaseBundleRepl, $this->aTemplates['multi_bundle_base']);
		}
		/**
		 * Получаем набор параметров товара
		 */
		$aAttributes = $modx->sbshop->oGeneralProduct->getExtendVisibleAttributes();
		/**
		 * Получаем набор ключей параметров раздела
		 */
		$aGeneralAttributes = array_keys($modx->sbshop->oGeneralCategory->getExtendAttributes());
		/**
		 * Массив отсортированных параметров
		 */
		$aGeneralOrderAttrubutes = array();
		/**
		 * Обрабатываем каждый параметр раздела
		 */
		foreach ($aGeneralAttributes as $sKey) {
			/**
			 * Если параметр входит в список раздела
			 */
			if(isset($aAttributes[$sKey])) {
				$aGeneralOrderAttrubutes[$sKey] = $aAttributes[$sKey];
			}
		}
		/**
		 * Обновляем массив параметров
		 */
		$aAttributes = $aGeneralOrderAttrubutes;
		/**
		 * Ряды значений
		 */
		$sAttrRows = '';
		/**
		 * Обрабатываем каждый параметр
		 */
		foreach ($aAttributes as $aAttrVal) {
			$aAttrRepl = $modx->sbshop->arrayToPlaceholders($aAttrVal);
			/**
			 * Формируем ряд
			 */
			$sAttrRows .= str_replace(array_keys($aAttrRepl), array_values($aAttrRepl), $this->aTemplates['attribute_row']);
		}
		/**
		 * Вставляем параметры в контейнер
		 */
		$aReplBlocks['[+sb.attributes+]'] = str_replace('[+sb.wrapper+]', $sAttrRows, $this->aTemplates['attribute_outer']);
		/**
		 * Если товар есть в наличии
		 */
		if($modx->sbshop->oGeneralProduct->getAttribute('existence')) {
			/**
			 * Делаем вставку блоков в основной шаблон
			 */
			$sOutput = str_replace(array_keys($aReplBlocks),array_values($aReplBlocks),$this->aTemplates['product']);
		} else {
			/**
			 * Делаем вставку блоков в основной шаблон
			 */
			$sOutput = str_replace(array_keys($aReplBlocks),array_values($aReplBlocks),$this->aTemplates['absent_product']);
		}
		/**
		 * Делаем вставку параметров
		 */
		$sOutput = str_replace(array_keys($aRepl),array_values($aRepl),$sOutput);
		/**
		 * Возвращаем результат
		 */
		return $sOutput;
	}
}

?>