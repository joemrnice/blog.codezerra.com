/**
 * Main JavaScript for CodeZerra Blog
 * Handles common functionality across the site
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initMobileMenu();
        initSearchForm();
        initImageLazyLoading();
        initSmoothScroll();
        initCopyToClipboard();
        initBackToTop();
    }

    /**
     * Mobile Menu Toggle
     */
    function initMobileMenu() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuOverlay = document.getElementById('menu-overlay');

        if (!mobileMenuButton || !mobileMenu) return;

        // Toggle menu
        mobileMenuButton.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            this.setAttribute('aria-expanded', !isExpanded);
            mobileMenu.classList.toggle('hidden');
            
            if (menuOverlay) {
                menuOverlay.classList.toggle('hidden');
            }

            // Toggle hamburger icon
            const hamburgerIcon = this.querySelector('.hamburger-icon');
            const closeIcon = this.querySelector('.close-icon');
            
            if (hamburgerIcon && closeIcon) {
                hamburgerIcon.classList.toggle('hidden');
                closeIcon.classList.toggle('hidden');
            }

            // Prevent body scroll when menu is open
            document.body.classList.toggle('overflow-hidden');
        });

        // Close menu when clicking overlay
        if (menuOverlay) {
            menuOverlay.addEventListener('click', function() {
                mobileMenuButton.click();
            });
        }

        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
                mobileMenuButton.click();
            }
        });

        // Close menu when clicking a link
        const menuLinks = mobileMenu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (!mobileMenu.classList.contains('hidden')) {
                    mobileMenuButton.click();
                }
            });
        });
    }

    /**
     * Search Form Handler
     */
    function initSearchForm() {
        const searchForms = document.querySelectorAll('.search-form');
        const searchInputs = document.querySelectorAll('.search-input');

        searchForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const input = this.querySelector('.search-input, input[name="q"]');
                
                if (input && !input.value.trim()) {
                    e.preventDefault();
                    input.focus();
                    showNotification('Please enter a search term', 'warning');
                }
            });
        });

        // Add search icon toggle
        const searchToggles = document.querySelectorAll('.search-toggle');
        searchToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const searchContainer = document.getElementById('search-container');
                if (searchContainer) {
                    searchContainer.classList.toggle('hidden');
                    const input = searchContainer.querySelector('input');
                    if (input && !searchContainer.classList.contains('hidden')) {
                        setTimeout(() => input.focus(), 100);
                    }
                }
            });
        });

        // Add live search suggestions (if search input exists)
        searchInputs.forEach(input => {
            let debounceTimer;
            
            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.trim();
                
                if (query.length < 2) return;
                
                debounceTimer = setTimeout(() => {
                    fetchSearchSuggestions(query, input);
                }, 300);
            });
        });
    }

    /**
     * Fetch search suggestions
     */
    function fetchSearchSuggestions(query, inputElement) {
        // Only fetch if search endpoint exists
        fetch(`/api/search-suggestions.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.suggestions) {
                    showSearchSuggestions(data.suggestions, inputElement);
                }
            })
            .catch(error => {
                console.log('Search suggestions not available:', error);
            });
    }

    /**
     * Show search suggestions
     */
    function showSearchSuggestions(suggestions, inputElement) {
        // Remove existing suggestions
        const existingSuggestions = document.getElementById('search-suggestions');
        if (existingSuggestions) {
            existingSuggestions.remove();
        }

        if (!suggestions || suggestions.length === 0) return;

        // Create suggestions dropdown
        const suggestionsDiv = document.createElement('div');
        suggestionsDiv.id = 'search-suggestions';
        suggestionsDiv.className = 'absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto';

        suggestions.forEach(suggestion => {
            const item = document.createElement('a');
            item.href = `/post.php?slug=${suggestion.slug}`;
            item.className = 'block px-4 py-2 hover:bg-gray-100 transition-colors';
            item.innerHTML = `
                <div class="font-medium text-gray-900">${highlightMatch(suggestion.title, inputElement.value)}</div>
                ${suggestion.excerpt ? `<div class="text-sm text-gray-600">${suggestion.excerpt}</div>` : ''}
            `;
            suggestionsDiv.appendChild(item);
        });

        // Position and insert suggestions
        const parent = inputElement.parentElement;
        parent.style.position = 'relative';
        parent.appendChild(suggestionsDiv);

        // Close suggestions on click outside
        document.addEventListener('click', function closeOnClickOutside(e) {
            if (!parent.contains(e.target)) {
                suggestionsDiv.remove();
                document.removeEventListener('click', closeOnClickOutside);
            }
        });
    }

    /**
     * Highlight search term in text
     */
    function highlightMatch(text, query) {
        const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
        return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
    }

    /**
     * Escape regex special characters
     */
    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Image Lazy Loading
     */
    function initImageLazyLoading() {
        const images = document.querySelectorAll('img[data-src], img[loading="lazy"]');

        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        
                        // Load image
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }

                        // Add fade-in effect
                        img.classList.add('fade-in');
                        
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px'
            });

            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for older browsers
            images.forEach(img => {
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
            });
        }
    }

    /**
     * Smooth Scroll for Anchor Links
     */
    function initSmoothScroll() {
        const anchorLinks = document.querySelectorAll('a[href^="#"]');

        anchorLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // Skip empty anchors or # only
                if (!href || href === '#' || href === '#!') return;

                const target = document.querySelector(href);
                
                if (target) {
                    e.preventDefault();
                    
                    const offsetTop = target.getBoundingClientRect().top + window.pageYOffset;
                    const headerHeight = document.querySelector('header')?.offsetHeight || 0;
                    
                    window.scrollTo({
                        top: offsetTop - headerHeight - 20,
                        behavior: 'smooth'
                    });

                    // Update URL
                    if (history.pushState) {
                        history.pushState(null, null, href);
                    }

                    // Focus target for accessibility
                    target.focus({ preventScroll: true });
                }
            });
        });
    }

    /**
     * Copy to Clipboard Function
     */
    function initCopyToClipboard() {
        // Add copy buttons to code blocks
        const codeBlocks = document.querySelectorAll('pre code');

        codeBlocks.forEach(codeBlock => {
            const pre = codeBlock.parentElement;
            const wrapper = document.createElement('div');
            wrapper.className = 'code-block-wrapper relative';
            
            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(pre);

            const copyButton = document.createElement('button');
            copyButton.className = 'copy-button absolute top-2 right-2 px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white text-xs rounded transition-colors';
            copyButton.textContent = 'Copy';
            copyButton.setAttribute('aria-label', 'Copy code to clipboard');

            copyButton.addEventListener('click', function() {
                copyToClipboard(codeBlock.textContent, this);
            });

            wrapper.appendChild(copyButton);
        });

        // Handle copy buttons with data-copy attribute
        const copyButtons = document.querySelectorAll('[data-copy]');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const text = this.getAttribute('data-copy');
                copyToClipboard(text, this);
            });
        });
    }

    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text, button) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showCopySuccess(button);
            }).catch(err => {
                console.error('Failed to copy:', err);
                showNotification('Failed to copy', 'error');
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                showCopySuccess(button);
            } catch (err) {
                console.error('Failed to copy:', err);
                showNotification('Failed to copy', 'error');
            }
            
            document.body.removeChild(textArea);
        }
    }

    /**
     * Show copy success feedback
     */
    function showCopySuccess(button) {
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('bg-green-600');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-600');
        }, 2000);
    }

    /**
     * Back to Top Button
     */
    function initBackToTop() {
        const backToTopButton = document.getElementById('back-to-top');
        
        if (backToTopButton) {
            // Show/hide button based on scroll position
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.remove('hidden');
                    backToTopButton.classList.add('opacity-100');
                } else {
                    backToTopButton.classList.remove('opacity-100');
                    backToTopButton.classList.add('hidden');
                }
            });

            // Scroll to top on click
            backToTopButton.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    }

    /**
     * Show notification (toast)
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 ${
            type === 'success' ? 'bg-green-600' :
            type === 'error' ? 'bg-red-600' :
            type === 'warning' ? 'bg-yellow-600' :
            'bg-blue-600'
        }`;
        notification.textContent = message;
        notification.style.transform = 'translateX(400px)';

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Expose utilities to global scope if needed
    window.CodeZerra = window.CodeZerra || {};
    window.CodeZerra.copyToClipboard = copyToClipboard;
    window.CodeZerra.showNotification = showNotification;

})();
