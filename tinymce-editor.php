<?php
// Hook into admin footer to load and sync TinyMCE
add_action('admin_footer', function() {
    $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!in_array($scriptName, ['posts.php', 'pages.php'])) {
        return;
    }
    ?>
    <!-- TinyMCE CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const textarea = document.querySelector('textarea[name="content"]');
        
        if (textarea) {
            // 1. Remove HTML5 required attribute to avoid hidden field submission block
            textarea.removeAttribute('required');

            // 2. Initialize TinyMCE with auto-sync
            tinymce.init({
                selector: 'textarea[name="content"]',
                height: 480,
                menubar: 'edit insert view format table tools',
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount', 'codesample'
                ],
                toolbar: 'undo redo | blocks fontfamily fontsize | ' +
                         'bold italic underline forecolor | alignleft aligncenter ' +
                         'alignright alignjustify | bullist numlist outdent indent | ' +
                         'image media link codesample | removeformat code fullscreen',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #333; padding: 15px; }',
                branding: false,
                promotion: false,
                relative_urls: false,
                remove_script_host: false,
                convert_urls: true,
                setup: function (editor) {
                    // Sync TinyMCE content to the textarea on every change/keystroke
                    editor.on('change keyup NodeChange', function () {
                        editor.save();
                    });
                }
            });

            // 3. Force full sync right before form submit
            const form = textarea.closest('form');
            if (form) {
                form.addEventListener('submit', function () {
                    tinymce.triggerSave();
                });
            }
        }
    });
    </script>
    <?php
});