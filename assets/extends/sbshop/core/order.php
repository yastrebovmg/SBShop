<?php

/**
 * @author Mukharev Maxim
 * @version 0.1a
 * 
 * @desription
 * 
 * Электронный магазин для MODx
 * 
 * Объект заказа
 */

class SBOrder {
	
	protected $aOrderData; // основные данные заказа
	protected $aOrderDataKeys; // ключи для основных данных заказа
	protected $aProducts; // информация о купленных товарах / количестве / конфигурации и т.д.
	protected $aOptions; // настраиваемые параметры заказа, определяемые процедурой оформления
	protected $oComments; // комментарии к заказу
	/**
	 * @var SBProductList
	 */
	protected $oProductList; // список товаров, которые лежат в корзине
	
	public function __construct($aParam = false) {
		/**
		 * Стандартный массив данных заказа
		 */
		$this->aOrderData = array(
			'id' => null,
			'user' => null,
			'date_add' => null,
			'date_edit' => null,
			'date_next' => null,
			'ip' => null,
			'status' => null,
			'price' => null,
			'products' => null,
			'options' => null,
			'comments' => null,
		);
		/**
		 * Ключи стандартных данных
		 */
		$this->aOrderDataKeys = array_keys($this->aOrderData);
		/**
		 * Инициализация массива параметров товаров в заказе
		 */
		$this->aProducts = array();
		/**
		 * Настройки заказа
		 */
		$this->aOptions = array();
		/**
		 * инициализация объекта управления комментариями
		 */
		$this->oComments = new SBTimeList();
		/**
		 * Если переданы параметры
		 */
		if(is_array($aParam)) {
			/**
			 * Устанавливаем параметры заказа по переданному массиву
			 */
			$this->setAttributes($aParam);
			/**
			 * Загружаем список заказанных товаров
			 */
			$this->oProductList = new SBProductList('',$this->getProductIds());
		}
	}
	
	/**
	 * Получение текущих данных корзины
	 */
	public function load() {
		/**
		 * Если есть ифнормация о заказе в сесии
		 */
		if(isset($_SESSION['sb.order'])) {
			/**
			 * Записываем ее
			 */
			$this->setAttributes($_SESSION['sb.order']);
			/**
			 * Загружаем список заказанных товаров
			 */
			$this->oProductList = new SBProductList('',$this->getProductIds());
		}
	}

	/**
	 * Загрузка заказа по указанному идентификатору
	 * @global <type> $modx
	 * @param <type> $iOrderId
	 * @return <type>
	 */
	public function loadById($iOrderId) {
		global $modx;
		/**
		 * Если идентификатор неправильный
		 */
		if($iOrderId == 0) {
			return false;
		}
		/**
		 * Делаем запрос
		 */
		$rs = $modx->db->select('*',$modx->getFullTableName('sbshop_orders'),'order_id = ' . $iOrderId);
		$aRows = $modx->db->makeArray($rs);
		/**
		 * Устанавливаем значение по первой записи
		 */
		$this->setAttributes($aRows[0]);
		/**
		 * Загружаем список заказанных товаров
		 */
		$this->oProductList = new SBProductList('',$this->getProductIds());
	}

	/**
	 * Установка заданного параметра заказа
	 * @param unknown_type $sParamName
	 * @param unknown_type $sParamValue
	 */
	public function setAttribute($sParamName, $sParamValue) {
		$this->setAttributes(array($sParamName => $sParamValue));
	}
	
	/**
	 * Установка основных параметров заказа
	 * @param unknown_type $aAttributes
	 */
	public function setAttributes($aAttributes = false) {
		/**
		 * Если передан массив значений
		 */
		if(is_array($aAttributes)) {
			/**
			 * Обрабатываем каждое значение
			 */
			foreach ($aAttributes as $sKey => $sVal) {
				/**
				 * Удаляем префикс category_ у ключа
				 */
				$sKey = str_replace('order_','',$sKey);
				/**
				 * Отсекаем лишние параметры
				 */
				if(in_array($sKey,$this->aOrderDataKeys)) {
					
					$this->aOrderData[$sKey] = $sVal;
				}
				/**
				 * Индивидуальная обработка
				 */
				switch ($sKey) {
					case 'products':
						$this->unserializeProducts($sVal);
					break;
					case 'options':
						$this->unserializeOptions($sVal);
					break;
					case 'comments':
						$this->unserializeComments($sVal);
					break;
				}
			}
		}
	}
	
	/**
	 * Получение заданного параметра категории
	 * @param $sParamName
	 * @return unknown_type
	 */
	public function getAttribute($sParamName) {
		return array_pop($this->getAttributes($sParamName));
	}
	
	/**
	 * Получение параметров заказа
	 * @param $aParams
	 * @return unknown_type
	 */
	public function getAttributes($aParams = false) {
		/**
		 * Если параметры не заданы, возвращаем весь массив
		 */
		if($aParams == false) {
			return $this->aOrderData;
		}
		/**
		 * Если передана строка, то делаем массив
		 */
		if(!is_array($aParams)) {
			$aParams = array($aParams);
		}
		/**
		 * Выбираем заданные параметры из массива
		 */
		$aResult = array();
		foreach ($aParams as $sParam) {
			if(isset($this->aOrderData[$sParam])) {
				$aResult[$sParam] = $this->aOrderData[$sParam];
			}
		}
		return $aResult;
	}
	
	/**
	 * Установка заданного параметра для продукта в заказе
	 * @param unknown_type $iProductId
	 * @param unknown_type $sParamName
	 * @param unknown_type $sParamValue
	 */
	/*public function setProduct($iProductId,$sParamName, $sParamValue) {
		$this->setProducts($iProductId,array($sParamName => $sParamValue));
	}*/

	public function addProduct($iProductId,$aParams = false) {
		/**
		 * Если передан массив значений
		 */
		if(is_array($aParams)) {
			/**
			 * Если передана индивидуальная комплектация, но нет значений
			 */
			if(isset($aParams['bundle']) and ($aParams['bundle'] === 'base' or $aParams['bundle'] === 'personal') and !isset($aParams['sboptions'])) {
				/**
				 * Удаляем индивидуальную комплектацию
				 */
				unset($aParams['bundle']);
			}
			/**
			 * Если передана комплектация или опции
			 */
			if(isset($aParams['bundle']) or (isset($aParams['sboptions']) and count($aParams['sboptions']) > 0)) {
				/**
				 * Загружаем заказанный товар
				 */
				$oProduct = new SBProduct();
				$oProduct->load($iProductId);
				/**
				 *
				 */
				/**
				 * Переменная для сбора идентификатора
				 */
				$aOptionRows = array();
				/**
				 * Переменная для сбора стоимости дополнительных опций
				 */
				$iPrice = 0;
				/**
				 * Если установлена комплектация
				 */
				if(isset($aParams['bundle']) and $aParams['bundle'] !== 'base' and $aParams['bundle'] !== 'personal') {
					/**
					 * Получаем список опций в комплектации
					 */
					$aBundleParams = $oProduct->getBundleOptions($aParams['bundle']);
					/**
					 * Если есть заказанные опции
					 */
					$aBundleOptions = array();
					if(is_array($aParams['sboptions']) and count($aParams['sboptions']) > 0) {
						/**
						 * Определяем какие дополнительны опции были заказаны
						 */
						$aBundleOptions = array_diff_assoc($aParams['sboptions'],$aBundleParams);
						/**
						 * Объединяем массивы значений
						 */
						$aParams['sboptions'] = $aBundleParams + $aParams['sboptions'];
						/**
						 * Получаем стоимость опций
						 */
						$iPrice = $oProduct->getPriceByOptions($aBundleOptions);
					} else {
						$aBundleOptions = array();
						$aParams['sboptions'] = $aBundleParams;
						/**
						 * Стоимость опций равна 0
						 */
						$iPrice = 0;
					}
				} else {
					/**
					 * Получаем значение стоимости опций
					 */
					$iPrice = $oProduct->getPriceByOptions($aParams['sboptions']);
				}
				/**
				 * Обрабатываем каждую опцию
				 */
				foreach ($aParams['sboptions'] as $iKey => $iVal) {
					/**
					 * Если значение опции не равно 'null'
					 */
					if($oProduct->getValueByNameIdAndValId($iKey,$iVal) != 'null') {
						/**
						 * Добавляем значение
						 */
						$aOptionRows[] = $iKey . ':' . $iVal;
					} else {
						/**
						 * Удаляем опцию из списка
						 */
						unset($aParams['sboptions'][$iKey]);
					}
				}
				/**
				 * Формируем новый идентификатор
				 */
				$iProductId .= '.' . implode('.', $aOptionRows);
				/**
				 * Добавляем дополнительную стоимость в настройки заказа
				 */
				$this->aProducts[$iProductId]['options_price'] = $iPrice;
			}
			/**
			 * Обрабатываем каждое значение
			 */
			foreach ($aParams as $sKey => $sVal) {
				/**
				 * Если параметр - quantity (количество) и он установлен
				 */
				if($sKey == 'quantity' and isset($this->aProducts[$iProductId]['quantity'])) {
					/**
					 * Суммируем имеющееся значение
					 */
					$this->aProducts[$iProductId]['quantity'] += $sVal;
				} else {
					/**
					 * Добавляем параметры
					 */
					$this->aProducts[$iProductId][$sKey] = $sVal;
				}
			}
		}
	}

	/**
	 * Установка параметров покупаемых товаров в заказе
	 * @param unknown_type $iProductId
	 * @param unknown_type $aProducts
	 */
	public function setProduct($iProductId,$aParams = false) {
		/**
		 * Если передан массив значений
		 */
		if(is_array($aParams)) {
			/**
			 * Если товар существует
			 */
			if($this->isProductExist($iProductId)) {
				/**
				 * Обрабатываем каждое значение
				 */
				foreach ($aParams as $sKey => $sVal) {

					/**
					 * Добавляем параметры
					 */
					$this->aProducts[$iProductId][$sKey] = $sVal;
				}
			}
			/**
			 * Если количество товара равно 0
			 */
			if($this->aProducts[$iProductId]['quantity'] == 0) {
				/**
				 * Удаляем товар из корзины
				 */
				$this->deleteProduct($iProductId);
			}
		}
	}

	public function isProductExist($iProductId) {
		if(isset($this->aProducts[$iProductId])) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Получить товар из корзины по идентификатору
	 * @param <type> $iId
	 */
	public function getProduct($iSetId) {
		/**
		 * Получаем чистый идентификатор без примесей опций
		 */
		$iProductId = $this->getProductIdBySetId($iSetId);
		/**
		 * Получаем товар
		 */
		return $this->oProductList->getProductById($iProductId);
	}

	public function getOrderSetInfo($iSetId) {
		return $this->aProducts[$iSetId];
	}

	/**
	 * Получение информации о товарах в заказе
	 */
	public function getProducts() {
		return $this->aProducts;
	}

	public function getProductPriceBySetId($iSetId) {
		global $modx;
		/**
		 * Получаем товар
		 */
		$oProduct = $this->getProduct($iSetId);
		/**
		 * Информация о заказе
		 */
		$aOrderInfo = $this->getOrderSetInfo($iSetId);
		/**
		 * Если не указана комплектация
		 */
		if(!isset($aOrderInfo['bundle'])) {
			/**
			 * Рассчитываем стоимость - сумма основной стоимости и стоимости опций
			 */
			$fPrice = $oProduct->getAttribute('price_full') + $aOrderInfo['options_price'];
		} elseif ($aOrderInfo['bundle'] === 'personal') {
			/**
			 * Индивидуальная комплектация. Суммируем цену товара и опций
			 */
			$fPrice = $oProduct->getAttribute('price_full') + $aOrderInfo['options_price'];
		} else {
			/**
			 * Любая другая комплектация. Берем стоимость комплектации
			 */
			$aBundle = $oProduct->getBundleById($aOrderInfo['bundle']);
			/**
			 * Если стоимость пустая
			 */
			if($aBundle['price'] === '') {
				/**
				 * Определяем стоимость по факту - товар + опции
				 */
				$fPrice = $oProduct->getAttribute('price_full') + $oProduct->getPriceByOptions($aBundle['options']);
			} elseif(substr($aBundle['price'],0,1) === '+') {
				/**
				 * Если первый символ - "+"
				 */
				$fPrice = $oProduct->getAttribute('price_full') + substr($aBundle['price'], 1);
			} else {
				$fPrice = $aBundle['price'];
			}
			/**
			 * Обработка надбавки
			 */
			if($aBundle['price_add'] != '') {
				$sPriceAdd = $aBundle['price_add'];
				/**
				 * Разбираем правило надбавки
				 */
				preg_match_all('/([\+\-=]?)([\d,\.]*)([%]?)/', $sPriceAdd, $aPriceAdd);
				/**
				 * Выделяем нужные данные: вид операции, число, тип операции
				 */
				$sAddOperation = $aPriceAdd[1][0];
				$sAddCost = $aPriceAdd[2][0];
				$sAddType = $aPriceAdd[3][0];

				/**
				 * Если тип операции - процент
				 */
				if($sAddType == '%') {
					/**
					 * Считаем стоимость с учетом процента и указанной опреации
					 */
					if($sAddOperation == '' or $sAddOperation == '+') {
						$fPrice = $fPrice * (1 + $sAddCost / 100);
					} elseif($sAddOperation == '-') {
						$fPrice = $fPrice * (1 - $sAddCost / 100);
					} elseif($sAddOperation == '=') {
						$fPrice = $fPrice * ($sAddCost / 100);
					}
				} else {
					/**
					 *Считаем стоимость с учетом указанного значения и операции
					 */
					if($sAddOperation == '' or $sAddOperation == '+') {
						$fPrice = $fPrice + $sAddCost;
					} elseif($sAddOperation == '-') {
						$fPrice = $fPrice - $sAddCost;
					} elseif($sAddOperation == '=') {
						$fPrice = $sAddCost;
					}
				}
				/**
				 * Округляем
				 */
				$fPrice = round($fPrice, $modx->sbshop->config['round_precision']);
			}

		}
		/**
		 * Возвращаем результат
		 */
		return $fPrice;
	}

	/**
	 * Получение количества товара в позиции по идентификатору
	 * @param  $iSetId
	 * @return
	 */
	public function getProductQuantityBySetId($iSetId) {
		return $this->aProducts[$iSetId]['quantity'];
	}

	/**
	 * Получение суммы позиции по идентификатору
	 * @param  $iSetId
	 * @return
	 */
	public function getProductSummBySetId($iSetId) {
		return $this->getProductPriceBySetId($iSetId) * $this->getProductQuantityBySetId($iSetId);
	}

	/**
	 * Получение полной стоимости заказанных товаров
	 * @return float
	 */
	public function getFullPrice() {
		/**
		 * Список идентификаторов настроек товаров
		 */
		$aProductSetIds = $this->getProductSetIds();
		/**
		 * Список идентификаторов товаров
		 */
		$aProductIds = $this->getProductIds();
		/**
		 * Если заказанных товаров нет
		 */
		if(count($aProductSetIds) == 0) {
			$fPrice = 0;
		} else {
			/**
			 * Загружаем список заказанных товаров
			 */
			$this->oProductList = new SBProductList('',$aProductIds);
			/**
			 * Инициализируем массив для суммарной стоимости товаров
			 */
			$aResPrices = array();
			/**
			 * Обрабатываем каждую позицию из идентификаторов товара
			 */
			foreach ($aProductSetIds as $iSetId) {
				$aResPrices[$iSetId] = $this->getProductPriceBySetId($iSetId) * $this->getProductQuantityBySetId($iSetId);
			}
			$fPrice = array_sum($aResPrices);
		}
		return $fPrice;
	}

	/**
	 * Получение списка уникальный идентификаторов заказанных товаров
	 * @return array
	 */
	public function getProductIds() {
		/**
		 * Обрабатываем записи
		 */
		$aResult = array();
		foreach ($this->aProducts as $sKey => $sVal) {
			$aResult[] = intval($sKey);
		}
		return array_unique($aResult);
	}

	/**
	 * Получение списка идентификаторов товаров, включая настройку
	 */
	public function getProductSetIds() {
		return array_keys($this->aProducts);
	}


	public function getProductIdBySetId($iSetId) {
		return intval($iSetId);
	}

	/**
	 * Удаление товара по идентификатору
	 * @param <type> $iProductId
	 */
	public function deleteProduct($iProductId) {
		$this->deleteProducts(array($iProductId));
	}

	/**
	 * Удаление выбранных товаров из корзины
	 * @param <type> $aProductIds
	 */
	public function deleteProducts($aProductIds) {
		/**
		 * Обрабатываем каждый идентификатор
		 */
		foreach ($aProductIds as $iProductId) {
			/**
			 * Если указанный товар есть в корзине
			 */
			if(isset($this->aProducts[$iProductId])) {
				/**
				 * Удаляем информацию о товаре в заказе
				 */
				unset($this->aProducts[$iProductId]);
			}
		}
	}

	/**
	 * Очищаем корзину
	 */
	public function clear() {
		/**
		 * Если в корзине что-то есть
		 */
		if(isset($_SESSION['sb.order'])) {
			/**
			 * Устанавливаем статус "брошенная корзина" статус -10
			 */
			$this->setAttribute('status', '-10');
			/**
			 * Сохраняем информацию статуса
			 */
			$this->save();
			/**
			 * Убираем данные сессии
			 */
			$this->clearSession();
			/**
			 * Сбрасываем все параметры
			 */
			foreach ($this->aOrderDataKeys as $sKey) {
				$this->aOrderData[$sKey] = null;
			}
			/**
			 * Очищаем информацию о заказанных товарах
			 */
			$this->aProducts = array();
			/**
			 * Очищаем информацию о настройках заказа
			 */
			$this->aOptions = array();
		}
	}

	/**
	 * Стираем данные из корзины без сохранения информации
	 */
	public function reset() {
		/**
		 * Если в корзине что-то есть
		 */
		if(isset($_SESSION['sb.order'])) {
			/**
			 * Убираем данные сессии
			 */
			$this->clearSession();
			/**
			 * Сбрасываем все параметры
			 */
			foreach ($this->aOrderDataKeys as $sKey) {
				$this->aOrderData[$sKey] = null;
			}
			/**
			 * Очищаем информацию о заказанных товарах
			 */
			$this->aProducts = array();
			/**
			 * Очищаем информацию о настройках заказа
			 */
			$this->aOptions = array();
		}
	}

	/**
	 * Сохранение состояния заказа
	 */
	public function save() {
		global $modx;
		/**
		 * Сериализуем данные по товарам
		 */
		$this->serializeProducts();
		/**
		 * Сериализуем опции настройки заказов
		 */
		$this->serializeOptions();
		/**
		 * Сериализуем комментарии
		 */
		$this->serializeComments();
		/**
		 * Если это новый заказ
		 */
		if($this->getAttribute('id') === null) {
			/**
			 * Устанавливаем время создания заказа
			 */
			$this->setAttribute('date_add',date('Y-m-d G:i:s'));
			/**
			 * Определяем IP пользователя
			 */
			$this->setAttribute('ip',$modx->sbshop->GetIp());
			/**
			 * Установка статуса заказа "в процессе заказа" - статус 0
			 */
			$this->setAttribute('status', '0');
		} else {
			/**
			 * Устанавливаем время создания заказа
			 */
			$this->setAttribute('date_edit',date('Y-m-d G:i:s'));
		}
		/**
		 * Если есть заказанные товары
		 */
		$this->setAttribute('price', $this->getFullPrice());
		/**
		 * Подготавливаем основные параметры товара для сохранения
		 * Добавляем префикс 
		 */
		$aKeys = $this->aOrderDataKeys;
		$aData = array();
		foreach ($aKeys as $sKey) {
			if($this->aOrderData[$sKey] !== null) {
				$aData['order_' . $sKey] = $this->aOrderData[$sKey];
			}
		}
		/**
		 * Если установлен идентификатор заказа
		 */
		if($this->getAttribute('id') == null) {
			/**
			 * Это новый заказ. Заносим в базу и если все получилось...
			 */
			if($modx->db->insert($aData,$modx->getFullTableName('sbshop_orders'))) {
				/**
				 * Устанавливаем идентификатор заказа
				 */
				$this->setAttribute('id',$modx->db->getInsertId());
			}
		} else {
			$modx->db->update($aData,$modx->getFullTableName('sbshop_orders'),'order_id='.$this->getAttribute('id'));
		}
		/**
		 * Заносим информацию в сессию
		 */

		$this->setSession();
	}
	
	/**
	 * Сериализация данных о заказанных товарах
	 */
	public function serializeProducts() {
		$this->aOrderData['products'] = json_encode($this->aProducts);
		return $this->aOrderData['products'];
	}
	
	/**
	 * Десериализация данных о заказанных товарах
	 * @param unknown_type $sParams
	 */
	public function unserializeProducts($sParams = false) {
		/**
		 * Если значений нет, то переводить нечего
		 */
		if(!$sParams) {
			return;
		}
		/**
		 * десериализуем данные из JSON
		 */
		$this->aProducts = json_decode($sParams, true);
		/**
		 * Возвращаем результат
		 */
		return $this->aProducts;
	}
	
	/**
	 * Сериализация данных о параметрах заказа
	 */
	public function serializeOptions() {
		$this->aOrderData['options'] = json_encode($this->aOptions);
		return $this->aOrderData['options'];
	}
	
	/**
	 * Десериализация данных о параметрах заказа
	 * @param unknown_type $sParams
	 */
	public function unserializeOptions($sParams) {
		/**
		 * Если значений нет, то переводить нечего
		 */
		if(!$sParams) {
			return;
		}
		/**
		 * десериализуем данные из JSON
		 */
		$this->aOptions = json_decode($sParams, true);
		/**
		 * Возвращаем результат
		 */
		return $this->aOptions;
	}

	/**
	 * Сериализация комментариев к заказу
	 */
	public function serializeComments() {
		$this->aOrderData['comments'] = $this->oComments->serialize();
	}


	/**
	 * Десериализация комментариев к заказу
	 * @param <type> $sParams
	 */
	public function unserializeComments($sParams) {
		/**
		 * Если значений нет, то переводить нечего
		 */
		if(!$sParams) {
			return;
		}
		/**
		 * Выполняем десериализцию и возвращаем результат
		 */
		return $this->oComments->unserialize($sParams);
	}

	/**
	 * Добавление комментария
	 * @param <type> $sData
	 */
	public function addComment($sData) {
		return $this->oComments->add($sData);
	}

	/**
	 * Получить первый комментарий
	 */
	public function getFirstComment() {
		return $this->oComments->getFirst();
	}

	/**
	 * Получение всех комментариев
	 */
	public function getComments() {
		return $this->oComments->getAll();
	}

	public function setSession() {
		global $modx;
		if(!$modx->sbshop->bManager) {
			$_SESSION['sb.order'] = $this->aOrderData;
		}
	}

	public function clearSession() {
		unset ($_SESSION['sb.order']);
	}
}

?>