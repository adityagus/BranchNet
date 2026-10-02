import './bootstrap';
import '../css/pagination.css';
import '../css/layout-fixes.css';

const clock = document.querySelector('.top-actions span:nth-child(2)');
const updateClock = () => {
    if (clock) {
        clock.textContent = 'Live · ' + new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Jakarta',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }).format(new Date());
    }
};
updateClock();
setInterval(updateClock, 1000);

// Keep dashboard and inventory pages live without a full browser reload.
const liveContent = document.querySelector('.content');
const isFormPage = document.querySelector('.form-card');
const isDetailPage = document.querySelector('.chart-card');
if (liveContent && !isFormPage && !isDetailPage) {
    setInterval(async () => {
        if (document.visibilityState === 'hidden' || document.activeElement?.matches('input,select,textarea')) return;

        try {
            const response = await fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                cache: 'no-store',
            });
            if (!response.ok) return;
            const html = await response.text();
            const fresh = new DOMParser().parseFromString(html, 'text/html').querySelector('.content');
            if (fresh) liveContent.innerHTML = fresh.innerHTML;
        } catch (error) {
            console.debug('Live refresh unavailable', error);
        }
    }, 15000);
}
