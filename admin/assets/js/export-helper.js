/**
 * Export Helper JavaScript
 * Provides client-side export functionality and utilities
 */

(function() {
  'use strict';

  /**
   * Export Table Data to CSV
   * @param {string} tableId - ID of the table element
   * @param {string} filename - Output filename
   */
  function exportTableToCSV(tableId, filename = 'table_export') {
    const table = document.getElementById(tableId);
    if (!table) {
      console.error('Table not found:', tableId);
      return;
    }

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
      const row = [];
      const cols = rows[i].querySelectorAll('td, th');

      for (let j = 0; j < cols.length; j++) {
        // Get text content, handling nested elements
        let data = cols[j].innerText || cols[j].textContent || '';
        // Clean up data (remove extra whitespace, handle quotes)
        data = data.replace(/"/g, '""');
        // Wrap in quotes if contains comma, newline, or quote
        if (data.includes(',') || data.includes('\n') || data.includes('"')) {
          data = '"' + data + '"';
        }
        row.push(data);
      }
      csv.push(row.join(','));
    }

    // Create CSV content with BOM for UTF-8
    const csvContent = '\uFEFF' + csv.join('\n');
    
    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename + '_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  /**
   * Export Data to JSON
   * @param {Array|Object} data - Data to export
   * @param {string} filename - Output filename
   */
  function exportToJSON(data, filename = 'export') {
    const jsonContent = JSON.stringify(data, null, 2);
    const blob = new Blob([jsonContent], { type: 'application/json;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename + '_' + new Date().toISOString().split('T')[0] + '.json');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  /**
   * Print current page/table
   * @param {string} tableId - Optional: ID of table to print (prints whole page if not provided)
   */
  function printTable(tableId = null) {
    if (tableId) {
      const table = document.getElementById(tableId);
      if (!table) {
        console.error('Table not found:', tableId);
        return;
      }

      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>Print</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              table { width: 100%; border-collapse: collapse; }
              th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
              th { background-color: #f2f2f2; }
              tr:nth-child(even) { background-color: #f9f9f9; }
            </style>
          </head>
          <body>
            ${table.outerHTML}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    } else {
      window.print();
    }
  }

  /**
   * Show export loading state
   */
  function showExportLoading() {
    if (typeof DesignSystem !== 'undefined' && DesignSystem.Loading) {
      DesignSystem.Loading.show('Preparing export...');
    }
  }

  /**
   * Hide export loading state
   */
  function hideExportLoading() {
    if (typeof DesignSystem !== 'undefined' && DesignSystem.Loading) {
      DesignSystem.Loading.hide();
    }
  }

  // Export to global scope
  window.ExportHelper = {
    tableToCSV: exportTableToCSV,
    toJSON: exportToJSON,
    print: printTable,
    showLoading: showExportLoading,
    hideLoading: hideExportLoading
  };

  // Add export button click handlers for data-export-table attributes
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-export-table]').forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        const tableId = this.getAttribute('data-export-table');
        const filename = this.getAttribute('data-export-filename') || 'export';
        showExportLoading();
        
        setTimeout(() => {
          exportTableToCSV(tableId, filename);
          hideExportLoading();
          
          if (typeof DesignSystem !== 'undefined' && DesignSystem.Toast) {
            DesignSystem.Toast.success('Export completed successfully!');
          }
        }, 500);
      });
    });
  });

})();

