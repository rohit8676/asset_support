</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global Toast Notification Helper
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `custom-toast toast-${type}`;
    const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    
    toast.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            <i class="bi ${iconClass} fs-5"></i>
            <span>${message}</span>
        </div>
        <button type="button" class="btn-close btn-close-white ms-3" aria-label="Close" style="font-size: 0.75rem;"></button>
    `;

    container.appendChild(toast);

    toast.querySelector('.btn-close').addEventListener('click', () => toast.remove());

    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
</script>
</body>
</html>
