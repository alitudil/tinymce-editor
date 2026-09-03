<?php
// Hook into admin footer to load TinyMCE + Markdown Dual Mode
add_action('admin_footer', function() {
    $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!in_array($scriptName, ['posts.php', 'pages.php'])) {
        return;
    }
    ?>
    <!-- TinyMCE CDN & Marked.js Markdown Compiler CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/12.0.0/marked.min.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const textarea = document.querySelector('textarea[name="content"]');
        if (!textarea) return;

        textarea.removeAttribute('required');

        // ----------------------------------------------------
        // 1. INJECT MODE SWITCHER TABS (VISUAL vs MARKDOWN)
        // ----------------------------------------------------
        const tabContainer = document.createElement('div');
        tabContainer.className = 'editor-tabs-bar';
        tabContainer.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <div style="display:flex; gap:6px;">
                    <button type="button" id="btnModeVisual" class="btn btn-sm btn-primary" style="font-size:0.8rem; padding:5px 12px;">👁️ Visual (WYSIWYG)</button>
                    <button type="button" id="btnModeMarkdown" class="btn btn-sm" style="font-size:0.8rem; padding:5px 12px; background:#e2e8f0; color:#334155;">✍️ Markdown</button>
                </div>
                <small id="modeNotice" style="color:#64748b; font-size:0.75rem;">Editing in Visual Mode</small>
            </div>
            <!-- Raw Markdown Textarea (Hidden by default) -->
            <textarea id="markdownEditor" rows="18" spellcheck="false" placeholder="# Type your Markdown here..." style="display:none; width:100%; font-family:Consolas, Monaco, monospace; font-size:0.95rem; line-height:1.6; padding:15px; border:1px solid #cbd5e1; border-radius:6px; background:#1e293b; color:#f8fafc; resize:vertical;"></textarea>
        `;

        textarea.parentNode.insertBefore(tabContainer, textarea);

        const btnVisual   = document.getElementById('btnModeVisual');
        const btnMarkdown = document.getElementById('btnModeMarkdown');
        const mdEditor    = document.getElementById('markdownEditor');
        const modeNotice  = document.getElementById('modeNotice');
        let currentMode   = 'visual';

        // ----------------------------------------------------
        // 2. INITIALIZE TINYMCE WITH MARKDOWN TYPING PATTERNS
        // ----------------------------------------------------
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
                editor.on('change keyup NodeChange', function () {
                    editor.save();
                });

                // Markdown live typing shortcuts (# H1, **bold**, `code`, - list)
                editor.on('keydown', function (e) {
                    if (e.key === ' ' || e.key === 'Enter') {
                        // TinyMCE native patterns auto-trigger
                    }
                });
            }
        });

        // ----------------------------------------------------
        // 3. TAB SWITCHING LOGIC (MARKDOWN <-> VISUAL)
        // ----------------------------------------------------
        btnVisual.addEventListener('click', function() {
            if (currentMode === 'visual') return;
            currentMode = 'visual';

            // Compile Markdown -> HTML via marked.js and put into TinyMCE
            const compiledHtml = marked.parse(mdEditor.value || '');
            if (tinymce.get('content')) {
                tinymce.get('content').setContent(compiledHtml);
                tinymce.get('content').show();
            }
            textarea.value = compiledHtml;

            mdEditor.style.display = 'none';
            btnVisual.style.background = '#0284c7';
            btnVisual.style.color = '#fff';
            btnMarkdown.style.background = '#e2e8f0';
            btnMarkdown.style.color = '#334155';
            modeNotice.innerText = 'Editing in Visual Mode';
        });

        btnMarkdown.addEventListener('click', function() {
            if (currentMode === 'markdown') return;
            currentMode = 'markdown';

            // Sync HTML from TinyMCE
            if (tinymce.get('content')) {
                const currentHtml = tinymce.get('content').getContent();
                // If markdownEditor is empty, use current textarea content
                if (!mdEditor.value.trim()) {
                    mdEditor.value = currentHtml;
                }
                tinymce.get('content').hide();
            }

            mdEditor.style.display = 'block';
            btnMarkdown.style.background = '#0284c7';
            btnMarkdown.style.color = '#fff';
            btnVisual.style.background = '#e2e8f0';
            btnVisual.style.color = '#334155';
            modeNotice.innerText = 'Editing in Raw Markdown (Auto-compiled to HTML on save)';
        });

        // Tab indentation inside Markdown Editor
        mdEditor.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });

        // ----------------------------------------------------
        // 4. FORM SUBMIT HANDLER: ENSURE HTML COMPILATION
        // ----------------------------------------------------
        const form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                if (currentMode === 'markdown') {
                    // Translate Markdown to clean HTML before saving
                    const compiledHtml = marked.parse(mdEditor.value || '');
                    textarea.value = compiledHtml;
                } else {
                    tinymce.triggerSave();
                }
            });
        }
    });
    </script>
    <?php
});
