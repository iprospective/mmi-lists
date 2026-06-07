// Agrandissement des photos produit (sans dépendance) : un clic sur une photo
// l'ouvre en plein écran dans une surcouche ; clic n'importe où ou Échap pour fermer.
(function () {
    function init() {
        var box = document.getElementById('lightbox');
        if (!box) { return; }
        var img      = box.querySelector('.lightbox-img');
        var closeBtn = box.querySelector('.lightbox-close');
        var lastFocus = null;

        function open(el) {
            lastFocus = el;
            img.src = el.currentSrc || el.src;
            img.alt = el.alt || '';
            box.hidden = false;
            box.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            closeBtn.focus();
        }
        function close() {
            box.hidden = true;
            box.setAttribute('aria-hidden', 'true');
            img.removeAttribute('src');
            document.body.style.overflow = '';
            if (lastFocus) { lastFocus.focus(); lastFocus = null; }
        }

        document.querySelectorAll('.card-photo img.zoomable').forEach(function (el) {
            el.addEventListener('click', function () { open(el); });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(el); }
            });
        });

        // Un clic n'importe où dans la surcouche (fond, image ou bouton) referme.
        box.addEventListener('click', close);
        document.addEventListener('keydown', function (e) {
            if (!box.hidden && e.key === 'Escape') { close(); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
