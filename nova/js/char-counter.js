/**
 * Nova System - Universal Character Counter
 * Automatically adds character count to all textarea fields with maxlength attribute
 *
 * Simple version: black text counter, no color changes
 * Works for: Categories, Subcategories, Articles, any textarea with maxlength
 */

document.addEventListener('DOMContentLoaded', function() {
    // Find all textareas with maxlength attribute
    document.querySelectorAll('textarea[maxlength]').forEach(function(textarea) {
        const maxLength = textarea.getAttribute('maxlength');
        const formText = textarea.parentElement.querySelector('.form-text');

        // Only add counter if form-text exists
        if (formText) {
            // Create counter element (bold text, no colors)
            const counterHTML = ' - <span class="char-count fw-bold">0</span>/' + maxLength + ' caratteri';
            formText.innerHTML += counterHTML;

            // Get counter span reference
            const counterSpan = formText.querySelector('.char-count');

            // Update counter function (simple version)
            function updateCounter() {
                counterSpan.textContent = textarea.value.length;
            }

            // Listen for input changes
            textarea.addEventListener('input', updateCounter);

            // Initialize counter on page load
            updateCounter();
        }
    });
});
