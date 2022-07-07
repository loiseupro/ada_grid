<?php 

class Adagrid {

	private $id_lang;
	private $currency_symbol;
	private $combinations;

	private $is_enable = false;
	private $show_isbn = false;
	private $show_ean13 = false;	
	private $show_image = false;
	private $show_reference = false;	
	private $show_only_one_image = false;
	private $show_minimal_quantity = false;
	private $force_order_out_stock = 0;
	private $show_qty = false;
	private $show_qty_min = 0;
	private $show_unavailable_attr = false;

	public function __construct(){
		$this->combinations = array();
		$this->id_lang = (int)Context::getContext()->language->id;
		$this->currency_symbol = Context::getContext()->currency->symbol;		

		$this->is_enable = (bool)Configuration::get('ADA_GRID_ENABLE');
		$this->show_ean13 = (bool)Configuration::get('ADA_GRID_SHOW_EAN');
		$this->show_isbn = (bool)Configuration::get('ADA_GRID_SHOW_ISBN');
		$this->show_image = (bool)Configuration::get('ADA_GRID_SHOW_IMAGE');
		$this->show_reference = (bool)Configuration::get('ADA_GRID_SHOW_REFERENCE');
		$this->show_only_one_image = (bool)Configuration::get('ADA_GRID_SHOW_ONE_IMAGE');
		$this->show_minimal_quantity = (bool)Configuration::get('ADA_GRID_SHOW_MIN_QTY');

		$this->show_qty_min = (int)Configuration::get('PS_LAST_QTIES');
		$this->force_order_out_stock = (int)Configuration::get('PS_ORDER_OUT_OF_STOCK');

		if((bool)Configuration::get('PS_DISPLAY_QTIES')) $this->show_qty = true;
		if((bool)Configuration::get('PS_DISP_UNAVAILABLE_ATTR')) $this->show_unavailable_attr = true;		

	}


	/* 
	* Get combination id
	* 
	*/
	private function getCombinationId($attr1, $attr2){
		$sql = 'SELECT id_product_attribute 
		FROM `'._DB_PREFIX_.'product_attribute_combination` 
		WHERE id_attribute = '.$attr1.' AND id_product_attribute IN (
		SELECT id_product_attribute 
		FROM `ps_product_attribute_combination` 
		WHERE id_attribute = '.$attr2.')';

		$result = Db::getInstance()->getRow($sql);
		return $result["id_product_attribute"];
	}


	/* 
	* Roud Price
	* 
	*/
	private function roudPrice($price, $decimals = 2){
		return number_format((float)$price, $decimals, '.', '');
	}


	/* 
	* Get image url for combination
	* 
	*/
	private function getCombinationImageUrl($id_combination){

		$result = '';

		$sql = 'SELECT id_image 
		FROM `'._DB_PREFIX_.'product_attribute_image` 
		WHERE id_product_attribute  = '.$id_combination;

		$images = Db::getInstance()->executeS($sql);
		if(count($images)>0){
			$link_obj = new Link();
			foreach($images as $pos => $id_image){
				$image_obj = new Image($id_image["id_image"]);
				$title = $image_obj->legend[$this->id_lang];
				$url = __PS_BASE_URI__  ."/img/p/" . $image_obj->getExistingImgPath(). '.' . $image_obj->image_format;
				$result .= '<img src="'.$url.'" alt="'.$title.'" title="'.$title.'" >';
				if($this->show_only_one_image){
					return $result;
				}
			}			
		}

		return $result;
	}	


	/*
	* Build html table grid
	*
	*/
	public function buildGrid($id_product){

		if(!$this->is_enable) return '';

		$table = '';
		$attrGroups = array();
		$attrGroupIds = array();
		$attrNames = array();
		$combinationQuantity = array();
		$combinationPrice = array();

		$productObj = new Product($id_product);
		$combinations = $productObj->getAttributeCombinations($this->id_lang, true);

		/* Simple product */
		if(count($combinations) == 0) return "";		

		$this->combinations = $combinations;

		/* Get attribute groups */
		foreach ($this->combinations as $combination) {
			$combinationQuantity["id_attribute_group"][$combination["id_attribute"]] = $combination["quantity"];
			$combinationPrice["id_attribute_group"][$combination["id_attribute"]] = $combination["price"];

			if(!in_array($combination["id_attribute_group"], $attrGroupIds)){
				$attrGroupIds[] = $combination["id_attribute_group"];
			}

			if(!in_array($combination["group_name"], $attrNames)){
				$attrNames[] = $combination["group_name"];
			}

			if(!isset($attrGroups[$combination["id_attribute_group"]][$combination["id_attribute"]])){
				$attrGroups[$combination["id_attribute_group"]][$combination["id_attribute"]] = $combination["attribute_name"];
			}else{
				$attrGroups[$combination["id_attribute_group"]][$combination["id_attribute"]] = $combination["attribute_name"];
			}
		}

		/* Limited to 2 attributes groups */
		if(count($attrGroupIds) !== 2) return "";		

		$valuesAttr1 = $attrGroups[$attrGroupIds[0]];
		$valuesAttr2 = $attrGroups[$attrGroupIds[1]];

		/* Start html */
		$table .= '<table class="table table-bordered">';

		/* Header */	
		$table .= '<thead><tr>';		

		/* Attribute group names */
		$table .= '<th>' . $attrNames[0] . '</th>';
		$table .= '<th>' . $attrNames[1] . '</th>';

		/* Combination image */
		if($this->show_image) $table .= '<th>Imagen</th>';
		
		/* Stock */
		if($this->show_qty) $table .= '<th>Stock</th>';

		/* Price */
		$table .= '<th class="price-cell">Precio</th>';

		/* Minimal qty */
		if($this->show_minimal_quantity) $table .= '<th>Cantidad mínima</th>';
		
		/* Qty */
		$table .= '<th>Cantidad</th>';
		
		/* Reference */
		if($this->show_reference) $table .= '<th>Referencia</th>';
		
		/* Ean13 */
		if($this->show_ean13) $table .= '<th>EAN13</th>';
		
		/* Isbn */
		if($this->show_isbn) $table .= '<th>ISBN</th>';
		
		$table .='</tr></thead><tbody>';

		/* Table body */
		foreach($valuesAttr1 as $valueAttr1Id => $valueAttr1Value){

			$rowspan = 0;

			$table .= '<tr class="first">';

			$table .= '<td width="20%" ROWSPAN_REPLACE >';

			/* Attribute 1 */
			$table .= '<span>'.$valueAttr1Value.'</span>';
			$table .= '</td>'; 

			foreach($valuesAttr2 as $valueAttr2Id => $valueAttr2Value){

				$id_combination = $this->getCombinationId($valueAttr1Id, $valueAttr2Id);
				$combination_obj = new Combination($id_combination);
				$price = Product::getPriceStatic($id_product,true,$id_combination);			

				if($this->show_unavailable_attr OR $combination_obj->quantity>0){

					/* Calc rowspan for first attribute */
					$rowspan ++;

					/* Attribute 2 value */
					$table .= '<td class="text-center">'.$valueAttr2Value.'</td>';

					/* Image */
					if($this->show_image){
						$table .= '<td class="">';
						$table .= $this->getCombinationImageUrl($id_combination);
						$table .= '</td>';
					}
					
					/* Qty */
					if($this->show_qty){
						if($combination_obj->quantity < $this->show_qty_min){
							$table .= '<td class="text-center">'.$combination_obj->quantity.'</td>';
						}else{
							/* Delete stock column header */
							$table = str_replace("<th>Stock</th>", "", $table);
						}
					}

					/* Price */
					$table .= '<td class="price">'.$this->roudPrice($price,2) . ' ' . $this->currency_symbol .'</td>';

					/* Minimal qty */
					if($this->show_minimal_quantity) $table .= '<td class="text-center">'.$combination_obj->minimal_quantity.'</td>';
					
					/* Qty input */
					$table .= '<td>';
					if($combination_obj->quantity > 0 OR $this->force_order_out_stock == 1){
						$table .= '<input type="text" 
						class="input-quantity" 
						style="max-width: 50px;" 
						data-id-product="'.$id_product.'" 
						data-id-combination="'.$id_combination.'" 
						data-quantity="'.$combination_obj->quantity.'" 
						data-minimal-quantity="'.$combination_obj->minimal_quantity.'" 
						data-id-attr1="'.$valueAttr1Id.'" 
						data-id-attr2="'.$valueAttr2Id.'" 
						data-val-attr1="'.$valueAttr1Value.'" 
						data-val-attr2="'.$valueAttr2Value.'" 
						data-force-out-stock="'.$this->force_order_out_stock.'" 
						/>';
					}
					$table .= '</td>';

					/* Reference */
					if($this->show_reference) $table .= '<td class="text-center">'.$combination_obj->reference.'</td>';
					
					/* Ean13 */
					if($this->show_ean13) $table .= '<td class="text-center">'.$combination_obj->ean13.'</td>';
					
					/* Isbn */
					if($this->show_isbn) $table .= '<td class="text-center">'.$combination_obj->isbn.'</td>';
					
					$table .= '</tr>';	
				}		

			}

			/* Update rowspan of actual iteration */	
			$table = str_replace("ROWSPAN_REPLACE", 'rowspan="'.$rowspan.'"', $table);
			$table .= '</tr>';

		}

		$table .= '</tbody></table>';

		return $table;

	}


}