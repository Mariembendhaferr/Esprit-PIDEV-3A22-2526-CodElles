function initWikipediaAutofill(btnId, descId, counterId) {
    const btn     = document.getElementById(btnId);
    const counter = document.getElementById(counterId);

    // Use name selector instead of id for fields Symfony controls
    const nom  = document.querySelector('[name$="[nomActivite]"]');
    const desc = document.querySelector('[name$="[descriptionActivite]"]');

    if (!btn || !desc || !nom) {
        console.warn('Wikipedia autofill: missing elements', { btn, desc, nom });
        return;
    }

    btn.addEventListener('click', async () => {
        const nomVal = nom.value.trim();
        if (!nomVal) {
            alert("Entrez d'abord le nom de l'activité");
            nom.focus();
            return;
        }

        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Chargement...';
        btn.disabled = true;

        const tryWiki = async (lang) => {
            const r = await fetch(
                `https://${lang}.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(nomVal)}`
            );
            if (!r.ok) return null;
            const d = await r.json();
            return d.extract || null;
        };

        try {
            const text = await tryWiki('fr') || await tryWiki('en');
            if (text) {
                desc.value = text.substring(0, 490);
                if (counter) counter.textContent = desc.value.length + ' / 500 caractères';
                desc.classList.add('input-valid');
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Rempli !';
            } else {
                btn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Non trouvé';
            }
        } catch(e) {
            console.error('Wikipedia error:', e);
            btn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Erreur réseau';
        }

        setTimeout(() => {
            btn.innerHTML = '<i class="fa-brands fa-wikipedia-w"></i> Auto-fill Wikipedia';
            btn.disabled = false;
        }, 2000);
    });
}