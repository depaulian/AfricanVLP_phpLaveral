// Contextual help and user guidance system for client application
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
        
        // Get local help data for client application
        getLocalHelpData(helpId) {
            const helpData = {
                'profile-management': {
                    title: 'Profile Management',
                    content: `
                        <h3>Managing Your Profile</h3>
                        <p>Keep your profile information up to date to connect with organizations and opportunities.</p>
                        <ul>
                            <li><strong>Personal Information:</strong> Update your contact details and bio</li>
                            <li><strong>Profile Picture:</strong> Upload a professional photo</li>
                            <li><strong>Interests:</strong> Select areas you're interested in volunteering</li>
                            <li><strong>Skills:</strong> Add your skills to match with opportunities</li>
                        </ul>
                        <p><strong>Privacy:</strong> You can control what information is visible to organizations.</p>
                    `
                },
                'organization-discovery': {
                    title: 'Finding Organizations',
                    content: `
                        <h3>Discovering Organizations</h3>
                        <p>Find organizations that match your interests and values.</p>
                        <ul>
                            <li><strong>Browse:</strong> Explore all registered organizations</li>
                            <li><strong>Search:</strong> Use keywords to find specific organizations</li>
                            <li><strong>Filter:</strong> Narrow results by location, category, or focus area</li>
                            <li><strong>Join:</strong> Request membership to organizations you're interested in</li>
                        </ul>
                        <p><strong>Tip:</strong> Read organization profiles carefully to understand their mission and requirements.</p>
                    `
                },
                'volunteering': {
                    title: 'Volunteering Opportunities',
                    content: `
                        <h3>Finding Volunteer Opportunities</h3>
                        <p>Discover meaningful ways to contribute to your community.</p>
                        <ul>
                            <li><strong>Browse Opportunities:</strong> View available volunteer positions</li>
                            <li><strong>Apply:</strong> Submit applications for opportunities that interest you</li>
                            <li><strong>Track History:</strong> Keep record of your volunteer activities</li>
                            <li><strong>Get Recognition:</strong> Earn certificates and recognition for your service</li>
                        </ul>
                    `
                },
                'events-news': {
                    title: 'Events and News',
                    content: `
                        <h3>Staying Informed</h3>
                        <p>Keep up with community events and important news.</p>
                        <ul>
                            <li><strong>Events:</strong> Find upcoming community events and activities</li>
                            <li><strong>News:</strong> Read the latest community news and updates</li>
                            <li><strong>Blog:</strong> Explore stories and insights from community members</li>
                            <li><strong>Resources:</strong> Access helpful documents and materials</li>
                        </ul>
                    `
                },
                'help-menu': {
                    title: 'Help Menu',
                    content: `
                        <h3>Available Help Topics</h3>
                        <ul>
                            <li><a href="#" onclick="helpSystem().showHelp('profile-management')">Profile Management</a></li>
                            <li><a href="#" onclick="helpSystem().showHelp('organization-discovery')">Finding Organizations</a></li>
                            <li><a href="#" onclick="helpSystem().showHelp('volunteering')">Volunteering Opportunities</a></li>
                            <li><a href="#" onclick="helpSystem().showHelp('events-news')">Events and News</a></li>
                            <li><a href="#" onclick="helpSystem().startTour()">Take a Tour</a></li>
                        </ul>
                        <h3>Keyboard Shortcuts</h3>
                        <ul>
                            <li><strong>F1:</strong> Show contextual help</li>
                            <li><strong>Ctrl+Shift+?:</strong> Show this help menu</li>
                            <li><strong>Escape:</strong> Close help</li>
                        </ul>
                        <h3>Contact Support</h3>
                        <p>If you need additional help, please contact our support team through the contact form.</p>
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
            if (path.includes('/profile')) return 'profile-management';
            if (path.includes('/organizations')) return 'organization-discovery';
            if (path.includes('/volunteer')) return 'volunteering';
            if (path.includes('/events') || path.includes('/news') || path.includes('/blog')) return 'events-news';
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