(function($) {
    // We need to copy the variable that WP uses for the inline editor
    var $wp_inline_edit = inlineEditPost.edit;

    // We overwrite the function with our own code
    inlineEditPost.edit = function(id) {
        // Call the original WP edit function
        $wp_inline_edit.apply(this, arguments);

        // Get the post ID
        var post_id = 0;
        if (typeof(id) === 'object') {
            post_id = parseInt(this.getId(id));
        }

        if (post_id > 0) {
            // Get the edit row
            var $edit_row = $('#edit-' + post_id);
            var $post_row = $('#post-' + post_id);

            // Get visibility status from the column
            // We look for the presence of the hidden icon or text
            var is_hidden = $post_row.find('.column-visibility .dashicons-hidden').length > 0;

            // Populate the checkbox
            $edit_row.find('input[name="mindful_media_hide_from_archive"]').prop('checked', is_hidden);
        }
    };
})(jQuery);
