document.addEventListener('DOMContentLoaded', function() {
    const questions = document.querySelectorAll('.question-card');

    // Highlight the card when a radio button is clicked
    questions.forEach(card => {
        card.addEventListener('click', function() {
            // Remove active class from all other cards
            questions.forEach(c => c.style.borderColor = '#dadce0');
            // Add active border to this card (FEU Green)
            this.style.borderColor = '#274c07';
            this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        });
    });

    // Simple submission confirmation
    const form = document.querySelector('form');
    form?.addEventListener('submit', function(event) {
        if (this.dataset.confirmSubmit === '1' && !confirm('Submit this evaluation now?')) {
            event.preventDefault();
            return;
        }

        const submitBtn = this.querySelector('.btn-submit');
        submitBtn.disabled = true;
        submitBtn.innerText = 'Submitting...';
    });
});
