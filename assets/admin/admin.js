/**
 * uses admin localized variable | proler
 */

;(function($, window, document) {
    class prolerAdminClass{
        constructor(){
            $(document).ready(() => {
				this.initGlobalEvents();
				this.initSectionEvents();
				this.initCommonEvents();
				this.initProductTabEvents();

				// auto add a new role section on load if there's no settings saved for it.
				const hasSection = $('.pr-settings .pr-item');
				if(hasSection.length === 0) $('.add-new').trigger('click');

				// auto expand first role section content.
				const contents = $(document.body).find('.pr-item .proler-option-content');
				if($(contents[0]).is(':hidden')) $(contents[0]).closest('.pr-item').find('.proler-arrow img').trigger('click');

				// add a new discount tier if there are none.
				const ranges = $('.discount-ranges-main').find('.discount-range-wrap .mpcdp_row');
				if(ranges.length === 0) $(ranges[0]).find('.add-new-disrange').trigger('click');
            });
        }
		initGlobalEvents(){
			const self = this;
			$(document.body).on('click', '.proler-collapse-all', function(){
				$(this).closest('.mpcdp_settings_section').find('.pr-settings .pr-item').each(function(){
					if(!$(this).find('.proler-option-content').is(':hidden')) $(this).find('span.proler-arrow img').trigger('click');
				});
			});
			$(document.body).on('click input', '.wfl-nopro', function(e){
				e.preventDefault();
				self.renderPopup($(this));
			});
			$(document.body).on('click', '.proler-popup .close', () => {
				$(document.body).find('.proler-popup').hide();
			});

			$(document.body).on('click', '.proler-delete-role', function(e){
				if(!confirm(proler.delete_role_msg)) e.preventDefault();
			});

			$(document.body).on('click', 'input[type="submit"],button.mpcdp_submit_button', function(e){
				e.preventDefault();
				const data = self.getSettings();
				console.log(data);
				if(Object.keys(data).length !== 0) e.preventDefault();
				$('input[name="proler_data"]').val(JSON.stringify(data));
			});
		}
		initSectionEvents(){
			const self = this;
			$(document.body).on('click', '.add-new', function(){
				$('.pr-settings').append(`<div class="mpcdp_settings_toggle pr-item">${$('.demo-item').html()}</div>`);
			});
			$(document.body).on('click', '.proler-delete', function(){
				const section = $(this).closest('.pr-item');
				section.hide('slow', function(){
					section.remove();
				});
			});
			$(document.body).on('click', '.proler-arrow img', function(){
				const content = $(this).closest('.pr-item').find('.proler-option-content');
				if(content.is(':visible')) $(this).attr('src', proler.right_arrow);
				else $(this).attr('src', proler.down_arrow);
				content.toggle('slow');
			});
			$(document.body).on('click', 'input[name="hide_price"]', function(){
				const textarea = $(this).closest('.mpcdp_settings_section').find('textarea');
				if($(this).is(':checked')) textarea.prop('disabled', false);
				else textarea.prop('disabled', true);
			});

			$(document.body).on('click', '.add-new-disrange', function(){
				$(this).closest('.discount-ranges-main').find('.discount-range-wrap').append($('.discount-range-demo').html());
			});
			$(document.body).on('click', '.delete-disrange', function(){
				const range = $(this).closest('.disrange-item');
				range.hide('slow', function(){
					range.remove();
				});
			});
		}
		initCommonEvents(){
			$(document.body).on('click', '.hurkanSwitch-switch-item', function(){
				const switchWrap = $(this).closest('.hurkanSwitch-switch-box');
				switchWrap.find('.hurkanSwitch-switch-item').each(function(){
					if($(this).hasClass('active')) $(this).removeClass('active');
					else $(this).addClass('active');
				});
				const wrap = $(this).closest('.mpcdp_row');
				wrap.find('input[type="checkbox"]').trigger('click');
				wrap.find('.prdis-msg').toggle('slow');
			});
		}
		initProductTabEvents(){
			$(document.body).on('click', 'input[name="proler_stype"]', function(){
				const val = $(this).val();
				const wrap = $('.role-settings-content');
				if(val === 'proler-based') wrap.show('slow');
				else wrap.hide('slow');
			});

			// auto hide tab content if it's not in correct scope.
			const type = $('input[name="proler_stype"]:checked').val();
			if(type !== 'proler-based' && $('.role-settings-content').is(':visible')) $('.role-settings-content').hide();
		}

		getSettings(){
			const self = this;
			const data = {};
			const type = $( 'input[name="proler_stype"]:checked' ).val();
			if(typeof type !== 'undefined') data['proler_stype'] = type;

			data['roles'] = {};
			$('.pr-settings').find('.pr-item').each(function(){
				var role = $(this).find('.proler-roles').val();
				if(typeof role !== 'undefined' && role.length > 0){
					data['roles'][role] = self.getRoleSettings($(this));
				}
			});
			return data;
		}
		getRoleSettings(row){
			const data = {};
			const fields = [
				{type: 'checkbox', name: 'pr_enable'},
				{type: 'checkbox', name: 'hide_price'},
				{type: 'textarea', name: 'hide_txt'},
				{type: 'input', name: 'min_qty'},
				{type: 'input', name: 'max_qty'},
				{type: 'input', name: 'discount'},
				{type: 'select', name: 'discount_type'},
				{type: 'checkbox', name: 'discount_text'},
				{type: 'checkbox', name: 'hide_regular_price'},
				{type: 'select', name: 'additional_discount_display'},
				{type: 'select', name: 'category'},
				{type: 'select', name: 'product_type'},
				{type: 'input', name: 'schedule_start'},
				{type: 'input', name: 'schedule_end'},
			];
			for(const i in fields){
				const fieldType = fields[i].type === 'checkbox' ? 'input' : fields[i].type;
				const field = row.find(`${fieldType}[name="${fields[i].name}"]`);
				if(fields[i].type === 'checkbox' && field.is(':checked')) data[fields[i].name] = true;
				else data[fields[i].name] = field.val();
			}
			data['ranges'] = this.getDiscountTiers(row);
			return data;
		}
		getDiscountTiers(row){
			const fields = [
				{type: 'select', name: 'discount_type'},
				{type: 'input', name: 'min_value'},
				{type: 'input', name: 'max_value'},
				{type: 'input', name: 'discount_value'},
			];
			const data = [];
			row.find('.discount-range-wrap .disrange-item').each(function(){
				const item = {};
				for(const i in fields){
					const field = $(this).find(`${fields[i].type}[name="${fields[i].name}"]`);
					if(fields[i].type === 'checkbox' && field.is(':checked')) item[fields[i].name] = true;
					else item[fields[i].name] = field.val();
				}
				data.push(item);
			});
			return data;
		}

		renderPopup(item){
			const label = item.attr('data-protxt');
			if(!label || label.length === 0) return;
			const popup = $(document.body).find('.proler-popup');
			popup.find('.focus span').text(label);
			popup.show();
			item.val('');
		}
    }

    new prolerAdminClass();
})(jQuery, window, document);
