import Alpine from 'alpinejs';

Alpine.data('tabs', (defaultTab = 0) => ({
    activeTab: defaultTab,
    
    init() {
        // Handle keyboard navigation
        this.$el.addEventListener('keydown', (event) => {
            this.handleKeydown(event);
        });
    },

    setActiveTab(index) {
        this.activeTab = index;
        
        // Focus the active tab for accessibility
        this.$nextTick(() => {
            const activeTabButton = this.$refs.tablist?.children[index]?.querySelector('[role="tab"]');
            if (activeTabButton) {
                activeTabButton.focus();
            }
        });
    },

    isActive(index) {
        return this.activeTab === index;
    },

    handleKeydown(event) {
        const tabs = Array.from(this.$refs.tablist?.querySelectorAll('[role="tab"]') || []);
        const currentIndex = tabs.indexOf(event.target);
        
        if (currentIndex === -1) return;

        switch (event.key) {
            case 'ArrowLeft':
                event.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : tabs.length - 1;
                this.setActiveTab(prevIndex);
                break;
            case 'ArrowRight':
                event.preventDefault();
                const nextIndex = currentIndex < tabs.length - 1 ? currentIndex + 1 : 0;
                this.setActiveTab(nextIndex);
                break;
            case 'Home':
                event.preventDefault();
                this.setActiveTab(0);
                break;
            case 'End':
                event.preventDefault();
                this.setActiveTab(tabs.length - 1);
                break;
        }
    },

    getTabId(index) {
        return `tab-${index}`;
    },

    getPanelId(index) {
        return `panel-${index}`;
    }
}));