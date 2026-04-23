// ─────────────────────────────────────────
//  ResultMS — main.js
// ─────────────────────────────────────────

// Auto-dismiss flash messages after 4 seconds
document.addEventListener('DOMContentLoaded', () => {
  const flash = document.querySelector('.flash-msg');
  if (flash) {
    setTimeout(() => {
      flash.style.transition = 'opacity .4s';
      flash.style.opacity = '0';
      setTimeout(() => flash.remove(), 400);
    }, 4000);
  }
});

// Confirm before delete actions
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.dataset.confirm || 'Are you sure?')) {
      e.preventDefault();
    }
  });
});

// Animate progress bars on page load
document.querySelectorAll('.progress-fill').forEach(bar => {
  const target = bar.dataset.width || '0';
  bar.style.width = '0%';
  requestAnimationFrame(() => {
    bar.style.width = target + '%';
  });
});

// Filter table rows by search input
const searchInput = document.getElementById('tableSearch');
if (searchInput) {
  searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}