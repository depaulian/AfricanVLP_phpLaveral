import Alpine from 'alpinejs';

Alpine.data('formValidation', (options = {}) => ({
    errors: {},
    touched: {},
    submitting: false,
    
    init() {
        this.setupAccessibility();
        
        // Watch for form submission
        this.$el.addEventListener('submit', (event) => {
            this.handleSubmit(event);
        });

        // Watch for input changes
        const inputs = this.$el.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            this.setupFieldAccessibility(input);
            
            input.addEventListener('blur', () => {
                this.validateField(input);
                this.touched[input.name] = true;
            });

            input.addEventListener('input', () => {
                if (this.touched[input.name]) {
                    this.validateField(input);
                }
            });
        });
    },

    setupAccessibility() {
        const form = this.$el;
        
        // Add form role if not present
        if (!form.getAttribute('role')) {
            form.setAttribute('role', 'form');
        }
        
        // Add aria-describedby for form-level help text
        const helpText = form.querySelector('.form-help');
        if (helpText && !helpText.id) {
            helpText.id = `${form.id || 'form'}-help`;
            form.setAttribute('aria-describedby', helpText.id);
        }
    },

    setupFieldAccessibility(field) {
        const fieldId = field.id || `field-${field.name}`;
        field.id = fieldId;
        
        // Link label to field
        const label = document.querySelector(`label[for="${fieldId}"]`) || 
                     field.closest('.form-group')?.querySelector('label');
        if (label && !label.getAttribute('for')) {
            label.setAttribute('for', fieldId);
        }
        
        // Setup error container
        let errorContainer = document.getElementById(`${field.name}-error`);
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.id = `${field.name}-error`;
            errorContainer.className = 'text-red-600 text-sm mt-1 min-h-[1.25rem]';
            errorContainer.setAttribute('aria-live', 'polite');
            errorContainer.setAttribute('role', 'alert');
            field.parentNode.appendChild(errorContainer);
        }
        
        // Link error container to field
        field.setAttribute('aria-describedby', `${field.name}-error`);
        
        // Add help text if present
        const helpText = field.parentNode.querySelector('.field-help');
        if (helpText) {
            const helpId = `${field.name}-help`;
            helpText.id = helpId;
            const describedBy = field.getAttribute('aria-describedby');
            field.setAttribute('aria-describedby', `${describedBy} ${helpId}`);
        }
    },

    validateField(field) {
        const rules = this.getFieldRules(field);
        const value = field.value.trim();
        const errors = [];

        // Required validation
        if (rules.required && !value) {
            errors.push(`${this.getFieldLabel(field)} is required`);
        }

        // Email validation
        if (rules.email && value && !this.isValidEmail(value)) {
            errors.push('Please enter a valid email address');
        }

        // Phone validation
        if (rules.phone && value && !this.isValidPhone(value)) {
            errors.push('Please enter a valid phone number');
        }

        // URL validation
        if (rules.url && value && !this.isValidUrl(value)) {
            errors.push('Please enter a valid URL');
        }

        // Min length validation
        if (rules.minLength && value.length < rules.minLength) {
            errors.push(`${this.getFieldLabel(field)} must be at least ${rules.minLength} characters`);
        }

        // Max length validation
        if (rules.maxLength && value.length > rules.maxLength) {
            errors.push(`${this.getFieldLabel(field)} must not exceed ${rules.maxLength} characters`);
        }

        // Number range validation
        if (rules.min !== undefined && parseFloat(value) < rules.min) {
            errors.push(`${this.getFieldLabel(field)} must be at least ${rules.min}`);
        }

        if (rules.max !== undefined && parseFloat(value) > rules.max) {
            errors.push(`${this.getFieldLabel(field)} must not exceed ${rules.max}`);
        }

        // Pattern validation
        if (rules.pattern && value && !new RegExp(rules.pattern).test(value)) {
            errors.push(rules.patternMessage || `${this.getFieldLabel(field)} format is invalid`);
        }

        // Password strength validation
        if (rules.password && value) {
            const strengthErrors = this.validatePasswordStrength(value);
            errors.push(...strengthErrors);
        }

        // Confirm password validation
        if (rules.confirmPassword && value) {
            const originalPassword = document.querySelector('[name="password"]')?.value;
            if (value !== originalPassword) {
                errors.push('Passwords do not match');
            }
        }

        // Custom validation
        if (rules.custom && typeof rules.custom === 'function') {
            const customError = rules.custom(value, field);
            if (customError) {
                errors.push(customError);
            }
        }

        // Update errors
        if (errors.length > 0) {
            this.errors[field.name] = errors;
        } else {
            delete this.errors[field.name];
        }

        this.updateFieldUI(field);
        
        // Announce validation result to screen readers
        if (errors.length > 0) {
            this.announceError(field, errors[0]);
        }
    },

    validateForm() {
        const inputs = this.$el.querySelectorAll('input, select, textarea');
        let isValid = true;
        let firstErrorField = null;

        inputs.forEach(input => {
            this.validateField(input);
            this.touched[input.name] = true;
            
            if (this.errors[input.name]) {
                isValid = false;
                if (!firstErrorField) {
                    firstErrorField = input;
                }
            }
        });

        // Focus and announce first error
        if (!isValid && firstErrorField) {
            firstErrorField.focus();
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            const errorCount = Object.keys(this.errors).length;
            this.announceFormErrors(errorCount);
        }

        return isValid;
    },

    handleSubmit(event) {
        event.preventDefault();
        
        if (this.submitting) return;

        const isValid = this.validateForm();
        
        if (isValid) {
            this.submitting = true;
            
            // If there's a custom submit handler, call it
            if (options.onSubmit) {
                options.onSubmit(event, this);
            } else {
                // Default form submission
                event.target.submit();
            }
        }
    },

    getFieldRules(field) {
        const rules = {};
        
        // Get rules from data attributes
        if (field.hasAttribute('required')) rules.required = true;
        if (field.type === 'email') rules.email = true;
        if (field.type === 'tel' || field.hasAttribute('data-phone')) rules.phone = true;
        if (field.type === 'url') rules.url = true;
        if (field.hasAttribute('minlength')) rules.minLength = parseInt(field.getAttribute('minlength'));
        if (field.hasAttribute('maxlength')) rules.maxLength = parseInt(field.getAttribute('maxlength'));
        if (field.hasAttribute('min')) rules.min = parseFloat(field.getAttribute('min'));
        if (field.hasAttribute('max')) rules.max = parseFloat(field.getAttribute('max'));
        if (field.hasAttribute('pattern')) {
            rules.pattern = field.getAttribute('pattern');
            rules.patternMessage = field.getAttribute('data-pattern-message');
        }
        if (field.hasAttribute('data-password')) rules.password = true;
        if (field.hasAttribute('data-confirm-password')) rules.confirmPassword = true;
        
        // Get custom rules from options
        if (options.rules && options.rules[field.name]) {
            Object.assign(rules, options.rules[field.name]);
        }

        return rules;
    },

    getFieldLabel(field) {
        const label = document.querySelector(`label[for="${field.id}"]`);
        if (label) {
            return label.textContent.replace('*', '').trim();
        }
        
        // Try to find label by proximity
        const proximityLabel = field.closest('.form-group')?.querySelector('label');
        if (proximityLabel) {
            return proximityLabel.textContent.replace('*', '').trim();
        }
        
        // Fallback to placeholder or name
        return field.placeholder || field.name.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },

    updateFieldUI(field) {
        const fieldErrors = this.errors[field.name] || [];
        const errorContainer = document.getElementById(`${field.name}-error`);
        
        // Remove existing error classes
        field.classList.remove('border-red-500', 'border-green-500', 'focus:border-red-500', 'focus:ring-red-500');
        
        if (fieldErrors.length > 0) {
            field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            field.setAttribute('aria-invalid', 'true');
            
            if (errorContainer) {
                errorContainer.textContent = fieldErrors[0];
                errorContainer.classList.remove('text-green-600');
                errorContainer.classList.add('text-red-600');
            }
        } else if (this.touched[field.name] && field.value.trim()) {
            field.classList.add('border-green-500');
            field.setAttribute('aria-invalid', 'false');
            
            if (errorContainer) {
                errorContainer.textContent = '✓ Valid';
                errorContainer.classList.remove('text-red-600');
                errorContainer.classList.add('text-green-600');
            }
        } else {
            field.setAttribute('aria-invalid', 'false');
            if (errorContainer) {
                errorContainer.textContent = '';
            }
        }
    },

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    isValidPhone(phone) {
        // Remove all non-digit characters
        const cleaned = phone.replace(/\D/g, '');
        // Check if it's a valid length (10-15 digits)
        return cleaned.length >= 10 && cleaned.length <= 15;
    },

    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    },

    validatePasswordStrength(password) {
        const errors = [];
        
        if (password.length < 8) {
            errors.push('Password must be at least 8 characters long');
        }
        
        if (!/[a-z]/.test(password)) {
            errors.push('Password must contain at least one lowercase letter');
        }
        
        if (!/[A-Z]/.test(password)) {
            errors.push('Password must contain at least one uppercase letter');
        }
        
        if (!/\d/.test(password)) {
            errors.push('Password must contain at least one number');
        }
        
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            errors.push('Password must contain at least one special character');
        }
        
        return errors;
    },

    announceError(field, error) {
        const announcement = `${this.getFieldLabel(field)}: ${error}`;
        
        // Create temporary announcement element
        const announcer = document.createElement('div');
        announcer.setAttribute('aria-live', 'assertive');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.className = 'sr-only';
        announcer.textContent = announcement;
        
        document.body.appendChild(announcer);
        
        setTimeout(() => {
            document.body.removeChild(announcer);
        }, 1000);
    },

    announceFormErrors(errorCount) {
        const message = errorCount === 1 
            ? 'There is 1 error in the form. Please review and correct it.'
            : `There are ${errorCount} errors in the form. Please review and correct them.`;
        
        const announcer = document.createElement('div');
        announcer.setAttribute('aria-live', 'assertive');
        announcer.setAttribute('role', 'alert');
        announcer.className = 'sr-only';
        announcer.textContent = message;
        
        document.body.appendChild(announcer);
        
        setTimeout(() => {
            document.body.removeChild(announcer);
        }, 3000);
    },

    hasError(fieldName) {
        return this.errors[fieldName] && this.errors[fieldName].length > 0;
    },

    getError(fieldName) {
        return this.errors[fieldName] ? this.errors[fieldName][0] : '';
    },

    clearErrors() {
        this.errors = {};
        this.touched = {};
        
        // Clear UI
        const inputs = this.$el.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('border-red-500', 'border-green-500', 'focus:border-red-500', 'focus:ring-red-500');
            input.setAttribute('aria-invalid', 'false');
            
            const errorContainer = document.getElementById(`${input.name}-error`);
            if (errorContainer) {
                errorContainer.textContent = '';
            }
        });
    },

    getFieldClass(fieldName) {
        if (this.hasError(fieldName)) {
            return 'border-red-500 focus:border-red-500 focus:ring-red-500';
        } else if (this.touched[fieldName] && !this.hasError(fieldName)) {
            return 'border-green-500 focus:border-green-500 focus:ring-green-500';
        }
        return 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
    },

    setSubmitting(state) {
        this.submitting = state;
    }
}));