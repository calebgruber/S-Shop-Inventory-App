/**
 * Barcode Utility
 * Handles barcode generation and label creation
 * All barcodes are CODE128-compatible for use with Zebra DS-series scanners
 */

/**
 * Generate a unique barcode for an item
 * Format: ITEM-{category}-{id}-{checksum} (CODE128 compatible)
 */
function generateItemBarcode(itemId, category = 'GEN') {
  // Create a category prefix (max 3 chars)
  const categoryPrefix = (category || 'GEN').substring(0, 3).toUpperCase();
  
  // Pad item ID to 6 digits
  const paddedId = String(itemId).padStart(6, '0');
  
  // Generate a simple checksum
  const checksum = calculateChecksum(paddedId);
  
  // Format: CAT-123456-C (e.g., MIC-000001-7 for Microphones)
  return `${categoryPrefix}-${paddedId}-${checksum}`;
}

/**
 * Calculate a simple checksum digit
 */
function calculateChecksum(str) {
  let sum = 0;
  for (let i = 0; i < str.length; i++) {
    sum += parseInt(str[i] || 0);
  }
  return sum % 10;
}

/**
 * Generate barcode for serialized item
 * Format: SERIAL-{serial_number}
 */
function generateSerialBarcode(serialNumber) {
  return `SN-${serialNumber}`;
}

/**
 * Generate unique show barcode
 * Format: SHOW-{id}-{timestamp}
 */
function generateShowBarcode(showId) {
  const timestamp = Date.now().toString(36).toUpperCase();
  return `SHOW-${String(showId).padStart(4, '0')}-${timestamp}`;
}

/**
 * Validate barcode format
 */
function validateBarcode(barcode) {
  if (!barcode || typeof barcode !== 'string') {
    return false;
  }
  
  // Check length (reasonable range)
  if (barcode.length < 3 || barcode.length > 50) {
    return false;
  }
  
  // Check for valid characters (alphanumeric, dash, underscore)
  return /^[A-Z0-9\-_]+$/i.test(barcode);
}

/**
 * Extract category from barcode
 */
function extractCategoryFromBarcode(barcode) {
  if (!barcode) return null;
  
  const parts = barcode.split('-');
  if (parts.length >= 1) {
    return parts[0];
  }
  
  return null;
}

/**
 * Generate barcodes for multiple items
 */
function generateBulkBarcodes(items) {
  return items.map(item => {
    if (item.barcode) {
      return item; // Already has a barcode
    }
    
    // Generate based on whether it's serialized
    if (item.serial_number) {
      return {
        ...item,
        barcode: generateSerialBarcode(item.serial_number)
      };
    } else {
      return {
        ...item,
        barcode: generateItemBarcode(item.id, item.category)
      };
    }
  });
}

module.exports = {
  generateItemBarcode,
  generateSerialBarcode,
  generateShowBarcode,
  validateBarcode,
  extractCategoryFromBarcode,
  generateBulkBarcodes,
  calculateChecksum
};
