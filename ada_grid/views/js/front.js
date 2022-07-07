
$(document).ready(function(){
	/* Grilla */
	if($('#combinationGrid').length){


		function checkAnyQty(){
			var totalQty = 0;
			$(".input-quantity").each(function(){	
				var inputVal = parseInt($(this).val());
				if (!isNaN(inputVal)){
					totalQty += inputVal;
				}
			});

			if (totalQty == 0){
				alert('¡Introduce las cantidades a comprar!');				
				return false;
			}
			return true;
		}

		$('#combinationGrid .input-quantity').on('change', function() {
			$(this).parent().children(".error").remove();
		});

		$(document).on('click', '#combinationGrid #envia', function() {

			
			var $submitBtn = $(this);

			/* Lanzamos la acción y esperamos a que se complete */
			if (!$submitBtn.hasClass('disabled')){

				/* Aux */
				var count_error = 0;
				var count_ok = 0;
				var cont_actual = 0;
				var modalActive = false;
				var resume_text = "";
				var showModal = prestashop.blockcart.showModal;				
				var refreshURL = $('.blockcart').data('refresh-url');
				var token = $("input[name=token]").val();				

				$("#combinationGrid tr td span.error").remove();
				$submitBtn.addClass('disabled');
				
				/* Check any qty */
				if(!checkAnyQty()){
					$submitBtn.removeClass('disabled');
					return false;
				}

				/* Check if introduced qty isn´t more than stock */
				$(".input-quantity").each(function(){
					var forceOutStock = $(this).data("force-out-stock");
					var inputQty = parseInt($(this).val());
					
					if(!isNaN(inputQty)){					
						if(inputQty > $(this).data("quantity") && forceOutStock == 0){
							$(this).before("<span class='error'>El stock es menor a la cantidad introducida</span>");
							count_error++;
						}else{
							if(inputQty < $(this).data("minimal-quantity")){
								$(this).before("<span class='error'>La cantidad mínima es " + $(this).data("minimal-quantity") + "</span>");
								count_error++;
							}else{
								count_ok++;
							}
						}
					}
				});

				if(count_error === 0){
					/* Loop inputs */
					$(".input-quantity").each(function(){	

						var qtyInput = $(this);
						var id_product = $(this).data("id-product");
						var id_combination = $(this).data("id-combination");
						var qty = parseInt($(this).val());	
						var combination_line_text = $(this).data("val-attr1") + "/" +$(this).data("val-attr2");

						if(!isNaN(qty)){		
							/* Add to cart */
							$.ajax({
								type: 'POST',
								headers: { "cache-control": "no-cache" },
								async: false,
								cache: false,
								data: 'controller=cart&action=update&add=1&ajax=true&qty='+qty+'&id_product='+id_product+'&id_product_attribute='+id_combination+'&id_customization=0&token=' + token ,
								success: function(jsonData){
									response = JSON.parse(jsonData);

									if(response.hasError){                       		
										var errors = response.errors; 
										for (i = 0; i < errors.length; i++) { 									
											qtyInput.before("<span class='error'>"+errors[i]+"</span>");
										}  

									}else if(response.success){
										
										/* Update header cart counter */
										prestashop.emit('updateCart', {
											resp: jsonData
										});	

										requestData = {
											id_product_attribute: id_combination,
											id_product: id_product,
											action: 'add-to-cart'
										};

										cont_actual ++; 
										resume_text = resume_text.concat("<div class='grilla-cart-lines col-md-12'>"+combination_line_text+" (<span class='qty'>"+qty+"</span>)</div>");

										$.post(refreshURL, requestData).then(function (resp) {
											$('.blockcart').replaceWith($(resp.preview).find('.blockcart'));
											if (resp.modal) {

												/* In last iteration, open modal */
												if( (cont_actual === count_ok) && (modalActive!==true) ){	
													showModal(resp.modal);
													/* Show custom text */ 												
													$("#blockcart-modal .modal-body .divide-right p").remove(); 												
													$("#blockcart-modal .modal-body .divide-right span").remove(); 
													$("#blockcart-modal .modal-body .divide-right .col-md-6:nth-child(2)").append(resume_text);
													modalActive = true;
												}

												/* Empty qty input */
												qtyInput.val("");
												$submitBtn.removeClass('disabled');
											}

										});
									}
								},
								error: function(jqXHR, textStatus, errorThrown){
									$submitBtn.removeClass('disabled');
								}
							});

						}
					});

				}else{ 
					$submitBtn.removeClass('disabled');
				}

			}

		});
	}


});