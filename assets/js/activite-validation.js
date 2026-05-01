function initActiviteValidation(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    // Use name selector — works regardless of Symfony generated IDs
    const nom   = form.querySelector('[name$="[nomActivite]"]');
    const cat   = form.querySelector('[name$="[categorieActivite]"]');
    const desc  = form.querySelector('[name$="[descriptionActivite]"]');
    const cout  = form.querySelector('[name$="[coutActivite]"]');
    const duree = form.querySelector('[name$="[dureeActivite]"]');
    const loc   = form.querySelector('[name$="[localisationActivite]"]');
    const img   = form.querySelector('[name$="[imageActivite]"]');
    const counter = document.getElementById('desc-count');

    function showErr(id, msg) {
        const el = document.getElementById('err-' + id);
        if (!el) return;
        el.textContent = msg || '';
        el.classList.toggle('show', !!msg);
    }
    function markField(el, ok) {
        if (!el) return;
        el.classList.toggle('input-valid',   ok);
        el.classList.toggle('input-invalid', !ok);
    }

    if (desc && counter) {
        desc.addEventListener('input', () => {
            const l = desc.value.length;
            counter.textContent = l + ' / 500 caractères';
            counter.style.color = l > 480 ? '#E53935' : '#C0A898';
        });
    }

    if (nom) nom.addEventListener('input', () => {
        const v = nom.value.trim();
        if (!v)                   { showErr('nom', 'Le nom est obligatoire');                   markField(nom, false); }
        else if (v.length < 3)    { showErr('nom', 'Minimum 3 caractères');                     markField(nom, false); }
        else if (/[0-9]/.test(v)) { showErr('nom', 'Le nom ne doit pas contenir de chiffres'); markField(nom, false); }
        else                      { showErr('nom', '');                                          markField(nom, true);  }
    });

    if (cat) cat.addEventListener('change', () => {
        if (!cat.value) { showErr('cat', 'Veuillez choisir une catégorie'); markField(cat, false); }
        else            { showErr('cat', '');                                markField(cat, true);  }
    });

    if (cout) cout.addEventListener('input', () => {
        const v = parseFloat(cout.value);
        if (!cout.value)   { showErr('cout', 'Le prix est obligatoire');   markField(cout, false); }
        else if (v <= 0)   { showErr('cout', 'Le prix doit être positif'); markField(cout, false); }
        else if (v > 99999){ showErr('cout', 'Maximum 99 999 DT');         markField(cout, false); }
        else               { showErr('cout', '');                           markField(cout, true);  }
    });

    if (duree) duree.addEventListener('input', () => {
        const v = parseInt(duree.value);
        if (!duree.value) { showErr('duree', 'La durée est obligatoire');    markField(duree, false); }
        else if (v < 1)   { showErr('duree', 'La durée doit être positive'); markField(duree, false); }
        else if (v > 1440){ showErr('duree', 'Maximum 1440 min (24h)');      markField(duree, false); }
        else              { showErr('duree', '');                             markField(duree, true);  }
    });

    if (img) img.addEventListener('blur', () => {
        const v = img.value.trim();
        if (!v)                         { showErr('image', ''); img.classList.remove('input-valid','input-invalid'); }
        else if (!v.startsWith('http')) { showErr('image', "L'URL doit commencer par http://"); markField(img, false); }
        else                            { showErr('image', ''); markField(img, true); }
    });

    if (loc) loc.addEventListener('blur', () => {
        if (!loc.value.trim()) { showErr('loc', 'La destination est obligatoire'); markField(loc, false); }
        else                   { showErr('loc', '');                                markField(loc, true);  }
    });

    form.addEventListener('submit', function(e) {
        let ok = true;
        const checks = [
            { el: nom,   id: 'nom',   fn: v => v.length >= 3 && !/[0-9]/.test(v),          msg: 'Nom invalide (min 3 caractères, sans chiffres)' },
            { el: cat,   id: 'cat',   fn: v => v !== '',                                     msg: 'Veuillez choisir une catégorie' },
            { el: cout,  id: 'cout',  fn: v => parseFloat(v) > 0 && parseFloat(v) <= 99999, msg: 'Prix invalide (0–99 999 DT)' },
            { el: duree, id: 'duree', fn: v => parseInt(v) >= 1 && parseInt(v) <= 1440,     msg: 'Durée invalide (1–1440 min)' },
            { el: loc,   id: 'loc',   fn: v => v.trim().length > 0,                          msg: 'La destination est obligatoire' },
        ];
        checks.forEach(c => {
            if (!c.el) return;
            if (!c.fn(c.el.value)) { showErr(c.id, c.msg); markField(c.el, false); ok = false; }
        });
        if (!ok) {
            e.preventDefault();
            form.querySelector('.field-error.show')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
}