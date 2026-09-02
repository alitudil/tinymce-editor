<?php
// Hook into admin footer to load TinyMCE & Markdown Parser
add_action('admin_footer', function() {
    $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!in_array($scriptName, ['posts.php', 'pages.php'])) {
        return;
    }
    ?>
    <!-- TinyMCE CDN & Lightweight Marked.js Markdown Parser -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/12.0.0/marked.min.js"></script>

    <style>
    .editor-mode-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-bottom: none;
        padding: 8px 12px;
        border-radius: 6px 6px 0 0;
        margin-top: 10px;
    }
    .mode-btn-group { display: flex; gap: 6px; }
    .mode-btn {
        background: #e2e8f0;
        color: #475569;
        border: none;
        padding: 5px 12px;
        border-radius: 4px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }
    .mode-btn.active {
        background: #0284c7;
        color: #fff;
    }
    .md-container {
        display: none;
        border: 1px solid #cbd5e1;
        border-radius: 0 0 6px 6px;
        background: #fff;
    }
    .md-split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: 480px;
    }
    @media (max-width: 800px) {
        .md-split { grid-template-columns: 1fr; }
    }
    #mdTextarea {
        width: 100%;
        height: 100%;
        min-height: 480px;
        padding: 15px;
        border: none;
        border-right: 1px solid #e2e8f0;
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.95rem;
        line-height: 1.6;
        resize: vertical;
        outline: none;
        background: #1e293b;
        color: #f8fafc;
        box-sizing: border-box;
    }
    #mdPreview {
        padding: 20px;
        overflow-y: auto;
        max-height: 520px;
        background: #ffffff;
        color: #1e293b;
        line-height: 1.7;
    }
    #mdPreview h1, #mdPreview h2, #mdPreview h3 { margin-bottom: 10px; }
    #mdPreview p { margin-bottom: 12px; }
    #mdPreview code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #d97706; }
    #mdPreview pre { background: #0f172a; color: #f8fafc; padding: 12px; border-radius: 6px; overflow-x: auto; }
    #mdPreview blockquote { border-left: 4px solid #0284c7; padding-left: 12px; color: #64748b; margin: 12px 0; }
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const originalTextarea = document.querySelector('textarea[name="content"]');
        if (!originalTextarea) return;

        // Remove required attribute to prevent hidden field validation conflicts
        originalTextarea.removeAttribute('required');

        // 1. Create Dual-Mode UI Container
        const formGroup = originalTextarea.closest('.form-group') || originalTextarea.parentElement;
        
        const modeBar = document.createElement('div');
        modeBar.className = 'editor-mode-bar';
        modeBar.innerHTML = `
            <div class="mode-btn-group">
                <button type="button" class="mode-btn active" id="btnModeVisual" onclick="switchEditorMode('visual')">📝 Visual (TinyMCE)</button>
                <button type="button" class="mode-btn" id="btnModeMarkdown" onclick="switchEditorMode('markdown')">🔤 Markdown</button>
            </div>
            <small style="color:#64748b; font-size:0.75rem;" id="modeHint">Standard WYSIWYG Mode</small>
        `;

        const mdContainer = document.createElement('div');
        mdContainer.className = 'md-container';
        mdContainer.id = 'mdContainer';
        mdContainer.innerHTML = `
            <div class="md-split">
                <textarea id="mdTextarea" placeholder="# Write Markdown here...&#10;&#10;**Bold text**, *italic*, [link](url), \`code\`, etc." spellcheck="false"></textarea>
                <div id="mdPreview">
                    <em style="color:#94a3b8;">Live Markdown preview will appear here...</em>
                </div>
            </div>
        `;

        // Insert Mode Bar and Markdown Editor right above the original textarea
        formGroup.insertBefore(modeBar, originalTextarea);
        formGroup.insertBefore(mdContainer, originalTextarea);

        const mdTextarea = document.getElementById('mdTextarea');
        const mdPreview  = document.getElementById('mdPreview');

        // 2. Initialize TinyMCE
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
            }
        });

        // 3. Markdown Live Preview Handler
        mdTextarea.addEventListener('input', function() {
            const rawMarkdown = mdTextarea.value;
            mdPreview.innerHTML = marked.parse(rawMarkdown || '*Nothing to preview*');
        });

        // Tab indentation support inside Markdown editor
        mdTextarea.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });

        // 4. Form Submit Interceptor: Auto-compile Markdown into HTML
        const form = originalTextarea.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                const currentMode = localStorage.getItem('wardok_editor_mode') || 'visual';
                if (currentMode === 'markdown') {
                    // Compile Markdown to HTML and assign to the underlying textarea
                    const compiledHtml = marked.parse(mdTextarea.value);
                    originalTextarea.value = compiledHtml;
                    if (window.tinymce && tinymce.get('content')) {
                        tinymce.get('content').setContent(compiledHtml);
                    }
                } else {
                    tinymce.triggerSave();
                }
            });
        }

        // Restore last chosen mode
        const savedMode = localStorage.getItem('wardok_editor_mode') || 'visual';
        if (savedMode === 'markdown') {
            setTimeout(() => switchEditorMode('markdown'), 300);
        }
    });

    // 5. Mode Switch Function
    function switchEditorMode(mode) {
        const btnVisual   = document.getElementById('btnModeVisual');
        const btnMarkdown = document.getElementById('btnModeMarkdown');
        const mdContainer = document.getElementById('mdContainer');
        const modeHint    = document.getElementById('modeHint');
        const originalTextarea = document.querySelector('textarea[name="content"]');
        const mdTextarea  = document.getElementById('mdTextarea');
        const mdPreview   = document.getElementById('mdPreview');

        localStorage.setItem('wardok_editor_mode', mode);

        if (mode === 'markdown') {
            btnMarkdown.classList.add('active');
            btnVisual.classList.remove('active');
            modeHint.innerText = 'Markdown Mode (Auto-compiles to HTML on save)';

            // Hide TinyMCE container
            const tmceContainer = originalTextarea.nextElementSibling;
            if (tmceContainer && tmceContainer.classList.contains('tox-tinymce')) {
                tmceContainer.style.display = 'none';
            }
            originalTextarea.style.display = 'none';
            mdContainer.style.display = 'block';

            // Populate Markdown textarea if empty
            if (!mdTextarea.value && window.tinymce && tinymce.get('content')) {
                const htmlContent = tinymce.get('content').getContent();
                if (htmlContent) {
                    mdTextarea.value = htmlContent; // Loads existing content
                    mdPreview.innerHTML = marked.parse(htmlContent);
                }
            }
            mdTextarea.focus();
        } else {
            btnVisual.classList.add('active');
            btnMarkdown.classList.remove('active');
            modeHint.innerText = 'Standard WYSIWYG Mode';

            // If switching back from Markdown, compile Markdown to HTML for TinyMCE
            if (mdTextarea.value) {
                const compiledHtml = marked.parse(mdTextarea.value);
                if (window.tinymce && tinymce.get('content')) {
                    tinymce.get('content').setContent(compiledHtml);
                }
            }

            mdContainer.style.display = 'none';
            const tmceContainer = originalTextarea.nextElementSibling;
            if (tmceContainer && tmceContainer.classList.contains('tox-tinymce')) {
                tmceContainer.style.display = 'block';
            }
        }
    }
    </script>
    <?php
});
