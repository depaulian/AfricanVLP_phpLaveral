import Alpine from 'alpinejs';

Alpine.data('tooltip', (content, options = {}) => ({
    show: false,
    content: content,
    placement: options.placement || 'top',
    delay: options.delay || 100,
    timeout: null,
    
    init() {
        this.$el.addEventListener('mouseenter', () => this.showTooltip());
        this.$el.addEventListener('mouseleave', () => this.hideTooltip());
        this.$el.addEventListener('focus', () => this.showTooltip());
        this.$el.addEventListener('blur', () => this.hideTooltip());
    },

    showTooltip() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
        
        this.timeout = setTimeout(() => {
            this.show = true;
            this.$nextTick(() => {
                this.positionTooltip();
            });
        }, this.delay);
    },

    hideTooltip() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
        
        this.show = false;
    },

    positionTooltip() {
        const tooltip = this.$refs.tooltip;
        const trigger = this.$el;
        
        if (!tooltip || !trigger) return;

        const triggerRect = trigger.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        let top, left;
        
        switch (this.placement) {
            case 'top':
                top = triggerRect.top - tooltipRect.height - 8;
                left = triggerRect.left + (triggerRect.width - tooltipRect.width) / 2;
                break;
            case 'bottom':
                top = triggerRect.bottom + 8;
                left = triggerRect.left + (triggerRect.width - tooltipRect.width) / 2;
                break;
            case 'left':
                top = triggerRect.top + (triggerRect.height - tooltipRect.height) / 2;
                left = triggerRect.left - tooltipRect.width - 8;
                break;
            case 'right':
                top = triggerRect.top + (triggerRect.height - tooltipRect.height) / 2;
                left = triggerRect.right + 8;
                break;
            default:
                top = triggerRect.top - tooltipRect.height - 8;
                left = triggerRect.left + (triggerRect.width - tooltipRect.width) / 2;
        }
        
        // Keep tooltip within viewport
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        if (left < 8) {
            left = 8;
        } else if (left + tooltipRect.width > viewportWidth - 8) {
            left = viewportWidth - tooltipRect.width - 8;
        }
        
        if (top < 8) {
            top = triggerRect.bottom + 8;
        } else if (top + tooltipRect.height > viewportHeight - 8) {
            top = triggerRect.top - tooltipRect.height - 8;
        }
        
        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
    },

    get arrowClass() {
        const classes = {
            top: 'top-full left-1/2 transform -translate-x-1/2 border-l-transparent border-r-transparent border-b-transparent border-t-gray-900',
            bottom: 'bottom-full left-1/2 transform -translate-x-1/2 border-l-transparent border-r-transparent border-t-transparent border-b-gray-900',
            left: 'left-full top-1/2 transform -translate-y-1/2 border-t-transparent border-b-transparent border-r-transparent border-l-gray-900',
            right: 'right-full top-1/2 transform -translate-y-1/2 border-t-transparent border-b-transparent border-l-transparent border-r-gray-900'
        };
        return classes[this.placement] || classes.top;
    }
}));