document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.jc-acf-record-row');

    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Prevent navigation if clicking on a link or button inside the row
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('a') || e.target.closest('button')) {
                return;
            }

            const url = this.getAttribute('data-url');
            if (url) {
                window.location.href = url;
            }
        });
    });
});
