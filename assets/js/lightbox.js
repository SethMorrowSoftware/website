/**
 * Product Image Lightbox
 */
(function() {
    // Inject lightbox CSS
    var style = document.createElement('style');
    style.textContent = `
        .lightbox-overlay { position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.9); z-index:99999; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.3s; cursor:zoom-out; }
        .lightbox-overlay.active { opacity:1; }
        .lightbox-media { max-width:90vw; max-height:90vh; object-fit:contain; transform:scale(0.9); transition:transform 0.3s; cursor:default; }
        .lightbox-overlay.active .lightbox-media { transform:scale(1); }
        .lightbox-video { width:min(90vw, 1100px); height:min(80vh, 620px); background:#000; border-radius:8px; }
        .lightbox-close { position:absolute; top:20px; right:20px; color:#fff; font-size:2rem; cursor:pointer; background:none; border:none; width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:50%; transition:background 0.2s; }
        .lightbox-close:hover { background:rgba(255,255,255,0.1); }
        .lightbox-nav { position:absolute; top:50%; transform:translateY(-50%); color:#fff; font-size:2rem; cursor:pointer; background:rgba(255,255,255,0.1); border:none; width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; transition:background 0.2s; }
        .lightbox-nav:hover { background:rgba(255,255,255,0.2); }
        .lightbox-prev { left:20px; }
        .lightbox-next { right:20px; }
        .lightbox-counter { position:absolute; bottom:20px; left:50%; transform:translateX(-50%); color:#fff; font-size:0.9rem; opacity:0.7; }
        .lightbox-caption { position:absolute; left:24px; right:24px; bottom:54px; text-align:center; color:#fff; font-size:0.95rem; line-height:1.45; opacity:0.92; }
    `;
    document.head.appendChild(style);

    var overlay = null;
    var mediaItems = [];
    var currentIndex = 0;

    function open(items, startIdx) {
        mediaItems = items;
        currentIndex = startIdx || 0;

        overlay = document.createElement('div');
        overlay.className = 'lightbox-overlay';
        overlay.innerHTML = '<button class="lightbox-close" aria-label="Close">&times;</button>' +
            '<button class="lightbox-nav lightbox-prev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>' +
            '<div class="lightbox-stage"></div>' +
            '<button class="lightbox-nav lightbox-next" aria-label="Next"><i class="fas fa-chevron-right"></i></button>' +
            '<div class="lightbox-caption"></div>' +
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
        if (mediaItems.length <= 1) {
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
        var activeVideo = overlay.querySelector('video');
        if (activeVideo) {
            activeVideo.pause();
            activeVideo.currentTime = 0;
        }
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
        var item = mediaItems[currentIndex];
        var stage = overlay.querySelector('.lightbox-stage');
        if (!stage || !item) return;
        stage.innerHTML = '';

        // Build media nodes with the DOM API rather than innerHTML string
        // concatenation so a caption/src containing quotes or markup cannot
        // break out of an attribute and inject HTML.
        if (item.type === 'video') {
            var video = document.createElement('video');
            video.className = 'lightbox-media lightbox-video';
            video.controls = true;
            video.playsInline = true;
            video.preload = 'metadata';
            var source = document.createElement('source');
            source.src = item.src;
            video.appendChild(source);
            video.appendChild(document.createTextNode('Your browser does not support the video tag.'));
            stage.appendChild(video);
        } else {
            var img = document.createElement('img');
            img.className = 'lightbox-media lightbox-img';
            img.src = item.src;
            img.alt = item.caption || '';
            stage.appendChild(img);
        }

        overlay.querySelector('.lightbox-counter').textContent = (currentIndex + 1) + ' / ' + mediaItems.length;
        overlay.querySelector('.lightbox-caption').textContent = item.caption || '';
    }

    function next() { currentIndex = (currentIndex + 1) % mediaItems.length; updateImage(); }
    function prev() { currentIndex = (currentIndex - 1 + mediaItems.length) % mediaItems.length; updateImage(); }
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
                    thumbs.forEach(function(t) {
                        imgList.push({ type: 'image', src: t.src, caption: t.alt || '' });
                    });
                } else {
                    imgList.push({ type: 'image', src: mainImg.src, caption: mainImg.alt || '' });
                }
                var startIdx = imgList.findIndex(function(i) { return i.src === mainImg.src; });
                if (startIdx === -1) startIdx = 0;
                open(imgList, startIdx);
            });
        }

        // Auto-bind to .lightbox-trigger elements (gallery pages, etc.)
        var triggers = document.querySelectorAll('.lightbox-trigger');
        if (triggers.length > 0) {
            var triggerItems = [];
            triggers.forEach(function(t) {
                var type = t.getAttribute('data-lightbox-type') || 'image';
                var src = t.getAttribute('href') || (t.querySelector('img') ? t.querySelector('img').src : '');
                triggerItems.push({
                    type: type,
                    src: src,
                    caption: t.getAttribute('data-caption') || ''
                });
            });
            triggers.forEach(function(trigger, idx) {
                trigger.style.cursor = 'zoom-in';
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    open(triggerItems, idx);
                });
            });
        }
    });
})();
