/**
 * PDF Generator Utility
 * Generates PDF documents for pull sheets, inventory reports, and return summaries
 */

const { jsPDF } = require('jspdf');
require('jspdf-autotable');
const JsBarcode = require('jsbarcode');
const fs = require('fs');
const path = require('path');
const { app } = require('electron');

/**
 * Generate a barcode image as base64
 * Note: Using a simpler method without canvas module for better compatibility
 */
function generateBarcode(value, options = {}) {
  try {
    // Create a virtual canvas using jsbarcode's built-in canvas support
    const { createCanvas } = require('canvas');
    const canvas = createCanvas(200, 80);
    JsBarcode(canvas, value, {
      format: 'CODE128',
      width: 2,
      height: 60,
      displayValue: true,
      fontSize: 14,
      ...options
    });
    return canvas.toDataURL('image/png');
  } catch (error) {
    console.warn('Canvas module not available, using text representation:', error.message);
    // Return a simple text representation if canvas fails
    return null;
  }
}

/**
 * Generate Pull Sheet PDF
 */
function generatePullSheetPDF(pullSheet) {
  const doc = new jsPDF();
  const pageWidth = doc.internal.pageSize.getWidth();
  
  // Header
  doc.setFontSize(20);
  doc.text('PULL SHEET', pageWidth / 2, 20, { align: 'center' });
  
  // Show barcode - unique identifier for scanning
  const showBarcode = `SHOW-${pullSheet.id}-${Date.now()}`;
  const barcodeImage = generateBarcode(showBarcode);
  
  if (barcodeImage) {
    doc.addImage(barcodeImage, 'PNG', pageWidth / 2 - 40, 25, 80, 25);
  } else {
    // Fallback: display barcode as text
    doc.setFontSize(10);
    doc.text(`Barcode: ${showBarcode}`, pageWidth / 2, 40, { align: 'center' });
  }
  
  // Show Information
  doc.setFontSize(12);
  let yPos = 60;
  doc.text(`Show: ${pullSheet.show_name}`, 20, yPos);
  yPos += 7;
  if (pullSheet.venue) {
    doc.text(`Venue: ${pullSheet.venue}`, 20, yPos);
    yPos += 7;
  }
  doc.text(`Pull Sheet: ${pullSheet.name || 'Default'}`, 20, yPos);
  yPos += 7;
  doc.text(`Date: ${new Date().toLocaleDateString()}`, 20, yPos);
  yPos += 7;
  if (pullSheet.pulled_by) {
    doc.text(`Pulled By: ${pullSheet.pulled_by}`, 20, yPos);
    yPos += 7;
  }
  
  // Items Table
  yPos += 5;
  const tableData = pullSheet.items.map(item => [
    item.name,
    item.barcode || 'N/A',
    item.quantity_requested,
    item.location || '',
    '___' // Checkbox for pulled
  ]);
  
  doc.autoTable({
    startY: yPos,
    head: [['Item', 'Barcode', 'Qty', 'Location', 'Pulled']],
    body: tableData,
    theme: 'grid',
    headStyles: { fillColor: [66, 139, 202] },
    styles: { fontSize: 10 },
    columnStyles: {
      0: { cellWidth: 60 },
      1: { cellWidth: 40 },
      2: { cellWidth: 20, halign: 'center' },
      3: { cellWidth: 40 },
      4: { cellWidth: 20, halign: 'center' }
    }
  });
  
  // Footer
  const pageCount = doc.internal.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.text(
      `Page ${i} of ${pageCount} - Generated: ${new Date().toLocaleString()}`,
      pageWidth / 2,
      doc.internal.pageSize.getHeight() - 10,
      { align: 'center' }
    );
  }
  
  // Save to file
  const userDataPath = app.getPath('userData');
  const pdfsDir = path.join(userDataPath, 'pdfs');
  if (!fs.existsSync(pdfsDir)) {
    fs.mkdirSync(pdfsDir, { recursive: true });
  }
  
  const filename = `pullsheet_${pullSheet.id}_${Date.now()}.pdf`;
  const filePath = path.join(pdfsDir, filename);
  
  // Convert ArrayBuffer to Buffer for fs.writeFileSync
  const pdfBuffer = Buffer.from(doc.output('arraybuffer'));
  fs.writeFileSync(filePath, pdfBuffer);
  
  return filePath;
}

/**
 * Generate Inventory Report PDF
 */
function generateInventoryReportPDF(data) {
  const doc = new jsPDF();
  const pageWidth = doc.internal.pageSize.getWidth();
  
  // Header
  doc.setFontSize(20);
  doc.text('INVENTORY REPORT', pageWidth / 2, 20, { align: 'center' });
  
  doc.setFontSize(10);
  doc.text(`Generated: ${new Date().toLocaleString()}`, pageWidth / 2, 28, { align: 'center' });
  
  // Items Table
  const tableData = data.items.map(item => [
    item.name,
    item.category || 'N/A',
    item.quantity_total || 0,
    item.quantity_available || 0,
    item.status || 'N/A',
    item.location || 'N/A'
  ]);
  
  doc.autoTable({
    startY: 35,
    head: [['Item', 'Category', 'Total', 'Available', 'Status', 'Location']],
    body: tableData,
    theme: 'striped',
    headStyles: { fillColor: [66, 139, 202] },
    styles: { fontSize: 9 },
    columnStyles: {
      0: { cellWidth: 50 },
      1: { cellWidth: 30 },
      2: { cellWidth: 20, halign: 'center' },
      3: { cellWidth: 25, halign: 'center' },
      4: { cellWidth: 25 },
      5: { cellWidth: 30 }
    }
  });
  
  // Summary
  const finalY = doc.lastAutoTable.finalY + 10;
  doc.setFontSize(12);
  doc.text(`Total Items: ${data.items.length}`, 20, finalY);
  
  // Footer
  const pageCount = doc.internal.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.text(
      `Page ${i} of ${pageCount}`,
      pageWidth / 2,
      doc.internal.pageSize.getHeight() - 10,
      { align: 'center' }
    );
  }
  
  // Save to file
  const userDataPath = app.getPath('userData');
  const pdfsDir = path.join(userDataPath, 'pdfs');
  if (!fs.existsSync(pdfsDir)) {
    fs.mkdirSync(pdfsDir, { recursive: true });
  }
  
  const filename = `inventory_${Date.now()}.pdf`;
  const filePath = path.join(pdfsDir, filename);
  
  // Convert ArrayBuffer to Buffer for fs.writeFileSync
  const pdfBuffer = Buffer.from(doc.output('arraybuffer'));
  fs.writeFileSync(filePath, pdfBuffer);
  
  return filePath;
}

/**
 * Generate Return Summary PDF
 */
function generateReturnSummaryPDF(returnData) {
  const doc = new jsPDF();
  const pageWidth = doc.internal.pageSize.getWidth();
  
  // Header
  doc.setFontSize(20);
  doc.text('RETURN SUMMARY', pageWidth / 2, 20, { align: 'center' });
  
  // Return Information
  doc.setFontSize(12);
  let yPos = 35;
  doc.text(`Show: ${returnData.show_name}`, 20, yPos);
  yPos += 7;
  doc.text(`Return Date: ${new Date(returnData.return_date).toLocaleDateString()}`, 20, yPos);
  yPos += 7;
  if (returnData.returned_by) {
    doc.text(`Returned By: ${returnData.returned_by}`, 20, yPos);
    yPos += 7;
  }
  
  // Items Table
  yPos += 5;
  const tableData = returnData.items.map(item => [
    item.name,
    item.quantity,
    item.condition || 'Good',
    item.notes || ''
  ]);
  
  doc.autoTable({
    startY: yPos,
    head: [['Item', 'Quantity', 'Condition', 'Notes']],
    body: tableData,
    theme: 'grid',
    headStyles: { fillColor: [66, 139, 202] },
    styles: { fontSize: 10 }
  });
  
  // Footer
  const pageCount = doc.internal.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.text(
      `Page ${i} of ${pageCount} - Generated: ${new Date().toLocaleString()}`,
      pageWidth / 2,
      doc.internal.pageSize.getHeight() - 10,
      { align: 'center' }
    );
  }
  
  // Save to file
  const userDataPath = app.getPath('userData');
  const pdfsDir = path.join(userDataPath, 'pdfs');
  if (!fs.existsSync(pdfsDir)) {
    fs.mkdirSync(pdfsDir, { recursive: true });
  }
  
  const filename = `return_${returnData.id}_${Date.now()}.pdf`;
  const filePath = path.join(pdfsDir, filename);
  
  // Convert ArrayBuffer to Buffer for fs.writeFileSync
  const pdfBuffer = Buffer.from(doc.output('arraybuffer'));
  fs.writeFileSync(filePath, pdfBuffer);
  
  return filePath;
}

/**
 * Generate Barcode Labels PDF
 * Creates printable barcode labels for inventory items
 * Standard Avery 5160/equivalent label size: 2.625" x 1" (66.675mm x 25.4mm)
 */
function generateBarcodeLabels(items, options = {}) {
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'letter' // 8.5" x 11" = 215.9mm x 279.4mm
  });
  
  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  
  // Label dimensions (Avery 5160 compatible - 3 columns x 10 rows)
  const labelWidth = 66.675;  // 2.625 inches
  const labelHeight = 25.4;   // 1 inch
  const marginLeft = 4.7625;  // Left margin
  const marginTop = 12.7;     // Top margin
  const cols = 3;
  const rows = 10;
  const horizontalGap = 3.175; // Gap between columns
  const verticalGap = 0;       // Gap between rows
  
  let currentPage = 0;
  let currentRow = 0;
  let currentCol = 0;
  
  items.forEach((item, index) => {
    // Calculate position
    const x = marginLeft + currentCol * (labelWidth + horizontalGap);
    const y = marginTop + currentRow * (labelHeight + verticalGap);
    
    // Draw label border (optional - comment out for final print)
    if (options.showBorders) {
      doc.setDrawColor(200, 200, 200);
      doc.rect(x, y, labelWidth, labelHeight);
    }
    
    // Add item name (truncate if too long)
    doc.setFontSize(8);
    doc.setFont('helvetica', 'bold');
    const itemName = item.name.length > 30 ? item.name.substring(0, 27) + '...' : item.name;
    doc.text(itemName, x + labelWidth / 2, y + 4, { align: 'center' });
    
    // Add barcode
    const barcodeImage = generateBarcode(item.barcode, {
      width: 1.5,
      height: 35,
      displayValue: true,
      fontSize: 10,
      margin: 0
    });
    
    if (barcodeImage) {
      try {
        doc.addImage(barcodeImage, 'PNG', x + 3, y + 6, labelWidth - 6, 14);
      } catch (error) {
        // Fallback: just show barcode text
        doc.setFontSize(10);
        doc.setFont('courier', 'normal');
        doc.text(item.barcode, x + labelWidth / 2, y + 13, { align: 'center' });
      }
    } else {
      // Fallback: show barcode as text
      doc.setFontSize(10);
      doc.setFont('courier', 'normal');
      doc.text(item.barcode, x + labelWidth / 2, y + 13, { align: 'center' });
    }
    
    // Add category and location (small text at bottom)
    doc.setFontSize(6);
    doc.setFont('helvetica', 'normal');
    const bottomText = `${item.category || 'N/A'} | ${item.location || 'N/A'}`;
    doc.text(bottomText, x + labelWidth / 2, y + labelHeight - 2, { align: 'center' });
    
    // Move to next label position
    currentCol++;
    if (currentCol >= cols) {
      currentCol = 0;
      currentRow++;
      
      if (currentRow >= rows) {
        currentRow = 0;
        if (index < items.length - 1) {
          doc.addPage();
        }
      }
    }
  });
  
  // Save to file
  const userDataPath = app.getPath('userData');
  const pdfsDir = path.join(userDataPath, 'pdfs');
  if (!fs.existsSync(pdfsDir)) {
    fs.mkdirSync(pdfsDir, { recursive: true });
  }
  
  const filename = `barcode_labels_${Date.now()}.pdf`;
  const filePath = path.join(pdfsDir, filename);
  
  const pdfBuffer = Buffer.from(doc.output('arraybuffer'));
  fs.writeFileSync(filePath, pdfBuffer);
  
  return filePath;
}

/**
 * Main PDF generation function
 */
async function generatePDF(type, data) {
  switch (type) {
    case 'pullsheet':
      return generatePullSheetPDF(data);
    case 'inventory':
      return generateInventoryReportPDF(data);
    case 'return':
      return generateReturnSummaryPDF(data);
    case 'labels':
      return generateBarcodeLabels(data.items, data.options || {});
    default:
      throw new Error(`Unknown PDF type: ${type}`);
  }
}

module.exports = {
  generatePDF,
  generateBarcode,
  generateBarcodeLabels
};
