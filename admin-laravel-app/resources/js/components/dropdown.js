import Alpine from 'alpinejs';

Alpine.data('dropdown', (options = {}) => ({
    open: false,
    placement: options.placement || 'bottom-start',
    
    init() {
        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!this.$el.contains(event.target)) {
                this.open = false;
            }
        });

        // Close dropdown on escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.open) {
                this.open = false;
                this.$refs.trigger?.focus();
            }
        });
    },

    toggle() {
        this.open = !this.open;
        
        if (this.open) {
            this.$nextTick(() => {
                this.focusFirstItem();
            });
        }
    },

    show() {
        this.open = true;
        this.$nextTick(() => {
            this.focusFirstItem();
        });
    },

    hide() {
        this.open = false;
    },

    focusFirstItem() {
        const firstItem = this.$refs.menu?.querySelector('[role="menuitem"], a, button');
        if (firstItem) {
            firstItem.focus();
        }
    },

    handleKeydown(event) {
        if (!this.open) return;

        const items = Array.from(this.$refs.menu?.querySelectorAll('[role="menuitem"], a, button') || []);
        const currentIndex = items.indexOf(document.activeElement);

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                items[nextIndex]?.focus();
                break;
            case 'ArrowUp':
                event.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                items[prevIndex]?.focus();
                break;
            case 'Home':
                event.preventDefault();
                items[0]?.focus();
                break;
            case 'End':
                event.preventDefault();
                items[items.length - 1]?.focus();
                break;
            case 'Enter':
            case ' ':
                event.preventDefault();
                document.activeElement?.click();
                break;
        }
    }
}));