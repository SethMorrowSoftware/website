/**
 * Main Site JavaScript
 * Business Website CMS
 */

document.addEventListener('DOMContentLoaded', function() {

    // ---- Mobile Navigation ----
    const navToggle = document.getElementById('navToggle');
    const mainNav = document.getElementById('mainNav');
    const mobileOverlay = document.getElementById('mobileOverlay');

    function closeNav() {
        if (navToggle) {
            navToggle.classList.remove('active');
            navToggle.setAttribute('aria-expanded', 'false');
        }
        if (mainNav) mainNav.classList.remove('open');
        if (mobileOverlay) mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (navToggle) {
        navToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            mainNav.classList.toggle('open');
            mobileOverlay.classList.toggle('active');
            var isOpen = mainNav.classList.contains('open');
            document.body.style.overflow = isOpen ? 'hidden' : '';
            this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Keyboard support for nav toggle
        navToggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', closeNav);
    }

    // Close mobile nav on nav link click or Escape key
    document.querySelectorAll('.nav-menu a, .nav-icons a, .nav-cta-btn').forEach(function(link) {
        link.addEventListener('click', function() {
            if (mainNav && mainNav.classList.contains('open')) {
                closeNav();
            }
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mainNav && mainNav.classList.contains('open')) {
            closeNav();
            if (navToggle) navToggle.focus();
        }
    });

    // ---- Sticky Header ----
    const header = document.getElementById('siteHeader');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }, { passive: true });
    }

    // ---- Scroll Animations (Fade In) ----
    const fadeElements = document.querySelectorAll('.fade-in');
    if (fadeElements.length > 0) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        fadeElements.forEach(function(el) {
            observer.observe(el);
        });
    }

    // ---- Testimonials Slider ----
    const testimonialTrack = document.getElementById('testimonialTrack');
    const testimonialDots = document.getElementById('testimonialDots');

    if (testimonialTrack && testimonialDots) {
        let currentSlide = 0;
        const slides = testimonialTrack.querySelectorAll('.testimonial-card');
        const dots = testimonialDots.querySelectorAll('.dot');
        const totalSlides = slides.length;
        let autoplayTimer = null;

        function goToSlide(index) {
            if (index < 0) index = totalSlides - 1;
            if (index >= totalSlides) index = 0;
            currentSlide = index;
            testimonialTrack.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
            dots.forEach(function(d, i) {
                d.classList.toggle('active', i === currentSlide);
            });
        }

        function startAutoplay() {
            stopAutoplay();
            autoplayTimer = setInterval(function() {
                goToSlide(currentSlide + 1);
            }, 5000);
        }

        function stopAutoplay() {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }

        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                goToSlide(parseInt(this.dataset.index));
                startAutoplay(); // Reset timer on manual interaction
            });
        });

        // Touch/swipe support for mobile
        var touchStartX = 0;
        var touchStartY = 0;
        var touchDiffX = 0;
        var isSwiping = false;

        testimonialTrack.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            isSwiping = false;
            stopAutoplay();
        }, { passive: true });

        testimonialTrack.addEventListener('touchmove', function(e) {
            if (!touchStartX) return;
            touchDiffX = e.touches[0].clientX - touchStartX;
            var touchDiffY = e.touches[0].clientY - touchStartY;

            // Only swipe horizontally if the gesture is more horizontal than vertical
            if (Math.abs(touchDiffX) > Math.abs(touchDiffY) && Math.abs(touchDiffX) > 10) {
                isSwiping = true;
                e.preventDefault();
            }
        }, { passive: false });

        testimonialTrack.addEventListener('touchend', function() {
            if (isSwiping && Math.abs(touchDiffX) > 50) {
                if (touchDiffX < 0) {
                    goToSlide(currentSlide + 1); // Swipe left = next
                } else {
                    goToSlide(currentSlide - 1); // Swipe right = prev
                }
            }
            touchStartX = 0;
            touchDiffX = 0;
            isSwiping = false;
            startAutoplay();
        }, { passive: true });

        startAutoplay();
    }

    // ---- Category Tabs (Catalog Page) ----
    const categoryTabs = document.querySelectorAll('.category-tab');
    const categorySections = document.querySelectorAll('.product-category-section');

    if (categoryTabs.length > 0 && categorySections.length > 0) {
        categoryTabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const category = this.dataset.category;

                categoryTabs.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');

                categorySections.forEach(function(section) {
                    if (category === 'all' || section.dataset.category === category) {
                        section.style.display = '';
                    } else {
                        section.style.display = 'none';
                    }
                });

                // Scroll active tab into view within scrollable container
                if (this.parentNode.classList.contains('category-tabs')) {
                    this.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            });
        });

        // Handle hash navigation
        if (window.location.hash) {
            var hash = window.location.hash.substring(1);
            var targetTab = document.querySelector('.category-tab[data-category="' + hash + '"]');
            if (targetTab) {
                targetTab.click();
                setTimeout(function() {
                    var el = document.getElementById(hash);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        }
    }

    // ---- Smooth scroll for anchor links ----
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            var targetId = this.getAttribute('href').substring(1);
            if (!targetId) return; // Skip empty # links
            var target = document.getElementById(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ---- Flash message dismiss ----
    var flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(function(msg) {
        // Add close button
        var closeBtn = document.createElement('button');
        closeBtn.className = 'flash-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', 'Dismiss message');
        closeBtn.addEventListener('click', function() {
            msg.style.transition = 'opacity 0.3s ease';
            msg.style.opacity = '0';
            setTimeout(function() { msg.remove(); }, 300);
        });
        msg.appendChild(closeBtn);

        // Auto-dismiss after 8 seconds
        setTimeout(function() {
            if (msg.parentNode) {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(function() { if (msg.parentNode) msg.remove(); }, 500);
            }
        }, 8000);
    });

    // ---- Back to Top Button ----
    var backToTop = document.createElement('button');
    backToTop.className = 'back-to-top';
    backToTop.innerHTML = '<i class="fas fa-chevron-up"></i>';
    backToTop.setAttribute('aria-label', 'Back to top');
    backToTop.addEventListener('click', function() { window.scrollTo({top: 0, behavior: 'smooth'}); });
    document.body.appendChild(backToTop);

    window.addEventListener('scroll', function() {
        if (window.scrollY > 400) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    }, { passive: true });

    // ---- Sticky Add-to-Cart Bar (Mobile Product Page) ----
    var addToCartForm = document.querySelector('.product-add-form');
    if (addToCartForm && window.innerWidth <= 768) {
        var productTitle = document.querySelector('.product-title');
        var productPrice = document.querySelector('.product-price-large');
        if (productTitle && productPrice) {
            var stickyBar = document.createElement('div');
            stickyBar.className = 'sticky-atc-bar';
            stickyBar.innerHTML = '<div class="sticky-atc-info"><div class="sticky-atc-name">' + productTitle.textContent + '</div><div class="sticky-atc-price">' + productPrice.textContent + '</div></div>' +
                '<button class="btn btn-primary" id="stickyAtcBtn"><i class="fas fa-cart-plus"></i> Add to Cart</button>';
            document.body.appendChild(stickyBar);

            document.getElementById('stickyAtcBtn').addEventListener('click', function() {
                addToCartForm.requestSubmit ? addToCartForm.requestSubmit() : addToCartForm.submit();
            });

            var formRect;
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        stickyBar.classList.remove('visible');
                    } else {
                        stickyBar.classList.add('visible');
                    }
                });
            }, { threshold: 0 });
            observer.observe(addToCartForm);
        }
    }

});
