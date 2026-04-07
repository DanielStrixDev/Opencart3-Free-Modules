;(function() {
    'use strict';

    // Load Swiper CSS and JS
    function loadSwiper() {
        return new Promise((resolve, reject) => {
            if (window.Swiper) {
                resolve();
                return;
            }

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css';
            document.head.appendChild(link);

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    // Generate or read user ID
    function getUserId() {
        let userId = localStorage.getItem('personalization_id');

        if (!userId) {
            userId = 'g_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('personalization_id', userId);
        }

        return userId;
    }

    // Send event to server
    function trackEvent(eventType, data) {
        const userId = getUserId();

        fetch('/index.php?route=extension/module/personalization/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                user_id: userId,
                event_type: eventType,
                data: data
            })
        }).catch(e => console.log('Personalization error:', e));
    }

    // Track product view
    function trackProductView() {
        const match = window.location.search.match(/product_id=(\d+)/);
        if (match && !sessionStorage.getItem('tracked_' + match[1])) {
            sessionStorage.setItem('tracked_' + match[1], '1');
            trackEvent('view', { product_id: parseInt(match[1]) });
        }
    }

    // Track add to cart
    function trackAddToCart() {
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.url && settings.url.includes('checkout/cart/add')) {
                    const params = new URLSearchParams(settings.data);
                    const productId = params.get('product_id');
                    if (productId) {
                        trackEvent('add_to_cart', { product_id: parseInt(productId) });
                    }
                }
            });
        }
    }

    // Initialize Swiper with autoplay and responsive breakpoints
    function initSwiper() {
        const swiperElement = document.querySelector('.personalization-swiper');
        if (!swiperElement || !window.Swiper) return;

        new Swiper(swiperElement, {
            slidesPerView: 1,
            spaceBetween: 20,
            autoplay: false,
            navigation: {
                nextEl: '.swiper-container .swiper-button-next',
                prevEl: '.swiper-container .swiper-button-prev'
            },
            pagination: {
                el: '.personalization-swiper .swiper-pagination',
                clickable: true
            },
            breakpoints: {
                320: {
                    slidesPerView: 1,
                    spaceBetween: 15
                },
                480: {
                    slidesPerView: 2,
                    spaceBetween: 15
                },
                768: {
                    slidesPerView: 3,
                    spaceBetween: 20
                },
                992: {
                    slidesPerView: 4,
                    spaceBetween: 20
                }
            },
            loop: false,
            grabCursor: true
        });
    }

    // Initialize module
    function init() {
        loadSwiper().then(() => {
            initSwiper();
            trackProductView();
            trackAddToCart();
        }).catch(e => {
            console.error('Failed to load Swiper:', e);
        });
    }

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();