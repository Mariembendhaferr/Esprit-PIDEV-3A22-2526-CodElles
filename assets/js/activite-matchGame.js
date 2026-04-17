// ══ MATCH GAME LOGIC ══════════════════════════════════════

// ══ STATE ════════════════════════════════════════════════
let pool = [], winner = null, round = 0, rightCard = null, leftCard = null;
const TOTAL_ROUNDS = 5;

// ══ SETUP ════════════════════════════════════════════════
function startGame() {
    const country = document.getElementById('setupCountry').value;
    const cat     = document.getElementById('setupCat').value;

    let filtered = ALL.filter(a => {
        const matchCat = !cat     || a.categorieActivite === cat;
        const matchLoc = !country || (a.localisationActivite || '').toLowerCase().includes(country.toLowerCase());
        return matchCat && matchLoc;
    });

    // Shuffle
    filtered = filtered.sort(() => Math.random() - 0.5);

    if (filtered.length < 2) {
        alert('Pas assez d\'activités pour ce filtre. Essaie une autre combinaison !');
        return;
    }

    pool   = filtered;
    round  = 0;
    winner = null;

    document.getElementById('setupView').style.display = 'none';
    document.getElementById('gameView').style.display  = 'block';
    document.getElementById('resultView').style.display = 'none';

    nextRound();
}

// ══ GAME LOGIC ════════════════════════════════════════════
function nextRound() {
    if (round >= TOTAL_ROUNDS) { showResult(); return; }

    round++;
    document.getElementById('roundNum').textContent = round;
    document.getElementById('progressBar').style.width = ((round - 1) / TOTAL_ROUNDS * 100) + '%';

    // Pick challenger from pool
    const challenger = pool.shift();

    if (!winner) {
        // First round: pick two from pool
        leftCard  = challenger;
        rightCard = pool.shift();
    } else {
        // Winner stays on left, challenger on right
        leftCard  = winner;
        rightCard = challenger;
    }

    renderCard('cardLeft',  leftCard);
    renderCard('cardRight', rightCard);
}

function renderCard(id, a) {
    const el = document.getElementById(id);
    const tags = (a.vibe_tags || [a.categorieActivite]).slice(0, 3);
    el.innerHTML = `
        <div class="choose-hint">⚡ Je choisis celle-ci !</div>
        ${a.imageActivite
            ? `<img class="duel-card-img" src="${a.imageActivite}" alt="${esc(a.nomActivite)}" loading="eager" decoding="async" fetchpriority="high" style="display:block;" onerror="this.outerHTML='<div class=duel-card-img-placeholder><i class=fa-solid\\ fa-image></i></div>'">`
            : `<div class="duel-card-img-placeholder"><i class="fa-solid fa-image"></i></div>`
        }
        <div class="duel-card-overlay"></div>
        <div class="duel-card-body">
            <div class="duel-card-name">${esc(a.nomActivite)}</div>
            ${a.localisationActivite ? `<div class="duel-card-loc"><i class="fa-solid fa-location-dot"></i>${esc(a.localisationActivite)}</div>` : ''}
            <div class="duel-card-tags">${tags.map(t => `<span class="duel-tag">${t}</span>`).join('')}</div>
            <p class="duel-card-desc">${esc((a.descriptionActivite || '').substring(0, 100))}${(a.descriptionActivite||'').length > 100 ? '…' : ''}</p>
        </div>`;
    el.style.animation = 'none';
    el.offsetHeight; // reflow
    el.style.animation = '';
}

function choose(side) {
    winner = side === 'left' ? leftCard : rightCard;
    const loserEl  = document.getElementById(side === 'left' ? 'cardRight' : 'cardLeft');
    const winnerEl = document.getElementById(side === 'left' ? 'cardLeft'  : 'cardRight');

    winnerEl.classList.add('winner-flash');
    loserEl.classList.add('loser-exit');

    setTimeout(() => {
        winnerEl.classList.remove('winner-flash');
        loserEl.classList.remove('loser-exit');

        if (round >= TOTAL_ROUNDS) {
            showResult();
        } else {
            nextRound();
        }
    }, 450);
}

// ══ RESULT ═══════════════════════════════════════════════
function showResult() {
    const a = winner;
    if (!a) return;

    // 1. Hide the game view
    document.getElementById('gameView').style.display = 'none';

    // 2. Build the result card HTML manually (no external function needed)
    const tags = (a.vibe_tags || [a.categorieActivite]).filter(Boolean).slice(0, 3);
    const resultCard = document.getElementById('resultCard');
    
    if (resultCard) {
        resultCard.innerHTML = `
            ${a.imageActivite
                ? `<img class="result-card-img" src="${a.imageActivite}" alt="${esc(a.nomActivite)}" onerror="this.outerHTML='<div class=result-card-img-placeholder>🏆</div>'">`
                : `<div class="result-card-img-placeholder">🏆</div>`
            }
            <div class="result-card-body">
                <div class="result-card-name">${esc(a.nomActivite)}</div>
                <div class="result-card-meta">
                    ${a.localisationActivite ? `<span>📍 ${esc(a.localisationActivite)}</span>` : ''}
                    ${a.coutActivite ? `<span>💰 ${esc(String(a.coutActivite))} DT</span>` : ''}
                </div>
                ${tags.length ? `<div class="result-tags">${tags.map(t => `<span class="result-tag">${esc(t)}</span>`).join('')}</div>` : ''}
                ${a.descriptionActivite ? `<p style="color:#5D4037;font-size:14px;line-height:1.7;margin:0 0 16px;">${esc(a.descriptionActivite)}</p>` : ''}
                <div class="ai-message" id="aiMessage">
                    <div class="ai-message-header">✨ Pourquoi ce choix ?</div>
                    <span class="ai-loading">Génération de votre recommandation…</span>
                </div>
            </div>
        `;
    }

    // 3. Update the button link (Check your actual route prefix)
    const btn = document.getElementById('resultViewBtn');
    if (btn) btn.href = '/activities/' + a.id;

    // 4. Show result view and trigger CSS animation
    const rv = document.getElementById('resultView');
    if (rv) {
        rv.style.display = 'block';
        rv.classList.remove('result-visible');
        
        // Force reflow and add animation class
        setTimeout(() => {
            rv.classList.add('result-visible');
        }, 50);
    }

    // 5. Fire confetti
    if (typeof launchConfetti === 'function') launchConfetti();

    // 6. Request AI message last
    fetchAIMessage(a);
}

// ══ AI INTEGRATION ════════════════════════════════════════
async function fetchAIMessage(a) {
    try {
        const configEl = document.getElementById('match-config');
        const url = configEl ? configEl.dataset.url : '/match-ai';

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nom: a.nomActivite,
                localisation: a.localisationActivite,
                categorie: a.categorieActivite,
                prix: a.coutActivite,
                description: a.descriptionActivite
            })
        });

        const data = await res.json();
        const msg = data.message;

        const container = document.querySelector('#aiMessage');
        if (container) {
            // Remove the loading spinner
            container.innerHTML = `<div class="ai-message-header">✨ L'avis de votre Coach IA</div><p class="ai-message-text" id="typewriter"></p>`;
            
            // 🔥 TYPING EFFECT LOGIC
            let i = 0;
            const speed = 30; // ms per character
            const txt = msg;
            function typeWriter() {
                if (i < txt.length) {
                    document.getElementById("typewriter").innerHTML += txt.charAt(i);
                    i++;
                    setTimeout(typeWriter, speed);
                }
            }
            typeWriter();
        }
    } catch(e) {
        console.error("AI Error:", e);
    }
}
// ══ CONFETTI ══════════════════════════════════════════════
function launchConfetti() {
    const colors = ['#C9A84C','#E8C070','#8B0000','#ffffff','#F5E8C0'];
    for (let i = 0; i < 60; i++) {
        setTimeout(() => {
            const el = document.createElement('div');
            el.className = 'confetti-piece';
            el.style.cssText = `
                top: 0;
                left:${Math.random() * 100}vw;
                background:${colors[Math.floor(Math.random()*colors.length)]};
                animation-duration:${1.5 + Math.random()*2}s;
                animation-delay:${Math.random()*0.5}s;
                width:${6+Math.random()*8}px;
                height:${6+Math.random()*8}px;
                border-radius:${Math.random() > 0.5 ? '50%' : '2px'};
            `;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 4000);
        }, i * 30);
    }
}

function restartGame() {
    document.getElementById('resultView').style.display = 'none';
    document.getElementById('setupView').style.display  = 'block';
    const btn = document.getElementById('startBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-play"></i> Lancer le tournoi';
}

// ══ UTILITIES ═════════════════════════════════════════════
function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── 3D Tilt effect ─────────────────────────────────────
document.addEventListener('mousemove', function(e) {
    document.querySelectorAll('.duel-card').forEach(card => {
        const rect  = card.getBoundingClientRect();
        const x     = e.clientX - rect.left - rect.width  / 2;
        const y     = e.clientY - rect.top  - rect.height / 2;
        const rotX  = (-y / rect.height * 12).toFixed(2);
        const rotY  = ( x / rect.width  * 12).toFixed(2);
        if (x > -rect.width/2 - 20 && x < rect.width/2 + 20 &&
            y > -rect.height/2 - 20 && y < rect.height/2 + 20) {
            card.style.transform = `perspective(800px) rotateX(${rotX}deg) rotateY(${rotY}deg) scale(1.03)`;
        }
    });
});
document.addEventListener('mouseleave', function() {
    document.querySelectorAll('.duel-card').forEach(card => {
        card.style.transform = '';
    });
});

// ── Countdown before game starts ───────────────────────
function startGameWithCountdown() {
    const btn = document.getElementById('startBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-hourglass"></i> 3...';
    setTimeout(() => btn.innerHTML = '<i class="fa-solid fa-hourglass-half"></i> 2...', 1000);
    setTimeout(() => btn.innerHTML = '<i class="fa-solid fa-hourglass-end"></i> 1...', 2000);
    setTimeout(() => { btn.innerHTML = '<i class="fa-solid fa-play"></i> C\'est parti !'; startGame(); }, 3000);
}
