// Éditeur de texte enrichi minimal (sans dépendance) : transforme tout bloc
// .wysiwyg en éditeur contenteditable synchronisé avec un <textarea> caché.
// Markup attendu :
//   <div class="wysiwyg" data-target="ID_DU_TEXTAREA">
//     <div class="wysiwyg-toolbar"><button data-cmd="bold">…</button>…</div>
//     <div class="wysiwyg-editor" contenteditable="true">…HTML…</div>
//   </div>
//   <textarea id="ID_DU_TEXTAREA" name="…" hidden>…HTML…</textarea>
(function () {
    function init() {
        // Entrée crée un paragraphe <p> (plutôt qu'un <div>) dans les navigateurs récents.
        try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}

        document.querySelectorAll('.wysiwyg').forEach(function (box) {
            if (box.dataset.wysiwygReady) { return; }
            box.dataset.wysiwygReady = '1';

            var editor = box.querySelector('.wysiwyg-editor');
            var field  = document.getElementById(box.dataset.target);
            if (!editor || !field) { return; }

            // Garantit qu'on édite toujours dans un paragraphe (sauts de ligne homogènes).
            function ensureParagraph() {
                var html = editor.innerHTML.trim();
                if (html === '' || html === '<br>') { editor.innerHTML = '<p><br></p>'; }
            }
            // Au chargement : enveloppe un contenu texte brut hérité dans un paragraphe.
            (function normalizeInitial() {
                var html = editor.innerHTML.trim();
                if (html === '' || html === '<br>') {
                    editor.innerHTML = '<p><br></p>';
                } else if (!/<(p|div|ul|ol|h2|h3|blockquote)[\s>]/i.test(html)) {
                    editor.innerHTML = '<p>' + html + '</p>';
                }
            })();
            editor.addEventListener('focus', ensureParagraph);

            function sync() { ensureParagraph(); field.value = editor.innerHTML.trim(); }

            box.querySelectorAll('[data-cmd]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var cmd = btn.dataset.cmd;
                    if (cmd === 'createLink') {
                        var url = prompt('Adresse du lien (https://…)');
                        if (url) { document.execCommand('createLink', false, url); }
                    } else {
                        document.execCommand(cmd, false, null);
                    }
                    editor.focus();
                    sync();
                });
            });

            editor.addEventListener('input', sync);
            var form = box.closest('form');
            if (form) { form.addEventListener('submit', sync); }
            sync();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
