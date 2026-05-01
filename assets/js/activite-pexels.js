function initPexelsAutofill() {
    const pexelsBtn  = document.getElementById('pexelsBtn');
    const imgField   = document.querySelector('[name$="[imageActivite]"]');
    const imgPreview = document.getElementById('imgPreview');
    const previewImg = document.getElementById('previewImg');

    if (!pexelsBtn || !imgField) {
        console.warn('Pexels: missing elements');
        return;
    }

    pexelsBtn.addEventListener('click', async () => {
        const nom      = document.querySelector('[name$="[nomActivite]"]')?.value.trim();
        const categorie = document.querySelector('[name$="[categorieActivite]"]')?.value.trim();
        const query    = nom || categorie || 'travel activity';

        if (!query) {
            alert("Entrez d'abord le nom de l'activité");
            return;
        }

        pexelsBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Recherche...';
        pexelsBtn.disabled = true;

        try {
            const res  = await fetch(`/activite/pexels-search?q=${encodeURIComponent(query)}`);
            const data = await res.json();

            if (data.url) {
                imgField.value = data.url;
                imgField.classList.remove('input-invalid');
                imgField.classList.add('input-valid');

                if (imgPreview && previewImg) {
                    previewImg.src = data.url;
                    imgPreview.style.display = 'block';
                }

                pexelsBtn.innerHTML = '<i class="fa-solid fa-check"></i> Trouvé !';
            } else {
                pexelsBtn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Non trouvé';
            }
        } catch(e) {
            console.error('Pexels error:', e);
            pexelsBtn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Erreur réseau';
        }

        setTimeout(() => {
            pexelsBtn.innerHTML = '<i class="fa-solid fa-image"></i> Auto Pexels';
            pexelsBtn.disabled = false;
        }, 2000);
    });

    // Show preview if image URL already filled (edit mode)
    if (imgField.value.trim() && imgPreview && previewImg) {
        previewImg.src = imgField.value.trim();
        imgPreview.style.display = 'block';
    }

    // Update preview when URL is typed manually
    imgField.addEventListener('blur', () => {
        const v = imgField.value.trim();
        if (v.startsWith('http') && imgPreview && previewImg) {
            previewImg.src = v;
            imgPreview.style.display = 'block';
        } else if (!v && imgPreview) {
            imgPreview.style.display = 'none';
        }
    });
}