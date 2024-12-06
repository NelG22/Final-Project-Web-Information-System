// Search functionality
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const contactCards = document.querySelectorAll('.contact-card');
    
    contactCards.forEach(card => {
        const name = card.querySelector('h3').textContent.toLowerCase();
        const email = card.querySelector('p:nth-child(3)').textContent.toLowerCase();
        const phone = card.querySelector('p:nth-child(2)').textContent.toLowerCase();
        
        if (name.includes(searchTerm) || email.includes(searchTerm) || phone.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});

// Modal handling
function showAddContactModal() {
    document.getElementById('addContactModal').style.display = 'block';
}

function closeAddContactModal() {
    document.getElementById('addContactModal').style.display = 'none';
}

function showEditContactModal() {
    document.getElementById('editContactModal').style.display = 'block';
}

function closeEditContactModal() {
    document.getElementById('editContactModal').style.display = 'none';
}

// Profile Modal Functions
function showProfileModal() {
    document.getElementById('profileModal').style.display = 'block';
}

function closeProfileModal() {
    document.getElementById('profileModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Show notification function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    const icon = document.createElement('i');
    icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    
    notification.appendChild(icon);
    notification.appendChild(document.createTextNode(message));
    
    document.body.appendChild(notification);
    
    // Remove notification after animation ends
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Add Contact
async function handleAddContact(event) {
    event.preventDefault();
    console.log('Handling add contact submission');
    
    const form = document.getElementById('addContactForm');
    const formData = new FormData(form);
    formData.append('action', 'add');
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Adding...';
    submitBtn.disabled = true;

    try {
        // Log form data for debugging
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        const response = await fetch('contact_operations.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status);
        const text = await response.text();
        console.log('Raw server response:', text);

        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            throw new Error('Server returned invalid JSON');
        }
        
        if (result.success) {
            showNotification(result.message || 'Contact added successfully', 'success');
            form.reset();
            closeAddContactModal();
            loadContacts();
        } else {
            throw new Error(result.message || 'Failed to add contact');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message || 'An error occurred while adding the contact', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
    
    return false;
}

function loadContacts() {
    location.reload();
}

// Edit Contact
function editContact(contactId) {
    if (!contactId) {
        console.error('No contact ID provided');
        return;
    }

    fetch(`contact_operations.php?action=get_contact&id=${contactId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.contact) {
            showEditContactModal(); // Show modal first
            setTimeout(() => { // Give modal time to render
                populateEditForm(data.contact);
            }, 100);
        } else {
            const errorMessage = data.debug_message || data.message || 'Failed to load contact details';
            showNotification(errorMessage, 'error');
        }
    })
    .catch(error => {
        console.error('Error fetching contact:', error);
        showNotification('Failed to load contact details', 'error');
    });
}

function populateEditForm(contact) {
    if (!contact) {
        console.error('No contact data provided');
        return;
    }

    // Get form elements
    const form = document.getElementById('editContactForm');
    if (!form) {
        console.error('Edit contact form not found');
        return;
    }

    // Set form values
    document.getElementById('edit_contact_id').value = contact.id;
    document.getElementById('edit_name').value = contact.name;
    document.getElementById('edit_phone').value = contact.phone;
    document.getElementById('edit_email').value = contact.email;
    
    // Set avatar preview if exists
    const preview = document.getElementById('editAvatarPreview');
    if (preview) {
        if (contact.avatar) {
            preview.style.backgroundImage = `url(${contact.avatar})`;
        } else {
            preview.style.backgroundImage = 'url("assets/default-avatar.png")';
        }
        preview.style.backgroundSize = 'cover';
        preview.style.backgroundPosition = 'center';
    } else {
        console.warn('Avatar preview element not found');
    }
    
    // Clear file input if it exists
    const fileInput = document.getElementById('editContactAvatarInput');
    if (fileInput) {
        fileInput.value = '';
    }
}

function previewEditContactAvatar(file) {
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('editAvatarPreview');
            preview.style.backgroundImage = `url(${e.target.result})`;
            preview.style.backgroundSize = 'cover';
            preview.style.backgroundPosition = 'center';
        };
        reader.readAsDataURL(file);
    }
}

async function handleEditContact(event) {
    event.preventDefault();
    console.log('Handling edit contact submission');

    const form = document.getElementById('editContactForm');
    const formData = new FormData(form);
    formData.append('action', 'edit_contact');
    
    // Show loading state
    const saveBtn = form.querySelector('button[type="submit"]');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;

    try {
        // Log form data for debugging
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        const response = await fetch('contact_operations.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status);
        const text = await response.text();
        console.log('Raw server response:', text);

        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            throw new Error('Server returned invalid JSON');
        }
        
        if (result.success) {
            showNotification(result.message || 'Contact updated successfully!', 'success');
            closeEditContactModal();
            loadContacts(); // Refresh the contacts list
        } else {
            throw new Error(result.message || 'Failed to update contact');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message || 'An error occurred while updating the contact', 'error');
    } finally {
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
    }
}

// Delete Contact
function deleteContact(contactId) {
    if (!contactId) {
        showNotification('Invalid contact ID', 'error');
        return;
    }

    if (confirm('Are you sure you want to delete this contact?')) {
        console.log('Deleting contact:', contactId); // Debug log

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('contact_id', contactId);

        // Debug log the FormData
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        fetch('contact_operations.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(async response => {
            const text = await response.text();
            console.log('Raw response:', text); // Debug log

            try {
                const data = JSON.parse(text);
                return { ok: response.ok, data };
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response: ' + text);
            }
        })
        .then(({ok, data}) => {
            console.log('Parsed response:', data); // Debug log

            if (!ok) {
                throw new Error(data.debug_message || data.message || 'Server error');
            }

            if (data.success) {
                showNotification(data.message, 'success');
                // Remove the contact card from the DOM
                const contactCard = document.querySelector(`[data-contact-id="${contactId}"]`);
                if (contactCard) {
                    contactCard.remove();
                } else {
                    console.warn('Contact card not found in DOM:', contactId);
                }
                loadContacts(); // Refresh the contacts list
            } else {
                throw new Error(data.debug_message || data.message || 'Failed to delete contact');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            showNotification(`Error deleting contact: ${error.message}`, 'error');
        });
    }
}

// Clear All Contacts
async function clearAllContacts() {
    if (!confirm('Are you sure you want to delete ALL your contacts? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'clear_contacts');
    
    try {
        const response = await fetch('profile_operations.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred while clearing contacts', 'error');
    }
}

// Delete Account
async function deleteAccount() {
    if (!confirm('WARNING: Are you sure you want to delete your account? This will permanently delete all your data and cannot be undone.')) {
        return;
    }
    
    // Double confirmation for account deletion
    if (!confirm('Please confirm again that you want to delete your account permanently.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_account');
    
    try {
        const response = await fetch('profile_operations.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred while deleting your account', 'error');
    }
}

// Avatar and Profile Management
async function updateAvatar(file) {
    if (!file) return;
    
    const formData = new FormData();
    formData.append('avatar', file);
    
    try {
        const response = await fetch('upload_avatar.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            // Update avatar preview
            const avatarContainer = document.querySelector('.avatar-container');
            const img = document.createElement('img');
            img.src = result.avatar_path;
            img.alt = 'Profile Avatar';
            img.className = 'profile-avatar';
            avatarContainer.querySelector('.default-avatar, .profile-avatar').replaceWith(img);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error uploading avatar', 'error');
    }
}

async function updateProfile(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('action', 'update_profile');
    
    try {
        const response = await fetch('profile_operations.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            if (result.username) {
                // Update username in the UI
                document.querySelector('.welcome-msg').textContent = `Welcome, ${result.username}!`;
            }
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error updating profile', 'error');
    }
}

// Contact Avatar Preview
function previewContactAvatar(file) {
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const avatar = document.getElementById('newContactAvatar');
        avatar.style.backgroundImage = `url(${e.target.result})`;
        avatar.textContent = '';
    };
    reader.readAsDataURL(file);
}

function updateNewContactAvatar(name) {
    if (!name) name = 'C';
    const avatar = document.getElementById('newContactAvatar');
    if (!avatar.style.backgroundImage) {
        avatar.textContent = name.charAt(0).toUpperCase();
    }
}
