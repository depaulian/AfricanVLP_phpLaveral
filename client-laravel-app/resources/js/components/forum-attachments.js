/**
 * Forum Attachments Component
 * Handles file upload, preview, and management for forum posts
 */

class ForumAttachments {
    constructor(options = {}) {
        this.options = {
            maxFiles: 5,
            maxFileSize: 10 * 1024 * 1024, // 10MB
            allowedTypes: [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain', 'text/csv', 'application/zip', 'application/x-rar-compressed',
                'application/x-7z-compressed', 'text/html', 'text/css', 'text/javascript',
                'application/json', 'application/xml'
            ],
            ...options
        };

        this.files = [];
        this.init();
    }

    init() {
        this.setupFileInput();
        this.setupDragAndDrop();
        this.setupEventListeners();
    }

    setupFileInput() {
        const fileInput = document.getElementById('attachments');
        if (!fileInput) return;

        fileInput.addEventListener('change', (e) => {
            this.handleFileSelection(e.target.files);
        });
    }

    setupDragAndDrop() {
        const dropZone = document.getElementById('attachment-drop-zone');
        if (!dropZone) return;

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            this.handleFileSelection(e.dataTransfer.files);
        });
    }

    setupEventListeners() {
        // Remove file buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-attachment')) {
                e.preventDefault();
                const index = parseInt(e.target.dataset.index);
                this.removeFile(index);
            }
        });

        // Preview buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('preview-attachment')) {
                e.preventDefault();
                const index = parseInt(e.target.dataset.index);
                this.previewFile(index);
            }
        });

        // Delete existing attachment buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-existing-attachment')) {
                e.preventDefault();
                const attachmentId = e.target.dataset.attachmentId;
                this.deleteExistingAttachment(attachmentId);
            }
        });
    }

    handleFileSelection(fileList) {
        const files = Array.from(fileList);
        
        // Check total file count
        if (this.files.length + files.length > this.options.maxFiles) {
            this.showError(`Maximum ${this.options.maxFiles} files allowed`);
            return;
        }

        files.forEach(file => {
            if (this.validateFile(file)) {
                this.addFile(file);
            }
        });

        this.updateFileInput();
        this.renderFileList();
    }

    validateFile(file) {
        // Check file size
        if (file.size > this.options.maxFileSize) {
            this.showError(`File "${file.name}" is too large. Maximum size is ${this.formatFileSize(this.options.maxFileSize)}`);
            return false;
        }

        // Check file type
        if (!this.options.allowedTypes.includes(file.type)) {
            this.showError(`File type "${file.type}" is not allowed for "${file.name}"`);
            return false;
        }

        // Check for dangerous extensions
        const extension = file.name.split('.').pop().toLowerCase();
        const dangerousExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php', 'asp', 'aspx', 'jsp', 'py', 'rb', 'pl', 'sh'];
        
        if (dangerousExtensions.includes(extension)) {
            this.showError(`File extension "${extension}" is not allowed for security reasons`);
            return false;
        }

        return true;
    }

    addFile(file) {
        const fileData = {
            file: file,
            id: Date.now() + Math.random(),
            name: file.name,
            size: file.size,
            type: file.type,
            preview: null
        };

        // Generate preview for images
        if (file.type.startsWith('image/')) {
            this.generateImagePreview(file, fileData);
        }

        this.files.push(fileData);
    }

    generateImagePreview(file, fileData) {
        const reader = new FileReader();
        reader.onload = (e) => {
            fileData.preview = e.target.result;
            this.renderFileList(); // Re-render to show preview
        };
        reader.readAsDataURL(file);
    }

    removeFile(index) {
        this.files.splice(index, 1);
        this.updateFileInput();
        this.renderFileList();
    }

    updateFileInput() {
        const fileInput = document.getElementById('attachments');
        if (!fileInput) return;

        // Create new FileList from our files array
        const dt = new DataTransfer();
        this.files.forEach(fileData => {
            dt.items.add(fileData.file);
        });
        fileInput.files = dt.files;
    }

    renderFileList() {
        const container = document.getElementById('attachment-list');
        if (!container) return;

        if (this.files.length === 0) {
            container.innerHTML = '';
            return;
        }

        const html = this.files.map((fileData, index) => {
            return `
                <div class="attachment-item bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            ${this.renderFileIcon(fileData)}
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">${this.escapeHtml(fileData.name)}</div>
                                <div class="text-xs text-gray-500">${this.formatFileSize(fileData.size)}</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            ${fileData.type.startsWith('image/') ? `
                                <button type="button" class="preview-attachment text-blue-600 hover:text-blue-800 text-sm" data-index="${index}">
                                    <i class="fas fa-eye mr-1"></i>Preview
                                </button>
                            ` : ''}
                            <button type="button" class="remove-attachment text-red-600 hover:text-red-800 text-sm" data-index="${index}">
                                <i class="fas fa-trash mr-1"></i>Remove
                            </button>
                        </div>
                    </div>
                    ${fileData.preview ? `
                        <div class="mt-2">
                            <img src="${fileData.preview}" alt="Preview" class="max-w-32 max-h-32 object-cover rounded border">
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');

        container.innerHTML = html;

        // Update file count display
        const countDisplay = document.getElementById('attachment-count');
        if (countDisplay) {
            countDisplay.textContent = `${this.files.length}/${this.options.maxFiles}`;
        }
    }

    renderFileIcon(fileData) {
        const iconClass = this.getFileIcon(fileData.type);
        return `<i class="${iconClass} text-gray-500 text-lg"></i>`;
    }

    getFileIcon(mimeType) {
        const iconMap = {
            'image/jpeg': 'fas fa-image',
            'image/png': 'fas fa-image',
            'image/gif': 'fas fa-image',
            'image/webp': 'fas fa-image',
            'image/svg+xml': 'fas fa-image',
            'application/pdf': 'fas fa-file-pdf',
            'application/msword': 'fas fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'fas fa-file-word',
            'application/vnd.ms-excel': 'fas fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'fas fa-file-excel',
            'application/vnd.ms-powerpoint': 'fas fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'fas fa-file-powerpoint',
            'text/plain': 'fas fa-file-alt',
            'text/csv': 'fas fa-file-csv',
            'application/zip': 'fas fa-file-archive',
            'application/x-rar-compressed': 'fas fa-file-archive',
            'application/x-7z-compressed': 'fas fa-file-archive',
            'text/html': 'fas fa-file-code',
            'text/css': 'fas fa-file-code',
            'text/javascript': 'fas fa-file-code',
            'application/json': 'fas fa-file-code',
            'application/xml': 'fas fa-file-code',
        };

        return iconMap[mimeType] || 'fas fa-file';
    }

    previewFile(index) {
        const fileData = this.files[index];
        if (!fileData || !fileData.type.startsWith('image/')) return;

        // Create modal for image preview
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="max-w-4xl max-h-full p-4">
                <div class="bg-white rounded-lg overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold">${this.escapeHtml(fileData.name)}</h3>
                        <button type="button" class="close-preview text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        <img src="${fileData.preview}" alt="Preview" class="max-w-full max-h-96 object-contain mx-auto">
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Close modal on click
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.classList.contains('close-preview')) {
                document.body.removeChild(modal);
            }
        });
    }

    deleteExistingAttachment(attachmentId) {
        if (!confirm('Are you sure you want to delete this attachment? This action cannot be undone.')) {
            return;
        }

        const button = document.querySelector(`[data-attachment-id="${attachmentId}"]`);
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Deleting...';
        }

        fetch(`/forums/attachments/${attachmentId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the attachment element from DOM
                const attachmentElement = button.closest('.attachment-item');
                if (attachmentElement) {
                    attachmentElement.remove();
                }
                this.showSuccess('Attachment deleted successfully');
            } else {
                this.showError(data.message || 'Failed to delete attachment');
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-trash mr-1"></i>Delete';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showError('An error occurred while deleting the attachment');
            if (button) {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-trash mr-1"></i>Delete';
            }
        });
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
            type === 'error' ? 'bg-red-100 border border-red-400 text-red-700' :
            type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' :
            'bg-blue-100 border border-blue-400 text-blue-700'
        }`;
        
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${
                    type === 'error' ? 'fa-exclamation-circle' :
                    type === 'success' ? 'fa-check-circle' :
                    'fa-info-circle'
                } mr-2"></i>
                <span>${this.escapeHtml(message)}</span>
                <button type="button" class="ml-2 text-current opacity-75 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize forum attachments if we're on a forum page
    if (document.getElementById('attachments') || document.getElementById('attachment-drop-zone')) {
        window.forumAttachments = new ForumAttachments();
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ForumAttachments;
}