document.addEventListener('DOMContentLoaded', () => {
    console.log('DysAdapt loaded successfully.');

    // Newsletter Form Logic
    const newsletterForm = document.querySelector('.newsletter__form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const emailInput = document.getElementById('email');
            const consentCheckbox = document.getElementById('consent');

            const email = emailInput.value;
            const consent = consentCheckbox.checked;

            try {
                // PHP Backend (Same domain)
                const response = await fetch('api/subscribe.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, consent })
                });

                const data = await response.json();

                if (response.ok) {
                    alert('Inscription réussie ! Merci.');
                    newsletterForm.reset();
                } else {
                    alert('Erreur : ' + (data.error || 'Une erreur est survenue.'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Impossible de contacter le serveur. Vérifiez que le backend tourne bien sur le port 3000.');
            }
        });
    }

    // Cookie Banner Logic
    const cookieBanner = document.getElementById('cookie-banner');
    const acceptBtn = document.getElementById('accept-cookies');
    const refuseBtn = document.getElementById('refuse-cookies');
    const consentKey = 'dysadapt_cookie_consent';

    // Show banner if no choice made
    if (!localStorage.getItem(consentKey)) {
        cookieBanner.classList.remove('hidden');
    }

    if (acceptBtn) {
        acceptBtn.addEventListener('click', () => {
            localStorage.setItem(consentKey, 'accepted');
            cookieBanner.classList.add('hidden');
        });
    }

    if (refuseBtn) {
        refuseBtn.addEventListener('click', () => {
            localStorage.setItem(consentKey, 'refused');
            cookieBanner.classList.add('hidden');
        });
    }
});
