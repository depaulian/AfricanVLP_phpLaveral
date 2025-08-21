import Alpine from 'alpinejs';

Alpine.data('modal', (initialOpen = false) => ({
    open: initialOpen,
    
    init() {
        // Close modal on escape key
        this.$watch('open', (value) => {
            if (value) {
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => {
                    this.$refs.modal?.focus();
                });
            } else {
                document.body.style.overflow = 'auto';
            }
        });
    },

    show() {
        this.open = true;
    },

    hide() {
        this.open = false;
    },

    toggle() {
        this.open = !this.open;
    },

    closeOnEscape(event) {
        if (event.key === 'Escape') {
            this.hide();
        }
    },

    closeOnBackdrop(event) {
        if (event.target === event.currentTarget) {
            this.hide();
        }
    }
}));

Alpine.data('confirmModal', () => ({
    open: false,
    title: 'Confirm Action',
    message: 'Are you sure you want to proceed?',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    confirmCallback: null,
    cancelCallback: null,
    
    show(options = {}) {
        this.title = options.title || this.title;
        this.message = options.message || this.message;
        this.confirmText = options.confirmText || this.confirmText;
        this.cancelText = options.cancelText || this.cancelText;
        this.confirmCallback = options.onConfirm || null;
        this.cancelCallback = options.onCancel || null;
        this.open = true;
    },

    confirm() {
        if (this.confirmCallback) {
            this.confirmCallback();
        }
        this.hide();
    },

    cancel() {
        if (this.cancelCallback) {
            this.cancelCallback();
        }
        this.hide();
    },

    hide() {
        this.open = false;
        this.confirmCallback = null;
        this.cancelCallback = null;
    }
}));