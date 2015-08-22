(function($){
"use strict";

	function prdctfltr_show_opened_cats() {
		var curr_cat = $('#prdctfltr_woocommerce').find('input[name="product_cat"]').val();
		if ( curr_cat !== undefined && curr_cat.indexOf(',') >= 0 ) {
			var curr_cats = curr_cat.split(',');
		}
		else {
			var curr_cats = [curr_cat];
		}

		$.each( curr_cats, function(index, item) {
			var curr = $('#prdctfltr_woocommerce .prdctfltr_filter.prdctfltr_cat').find('.prdctfltr_sub input[value="'+item+'"]').closest('.prdctfltr_sub');
			curr.show();
			curr.prev().addClass('prdctfltr_clicked');
			$('#prdctfltr_woocommerce .prdctfltr_filter.prdctfltr_cat').find('.prdctfltr_sub[data-sub="'+item+'"]').show();
		});
	}
	prdctfltr_show_opened_cats();

	String.prototype.getValueByKey = function (k) {
		var p = new RegExp('\\b' + k + '\\b', 'gi');
		return this.search(p) != -1 ? decodeURIComponent(this.substr(this.search(p) + k.length + 1).substr(0, this.substr(this.search(p) + k.length + 1).search(/(&|;|$)/))) : "";
	};

	var curr_data = {};

	function prdctfltr_init_scroll() {
		if ( $('#prdctfltr_woocommerce').hasClass('prdctfltr_scroll_active') ) {

			$(".prdctfltr_checkboxes").mCustomScrollbar({
				axis:"y",
				scrollInertia:550,
				autoExpandScrollbar:true,
				advanced:{
					updateOnBrowserResize:true,
					updateOnContentResize:true
				}
			});

			if ( $('#prdctfltr_woocommerce').hasClass('pf_mod_row') && ( $(".prdctfltr_checkboxes").length > $('.prdctfltr_filter_wrapper:first').attr('data-columns') ) ) {

				if ( $('.prdctfltr-widget').length == 0 ) {

					var curr_scroll_column = $('.prdctfltr_filter:first').width();
					var curr_columns = $('.prdctfltr_filter').length;

					$('.prdctfltr_filter_inner').css('width', curr_columns*curr_scroll_column);
					$('.prdctfltr_filter').css('width', curr_scroll_column);

					$(".prdctfltr_filter_wrapper").mCustomScrollbar({
						axis:"x",
						scrollInertia:550,
						scrollbarPosition:"outside",
						autoExpandScrollbar:true,
						advanced:{
							updateOnBrowserResize:true,
							updateOnContentResize:false
						}
					});
				}
			}

			if ( $('.prdctfltr-widget').length == 0 ) {
				$('.prdctfltr_slide .prdctfltr_woocommerce_ordering').hide();
			}

		}
	}

	function prdctfltr_respond(curr) {

		var activated = [];

		if ( curr.find('input[name="reset_filter"]:checked').length > 0 ) {
			curr.find('input[name="reset_filter"]').remove();
			curr.find('input[type="hidden"], input[name="sale_products"], input[name="instock_products"]:not([type="hidden"])').remove();
		}
		else {
			curr.find('.prdctfltr_filter input[type="hidden"]').each(function() {

				var curr_val = $(this).val();
				var curr_name = $(this).attr('name');

				if ( curr_val == '' ) {
					$(this).remove();
				}
				else {
					if ( $.inArray(curr_name, activated) == -1 ) {
						activated.push(curr_name);
					}
					else {
						var first = curr.find('[name="'+curr_name+'"]:first');
						first.val( first.val() + ',' + $(this).val() );
						$(this).remove();
					}
				}
			});
		}


		if ( ( curr.closest('.prdctfltr_sc_products').length > 0 && curr.closest('.prdctfltr_sc_products').hasClass('prdctfltr_ajax') ) || ( $('.prdctfltr_sc_products:first').length > 0 && $('.prdctfltr_sc_products:first').hasClass('prdctfltr_ajax') ) ) {

			var curr_sc = ( curr.closest('.prdctfltr_sc_products').length > 0 ? curr.closest('.prdctfltr_sc_products') : $('.prdctfltr_sc_products:first') );

			var curr_fields = {};

			$('.prdctfltr_filter input[type="hidden"]').each( function() {
				curr_fields[$(this).attr('name')] = $(this).attr('value');
			});
			if ( $('#prdctfltr_woocommerce').find('input[name="sale_products"]:checked').length > 0 ) {
				curr_fields['sale_products'] = 'yes';
			}
			if ( $('#prdctfltr_woocommerce').find('input[name="instock_products"]:checked').length > 0 ) {
				curr_fields['instock_products'] = 'in';
			}

			var curr_widget = 'no';
			if ( $('.prdctfltr-widget').length > 0 ) {
				curr_widget = 'yes';
			}

			var data = {
				action: 'prdctfltr_respond',
				pf_query: curr_sc.attr('data-query'),
				pf_shortcode: curr_sc.attr('data-shortcode'),
				pf_page: ( curr_data['paginated'] !== undefined ? curr_sc.attr('data-page') : 1 ),
				pf_filters: curr_fields,
				pf_widget: curr_widget
			}

			$.post(prdctfltr.ajax, data, function(response) {
				if (response) {

						curr_sc.after(response);
						var curr_next = curr_sc.next();

						curr_next.css({'position':'absolute', 'top':0, 'left':0});

						var curr_products = curr_next.find('.product');

						curr_next.find('.product').css('opacity', 0);

						curr_sc.css({'position':'absolute', 'top':0, 'left':0}).fadeOut(100).remove();
						curr_next.removeAttr('style');

						if ( $(response).find('script').length > 0 ) {
							$(response).find('script').each(function(i) {
								eval($(this).text());
							});
						}

						prdctfltr_init_scroll();
						prdctfltr_show_opened_cats();
						if ( $('#prdctfltr_woocommerce').hasClass('pf_mod_masonry') ) {

							$('#prdctfltr_woocommerce .prdctfltr_woocommerce_ordering').show();
							$('#prdctfltr_woocommerce').find('.prdctfltr_filter_inner').isotope({
								resizable: false,
								masonry: { }
							});
							if ( !$('#prdctfltr_woocommerce').hasClass('prdctfltr_always_visible') ) {
								$('#prdctfltr_woocommerce .prdctfltr_woocommerce_ordering').hide();
							}
						}

						curr_products.each(function(i) {
							$(this).delay((i++) * 100).fadeTo(100, 1);
						});

				}
				else {
					alert('Error!');
				}
			});

			var curr_widget = curr.closest('#prdctfltr_woocommerce').parent();

			if ( curr_widget.hasClass('prdctfltr-widget') ) {

				var rpl = $('<div></div>').append(curr_widget.find('.prdctfltr_filter').children(':not(input):first').clone()).html().toString().replace(/\t/g, '');
				var rpl_off = $('<div></div>').append(curr_widget.find('.prdctfltr_filter').children(':not(input):first').find('.prdctfltr_widget_title').clone()).html().toString().replace(/\t/g, '');
				
				rpl = rpl.replace(rpl_off, '%%%');

				var widget_data = {
					action: 'prdctfltr_widget_respond',
					pf_query: curr_sc.attr('data-query'),
					pf_shortcode: curr_sc.attr('data-shortcode'),
					pf_filters: curr_fields,
					pf_preset: curr_widget.find('#prdctfltr_woocommerce').attr('data-preset'),
					pf_template: curr_widget.find('#prdctfltr_woocommerce').attr('data-template'),
					pf_widget_title: $.trim(rpl)
				}

				$.post(prdctfltr.ajax, widget_data, function(response) {
					if (response) {

						curr_widget.after(response);
						var curr_widget_next = curr_widget.next();

						curr_widget_next.css({'position':'absolute', 'top':0, 'left':0});

						var curr_products = curr_widget_next.find('.product');

						curr_widget.css({'position':'absolute', 'top':0, 'left':0}).fadeOut(100).remove();
						curr_widget_next.removeAttr('style');

						if ( $(response).find('script').length > 0 ) {
							$(response).find('script').each(function(i) {
								eval($(this).text());
							});
						}

						prdctfltr_init_scroll();
						prdctfltr_show_opened_cats();

					}
					else {
						alert('Error!');
					}
				});

			}

		}
		else {
			curr.find('.prdctfltr_filter input[type="hidden"]').each( function() {
				if ( curr.find('.prdctfltr_add_inputs input[name='+$(this).attr('name')+']').length > 0 ) {
					curr.find('.prdctfltr_add_inputs input[name='+$(this).attr('name')+']').remove();
				}
			});
			curr.submit();
		}

		return false;
	}

	function prdctfltr_submit_form() {


		if ( $('#prdctfltr_woocommerce').hasClass('prdctfltr_click_filter') || $('#prdctfltr_woocommerce').find('input[name="reset_filter"]:checked').length > 0 ) {

			var curr = $('#prdctfltr_woocommerce .prdctfltr_woocommerce_ordering');

			prdctfltr_respond(curr);

		}

	}

	$(document).on('click', '#prdctfltr_woocommerce_filter_submit', function() {

		var curr = $(this).parent();

		prdctfltr_respond(curr);

		return false;

	});

	$(document).on('click', '#prdctfltr_woocommerce_filter', function(){

		if ( !$('#prdctfltr_woocommerce').hasClass('prdctfltr_always_visible') ) {
			var curr = $(this).parent().children('form');
			if( $(this).hasClass('prdctfltr_active') ) {
				curr.stop(true,true).slideUp(200);
				$(this).removeClass('prdctfltr_active');
			}
			else {
				$(this).addClass('prdctfltr_active')
				curr.css({right: 0}).stop(true,true).slideDown(200);
			}
		}

		return false;
	});


	/*select*/
	$(document).on('click', '.pf_default_select .prdctfltr_widget_title', function() {

		var curr = $(this).closest('.prdctfltr_filter').find('.prdctfltr_checkboxes');

		if ( !curr.hasClass('prdctfltr_down') ) {
			curr.prev().find('.prdctfltr-down').attr('class', 'prdctfltr-up');
			curr.addClass('prdctfltr_down');
			curr.slideDown(100);
		}
		else {
			curr.slideUp(100);
			curr.removeClass('prdctfltr_down');
			curr.prev().find('.prdctfltr-up').attr('class', 'prdctfltr-down');
		}

	});

	/*select*/
	var pf_select_opened = false;
	$(document).on('click', '.pf_select .prdctfltr_filter > span', function() {
		pf_select_opened = true;
		var curr = $(this).next();

		if ( !curr.hasClass('prdctfltr_down') ) {
			curr.prev().find('.prdctfltr-down').attr('class', 'prdctfltr-up');
			curr.addClass('prdctfltr_down');
			curr.slideDown(100, function() {
				pf_select_opened = false;
			});
			curr.closest('.prdctfltr_checkboxes').css({ 'z-index' : $('#prdctfltr_woocommerce').find('.prdctfltr_down').length });
			if ( !$('body').hasClass('pf_select_opened') ) {
				$('body').addClass('pf_select_opened');
			}
		}
		else {
			curr.slideUp(100, function() {
				pf_select_opened = false;
			});
			curr.removeClass('prdctfltr_down');
			curr.prev().find('.prdctfltr-up').attr('class', 'prdctfltr-down');
			if ( $('#prdctfltr_woocommerce').find('.prdctfltr_down').length == 0 ) {
				$('body').removeClass('pf_select_opened');
			}
		}

	});

	$(document).on( 'click', 'body.pf_select_opened', function(e) {

		var curr_target = $(e.target);

		if ( $('#prdctfltr_woocommerce').find('.prdctfltr_down').length > 0 && pf_select_opened === false && !curr_target.is('span, input, i') ) {
			$('#prdctfltr_woocommerce').find('.prdctfltr_down').each( function() {
				var curr = $(this);
				if ( curr.is(':visible') ) {
					curr.slideUp(100);
					curr.removeClass('prdctfltr_down');
					curr.prev().find('.prdctfltr-up').attr('class', 'prdctfltr-down');
				}
			});
			$('body').removeClass('pf_select_opened');
		}
	});

	$(document).on('click', 'span.prdctfltr_sale input[type="checkbox"], span.prdctfltr_instock input[type="checkbox"], span.prdctfltr_reset input[type="checkbox"]', function() {

		var curr = $(this).parent();

		if ( !curr.hasClass('prdctfltr_active') ) {
			curr.addClass('prdctfltr_active');
		}
		else {
			curr.removeClass('prdctfltr_active');
		}

	});

	$(document).on('click', '.prdctfltr_instock:not(span) input[type="checkbox"]', function() {
		var curr_chckbx =  $(this);

		var curr = $(this).closest('.prdctfltr_filter');
		var curr_var = $(this).val();

		curr.children(':first').val(curr_var);

		curr.find('input:not([type="hidden"])').prop('checked', false);
		curr.find('label').removeClass('prdctfltr_active');
		curr_chckbx.prop('checked', true);
		curr_chckbx.parent().addClass('prdctfltr_active');

		prdctfltr_submit_form();
	});

	$(document).on('click', '.prdctfltr_orderby input[type="checkbox"]', function() {
		var curr_chckbx =  $(this);

		var curr = $(this).closest('.prdctfltr_filter');
		var curr_var = $(this).val();

		curr.children(':first').val(curr_var);

		curr.find('input:not([type="hidden"])').prop('checked', false);
		curr.find('label').removeClass('prdctfltr_active');
		curr_chckbx.prop('checked', true);
		curr_chckbx.parent().addClass('prdctfltr_active');

		prdctfltr_submit_form();
	});

	$(document).on('click', '.prdctfltr_byprice input[type="checkbox"]', function() {
		var curr_chckbx =  $(this);

		var curr = $(this).closest('.prdctfltr_filter');
		var curr_var = $(this).val().split('-');

		curr.children(':first').val(curr_var[0]);
		curr.children(':first').next().val(curr_var[1]);

		curr.find('input:not([type="hidden"])').prop('checked', false);
		curr.find('label').removeClass('prdctfltr_active');
		curr_chckbx.prop('checked', true);
		curr_chckbx.parent().addClass('prdctfltr_active');

		prdctfltr_submit_form();
	});

	$(document).on('click', '.prdctfltr_characteristics input[type="checkbox"], .prdctfltr_tag input[type="checkbox"], .prdctfltr_cat input[type="checkbox"], .prdctfltr_attributes input[type="checkbox"]', function() {
		var curr_chckbx = $(this);
		var curr = curr_chckbx.closest('.prdctfltr_filter');
		var curr_var = curr_chckbx.val();
		var curr_attr = curr.children(':first').attr('name');

		if ( $('#prdctfltr_woocommerce').hasClass('pf_adptv_unclick') ) {
			if ( curr_chckbx.parent().hasClass( 'pf_adoptive_hide' ) ) {
				return false;
			}
		}

		if ( curr_var == '' && curr.find('label').is(':first-child') ) {
			curr.find('input[type="hidden"]').val('');
			curr.find('label').removeClass('prdctfltr_active');

			if ( $('#prdctfltr_woocommerce .prdctfltr_add_inputs input[name="'+curr_attr+'"]').length > 0 ) {
				$('#prdctfltr_woocommerce .prdctfltr_add_inputs input[name="'+curr_attr+'"]').remove();
			}

			if ( curr.parent().find('input[name="'+curr_attr+'"]').length > 1 ) {

				curr.parent().find('input[name="'+curr_attr+'"]').each( function() {
					$(this).val('');
				});

			}

			prdctfltr_submit_form();
		}

		if ( curr.hasClass('prdctfltr_cat') ) {
			var curr_parent = curr.find('[data-sub='+curr_var+']');
			
			if ( curr_parent.length > 0 ) {
				if ( !curr_chckbx.parent().hasClass('prdctfltr_clicked') ) {
					curr.find('[data-sub='+curr_var+']').slideToggle();
					curr_chckbx.parent().addClass('prdctfltr_clicked');
					return false;
				}
				else {
					if ( curr_chckbx.parent().hasClass('prdctfltr_active') ) {
						curr.find('[data-sub='+curr_var+']').slideToggle();
						curr.find('[data-sub='+curr_var+'] input').each( function() {
							if ( $(this).parent().hasClass('prdctfltr_active') ) {
								prdctfltr_check(curr, $(this), $(this).val().split('-'));
							}
						});
						curr_chckbx.parent().removeClass('prdctfltr_clicked');

					}
				}
			}
		}

		prdctfltr_check(curr, curr_chckbx, curr_var);

		prdctfltr_submit_form();
	});


	function prdctfltr_check(curr, curr_chckbx, curr_var) {

		if ( curr.hasClass('prdctfltr_multi') ) {

			if ( curr_chckbx.val() !== '' ) {
				if ( curr.find('label:first').hasClass('prdctfltr_active') ) {
					curr.find('label:first').removeClass('prdctfltr_active').find('input').prop('checked', false);
				}
				if ( curr_chckbx.parent().hasClass('prdctfltr_active') ) {

					if ( curr.parent().hasClass('prdctfltr_clicked') ) {
						curr.parent().removeClass('prdctfltr_clicked');
						curr.find('[data-sub='+curr_chckbx.val()+']').slideToggle();
					}

					curr_chckbx.prop('checked', false);
					curr_chckbx.parent().removeClass('prdctfltr_active');

					var curr_settings = ( curr.children(':first').val().indexOf(',') > 0 ? curr.children(':first').val().replace(',' + curr_var, '').replace(curr_var + ',', '') : '' );

					curr.children(':first').val(curr_settings);
				}
				else {
					curr_chckbx.prop('checked', true);
					curr_chckbx.parent().addClass('prdctfltr_active');

					var curr_settings = ( curr.children(':first').val() == '' ? curr_var : curr.children(':first').val() + ',' + curr_var );
					curr.children(':first').val(curr_settings);
				}
			}
			else {
				if ( curr_chckbx.parent().hasClass('prdctfltr_active') ) {

					if ( curr.parent().hasClass('prdctfltr_clicked') ) {
						curr.parent().removeClass('prdctfltr_clicked');
						curr.find('[data-sub='+curr_chckbx.val()+']').slideToggle();
					}

					curr_chckbx.prop('checked', false);
					curr_chckbx.parent().removeClass('prdctfltr_active');
				}
				else {
					curr.children(':first').val('');
					curr.find('input:not([type="hidden"])').prop('checked', false);
					curr.find('label').removeClass('prdctfltr_active');
					curr_chckbx.prop('checked', true);
					curr_chckbx.parent().addClass('prdctfltr_active');
				}
			}


		}
		else {

			curr.children(':first').val(curr_var);

			curr.find('input:not([type="hidden"])').prop('checked', false);
			curr.find('label').removeClass('prdctfltr_active');
			curr_chckbx.prop('checked', true);
			curr_chckbx.parent().addClass('prdctfltr_active');
		}
	}


	$(document).on('click', 'span.prdctfltr_sale input[type="checkbox"], span.prdctfltr_instock input[type="checkbox"], span.prdctfltr_reset input[type="checkbox"]', function() {
		prdctfltr_submit_form();
	});



	$(document).on('click', '#prdctfltr_woocommerce span a, .prdctfltr_widget_title a', function() {

		var curr = $('#prdctfltr_woocommerce .prdctfltr_woocommerce_ordering');
		var curr_key = $(this).attr('data-key');

		if ( curr_key == 'byprice' ) {
			curr.find('.prdctfltr_byprice input[type="hidden"], .prdctfltr_price input[type="hidden"]').each(function() {
				$(this).remove();
			});
		}
		else if ( curr_key == 'product_cat' ) {
			curr.find('.prdctfltr_'+curr_key+' input[type="hidden"], .prdctfltr_cat input[type="hidden"]').each(function() {
				$(this).remove();
			});
		}
		else if ( curr_key == 'product_tag' ) {
			curr.find('.prdctfltr_'+curr_key+' input[type="hidden"], .prdctfltr_tag input[type="hidden"]').each(function() {
				$(this).remove();
			});
		}
		else if ( curr_key.substr(0,4) == 'rng_' ) {
			curr.find('.prdctfltr_range input[type="hidden"][name$="'+curr_key.substr(4, curr_key.length)+'"]').each(function() {
				$(this).remove();
			});
		}
		else {
			curr.find('.prdctfltr_'+curr_key+' input[type="hidden"]').each(function() {
				$(this).remove();
			});
		}

		prdctfltr_respond(curr);

		return false;
	});


	if ( $('.prdctfltr_sc_products').hasClass('prdctfltr_ajax') ) {
		$(document).on('click', '.prdctfltr_sc_products .woocommerce-pagination a', function() {

			var activated = [];

			var curr_sc = $(this).closest('.prdctfltr_sc_products');

			curr_sc.find('.prdctfltr_filter input[type="hidden"]').each(function() {

				var curr_val = $(this).val();
				var curr_name = $(this).attr('name');

				if ( curr_val == '' ) {
					$(this).remove();
				}
				else {
					if ( $.inArray(curr_name, activated) == -1 ) {
						activated.push(curr_name);
					}
					else {
						var first = curr_sc.find('[name="'+curr_name+'"]:first');
						first.val( first.val() + ',' + $(this).val() );
						$(this).remove();
					}
				}
			});


			var curr_href = $(this).attr('href');

			if ( curr_href.indexOf('paged=') >= 0 ) {
				var pf_paged = curr_href.getValueByKey('paged');
			}

			var curr_fields = {};

			$('.prdctfltr_filter input[type="hidden"]').each( function() {
				curr_fields[$(this).attr('name')] = $(this).attr('value');
			});
			if ( $('#prdctfltr_woocommerce').find('input[name="sale_products"]:checked').length > 0 ) {
				curr_fields['sale_products'] = 'yes';
			}
			if ( $('#prdctfltr_woocommerce').find('input[name="instock_products"]:checked').length > 0 ) {
				curr_fields['instock_products'] = 'in';
			}

			var curr_widget = 'no';
			if ( $('.prdctfltr-widget').length > 0 ) {
				curr_widget = 'yes';
			}

			var data = {
				action: 'prdctfltr_respond',
				pf_query: curr_sc.attr('data-query'),
				pf_shortcode: curr_sc.attr('data-shortcode'),
				pf_page: curr_sc.attr('data-page'),
				pf_action: curr_sc.attr('action'),
				pf_paged: pf_paged,
				pf_filters: curr_fields,
				pf_widget: curr_widget
			}

			data.pf_query = data.pf_query.replace('paged='+data.pf_page, 'paged='+data.pf_paged);

			$.post(prdctfltr.ajax, data, function(response) {
				if (response) {

					curr_sc.after(response);
					var curr_next = curr_sc.next();

					curr_next.css({'position':'absolute', 'top':0, 'left':0});

					var curr_products = curr_next.find('.product');

					curr_next.find('.product').css('opacity', 0);

					curr_sc.css({'position':'absolute', 'top':0, 'left':0}).fadeOut(100).remove();
					curr_next.removeAttr('style');

					if ( $(response).find('script').length > 0 ) {
						$(response).find('script').each(function(i) {
							eval($(this).text());
						});
					}

					prdctfltr_init_scroll();
					if ( $('#prdctfltr_woocommerce').hasClass('pf_mod_masonry') ) {

						$('#prdctfltr_woocommerce .prdctfltr_woocommerce_ordering').show();
						$('#prdctfltr_woocommerce').find('.prdctfltr_filter_inner').isotope({
							resizable: false,
							masonry: { }
						});
						if ( !$('#prdctfltr_woocommerce').hasClass('prdctfltr_always_visible') ) {
							$('#prdctfltr_woocommerce .prdctfltr_woocommerce_ordering').hide();
						}
					}

					curr_products.each(function(i) {
						$(this).delay((i++) * 100).fadeTo(100, 1);
					});
					curr_data['paginated'] == true;

				}
				else {
					alert('Error!');
				}
			});

			return false;
		});
	}

	if ( $('#prdctfltr_woocommerce').hasClass('pf_mod_masonry') ) {
		$('#prdctfltr_woocommerce .prdctfltr_woocommerce_ordering').show();
		$('#prdctfltr_woocommerce').find('.prdctfltr_filter_inner').isotope({
			resizable: false,
			masonry: { }
		});
		if ( !$('#prdctfltr_woocommerce').hasClass('prdctfltr_always_visible') ) {
			$('#prdctfltr_woocommerce .prdctfltr_woocommerce_ordering').hide();
		}
		$(window).load( function() {
			$('#prdctfltr_woocommerce').find('.prdctfltr_filter_inner').isotope('layout');
		});
	}

	if ( $('#prdctfltr_woocommerce').hasClass('pf_mod_row') ) {
		if ( $('.prdctfltr-widget').length == 0 ) {
			$(window).on('resize', function() {
				if ( window.matchMedia('(max-width: 768px)').matches ) {
					$('.prdctfltr_filter_inner').css('width', 'auto');
				}
				else {
					var curr_columns = $('.prdctfltr_filter_wrapper:first').attr('data-columns');

					var curr_scroll_column = $('#prdctfltr_woocommerce .prdctfltr_woocommerce_ordering').width();
					var curr_columns_length = $('.prdctfltr_filter').length;

					$('.prdctfltr_filter_inner').css('width', curr_columns_length*curr_scroll_column/curr_columns);
					$('.prdctfltr_filter').css('width', curr_scroll_column/curr_columns);
				}
			});
		}
	}

	prdctfltr_init_scroll();

	if ( $('.prdctfltr-widget').length > 0 ) {
		$('.prdctfltr-widget span.prdctfltr_widget_title').each( function() {
			var curr = $(this);
			if ( curr.children('span').length > 0 ) {
				var curr_filter = curr.closest('.prdctfltr_filter');
				curr.find('.prdctfltr-down').removeClass('prdctfltr-down').addClass('prdctfltr-up');
				curr_filter.find('.prdctfltr_checkboxes').addClass('prdctfltr_down').css({'display':'block'});
			}
		});
	}

	if ( $('#prdctfltr_woocommerce').hasClass('prdctfltr_click_filter') ) {
		$(document).on( 'change', 'input[name^="rng_"]', function() {
			var curr = $('#prdctfltr_woocommerce .prdctfltr_woocommerce_ordering');
			prdctfltr_respond(curr);
		});
	}

	if ((/Trident\/7\./).test(navigator.userAgent)) {
		$(document).on('click', '.prdctfltr_checkboxes label img', function() {
			$(this).parents('label').children('input:first').change().click();
		});
	}

	if ((/Trident\/4\./).test(navigator.userAgent)) {
		$(document).on('click', '.prdctfltr_checkboxes label > span > img, .prdctfltr_checkboxes label > span', function() {
			$(this).parents('label').children('input:first').change().click();
		});
	}

})(jQuery);