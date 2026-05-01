function initActiviteMap() {
    // Find by name pattern, not id
    const loc = document.querySelector('[name$="[localisationActivite]"]');
    const suggestBox = document.getElementById('suggestions');
    const mapEl = document.getElementById('osmMap');

    if (!mapEl || typeof L === 'undefined') return;

    let osmMap = L.map('osmMap').setView([36.8, 10.2], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(osmMap);

    let osmMarker = null;

    // If existing value, zoom to it
    if (loc && loc.value.trim()) {
        fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(loc.value)}&format=json&limit=1&accept-language=fr`,
            { headers: { 'User-Agent': 'DouraMondo/1.0' } })
            .then(r => r.json())
            .then(data => {
                if (data.length > 0) {
                    osmMap.setView([parseFloat(data[0].lat), parseFloat(data[0].lon)], 13);
                    osmMarker = L.marker([parseFloat(data[0].lat), parseFloat(data[0].lon)]).addTo(osmMap);
                }
            }).catch(() => {});
    }

    // Click map → reverse geocode → fill field
    osmMap.on('click', async (e) => {
        const { lat, lng } = e.latlng;
        if (osmMarker) osmMap.removeLayer(osmMarker);
        osmMarker = L.marker([lat, lng]).addTo(osmMap);
        try {
            const r = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=fr`,
                { headers: { 'User-Agent': 'DouraMondo/1.0' } }
            );
            const data = await r.json();
            if (data.address && loc) {
                loc.value = data.address.city
                    || data.address.town
                    || data.address.village
                    || data.address.county
                    || data.display_name;
                // trigger validation highlight
                loc.classList.remove('input-invalid');
                loc.classList.add('input-valid');
                const err = document.getElementById('err-loc');
                if (err) { err.textContent = ''; err.classList.remove('show'); }
            }
        } catch(err) {}
    });

    // Autocomplete suggestions
    if (loc && suggestBox) {
        let timeout;
        loc.addEventListener('input', () => {
            clearTimeout(timeout);
            if (loc.value.trim().length < 3) { suggestBox.style.display = 'none'; return; }
            timeout = setTimeout(async () => {
                try {
                    const r = await fetch(
                        `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(loc.value)}&format=json&limit=5&accept-language=fr`,
                        { headers: { 'User-Agent': 'DouraMondo/1.0' } }
                    );
                    const data = await r.json();
                    suggestBox.innerHTML = '';
                    if (!data.length) { suggestBox.style.display = 'none'; return; }
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.textContent = item.display_name;
                        div.style.cssText = 'padding:10px 14px; cursor:pointer; font-size:12px; border-bottom:1px solid #F5EDEA;';
                        div.addEventListener('mouseenter', () => div.style.background = '#FFF8F0');
                        div.addEventListener('mouseleave', () => div.style.background = 'white');
                        div.addEventListener('click', () => {
                            loc.value = item.display_name;
                            suggestBox.style.display = 'none';
                            loc.classList.add('input-valid');
                        });
                        suggestBox.appendChild(div);
                    });
                    suggestBox.style.display = 'block';
                } catch(e) { suggestBox.style.display = 'none'; }
            }, 400);
        });
        document.addEventListener('click', e => {
            if (!loc.contains(e.target) && !suggestBox.contains(e.target))
                suggestBox.style.display = 'none';
        });
    }
}