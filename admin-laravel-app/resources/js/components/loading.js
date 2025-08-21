import Alpine from 'alpinejs';

Alpine.data('loading', (initialState = false) => ({
    loading: initialState,
    
    start() {
        this.loading = true;
    },

    stop() {
        this.loading = false;
    },

    toggle() {
        this.loading = !this.loading;
    },

    async withLoading(asyncFunction) {
        this.start();
        try {
            const result = await asyncFunction();
            return result;
        } finally {
            this.stop();
        }
    }
}));

Alpine.data('buttonLoading', () => ({
    loading: false,
    originalText: '',
    
    init() {
        this.originalText = this.$el.textContent.trim();
    },

    start(loadingText = 'Loading...') {
        this.loading = true;
        this.$el.disabled = true;
        this.$el.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            ${loadingText}
        `;
    },

    stop() {
        this.loading = false;
        this.$el.disabled = false;
        this.$el.textContent = this.originalText;
    },

    async withLoading(asyncFunction, loadingText = 'Loading...') {
        this.start(loadingText);
        try {
            const result = await asyncFunction();
            return result;
        } finally {
            this.stop();
        }
    }
}));

Alpine.data('pageLoading', () => ({
    loading: false,
    progress: 0,
    
    start() {
        this.loading = true;
        this.progress = 0;
        this.simulateProgress();
    },

    stop() {
        this.progress = 100;
        setTimeout(() => {
            this.loading = false;
            this.progress = 0;
        }, 200);
    },

    simulateProgress() {
        if (!this.loading) return;
        
        const increment = Math.random() * 15;
        this.progress = Math.min(this.progress + increment, 90);
        
        setTimeout(() => {
            this.simulateProgress();
        }, 200);
    }
}));