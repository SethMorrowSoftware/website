/**
 * Product Image Lightbox
 */
(function() {
    // Inject lightbox CSS
    var style = document.createElement('style');
    style.textContent = `
        .lightbox-overlay { position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.9); z-index:99999; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.3s; cursor:zoom-out; }
        .lightbox-overlay.active { opacity:1; }
        .lightbox-img { max-width:90vw; max-height:90vh; object-fit:contain; transform:scale(0.9); transition:transform 0.3s; cursor:default; }
        .lightbox-overlay.active .lightbox-img { transform:scale(1); }
        .lightbox-close { position:absolute; top:20px; right:20px; color:#fff; font-size:2rem; cursor:pointer; background:none; border:none; width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:50%; transition:background 0.2s; }
        .lightbox-close:hover { background:rgba(255,255,255,0.1); }
        .lightbox-nav { position:absolute; top:50%; transform:translateY(-50%); color:#fff; font-size:2rem; cursor:pointer; background:rgba(255,255,255,0.1); border:none; width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; transition:background 0.2s; }
        .lightbox-nav:hover { background:rgba(255,255,255,0.2); }
        .lightbox-prev { left:20px; }
        .lightbox-next { right:20px; }
        .lightbox-counter { position:absolute; bottom:20px; left:50%; transform:translateX(-50%); color:#fff; font-size:0.9rem; opacity:0.7; }
    `;
    document.head.appendChild(style);

    var overlay = null;
    var images = [];
    var currentIndex = 0;

    function open(imgArray, startIdx) {
        images = imgArray;
        currentIndex = startIdx || 0;

        overlay = document.createElement('div');
        overlay.className = 'lightbox-overlay';
        overlay.innerHTML = '<button class="lightbox-close" aria-label="Close">&times;</button>' +
            '<button class="lightbox-nav lightbox-prev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>' +
            '<img class="lightbox-img" src="" alt="">' +
            '<button class="lightbox-nav lightbox-next" aria-label="Next"><i class="fas fa-chevron-right"></i></button>' +
            '<div class="lightbox-counter"></div>';

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
        updateImage();

        requestAnimationFrame(function() { overlay.classList.add('active'); });

        overlay.querySelector('.lightbox-close').addEventListener('click', close);
        overlay.querySelector('.lightbox-prev').addEventListener('click', function(e) { e.stopPropagation(); prev(); });
        overlay.querySelector('.lightbox-next').addEventListener('click', function(e) { e.stopPropagation(); next(); });
        overlay.addEventListener('click', function(e) { if (e.target === overlay) close(); });
        document.addEventListener('keydown', handleKey);

        // Hide nav if single image
        if (images.length <= 1) {
            overlay.querySelector('.lightbox-prev').style.display = 'none';
            overlay.querySelector('.lightbox-next').style.display = 'none';
            overlay.querySelector('.lightbox-counter').style.display = 'none';
        }

        // Touch swipe support
        var touchStartX = 0;
        overlay.addEventListener('touchstart', function(e) { touchStartX = e.touches[0].clientX; }, {passive:true});
        overlay.addEventListener('touchend', function(e) {
            var diff = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(diff) > 50) { diff < 0 ? next() : prev(); }
        }, {passive:true});
    }

    function close() {
        if (!overlay) return;
        overlay.classList.remove('active');
        document.removeEventListener('keydown', handleKey);
        setTimeout(function() {
            if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
            overlay = null;
            document.body.style.overflow = '';
        }, 300);
    }

    function updateImage() {
        if (!overlay) return;
        overlay.querySelector('.lightbox-img').src = images[currentIndex];
        overlay.querySelector('.lightbox-counter').textContent = (currentIndex + 1) + ' / ' + images.length;
    }

    function next() { currentIndex = (currentIndex + 1) % images.length; updateImage(); }
    function prev() { currentIndex = (currentIndex - 1 + images.length) % images.length; updateImage(); }
    function handleKey(e) {
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowRight') next();
        if (e.key === 'ArrowLeft') prev();
    }

    // Auto-bind to product gallery images
    document.addEventListener('DOMContentLoaded', function() {
        var mainImg = document.getElementById('mainProductImage');
        if (mainImg) {
            mainImg.style.cursor = 'zoom-in';
            mainImg.addEventListener('click', function() {
                var thumbs = document.querySelectorAll('.product-thumb img');
                var imgList = [];
                if (thumbs.length > 0) {
                    thumbs.forEach(function(t) { imgList.push(t.src); });
                } else {
                    imgList.push(mainImg.src);
                }
                var startIdx = imgList.indexOf(mainImg.src);
                if (startIdx === -1) startIdx = 0;
                open(imgList, startIdx);
            });
        }
    });
})();
