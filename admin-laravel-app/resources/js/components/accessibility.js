// Accessibility utilities and components for WCAG 2.1 AA compliance
export default function accessibility() {
    return {
        // Focus management
        trapFocus(element) {
            const focusableElements = element.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            element.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === firstElement) {
                            lastElement.focus();
                            e.preventDefault();
                        }
                    } else {
                        if (document.activeElement === lastElement) {
                            firstElement.focus();
                            e.preventDefault();
                        }
                    }
                }
            });
        },

        // Announce to screen readers
        announce(message, priority = 'polite') {
            const announcer = document.createElement('div');
            announcer.setAttribute('aria-live', priority);
            announcer.setAttribute('aria-atomic', 'true');
            announcer.className = 'sr-only';
            document.body.appendChild(announcer);
            
            setTimeout(() => {
                announcer.textContent = message;
            }, 100);
            
            setTimeout(() => {
                document.body.removeChild(announcer);
            }, 3000);
        },

        // Skip link functionality
        skipLink() {
            return {
                show: false,
                init() {
                    this.$el.addEventListener('focus', () => {
                        this.show = true;
                    });
                    this.$el.addEventListener('blur', () => {
                        this.show = false;
                    });
                },
                skipToMain() {
                    const mainContent = document.getElementById('main-content');
                    if (mainContent) {
                        mainContent.focus();
                        mainContent.scrollIntoView();
                    }
                }
            };
        },

        // High contrast mode detection
        highContrastMode() {
            return {
                enabled: false,
                init() {
                    // Check for high contrast preference
                    if (window.matchMedia('(prefers-contrast: high)').matches) {
                        this.enabled = true;
                        document.documentElement.classList.add('high-contrast');
                    }
                    
                    // Listen for changes
                    window.matchMedia('(prefers-contrast: high)').addEventListener('change', (e) => {
                        this.enabled = e.matches;
                        document.documentElement.classList.toggle('high-contrast', e.matches);
                    });
                }
            };
        },

        // Reduced motion detection
        reducedMotion() {
            return {
                enabled: false,
                init() {
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        this.enabled = true;
                        document.documentElement.classList.add('reduced-motion');
                    }
                    
                    window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', (e) => {
                        this.enabled = e.matches;
                        document.documentElement.classList.toggle('reduced-motion', e.matches);
                    });
                }
            };
        },

        // Keyboard navigation helper
        keyboardNavigation() {
            return {
                init() {
                    // Show focus indicators when using keyboard
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Tab') {
                            document.body.classList.add('keyboard-navigation');
                        }
                    });
                    
                    document.addEventListener('mousedown', () => {
                        document.body.classList.remove('keyboard-navigation');
                    });
                }
            };
        },

        // ARIA live region for dynamic content
        liveRegion() {
            return {
                messages: [],
                addMessage(message, type = 'status') {
                    this.messages.push({
                        id: Date.now(),
                        text: message,
                        type: type,
                        timestamp: new Date().toLocaleTimeString()
                    });
                    
                    // Auto-remove after 5 seconds
                    setTimeout(() => {
                        this.removeMessage(this.messages[this.messages.length - 1].id);
                    }, 5000);
                },
                removeMessage(id) {
                    this.messages = this.messages.filter(msg => msg.id !== id);
                }
            };
        }
    };
}

// Color contrast utilities
export const colorContrast = {
    // Calculate contrast ratio between two colors
    getContrastRatio(color1, color2) {
        const l1 = this.getLuminance(color1);
        const l2 = this.getLuminance(color2);
        const lighter = Math.max(l1, l2);
        const darker = Math.min(l1, l2);
        return (lighter + 0.05) / (darker + 0.05);
    },

    // Get relative luminance of a color
    getLuminance(color) {
        const rgb = this.hexToRgb(color);
        const rsRGB = rgb.r / 255;
        const gsRGB = rgb.g / 255;
        const bsRGB = rgb.b / 255;

        const r = rsRGB <= 0.03928 ? rsRGB / 12.92 : Math.pow((rsRGB + 0.055) / 1.055, 2.4);
        const g = gsRGB <= 0.03928 ? gsRGB / 12.92 : Math.pow((gsRGB + 0.055) / 1.055, 2.4);
        const b = bsRGB <= 0.03928 ? bsRGB / 12.92 : Math.pow((bsRGB + 0.055) / 1.055, 2.4);

        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    },

    // Convert hex to RGB
    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    },

    // Check if contrast meets WCAG AA standards
    meetsWCAG_AA(color1, color2) {
        return this.getContrastRatio(color1, color2) >= 4.5;
    },

    // Check if contrast meets WCAG AAA standards
    meetsWCAG_AAA(color1, color2) {
        return this.getContrastRatio(color1, color2) >= 7;
    }
};