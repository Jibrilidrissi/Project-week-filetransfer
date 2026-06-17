document.addEventListener('DOMContentLoaded', () => {
    // Initialize elements
    const dropZone = document.querySelector('.drag-drop-zone');
    const fileInput = document.getElementById('bestand');
    const startState = document.querySelector('.upload-start-state');
    const splitLayout = document.querySelector('.upload-split-layout');
    const fileNameSpan = document.querySelector('.file-item__name');
    const fileSizeSpan = document.querySelector('.file-item__size');
    const fileClearBtn = document.querySelector('.file-item__clear');
    const fileListHeader = document.querySelector('.file-list-header');
    
    const uploadForm = document.querySelector('.upload-form');
    const progressContainer = document.querySelector('.upload-progress-container');
    const progressBar = document.querySelector('.upload-progress-bar');
    const progressPercent = document.querySelector('.upload-progress-percent');
    const progressStatus = document.getElementById('progress-status-text');

    // 1. Drag & Drop functionality
    if (dropZone && fileInput) {
        // Highlight drop zone
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('drag-drop-zone--dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('drag-drop-zone--dragover');
            }, false);
        });

        // Handle file drop
        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                fileInput.files = files;
                updateFileInfo(files[0]);
            }
        });

        // Handle file change via file input browse button
        fileInput.addEventListener('change', (e) => {
            if (fileInput.files.length > 0) {
                updateFileInfo(fileInput.files[0]);
            }
        });
    }

    // 2. Clear File Selection
    if (fileClearBtn) {
        fileClearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            resetFileSelection();
        });
    }

    function updateFileInfo(file) {
        if (!fileNameSpan || !fileSizeSpan) return;
        
        // Validate file extension
        const ext = file.name.split('.').pop().toLowerCase();
        if (typeof ALLOWED_EXTENSIONS !== 'undefined' && !ALLOWED_EXTENSIONS.includes(ext)) {
            showErrorAlert(`Fout: Het bestandstype (.${ext}) is niet ondersteund.`);
            resetFileSelection();
            return;
        }

        // Validate file size
        if (typeof MAX_FILE_SIZE !== 'undefined' && file.size > MAX_FILE_SIZE) {
            showErrorAlert(`Fout: Het bestand is te groot. Maximale grootte is ${formatBytes(MAX_FILE_SIZE)}.`);
            resetFileSelection();
            return;
        }

        // Clear any previous alerts
        clearAlerts();
        
        fileNameSpan.textContent = file.name;
        fileSizeSpan.textContent = formatBytes(file.size);
        
        if (fileListHeader) {
            fileListHeader.textContent = '1 uploaded file';
        }

        // Toggle layout views
        if (startState) startState.style.display = 'none';
        if (splitLayout) splitLayout.style.display = 'grid';
        
        // Auto-scroll to clear view if needed
        splitLayout.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function showErrorAlert(message) {
        let currentAlert = document.querySelector('.container > .alert');
        if (currentAlert) {
            currentAlert.className = 'alert alert--error';
            currentAlert.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>${message}</span>
            `;
        } else {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert--error';
            alertDiv.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>${message}</span>
            `;
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
        }
    }

    function clearAlerts() {
        const currentAlert = document.querySelector('.container > .alert');
        if (currentAlert) {
            currentAlert.remove();
        }
    }

    function resetFileSelection() {
        if (fileInput) fileInput.value = '';
        
        // Toggle layout views back
        if (startState) startState.style.display = 'block';
        if (splitLayout) splitLayout.style.display = 'none';
    }

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // 3. XHR Form Upload with Real-time Progress Tracking
    if (uploadForm && fileInput) {
        uploadForm.addEventListener('submit', (e) => {
            // Verify there is a file
            if (!fileInput.files || fileInput.files.length === 0) {
                return; // Let native HTML validation handle it
            }

            e.preventDefault();

            const formData = new FormData(uploadForm);
            const xhr = new XMLHttpRequest();

            // Display progress container
            if (progressContainer) {
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressPercent.textContent = '0%';
                progressStatus.textContent = 'Preparing secure transfer keys...';
            }

            // Track upload progress
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    const percentComplete = Math.round((event.loaded / event.total) * 100);
                    
                    if (progressBar && progressPercent) {
                        progressBar.style.width = percentComplete + '%';
                        progressPercent.textContent = percentComplete + '%';
                    }

                    // Dynamic security status texts
                    if (percentComplete < 25) {
                        progressStatus.textContent = 'Initiating end-to-end tunnel encryption...';
                    } else if (percentComplete < 60) {
                        progressStatus.textContent = `Uploading file packets... (${percentComplete}%)`;
                    } else if (percentComplete < 85) {
                        progressStatus.textContent = 'Securing server storage environment...';
                    } else if (percentComplete < 100) {
                        progressStatus.textContent = 'Generating file checksum and SHA-256 integrity hash...';
                    } else {
                        progressStatus.textContent = 'Finalizing transaction protocol...';
                    }
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    // Success or parsed error
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(xhr.responseText, 'text/html');
                    const responseAlert = doc.querySelector('.alert');

                    if (responseAlert && responseAlert.classList.contains('alert--error')) {
                        // Display error without reloading
                        let currentAlert = document.querySelector('.container > .alert');
                        if (currentAlert) {
                            currentAlert.className = responseAlert.className;
                            currentAlert.innerHTML = responseAlert.innerHTML;
                        } else {
                            const alertDiv = document.createElement('div');
                            alertDiv.className = responseAlert.className;
                            alertDiv.innerHTML = responseAlert.innerHTML;
                            const container = document.querySelector('.container');
                            container.insertBefore(alertDiv, container.firstChild);
                        }
                        
                        // Scroll to alert
                        document.querySelector('.container').scrollIntoView({ behavior: 'smooth' });
                        
                        // Hide progress bar
                        if (progressContainer) progressContainer.style.display = 'none';
                    } else {
                        // Success - let's set bar to 100% and delay a bit for visual satisfaction
                        if (progressBar) progressBar.style.width = '100%';
                        if (progressPercent) progressPercent.textContent = '100%';
                        if (progressStatus) progressStatus.textContent = 'Transfer complete! Refreshing...';

                        setTimeout(() => {
                            window.location.reload();
                        }, 800);
                    }
                } else {
                    if (progressStatus) progressStatus.textContent = 'Connection failed. Please retry.';
                }
            });

            xhr.addEventListener('error', () => {
                if (progressStatus) progressStatus.textContent = 'Connection error. Please try again.';
            });

            xhr.open('POST', 'voorpagina.php', true);
            xhr.send(formData);
        });
    }

    // 4. Copy to Clipboard Utility with Dynamic Toast
    window.copyToClipboard = function(text, btnElement) {
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            // Show premium toast
            showToast('Copied to clipboard!');

            // Flash success state on the button itself if it's an icon
            if (btnElement) {
                const originalHTML = btnElement.innerHTML;
                btnElement.style.color = 'var(--success)';
                btnElement.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                `;

                setTimeout(() => {
                    btnElement.style.color = '';
                    btnElement.innerHTML = originalHTML;
                }, 1500);
            }
        }).catch(err => {
            console.error('Could not copy text: ', err);
        });
    };

    function showToast(message) {
        let toast = document.getElementById('copy-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'copy-toast';
            toast.className = 'copy-toast';
            document.body.appendChild(toast);
        }

        toast.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>${message}</span>
        `;

        // Trigger show animation
        setTimeout(() => toast.classList.add('show'), 50);

        // Hide after 2 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2000);
    }
});
