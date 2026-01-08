/**
 * Custom Dashboard Controller - Admin JavaScript
 * 
 * @package CustomDashboardController
 * @version 1.4.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // =============================================
        // Logo Upload Functionality
        // =============================================
        
        $('#cdc_upload_logo').on('click', function(e) {
            e.preventDefault();
            
            var mediaUploader = wp.media({
                title: 'Select Logo',
                button: { text: 'Use as Logo' },
                multiple: false,
                library: { type: 'image' }
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#cdc_custom_logo').val(attachment.url);
                $('#cdc_logo_preview').html('<img src="' + attachment.url + '" alt="Logo">');
                $('#cdc_remove_logo').show();
            });
            
            mediaUploader.open();
        });
        
        $('#cdc_remove_logo').on('click', function(e) {
            e.preventDefault();
            $('#cdc_custom_logo').val('');
            $('#cdc_logo_preview').html('<span class="cdc-no-logo">No logo selected</span>');
            $(this).hide();
        });
        
        // =============================================
        // Menu Visibility - Checkbox Interactions
        // =============================================
        
        // Toggle item styling when checkbox changes
        $(document).on('change', '.cdc-menu-item input[type="checkbox"]', function() {
            var $item = $(this).closest('.cdc-menu-item');
            
            if ($(this).is(':checked')) {
                $item.addClass('cdc-hidden');
                $item.find('.cdc-menu-status').text('🚫');
            } else {
                $item.removeClass('cdc-hidden');
                $item.find('.cdc-menu-status').text('✅');
            }
            
            // Update counter
            updateHiddenCount($(this).closest('.cdc-role-card'));
        });
        
        // Check All button
        $(document).on('click', '.cdc-check-all', function() {
            var target = $(this).data('target');
            $('[data-group="' + target + '"] input[type="checkbox"]').prop('checked', true).trigger('change');
        });
        
        // Uncheck All button
        $(document).on('click', '.cdc-uncheck-all', function() {
            var target = $(this).data('target');
            $('[data-group="' + target + '"] input[type="checkbox"]').prop('checked', false).trigger('change');
        });
        
        // Update hidden count for a role card
        function updateHiddenCount($card) {
            var count = $card.find('input[type="checkbox"]:checked').length;
            $card.find('.cdc-hidden-count').text(count + ' hidden');
        }
        
        // =============================================
        // Submenu Visibility - Panel Switching
        // =============================================
        
        $('#cdc-parent-select').on('change', function() {
            var parentSlug = $(this).val();
            
            // Hide all panels
            $('.cdc-submenu-panel').hide();
            
            // Show selected panel
            if (parentSlug) {
                $('.cdc-submenu-panel[data-parent="' + parentSlug + '"]').fadeIn(200);
            }
        });
        
        // =============================================
        // Parent Menu Order - Sortable
        // =============================================
        
        var $menuSortable = $('#cdc-sortable-menu');
        var $menuPreview = $('#cdc-menu-preview');
        
        if ($menuSortable.length) {
            $menuSortable.sortable({
                handle: '.cdc-drag-handle',
                placeholder: 'cdc-sortable-item ui-sortable-placeholder',
                cursor: 'grabbing',
                opacity: 0.8,
                revert: 100,
                update: function() {
                    updateMenuPreview();
                }
            }).disableSelection();
        }
        
        // Update preview panel for parent menus
        function updateMenuPreview() {
            $menuSortable.find('.cdc-sortable-item').each(function() {
                var slug = $(this).data('slug');
                var $previewItem = $menuPreview.find('[data-slug="' + slug + '"]');
                if ($previewItem.length) {
                    $menuPreview.append($previewItem);
                }
            });
        }
        
        // Save parent menu order
        $('#cdc-save-menu-order').on('click', function() {
            var $btn = $(this);
            var $status = $('#cdc-menu-order-status');
            var order = [];
            
            $menuSortable.find('.cdc-sortable-item').each(function() {
                var slug = $(this).data('slug');
                if (slug) order.push(slug);
            });
            
            if (order.length === 0) {
                showStatus($status, 'No items found', 'error');
                return;
            }
            
            $btn.prop('disabled', true);
            showStatus($status, cdcAdmin.strings.saving, 'loading');
            
            $.ajax({
                url: cdcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cdc_save_menu_order',
                    nonce: cdcAdmin.nonce,
                    order: order
                },
                success: function(response) {
                    if (response.success) {
                        showStatus($status, response.data.message || cdcAdmin.strings.saved, 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showStatus($status, response.data.message || cdcAdmin.strings.error, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    showStatus($status, cdcAdmin.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Reset parent menu order
        $('#cdc-reset-menu-order').on('click', function() {
            if (!confirm(cdcAdmin.strings.confirmReset)) return;
            
            var $btn = $(this);
            var $status = $('#cdc-menu-order-status');
            
            $btn.prop('disabled', true);
            showStatus($status, cdcAdmin.strings.saving, 'loading');
            
            $.ajax({
                url: cdcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cdc_reset_menu_order',
                    nonce: cdcAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showStatus($status, response.data.message, 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showStatus($status, response.data.message || cdcAdmin.strings.error, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    showStatus($status, cdcAdmin.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // =============================================
        // Submenu Order - Panel Switching & Sortable
        // =============================================
        
        // Panel switching for submenu order
        $('#cdc-submenu-order-select').on('change', function() {
            var parentSlug = $(this).val();
            
            // Hide all panels
            $('.cdc-submenu-order-panel').hide();
            
            // Show selected panel and initialize sortable
            if (parentSlug) {
                var $panel = $('.cdc-submenu-order-panel[data-parent="' + parentSlug + '"]');
                $panel.fadeIn(200);
                
                // Initialize sortable if not already
                var $sortable = $panel.find('.cdc-sortable-submenu');
                if (!$sortable.hasClass('ui-sortable')) {
                    $sortable.sortable({
                        handle: '.cdc-drag-handle',
                        placeholder: 'cdc-sortable-item ui-sortable-placeholder',
                        cursor: 'grabbing',
                        opacity: 0.8,
                        revert: 100
                    }).disableSelection();
                }
            }
        });
        
        // Save submenu order
        $(document).on('click', '.cdc-save-submenu-order', function() {
            var $btn = $(this);
            var parentSlug = $btn.data('parent');
            var $status = $('.cdc-submenu-status[data-parent="' + parentSlug + '"]');
            var $sortable = $('.cdc-sortable-submenu[data-parent="' + parentSlug + '"]');
            var order = [];
            
            $sortable.find('.cdc-sortable-item').each(function() {
                var slug = $(this).data('slug');
                if (slug) order.push(slug);
            });
            
            if (order.length === 0) {
                showStatus($status, 'No items found', 'error');
                return;
            }
            
            $btn.prop('disabled', true);
            showStatus($status, cdcAdmin.strings.saving, 'loading');
            
            $.ajax({
                url: cdcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cdc_save_submenu_order',
                    nonce: cdcAdmin.nonce,
                    parent_slug: parentSlug,
                    order: order
                },
                success: function(response) {
                    if (response.success) {
                        showStatus($status, response.data.message || cdcAdmin.strings.saved, 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showStatus($status, response.data.message || cdcAdmin.strings.error, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    showStatus($status, cdcAdmin.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Reset submenu order
        $(document).on('click', '.cdc-reset-submenu-order', function() {
            if (!confirm(cdcAdmin.strings.confirmReset)) return;
            
            var $btn = $(this);
            var parentSlug = $btn.data('parent');
            var $status = $('.cdc-submenu-status[data-parent="' + parentSlug + '"]');
            
            $btn.prop('disabled', true);
            showStatus($status, cdcAdmin.strings.saving, 'loading');
            
            $.ajax({
                url: cdcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cdc_reset_submenu_order',
                    nonce: cdcAdmin.nonce,
                    parent_slug: parentSlug
                },
                success: function(response) {
                    if (response.success) {
                        showStatus($status, response.data.message, 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showStatus($status, response.data.message || cdcAdmin.strings.error, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    showStatus($status, cdcAdmin.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // =============================================
        // Custom Admin Bar Items (NEW v1.4.1)
        // =============================================
        
        // Add custom admin bar item
        $('#cdc-add-custom-item').on('click', function() {
            var $btn = $(this);
            var $status = $('#cdc-custom-item-status');
            
            // Get form values
            var title = $('#cdc-custom-title').val().trim();
            var url = $('#cdc-custom-url').val().trim();
            var newTab = $('#cdc-custom-newtab').is(':checked');
            var roles = [];
            
            $('.cdc-custom-role:checked').each(function() {
                roles.push($(this).val());
            });
            
            // Validate
            if (!title) {
                showStatus($status, 'Please enter a title', 'error');
                return;
            }
            
            if (!url) {
                showStatus($status, 'Please enter a URL', 'error');
                return;
            }
            
            $btn.prop('disabled', true);
            showStatus($status, cdcAdmin.strings.saving, 'loading');
            
            $.ajax({
                url: cdcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cdc_save_custom_adminbar_item',
                    nonce: cdcAdmin.nonce,
                    title: title,
                    url: url,
                    new_tab: newTab ? 'true' : 'false',
                    roles: roles
                },
                success: function(response) {
                    if (response.success) {
                        showStatus($status, response.data.message || 'Item added!', 'success');
                        // Clear form
                        $('#cdc-custom-title').val('');
                        $('#cdc-custom-url').val('');
                        $('#cdc-custom-newtab').prop('checked', false);
                        $('.cdc-custom-role').prop('checked', true);
                        // Reload to show new item
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        showStatus($status, response.data.message || cdcAdmin.strings.error, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    showStatus($status, cdcAdmin.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Delete custom admin bar item
        $(document).on('click', '.cdc-delete-custom-item', function() {
            if (!confirm('Are you sure you want to delete this item?')) {
                return;
            }
            
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var itemId = $btn.data('item-id');
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: cdcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cdc_delete_custom_adminbar_item',
                    nonce: cdcAdmin.nonce,
                    item_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || cdcAdmin.strings.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(cdcAdmin.strings.error);
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // =============================================
        // Dashboard Widgets (NEW v1.4.2)
        // =============================================
        
        // Add/Update dashboard widget
        $('#cdc-add-widget').on('click', function() {
            var $btn = $(this);
            var $status = $('#cdc-widget-status');
            var editId = $('#cdc-widget-edit-id').val();
            
            // Get form values
            var title = $('#cdc-widget-title').val().trim();
            var shortcode = $('#cdc-widget-shortcode').val().trim();
            var roles = [];
            
            $('.cdc-widget-role:checked').each(function() {
                roles.push($(this).val());
            });
            
            // Validate
            if (!title) {
                showStatus($status, 'Please enter a widget title', 'error');
                $('#cdc-widget-title').focus();
                return;
            }
            
            if (!shortcode) {
                showStatus($status, 'Please enter a shortcode', 'error');
                $('#cdc-widget-shortcode').focus();
                return;
            }
            
            $btn.prop('disabled', true);
            showStatus($status, cdcAdmin.strings.saving, 'loading');
            
            $.ajax({
                url: cdcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cdc_save_dashboard_widget',
                    nonce: cdcAdmin.nonce,
                    title: title,
                    type: 'shortcode',
                    shortcode: shortcode,
                    roles: roles,
                    widget_id: editId
                },
                success: function(response) {
                    if (response.success) {
                        showStatus($status, response.data.message || 'Widget saved!', 'success');
                        // Reset form
                        resetWidgetForm();
                        // Reload to show changes
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        showStatus($status, response.data.message || cdcAdmin.strings.error, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    showStatus($status, cdcAdmin.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Edit widget - load data into form
        $(document).on('click', '.cdc-edit-widget', function() {
            var $btn = $(this);
            var widgetId = $btn.data('widget-id');
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: cdcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cdc_get_widget_data',
                    nonce: cdcAdmin.nonce,
                    widget_id: widgetId
                },
                success: function(response) {
                    if (response.success && response.data.widget) {
                        var widget = response.data.widget;
                        
                        // Populate form
                        $('#cdc-widget-title').val(widget.title);
                        $('#cdc-widget-shortcode').val(widget.shortcode);
                        $('#cdc-widget-edit-id').val(widget.id);
                        
                        // Set roles
                        $('.cdc-widget-role').prop('checked', false);
                        if (widget.roles && widget.roles.length > 0) {
                            widget.roles.forEach(function(role) {
                                $('.cdc-widget-role[value="' + role + '"]').prop('checked', true);
                            });
                        } else {
                            // All roles if none specified
                            $('.cdc-widget-role').prop('checked', true);
                        }
                        
                        // Update UI for edit mode
                        $('#cdc-widget-form-title').text('Edit Widget');
                        $('#cdc-add-widget .cdc-btn-text').text('Update Widget');
                        $('#cdc-add-widget .dashicons').removeClass('dashicons-plus-alt').addClass('dashicons-saved');
                        $('#cdc-cancel-edit').show();
                        $('.cdc-widget-form').addClass('edit-mode');
                        
                        // Scroll to form
                        $('html, body').animate({
                            scrollTop: $('#cdc-widget-form-title').offset().top - 50
                        }, 300);
                    } else {
                        alert(response.data.message || 'Error loading widget');
                    }
                    $btn.prop('disabled', false);
                },
                error: function() {
                    alert('Error loading widget data');
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Cancel edit
        $('#cdc-cancel-edit').on('click', function() {
            resetWidgetForm();
        });
        
        // Reset widget form
        function resetWidgetForm() {
            $('#cdc-widget-title').val('');
            $('#cdc-widget-shortcode').val('');
            $('#cdc-widget-edit-id').val('');
            $('.cdc-widget-role').prop('checked', true);
            $('#cdc-widget-form-title').text('Add Shortcode Widget');
            $('#cdc-add-widget .cdc-btn-text').text('Add Widget');
            $('#cdc-add-widget .dashicons').removeClass('dashicons-saved').addClass('dashicons-plus-alt');
            $('#cdc-add-widget').prop('disabled', false);
            $('#cdc-cancel-edit').hide();
            $('.cdc-widget-form').removeClass('edit-mode');
        }
        
        // Delete dashboard widget
        $(document).on('click', '.cdc-delete-widget', function() {
            if (!confirm('Are you sure you want to delete this widget?')) {
                return;
            }
            
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var widgetId = $btn.data('widget-id');
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: cdcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cdc_delete_dashboard_widget',
                    nonce: cdcAdmin.nonce,
                    widget_id: widgetId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            // Check if table is empty
                            if ($('.cdc-widgets-table tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        alert(response.data.message || cdcAdmin.strings.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(cdcAdmin.strings.error);
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // =============================================
        // Utility Functions
        // =============================================
        
        /**
         * Show status message
         * @param {jQuery} $element Status element
         * @param {string} message Message to display
         * @param {string} type Type: success, error, loading
         */
        function showStatus($element, message, type) {
            $element
                .removeClass('success error loading')
                .addClass(type)
                .text(message)
                .fadeIn();
            
            if (type !== 'loading') {
                setTimeout(function() {
                    $element.fadeOut();
                }, 5000);
            }
        }
        
    });
    
})(jQuery);
