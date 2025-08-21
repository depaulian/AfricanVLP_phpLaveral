import Alpine from 'alpinejs';

Alpine.data('accordion', (options = {}) => ({
    items: [],
    allowMultiple: options.allowMultiple || false,
    
    init() {
        // Initialize items from DOM
        const accordionItems = this.$el.querySelectorAll('[data-accordion-item]');
        this.items = Array.from(accordionItems).map((item, index) => ({
            id: index,
            open: item.hasAttribute('data-open'),
            element: item
        }));
    },

    toggle(itemId) {
        const item = this.items.find(i => i.id === itemId);
        if (!item) return;

        if (!this.allowMultiple) {
            // Close all other items
            this.items.forEach(i => {
                if (i.id !== itemId) {
                    i.open = false;
                }
            });
        }

        item.open = !item.open;
        
        // Update DOM
        this.updateDOM();
    },

    open(itemId) {
        const item = this.items.find(i => i.id === itemId);
        if (!item) return;

        if (!this.allowMultiple) {
            this.items.forEach(i => {
                i.open = i.id === itemId;
            });
        } else {
            item.open = true;
        }
        
        this.updateDOM();
    },

    close(itemId) {
        const item = this.items.find(i => i.id === itemId);
        if (!item) return;

        item.open = false;
        this.updateDOM();
    },

    closeAll() {
        this.items.forEach(item => {
            item.open = false;
        });
        this.updateDOM();
    },

    openAll() {
        if (this.allowMultiple) {
            this.items.forEach(item => {
                item.open = true;
            });
            this.updateDOM();
        }
    },

    isOpen(itemId) {
        const item = this.items.find(i => i.id === itemId);
        return item ? item.open : false;
    },

    updateDOM() {
        this.items.forEach(item => {
            const button = item.element.querySelector('[data-accordion-trigger]');
            const content = item.element.querySelector('[data-accordion-content]');
            
            if (button) {
                button.setAttribute('aria-expanded', item.open.toString());
            }
            
            if (content) {
                if (item.open) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                    content.classList.remove('hidden');
                } else {
                    content.style.maxHeight = '0px';
                    setTimeout(() => {
                        if (!item.open) {
                            content.classList.add('hidden');
                        }
                    }, 300);
                }
            }
        });
    },

    handleKeydown(event, itemId) {
        switch (event.key) {
            case 'Enter':
            case ' ':
                event.preventDefault();
                this.toggle(itemId);
                break;
            case 'ArrowDown':
                event.preventDefault();
                this.focusNext(itemId);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.focusPrevious(itemId);
                break;
            case 'Home':
                event.preventDefault();
                this.focusFirst();
                break;
            case 'End':
                event.preventDefault();
                this.focusLast();
                break;
        }
    },

    focusNext(currentId) {
        const nextId = currentId < this.items.length - 1 ? currentId + 1 : 0;
        this.focusItem(nextId);
    },

    focusPrevious(currentId) {
        const prevId = currentId > 0 ? currentId - 1 : this.items.length - 1;
        this.focusItem(prevId);
    },

    focusFirst() {
        this.focusItem(0);
    },

    focusLast() {
        this.focusItem(this.items.length - 1);
    },

    focusItem(itemId) {
        const item = this.items.find(i => i.id === itemId);
        if (item) {
            const button = item.element.querySelector('[data-accordion-trigger]');
            if (button) {
                button.focus();
            }
        }
    }
}));