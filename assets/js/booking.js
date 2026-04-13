// ====== BOOKING SYSTEM ======
// Add this to your client activity detail Twig template's <script> section

function bookActivity(activityId, userId) {
    if (!userId) {
        alert('Please log in to book an activity');
        return;
    }

    // Show loading state
    const btn = document.querySelector(`[data-book-btn="${activityId}"]`);
    if (!btn) return;
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Booking...';
    btn.disabled = true;

    fetch(`/booking/reserve/${activityId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: `user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('✓ ' + data.message, 'success');
            updateActivityUI(activityId, data, true);
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Booked!';
            btn.style.background = '#6b0000';
        } else {
            showToast('✗ ' + data.message, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while booking', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function cancelBooking(activityId, userId) {
    if (!userId) {
        alert('Please log in');
        return;
    }

    if (!confirm('Cancel this booking?')) return;

    const btn = document.querySelector(`[data-book-btn="${activityId}"]`);
    if (!btn) return;

    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cancelling...';
    btn.disabled = true;

    fetch(`/booking/cancel/${activityId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: `user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('✓ Booking cancelled', 'success');
            updateActivityUI(activityId, data, false);
            btn.innerHTML = '<i class="fa-solid fa-calendar"></i> Book Activity';
            btn.style.background = '';
        } else {
            showToast('✗ ' + data.message, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function updateActivityUI(activityId, data, isBooked) {
    // Update capacity display
    const capacityEl = document.querySelector(`[data-capacity="${activityId}"]`);
    if (capacityEl) {
        const percentage = Math.round((data.places_reserved / data.capacity) * 100);
        capacityEl.innerHTML = `
            <div><strong>Capacity</strong></div>
            <p>${data.places_reserved} / ${data.capacity} booked (${percentage}%)</p>
            <div style="background:#eee; height:6px; border-radius:3px; margin-top:8px; overflow:hidden;">
                <div style="background:#8B0000; height:100%; width:${percentage}%; transition:width 0.3s;"></div>
            </div>
        `;
    }

    // Update places display
    const placesEl = document.querySelector(`[data-places="${activityId}"]`);
    if (placesEl) {
        if (data.places_remaining <= 0) {
            placesEl.innerHTML = '<span style="color:#8B0000; font-weight:700;">FULL</span>';
        } else {
            placesEl.innerHTML = `<span style="color:#28a745;">${data.places_remaining} places left</span>`;
        }
    }
}

function getActivityStatus(activityId) {
    return fetch(`/booking/status/${activityId}`)
        .then(r => r.json())
        .then(data => {
            console.log('Activity Status:', data);
            return data;
        })
        .catch(e => console.error('Error:', e));
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#0d6efd'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideInUp 0.3s ease;
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutDown 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add animation styles to document
if (!document.querySelector('style[data-booking]')) {
    const style = document.createElement('style');
    style.setAttribute('data-booking', 'true');
    style.innerHTML = `
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideOutDown {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(20px); }
        }
    `;
    document.head.appendChild(style);
}