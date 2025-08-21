/**
 * Forum Search Component
 * Handles advanced search functionality with autocomplete, filters, and real-time suggestions
 */

class ForumSearch {
    constructor(options = {}) {
        this.options = {
            searchInputId: 'forum-search-input',
            suggestionsContainerId: 'search-suggestions',
            filtersContainerId: 'search-filters',
            resultsContainerId: 'search-results',
            minQueryLength: 2,
            debounceDelay: 300,
            maxSuggestions: 10,
            ...options
        };

        this.searchInput = null;
        this.suggestionsContainer = null;
        this.filtersContainer = null;
        this.resultsContainer = null;
        this.debounceTimer = null;
        this.currentQuery = '';
        this.activeFilters = {};
        this.searchFilters = {};

        this.init();
    }

    init() {
        this.setupElements();
        this.setupEventListeners();
        this.loadSearchFilters();
        this.setupKeyboardNavigation();
    }

    setupElements() {
        this.searchInput = document.getElementById(this.options.searchInputId);
        this.suggestionsContainer = document.getElementById(this.options.suggestionsContainerId);
        this.filtersContainer = document.getElementById(this.options.filtersContainerId);
        this.resultsContainer = document.getElementById(this.options.resultsContainerId);

        if (!this.searchInput) {
            console.warn('Forum search input not found');
            return;
        }

        // Create suggestions container if it doesn't exist
        if (!this.suggestionsContainer) {
            this.createSuggestionsContainer();
        }
    }

    createSuggestionsContainer() {
        this.suggestionsContainer = document.createElement('div');
        this.suggestionsContainer.id = this.options.suggestionsContainerId;
        this.suggestionsContainer.className = 'absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden';
        
        // Insert after search input
        this.searchInput.parentNode.insertBefore(this.suggestionsContainer, this.searchInput.nextSibling);
    }

    setupEventListeners() {
        if (!this.searchInput) return;

        // Search input events
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });

        this.searchInput.addEventListener('focus', () => {
            if (this.currentQuery.length >= this.options.minQueryLength) {
                this.showSuggestions();
            }
        });

        this.searchInput.addEventListener('blur', () => {
            // Delay hiding to allow clicking on suggestions
            setTimeout(() => this.hideSuggestions(), 150);
        });

        // Filter change events
        document.addEventListener('change', (e) => {
            if (e.target.matches('.search-filter')) {
                this.handleFilterChange(e.target);
            }
        });

        // Advanced search toggle
        const advancedToggle = document.getElementById('advanced-search-toggle');
        if (advancedToggle) {
            advancedToggle.addEventListener('click', () => {
                this.toggleAdvancedSearch();
            });
        }

        // Search form submission
        const searchForm = document.getElementById('forum-search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }

        // Clear search button
        const clearButton = document.getElementById('clear-search');
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                this.clearSearch();
            });
        }

        // Click outside to hide suggestions
        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.suggestionsContainer.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }

    setupKeyboardNavigation() {
        if (!this.searchInput) return;

        this.searchInput.addEventListener('keydown', (e) => {
            const suggestions = this.suggestionsContainer.querySelectorAll('.suggestion-item');
            const activeSuggestion = this.suggestionsContainer.querySelector('.suggestion-item.active');
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.navigateSuggestions(suggestions, activeSuggestion, 'down');
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    this.navigateSuggestions(suggestions, activeSuggestion, 'up');
                    break;
                case 'Enter':
                    if (activeSuggestion) {
                        e.preventDefault();
                        this.selectSuggestion(activeSuggestion);
                    }
                    break;
                case 'Escape':
                    this.hideSuggestions();
                    break;
            }
        });
    }

    navigateSuggestions(suggestions, activeSuggestion, direction) {
        if (suggestions.length === 0) return;

        // Remove current active class
        if (activeSuggestion) {
            activeSuggestion.classList.remove('active');
        }

        let nextIndex = 0;
        if (activeSuggestion) {
            const currentIndex = Array.from(suggestions).indexOf(activeSuggestion);
            if (direction === 'down') {
                nextIndex = (currentIndex + 1) % suggestions.length;
            } else {
                nextIndex = currentIndex === 0 ? suggestions.length - 1 : currentIndex - 1;
            }
        }

        suggestions[nextIndex].classList.add('active');
        suggestions[nextIndex].scrollIntoView({ block: 'nearest' });
    }

    handleSearchInput(query) {
        this.currentQuery = query.trim();

        // Clear previous debounce timer
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        // Debounce the search suggestions
        this.debounceTimer = setTimeout(() => {
            if (this.currentQuery.length >= this.options.minQueryLength) {
                this.fetchSuggestions(this.currentQuery);
            } else {
                this.hideSuggestions();
            }
        }, this.options.debounceDelay);
    }

    async fetchSuggestions(query) {
        try {
            const response = await fetch(`/forums/search/suggestions?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to fetch suggestions');

            const data = await response.json();
            if (data.success) {
                this.displaySuggestions(data.suggestions);
            }
        } catch (error) {
            console.error('Error fetching search suggestions:', error);
        }
    }

    displaySuggestions(suggestions) {
        if (!this.suggestionsContainer || suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }

        const html = suggestions.map((suggestion, index) => `
            <div class="suggestion-item px-4 py-2 hover:bg-gray-100 cursor-pointer flex items-center space-x-2 ${index === 0 ? 'active' : ''}" 
                 data-suggestion="${this.escapeHtml(suggestion.text)}" 
                 data-type="${suggestion.type}">
                <i class="fas ${this.getSuggestionIcon(suggestion.type)} text-gray-500"></i>
                <span>${this.highlightQuery(suggestion.text, this.currentQuery)}</span>
                <span class="text-xs text-gray-400 ml-auto">${suggestion.type}</span>
            </div>
        `).join('');

        this.suggestionsContainer.innerHTML = html;
        this.showSuggestions();

        // Add click listeners to suggestions
        this.suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                this.selectSuggestion(item);
            });
        });
    }

    selectSuggestion(suggestionElement) {
        const suggestionText = suggestionElement.dataset.suggestion;
        this.searchInput.value = suggestionText;
        this.currentQuery = suggestionText;
        this.hideSuggestions();
        this.performSearch();
    }

    getSuggestionIcon(type) {
        const icons = {
            'forum': 'fa-comments',
            'thread': 'fa-list',
            'post': 'fa-comment',
            'user': 'fa-user'
        };
        return icons[type] || 'fa-search';
    }

    highlightQuery(text, query) {
        if (!query) return this.escapeHtml(text);
        
        const regex = new RegExp(`(${this.escapeRegex(query)})`, 'gi');
        return this.escapeHtml(text).replace(regex, '<mark class="bg-yellow-200">$1</mark>');
    }

    showSuggestions() {
        if (this.suggestionsContainer) {
            this.suggestionsContainer.classList.remove('hidden');
        }
    }

    hideSuggestions() {
        if (this.suggestionsContainer) {
            this.suggestionsContainer.classList.add('hidden');
        }
    }

    async loadSearchFilters() {
        try {
            const response = await fetch('/forums/search/filters', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to load search filters');

            const data = await response.json();
            if (data.success) {
                this.searchFilters = data.filters;
                this.populateFilterOptions();
            }
        } catch (error) {
            console.error('Error loading search filters:', error);
        }
    }

    populateFilterOptions() {
        // Populate category filter
        const categorySelect = document.getElementById('category-filter');
        if (categorySelect && this.searchFilters.categories) {
            this.populateSelectOptions(categorySelect, this.searchFilters.categories);
        }

        // Populate organization filter
        const organizationSelect = document.getElementById('organization-filter');
        if (organizationSelect && this.searchFilters.organizations) {
            this.populateSelectOptions(organizationSelect, this.searchFilters.organizations);
        }

        // Populate forum filter
        const forumSelect = document.getElementById('forum-filter');
        if (forumSelect && this.searchFilters.forums) {
            this.populateSelectOptions(forumSelect, this.searchFilters.forums);
        }

        // Populate sort options
        const sortSelect = document.getElementById('sort-filter');
        if (sortSelect && this.searchFilters.sort_options) {
            this.populateSelectOptions(sortSelect, this.searchFilters.sort_options);
        }
    }

    populateSelectOptions(selectElement, options) {
        // Keep the first option (usually "All" or placeholder)
        const firstOption = selectElement.querySelector('option');
        selectElement.innerHTML = '';
        if (firstOption) {
            selectElement.appendChild(firstOption);
        }

        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label;
            selectElement.appendChild(optionElement);
        });
    }

    handleFilterChange(filterElement) {
        const filterName = filterElement.name;
        const filterValue = filterElement.value;

        if (filterValue) {
            this.activeFilters[filterName] = filterValue;
        } else {
            delete this.activeFilters[filterName];
        }

        // Update filter display
        this.updateActiveFiltersDisplay();

        // Auto-search if there's a query
        if (this.currentQuery.length >= this.options.minQueryLength) {
            this.performSearch();
        }
    }

    updateActiveFiltersDisplay() {
        const activeFiltersContainer = document.getElementById('active-filters');
        if (!activeFiltersContainer) return;

        if (Object.keys(this.activeFilters).length === 0) {
            activeFiltersContainer.innerHTML = '';
            activeFiltersContainer.classList.add('hidden');
            return;
        }

        const filtersHtml = Object.entries(this.activeFilters).map(([key, value]) => {
            const label = this.getFilterLabel(key, value);
            return `
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                    ${label}
                    <button type="button" class="ml-2 text-blue-600 hover:text-blue-800" onclick="forumSearch.removeFilter('${key}')">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
            `;
        }).join('');

        activeFiltersContainer.innerHTML = `
            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-sm text-gray-600">Active filters:</span>
                ${filtersHtml}
                <button type="button" class="text-sm text-red-600 hover:text-red-800" onclick="forumSearch.clearAllFilters()">
                    Clear all
                </button>
            </div>
        `;
        activeFiltersContainer.classList.remove('hidden');
    }

    getFilterLabel(key, value) {
        const labelMaps = {
            category: this.searchFilters.categories,
            organization_id: this.searchFilters.organizations,
            forum_id: this.searchFilters.forums,
            sort: this.searchFilters.sort_options
        };

        const options = labelMaps[key];
        if (options) {
            const option = options.find(opt => opt.value == value);
            return option ? option.label : value;
        }

        return value;
    }

    removeFilter(filterName) {
        delete this.activeFilters[filterName];
        
        // Update form element
        const filterElement = document.querySelector(`[name="${filterName}"]`);
        if (filterElement) {
            filterElement.value = '';
        }

        this.updateActiveFiltersDisplay();

        // Re-search if there's a query
        if (this.currentQuery.length >= this.options.minQueryLength) {
            this.performSearch();
        }
    }

    clearAllFilters() {
        this.activeFilters = {};
        
        // Clear all filter form elements
        document.querySelectorAll('.search-filter').forEach(element => {
            element.value = '';
        });

        this.updateActiveFiltersDisplay();

        // Re-search if there's a query
        if (this.currentQuery.length >= this.options.minQueryLength) {
            this.performSearch();
        }
    }

    toggleAdvancedSearch() {
        const advancedFilters = document.getElementById('advanced-filters');
        const toggleButton = document.getElementById('advanced-search-toggle');
        
        if (advancedFilters && toggleButton) {
            const isHidden = advancedFilters.classList.contains('hidden');
            
            if (isHidden) {
                advancedFilters.classList.remove('hidden');
                toggleButton.innerHTML = '<i class="fas fa-chevron-up mr-2"></i>Hide Advanced Filters';
            } else {
                advancedFilters.classList.add('hidden');
                toggleButton.innerHTML = '<i class="fas fa-chevron-down mr-2"></i>Show Advanced Filters';
            }
        }
    }

    async performSearch() {
        if (!this.currentQuery && Object.keys(this.activeFilters).length === 0) {
            return;
        }

        this.showLoadingState();

        try {
            const params = new URLSearchParams({
                q: this.currentQuery,
                ...this.activeFilters
            });

            const response = await fetch(`/forums/search?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) throw new Error('Search failed');

            const html = await response.text();
            this.updateSearchResults(html);
            
            // Update URL without page reload
            const newUrl = `/forums/search?${params}`;
            window.history.pushState({}, '', newUrl);

        } catch (error) {
            console.error('Search error:', error);
            this.showErrorState();
        } finally {
            this.hideLoadingState();
        }
    }

    updateSearchResults(html) {
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = html;
        }
    }

    showLoadingState() {
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">Searching...</p>
                    </div>
                </div>
            `;
        }
    }

    showErrorState() {
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle text-3xl text-red-400 mb-4"></i>
                        <p class="text-gray-600">Search failed. Please try again.</p>
                    </div>
                </div>
            `;
        }
    }

    hideLoadingState() {
        // Loading state is replaced by results or error state
    }

    clearSearch() {
        this.searchInput.value = '';
        this.currentQuery = '';
        this.clearAllFilters();
        this.hideSuggestions();
        
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = '';
        }

        // Update URL
        window.history.pushState({}, '', '/forums/search');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize forum search if we're on a search page
    if (document.getElementById('forum-search-input')) {
        window.forumSearch = new ForumSearch();
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ForumSearch;
}