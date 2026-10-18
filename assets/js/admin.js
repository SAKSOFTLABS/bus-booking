/**
 * Admin Panel Interactivity & CSV Exporter
 */

document.addEventListener('DOMContentLoaded', function () {
    // Sidebar toggle for mobile
    const toggleBtn = document.getElementById('toggleSidebarBtn');
    const sidebar = document.getElementById('adminSidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    // Export Table to CSV
    window.exportTableToCSV = function (tableId, filename = 'report.csv') {
        const table = document.getElementById(tableId);
        if (!table) return;

        let csv = [];
        const rows = table.querySelectorAll('tr');

        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll('td, th');

            for (let j = 0; j < cols.length; j++) {
                // Ignore elements marked no-export
                if (cols[j].classList.contains('no-export')) continue;
                
                let text = cols[j].innerText.replace(/"/g, '""').trim();
                row.push('"' + text + '"');
            }
            if (row.length > 0) {
                csv.push(row.join(','));
            }
        }

        const csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const downloadLink = document.createElement('a');
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        downloadLink.remove();
    };
});
