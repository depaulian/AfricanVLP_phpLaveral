// Contextual help and user guidance system
export default function helpSystem() {
    return {
        activeHelp: null,
        helpVisible: false,
        tourActive: false,
        tourStep: 0,
        tourSteps: [],
        
        init() {
            this.setupHelpTriggers();
            this.setupKeyboardShortcuts();
            this.loadTourSteps();
        },
        
        // Setup help triggers
        setupHelpTriggers() {
            // Help buttons
            document.querySelectorAll('[data-help]').forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showHelp(trigger.getAttribute('data-help'));
                });
                
                // Add keyboard support
                trigger.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.showHelp(trigger.getAttribute('data-help'));
                    }
                });
            });
            
            // Field help on focus
            document.querySelectorAll('input, select, textarea').forEach(field => {
                const helpId = field.getAttribute('data-help-id');
                if (helpId) {
                    field.addEventListener('focus', () => {
                        this.showFieldHelp(helpId);
                    });
                    
                    field.addEventListener('blur', () => {
                        this.hideFieldHelp(helpId);
                    });
                }
            });
        },
        
        // Setup keyboard shortcuts
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // F1 for help
                if (e.key === 'F1') {
                    e.preventDefault();
                    this.showContextualHelp();
                }
                
                // Escape to close help
                if (e.key === 'Escape' && this.helpVisible) {
                    this.hideHelp();
                }
                
                // Ctrl+Shift+? for help menu
                if (e.ctrlKey && e.shiftKey && e.key === '?') {
                    e.preventDefault();
                    this.showHelpMenu();
                }
            });
        },
        
        // Load tour steps from data attributes or configuration
        loadTourSteps() {
            const tourData = document.querySelector('[data-tour-steps]');
            if (tourData) {
                try {
                    this.tourSteps = JSON.parse(tourData.getAttribute('data-tour-steps'));
                } catch (e) {
                    console.warn('Invalid tour steps data:', e);
                }
            }
        },
        
        // Show help for specific topic
        showHelp(helpId) {
            this.activeHelp = helpId;
            this.helpVisible = true;
            
            // Load help content
            this.loadHelpContent(helpId);
            
            // Announce to screen readers
            this.announceHelp(`Help opened for ${helpId}`);
            
            // Focus help content
            this.$nextTick(() => {
                const helpContent = document.getElementById('help-content');
                if (helpContent) {
                    helpContent.focus();
                }
            });
        },
        
        // Show contextual help based on current page/section
        showContextualHelp() {
            const currentSection = this.getCurrentSection();
            const helpId = `help-${currentSection}`;
            this.showHelp(helpId);
        },
        
        // Show field-specific help
        showFieldHelp(helpId) {
            const helpElement = document.getElementById(helpId);
            if (helpElement) {
                helpElement.classList.remove('hidden');
                helpElement.setAttribute('aria-hidden', 'false');
            }
        },
        
        // Hide field-specific help
        hideFieldHelp(helpId) {
            const helpElement = document.getElementById(helpId);
            if (helpElement) {
                helpElement.classList.add('hidden');
                helpElement.setAttribute('aria-hidden', 'true');
            }
        },
        
        // Hide help
        hideHelp() {
            this.helpVisible = false;
            this.activeHelp = null;
            
            // Return focus to trigger element
            const trigger = document.querySelector(`[data-help="${this.activeHelp}"]`);
            if (trigger) {
                trigger.focus();
            }
        },
        
        // Show help menu
        showHelpMenu() {
            this.showHelp('help-menu');
        },
        
        // Load help content (could be from API or local data)
        async loadHelpContent(helpId) {
            try {
                // Try to load from local help data first
                const helpData = this.getLocalHelpData(helpId);
                if (helpData) {
                    this.displayHelpContent(helpData);
                    return;
                }
                
                // Fallback to API if available
                const response = await fetch(`/api/help/${helpId}`);
                if (response.ok) {
                    const data = await response.json();
                    this.displayHelpContent(data);
                } else {
                    this.displayHelpContent({
                        title: 'Help Not Available',
                        content: 'Help content for this section is not available at the moment.'
                    });
                }
            } catch (error) {
                console.error('Error loading help content:', error);
                this.displayHelpContent({
                    title: 'Error',
                    content: 'Unable to load help content. Please try again later.'
                });
            }
        },
        
        // Get local help data
        getLocalHelpData(helpId) {
            const helpData = {
                'user-management': {
                    title: 'User Management',
                    content: `
                        <h3>Managing Users</h3>
                        <p>Use this interface to create, edit, and manage user accounts.</p>
                        <ul>
                            <li><strong>Add User:</strong> Click the "Add User" button to create a new account</li>
                            <li><strong>Edit User:</strong> Click the edit icon next to any user to modify their details</li>
                            <li><strong>Search:</strong> Use the search box to find specific users</li>
                            <li><strong>Filter:</strong> Use the filter options to narrow down the user list</li>
                        </ul>
                        <p><strong>Keyboard Shortcuts:</strong></p>
                        <ul>
                            <li>Ctrl+N: Add new user</li>
                            <li>Ctrl+F: Focus search box</li>
                            <li>F1: Show this help</li>
                        </ul>
                    `
                },
                'organization-management': {
                    title: 'Organization Management',
                    content: `
                        <h3>Managing Organizations</h3>
                        <p>This section allows you to manage organization profiles and memberships.</p>
                        <ul>
                            <li><strong>Create Organization:</strong> Use the form to add new organizations</li>
                            <li><strong>Manage Members:</strong> Add or remove organization members</li>
                            <li><strong>Set Permissions:</strong> Configure organization-level permissions</li>
                        </ul>
                    `
                },
                'content-management': {
                    title: 'Content Management',
                    content: `
                        <h3>Managing Content</h3>
                        <p>Create and manage news articles, blog posts, and resources.</p>
                        <ul>
                            <li><strong>Rich Text Editor:</strong> Use the editor toolbar for formatting</li>
                            <li><strong>Media Upload:</strong> Drag and drop files or click to browse</li>
                            <li><strong>Categories:</strong> Organize content with categories and tags</li>
                        </ul>
                    `
                },
                'help-menu': {
                    title: 'Help Menu',
                    content: `
                        <h3>Available Help Topics</h3>
                        <ul>
                            <li><a href="#" onclick="helpSystem().showHelp('user-management')">User Management</a></li>
                            <li><a href="#" onclick="helpSystem().showHelp('organization-management')">Organization Management</a></li>
                            <li><a href="#" onclick="helpSystem().showHelp('content-management')">Content Management</a></li>
                            <li><a href="#" onclick="helpSystem().startTour()">Take a Tour</a></li>
                        </ul>
                        <h3>Keyboard Shortcuts</h3>
                        <ul>
                            <li><strong>F1:</strong> Show contextual help</li>
                            <li><strong>Ctrl+Shift+?:</strong> Show this help menu</li>
                            <li><strong>Escape:</strong> Close help</li>
                        </ul>
                    `
                }
            };
            
            return helpData[helpId] || null;
        },
        
        // Display help content
        displayHelpContent(data) {
            const helpContent = document.getElementById('help-content');
            if (helpContent) {
                helpContent.innerHTML = `
                    <h2>${data.title}</h2>
                    <div class="help-body">${data.content}</div>
                `;
            }
        },
        
        // Get current section for contextual help
        getCurrentSection() {
            const path = window.location.pathname;
            if (path.includes('/users')) return 'user-management';
            if (path.includes('/organizations')) return 'organization-management';
            if (path.includes('/content') || path.includes('/news') || path.includes('/blog')) return 'content-management';
            return 'general';
        },
        
        // Start guided tour
        startTour() {
            if (this.tourSteps.length === 0) {
                this.announceHelp('No tour available for this page');
                return;
            }
            
            this.tourActive = true;
            this.tourStep = 0;
            this.hideHelp();
            this.showTourStep();
        },
        
        // Show current tour step
        showTourStep() {
            if (this.tourStep >= this.tourSteps.length) {
                this.endTour();
                return;
            }
            
            const step = this.tourSteps[this.tourStep];
            const target = document.querySelector(step.target);
            
            if (!target) {
                this.nextTourStep();
                return;
            }
            
            // Highlight target element
            this.highlightElement(target);
            
            // Show tour popup
            this.showTourPopup(step, target);
            
            // Announce step
            this.announceHelp(`Tour step ${this.tourStep + 1} of ${this.tourSteps.length}: ${step.title}`);
        },
        
        // Show tour popup
        showTourPopup(step, target) {
            // Remove existing tour popup
            const existingPopup = document.getElementById('tour-popup');
            if (existingPopup) {
                existingPopup.remove();
            }
            
            // Create tour popup
            const popup = document.createElement('div');
            popup.id = 'tour-popup';
            popup.className = 'fixed z-50 bg-white border border-gray-300 rounded-lg shadow-lg p-4 max-w-sm';
            popup.setAttribute('role', 'dialog');
            popup.setAttribute('aria-labelledby', 'tour-title');
            popup.setAttribute('aria-describedby', 'tour-content');
            
            popup.innerHTML = `
                <div class="flex justify-between items-start mb-2">
                    <h3 id="tour-title" class="font-semibold text-lg">${step.title}</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600" onclick="helpSystem().endTour()">
                        <span class="sr-only">Close tour</span>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
                <div id="tour-content" class="text-gray-700 mb-4">${step.content}</div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">${this.tourStep + 1} of ${this.tourSteps.length}</span>
                    <div class="space-x-2">
                        ${this.tourStep > 0 ? '<button type="button" class="px-3 py-1 text-sm bg-gray-200 rounded hover:bg-gray-300" onclick="helpSystem().previousTourStep()">Previous</button>' : ''}
                        <button type="button" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700" onclick="helpSystem().nextTourStep()">
                            ${this.tourStep === this.tourSteps.length - 1 ? 'Finish' : 'Next'}
                        </button>
                    </div>
                </div>
            `;
            
            // Position popup near target
            document.body.appendChild(popup);
            this.positionTourPopup(popup, target);
            
            // Focus popup
            popup.focus();
        },
        
        // Position tour popup
        positionTourPopup(popup, target) {
            const targetRect = target.getBoundingClientRect();
            const popupRect = popup.getBoundingClientRect();
            
            let top = targetRect.bottom + 10;
            let left = targetRect.left;
            
            // Adjust if popup goes off screen
            if (left + popupRect.width > window.innerWidth) {
                left = window.innerWidth - popupRect.width - 10;
            }
            
            if (top + popupRect.height > window.innerHeight) {
                top = targetRect.top - popupRect.height - 10;
            }
            
            popup.style.top = `${top}px`;
            popup.style.left = `${left}px`;
        },
        
        // Highlight element
        highlightElement(element) {
            // Remove existing highlights
            document.querySelectorAll('.tour-highlight').forEach(el => {
                el.classList.remove('tour-highlight');
            });
            
            // Add highlight to current element
            element.classList.add('tour-highlight');
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },
        
        // Next tour step
        nextTourStep() {
            this.tourStep++;
            this.showTourStep();
        },
        
        // Previous tour step
        previousTourStep() {
            if (this.tourStep > 0) {
                this.tourStep--;
                this.showTourStep();
            }
        },
        
        // End tour
        endTour() {
            this.tourActive = false;
            this.tourStep = 0;
            
            // Remove tour popup
            const popup = document.getElementById('tour-popup');
            if (popup) {
                popup.remove();
            }
            
            // Remove highlights
            document.querySelectorAll('.tour-highlight').forEach(el => {
                el.classList.remove('tour-highlight');
            });
            
            this.announceHelp('Tour completed');
        },
        
        // Announce to screen readers
        announceHelp(message) {
            const announcer = document.createElement('div');
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            announcer.className = 'sr-only';
            announcer.textContent = message;
            
            document.body.appendChild(announcer);
            
            setTimeout(() => {
                document.body.removeChild(announcer);
            }, 2000);
        }
    };
}