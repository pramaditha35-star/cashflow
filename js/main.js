// js/main.js

/**
 * Open a modal by its element ID.
 * @param {string} modalId 
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

/**
 * Close a modal by its element ID.
 * @param {string} modalId 
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Global click handler to close modals when clicking on the overlay backdrop
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal-backdrop')) {
        event.target.style.display = 'none';
    }
});
