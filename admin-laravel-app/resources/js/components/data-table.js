import Alpine from 'alpinejs';

Alpine.data('dataTable', (options = {}) => ({
    data: options.data || [],
    filteredData: [],
    selectedItems: [],
    selectAll: false,
    sortColumn: options.defaultSort || null,
    sortDirection: 'asc',
    searchQuery: '',
    currentPage: 1,
    perPage: options.perPage || 10,
    loading: false,
    
    init() {
        this.filteredData = [...this.data];
        this.updatePagination();
        
        // Watch for changes
        this.$watch('searchQuery', () => {
            this.filterData();
            this.currentPage = 1;
            this.updatePagination();
        });
        
        this.$watch('selectAll', (value) => {
            if (value) {
                this.selectedItems = this.paginatedData.map(item => item.id);
            } else {
                this.selectedItems = [];
            }
        });
    },

    filterData() {
        if (!this.searchQuery) {
            this.filteredData = [...this.data];
            return;
        }

        const query = this.searchQuery.toLowerCase();
        this.filteredData = this.data.filter(item => {
            return Object.values(item).some(value => {
                if (value === null || value === undefined) return false;
                return value.toString().toLowerCase().includes(query);
            });
        });
    },

    sortBy(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }

        this.filteredData.sort((a, b) => {
            let aVal = a[column];
            let bVal = b[column];

            // Handle null/undefined values
            if (aVal === null || aVal === undefined) aVal = '';
            if (bVal === null || bVal === undefined) bVal = '';

            // Convert to strings for comparison
            aVal = aVal.toString().toLowerCase();
            bVal = bVal.toString().toLowerCase();

            if (this.sortDirection === 'asc') {
                return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
            } else {
                return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
            }
        });

        this.updatePagination();
    },

    toggleSelection(itemId) {
        const index = this.selectedItems.indexOf(itemId);
        if (index > -1) {
            this.selectedItems.splice(index, 1);
        } else {
            this.selectedItems.push(itemId);
        }
        
        this.selectAll = this.selectedItems.length === this.paginatedData.length;
    },

    isSelected(itemId) {
        return this.selectedItems.includes(itemId);
    },

    goToPage(page) {
        if (page >= 1 && page <= this.totalPages) {
            this.currentPage = page;
            this.updatePagination();
        }
    },

    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.updatePagination();
        }
    },

    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.updatePagination();
        }
    },

    updatePagination() {
        const start = (this.currentPage - 1) * this.perPage;
        const end = start + this.perPage;
        this.paginatedData = this.filteredData.slice(start, end);
        
        // Reset selection if items are no longer visible
        this.selectedItems = this.selectedItems.filter(id => 
            this.paginatedData.some(item => item.id === id)
        );
        
        this.selectAll = this.selectedItems.length === this.paginatedData.length && this.paginatedData.length > 0;
    },

    get totalPages() {
        return Math.ceil(this.filteredData.length / this.perPage);
    },

    get paginatedData() {
        const start = (this.currentPage - 1) * this.perPage;
        const end = start + this.perPage;
        return this.filteredData.slice(start, end);
    },

    get paginationInfo() {
        const start = (this.currentPage - 1) * this.perPage + 1;
        const end = Math.min(this.currentPage * this.perPage, this.filteredData.length);
        const total = this.filteredData.length;
        
        return `Showing ${start} to ${end} of ${total} entries`;
    },

    get paginationPages() {
        const pages = [];
        const maxVisible = 5;
        const half = Math.floor(maxVisible / 2);
        
        let start = Math.max(1, this.currentPage - half);
        let end = Math.min(this.totalPages, start + maxVisible - 1);
        
        if (end - start + 1 < maxVisible) {
            start = Math.max(1, end - maxVisible + 1);
        }
        
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        
        return pages;
    },

    getSortIcon(column) {
        if (this.sortColumn !== column) {
            return '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>';
        }
        
        if (this.sortDirection === 'asc') {
            return '<svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>';
        } else {
            return '<svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path></svg>';
        }
    },

    async bulkAction(action, endpoint) {
        if (this.selectedItems.length === 0) {
            window.showNotification('Please select at least one item', 'warning');
            return;
        }

        const confirmed = confirm(`Are you sure you want to ${action} ${this.selectedItems.length} item(s)?`);
        if (!confirmed) return;

        this.loading = true;
        
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    action: action,
                    items: this.selectedItems
                })
            });

            const result = await response.json();
            
            if (result.success) {
                window.showNotification(result.message || `${action} completed successfully`, 'success');
                
                // Remove deleted items from data
                if (action === 'delete') {
                    this.data = this.data.filter(item => !this.selectedItems.includes(item.id));
                    this.filterData();
                    this.updatePagination();
                }
                
                this.selectedItems = [];
                this.selectAll = false;
            } else {
                window.showNotification(result.message || `${action} failed`, 'error');
            }
        } catch (error) {
            console.error('Bulk action failed:', error);
            window.showNotification(`${action} failed`, 'error');
        } finally {
            this.loading = false;
        }
    },

    async refreshData(endpoint) {
        this.loading = true;
        
        try {
            const response = await fetch(endpoint);
            const result = await response.json();
            
            if (result.success) {
                this.data = result.data;
                this.filterData();
                this.updatePagination();
                this.selectedItems = [];
                this.selectAll = false;
            }
        } catch (error) {
            console.error('Failed to refresh data:', error);
            window.showNotification('Failed to refresh data', 'error');
        } finally {
            this.loading = false;
        }
    }
}));