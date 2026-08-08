import './bootstrap';
import 'bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const appContent = document.getElementById('app-content');
    if (!appContent) return;

    // SPA Navigation Handler
    const navigateTo = async (url, pushToHistory = true) => {
        try {
            appContent.style.transition = 'opacity 0.15s ease';
            appContent.style.opacity = '0.4';

            const response = await fetch(url, {
                headers: {
                    'X-SPA-Request': 'true',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                window.location.href = url;
                return;
            }

            const htmlText = await response.text();

            // Extract content and title from response
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');

            const newContent = doc.getElementById('app-content') ? doc.getElementById('app-content').innerHTML : doc.body.innerHTML;
            const newTitle = doc.querySelector('title') ? doc.querySelector('title').innerText : document.title;

            appContent.innerHTML = newContent;
            document.title = newTitle;

            if (pushToHistory) {
                window.history.pushState({ url }, '', url);
            }

            // Update active link state in sidebar
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                if (link.getAttribute('href') === url || link.href === window.location.href) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });

            // Re-execute scripts embedded in swapped content
            appContent.querySelectorAll('script').forEach(script => {
                const newScript = document.createElement('script');
                Array.from(script.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(script.innerHTML));
                script.parentNode.replaceChild(newScript, script);
            });

            // Trigger DOMContentLoaded equivalent for page-specific JS initializers
            document.dispatchEvent(new Event('spa:loaded'));
            window.scrollTo(0, 0);
        } catch (err) {
            console.error('SPA Navigation error, falling back to full reload:', err);
            window.location.href = url;
        } finally {
            appContent.style.opacity = '1';
        }
    };

    // Intercept click on links
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        // Only intercept same-origin links
        if (link.origin === window.location.origin) {
            e.preventDefault();
            if (window.location.href !== link.href) {
                navigateTo(link.href);
            }
        }
    });

    // Handle Browser Back / Forward
    window.addEventListener('popstate', (e) => {
        navigateTo(window.location.href, false);
    });

    // Web Splash Screen Handler
    const splashScreen = document.getElementById('splash-screen');
    if (splashScreen) {
        const hasVisited = sessionStorage.getItem('sidodadi_splash_shown');
        if (hasVisited) {
            splashScreen.style.display = 'none';
        } else {
            const progressBar = document.getElementById('splash-progress-bar');
            const statusText = document.getElementById('splash-status');

            const steps = [
                { progress: '35%', text: 'Menyiapkan berkas sistem...' },
                { progress: '70%', text: 'Memuat data kependudukan...' },
                { progress: '100%', text: 'Sistem siap!' }
            ];

            let currentStep = 0;
            const interval = setInterval(() => {
                if (currentStep < steps.length) {
                    if (progressBar) progressBar.style.width = steps[currentStep].progress;
                    if (statusText) statusText.innerText = steps[currentStep].text;
                    currentStep++;
                } else {
                    clearInterval(interval);
                    setTimeout(() => {
                        splashScreen.classList.add('fade-out');
                        sessionStorage.setItem('sidodadi_splash_shown', 'true');
                        setTimeout(() => {
                            splashScreen.style.display = 'none';
                        }, 600);
                    }, 300);
                }
            }, 350);
        }
    }

    // Sticky Heading Scroll Morphing Handler (Digdaya / Gendongkulon inspired)
    const initStickyHeadingScroll = () => {
        const stickyElements = document.querySelectorAll('.sticky-heading, .resident-storage-summary');
        if (!stickyElements.length) return;

        const handleScroll = () => {
            const isScrolled = window.scrollY > 15;
            stickyElements.forEach(el => {
                if (isScrolled) {
                    el.classList.add('scrolled');
                } else {
                    el.classList.remove('scrolled');
                }
            });
        };

        window.removeEventListener('scroll', handleScroll);
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
    };

    initStickyHeadingScroll();
    document.addEventListener('spa:loaded', initStickyHeadingScroll);
});


