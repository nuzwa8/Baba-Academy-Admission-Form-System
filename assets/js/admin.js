/**
 * assets/js/admin.js
 * Baba Academy Admission System - Admin Dashboard Sorting Logic
 * Phase 5 - JavaScript for Dynamic Table Sorting
 */

document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('adminTable');
    if (!table) return;

    const headers = table.querySelectorAll('th');
    const tbody = table.querySelector('tbody');

    // State to track current sorting
    let currentSortColumn = null;
    let currentSortDirection = 'asc'; // 'asc' for Ascending, 'desc' for Descending

    /**
     * Finds the value to be used for sorting from a cell.
     * It prefers data-sort-value for numerical columns (like fees/dates).
     * @param {HTMLElement} cell The table cell (td) element.
     * @returns {string|number} The value to sort by.
     */
    function getCellValue(cell) {
        // Use data-sort-value if available (for precise numerical/date sorting)
        if (cell.hasAttribute('data-sort-value')) {
            const sortValue = cell.getAttribute('data-sort-value');
            // Try to parse as float, otherwise treat as string
            return parseFloat(sortValue) || sortValue;
        }

        // Handle specific columns that need special treatment
        const header = cell.closest('table').querySelector(`th:nth-child(${cell.cellIndex + 1})`).textContent.trim();

        if (header === 'Days Remaining') {
            const text = cell.textContent.trim();
            if (text === 'Overdue') return -Infinity; // Overdue comes first
            if (text === 'Today') return 0;
            if (text === 'N/A') return Infinity; // N/A comes last
            return parseInt(text) || Infinity; // Sort by number of days
        }
        
        // Default to text content
        return cell.textContent.trim();
    }

    /**
     * Sorts the table rows based on the column index.
     * @param {number} columnIdx The index of the column to sort by.
     */
    function sortTable(columnIdx) {
        const rowsArray = Array.from(tbody.querySelectorAll('tr'));

        // Determine the sorting direction
        const direction = (columnIdx === currentSortColumn && currentSortDirection === 'asc') ? 'desc' : 'asc';

        rowsArray.sort((rowA, rowB) => {
            const cellA = rowA.cells[columnIdx];
            const cellB = rowB.cells[columnIdx];

            const valA = getCellValue(cellA);
            const valB = getCellValue(cellB);

            let comparison = 0;

            if (typeof valA === 'number' && typeof valB === 'number') {
                comparison = valA - valB;
            } else {
                // Case-insensitive string comparison
                const strA = String(valA).toLowerCase();
                const strB = String(valB).toLowerCase();
                if (strA < strB) comparison = -1;
                if (strA > strB) comparison = 1;
            }

            return direction === 'asc' ? comparison : comparison * -1;
        });

        // Clear existing rows and append sorted rows
        tbody.innerHTML = '';
        rowsArray.forEach(row => tbody.appendChild(row));

        // Update sorting state and header class
        headers.forEach(header => header.classList.remove('sort-asc', 'sort-desc'));
        headers[columnIdx].classList.add(`sort-${direction}`);
        
        currentSortColumn = columnIdx;
        currentSortDirection = direction;
    }

    // Attach click event listeners to headers
    headers.forEach((header, index) => {
        // Skip the first column (#) and Screenshot column from sorting
        if (index > 0 && header.textContent.trim() !== 'Screenshot') {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                sortTable(index);
            });
        }
    });

    // Initial sort (optional: e.g., sort by Date Submitted in descending order)
    // sortTable(headers.length - 1); // Sort by the last column (Date Submitted)

});
