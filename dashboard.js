// Initialize all event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
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
});

// Modal Functions
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
    const modal = document.getElementById('profileModal');
    modal.style.display = 'block';
}

function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    modal.style.display = 'none';
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
        const response = await fetch('contact_operations.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
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
}

function loadContacts() {
    location.reload();
}

// Edit Contact
function editContact(contactId) {
    // Show loading state
    const editButton = document.querySelector(`button[onclick="editContact(${contactId})"]`);
    if (editButton) {
        editButton.disabled = true;
        editButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    }

    // Fetch contact details
    fetch(`contact_operations.php?action=get&id=${contactId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditForm(data.contact);
                showEditContactModal();
            } else {
                showNotification(data.message || 'Failed to fetch contact details', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to fetch contact details', 'error');
        })
        .finally(() => {
            // Reset button state
            if (editButton) {
                editButton.disabled = false;
                editButton.innerHTML = '<i class="fas fa-edit"></i> Edit';
            }
        });
}

function populateEditForm(contact) {
    document.getElementById('edit_contact_id').value = contact.id;
    document.getElementById('edit_name').value = contact.name;
    document.getElementById('edit_phone').value = contact.phone;
    document.getElementById('edit_email').value = contact.email;
}

function handleEditContact(event) {
    event.preventDefault();
    
    const form = document.getElementById('editContactForm');
    const formData = new FormData(form);
    formData.append('action', 'edit_contact');
    
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('contact_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Contact updated successfully', 'success');
            closeEditContactModal();
            loadContacts();
        } else {
            showNotification(data.message || 'Failed to update contact', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to update contact', 'error');
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Save Changes';
    });
}

// Delete Contact
function deleteContact(contactId) {
    if (!contactId) {
        showNotification('Invalid contact ID', 'error');
        return;
    }

    if (confirm('Are you sure you want to delete this contact?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('contact_id', contactId);

        fetch('contact_operations.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Contact deleted successfully', 'success');
                loadContacts();
            } else {
                throw new Error(data.message || 'Failed to delete contact');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification(error.message || 'Failed to delete contact', 'error');
        });
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let modal of modals) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}

// Profile Management
async function fetchProfileData() {
    try {
        const response = await fetch('profile_operations.php', {
            method: 'GET',
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'Failed to fetch profile data');
        }

        // Update form fields with profile data
        const data = result.data;
        populateProfileForm(data);
        
        return data;
    } catch (error) {
        console.error('Error fetching profile:', error);
        throw error;
    }
}

function populateProfileForm(profile) {
    if (!profile) return;

    const form = document.getElementById('profileForm');
    if (!form) return;

    // Map database fields to form fields
    const fieldMapping = {
        'name': 'profile_name',
        'email': 'profile_email',
        'phone': 'profile_phone',
        'avatar': 'profile_avatar_preview'
    };

    // Populate text inputs
    Object.entries(fieldMapping).forEach(([dbField, formField]) => {
        if (dbField !== 'avatar') {
            const input = document.getElementById(formField);
            if (input) {
                input.value = profile[dbField] || '';
            }
        }
    });
    
    // Handle avatar preview
    const previewImg = document.getElementById('profile_avatar_preview');
    if (previewImg) {
        if (profile.avatar) {
            previewImg.src = profile.avatar;
            previewImg.style.display = 'block';
        } else {
            previewImg.style.display = 'none';
        }
    }
}

async function handleProfileUpdate(event) {
    event.preventDefault();
    
    const form = document.getElementById('profileForm');
    const formData = new FormData(form);
    formData.append('action', 'update_profile');
    
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    try {
        const response = await fetch('profile_operations.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Profile updated successfully', 'success');
            if (data.data) {
                updateProfileSidebar(data.data);
                // Update the default avatar if no profile picture
                if (!data.data.avatar) {
                    const defaultAvatar = document.querySelector('.profile-avatar .default-avatar');
                    if (defaultAvatar && data.data.name) {
                        defaultAvatar.textContent = data.data.name.charAt(0).toUpperCase();
                    }
                }
            }
            closeProfileModal();
        } else {
            throw new Error(data.message || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showNotification(error.message || 'Failed to update profile', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
}

async function updateProfileSidebar(profile) {
    if (!profile) return;
    
    // Update profile information in the sidebar
    const nameElement = document.querySelector('.profile-header h2');
    const emailElement = document.querySelector('.profile-info-item:nth-child(1) span');
    const phoneElement = document.querySelector('.profile-info-item:nth-child(2) span');
    const avatarContainer = document.querySelector('.profile-avatar');
    
    // Update text content
    if (nameElement) nameElement.textContent = profile.name || '';
    if (emailElement) emailElement.textContent = profile.email || 'No email set';
    if (phoneElement) phoneElement.textContent = profile.phone || 'No phone set';
    
    // Update avatar
    if (avatarContainer) {
        if (profile.avatar) {
            avatarContainer.innerHTML = `<img src="${profile.avatar}" alt="Profile Picture">`;
        } else if (profile.name) {
            avatarContainer.innerHTML = `<div class="default-avatar">${profile.name.charAt(0).toUpperCase()}</div>`;
        } else {
            avatarContainer.innerHTML = `<div class="default-avatar">?</div>`;
        }
    }
}

// Preview avatar image before upload
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
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

// Profile Management
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
