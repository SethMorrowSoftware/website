/**
 * Main Site JavaScript
 * Hudson Valley Supply & Recycling LLC
 */

document.addEventListener('DOMContentLoaded', function() {

    // ---- Mobile Navigation ----
    const navToggle = document.getElementById('navToggle');
    const mainNav = document.getElementById('mainNav');
    const mobileOverlay = document.getElementById('mobileOverlay');

    if (navToggle) {
        navToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            mainNav.classList.toggle('open');
            mobileOverlay.classList.toggle('active');
            document.body.style.overflow = mainNav.classList.contains('open') ? 'hidden' : '';
        });
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', function() {
            navToggle.classList.remove('active');
            mainNav.classList.remove('open');
            mobileOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // Close mobile nav on link click
    document.querySelectorAll('.nav-menu a').forEach(function(link) {
        link.addEventListener('click', function() {
            if (mainNav.classList.contains('open')) {
                navToggle.classList.remove('active');
                mainNav.classList.remove('open');
                mobileOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
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
        });
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

        function goToSlide(index) {
            if (index < 0) index = totalSlides - 1;
            if (index >= totalSlides) index = 0;
            currentSlide = index;
            testimonialTrack.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
            dots.forEach(function(d, i) {
                d.classList.toggle('active', i === currentSlide);
            });
        }

        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                goToSlide(parseInt(this.dataset.index));
            });
        });

        // Auto-advance
        setInterval(function() {
            goToSlide(currentSlide + 1);
        }, 5000);
    }

    // ---- Category Tabs (Materials Page) ----
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
            var target = document.getElementById(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ---- Flash message auto-dismiss ----
    var flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(function(msg) {
        setTimeout(function() {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(function() { msg.remove(); }, 500);
        }, 6000);
    });

});
