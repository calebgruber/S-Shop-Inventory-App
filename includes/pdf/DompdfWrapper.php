<?php
/**
 * DompdfWrapper - Simplified interface for PDF generation using Dompdf
 * 
 * This wrapper provides an easy way to generate PDFs from HTML/CSS
 * Install Dompdf first (see INSTALL_DOMPDF.md)
 */

class DompdfWrapper {
    private $dompdf;
    private $isAvailable = false;
    
    public function __construct() {
        // Try to load Dompdf
        $autoloadPath = __DIR__ . '/../dompdf/autoload.inc.php';
        
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            
            if (class_exists('Dompdf\Dompdf')) {
                $this->dompdf = new \Dompdf\Dompdf();
                $this->isAvailable = true;
                
                // Set options
                $this->dompdf->set_option('isHtml5ParserEnabled', true);
                $this->dompdf->set_option('isRemoteEnabled', true); // For loading external images
            }
        }
    }
    
    /**
     * Check if Dompdf is available
     */
    public function isAvailable() {
        return $this->isAvailable;
    }
    
    /**
     * Generate PDF from HTML string
     * 
     * @param string $html HTML content
     * @param string $filename Output filename
     * @param string $mode 'download', 'inline', or 'string'
     * @return string|null Returns PDF string if mode is 'string', null otherwise
     */
    public function generateFromHtml($html, $filename = 'document.pdf', $mode = 'download') {
        if (!$this->isAvailable) {
            throw new Exception('Dompdf is not installed. See INSTALL_DOMPDF.md');
        }
        
        // Load HTML
        $this->dompdf->loadHtml($html);
        
        // Set paper size and orientation
        $this->dompdf->setPaper('letter', 'portrait');
        
        // Render PDF
        $this->dompdf->render();
        
        // Output
        if ($mode === 'string') {
            return $this->dompdf->output();
        } else {
            $this->dompdf->stream($filename, ['Attachment' => ($mode === 'download')]);
            return null;
        }
    }
    
    /**
     * Generate PDF from HTML file
     */
    public function generateFromFile($filepath, $filename = 'document.pdf', $mode = 'download') {
        if (!file_exists($filepath)) {
            throw new Exception("HTML file not found: $filepath");
        }
        
        $html = file_get_contents($filepath);
        return $this->generateFromHtml($html, $filename, $mode);
    }
    
    /**
     * Create a pullsheet PDF with proper styling
     */
    public function generatePullsheetPDF($pullsheet, $items, $barcodeImageData) {
        $html = $this->buildPullsheetHtml($pullsheet, $items, $barcodeImageData);
        return $this->generateFromHtml($html, 'pullsheet-' . $pullsheet['barcode'] . '.pdf', 'inline');
    }
    
    /**
     * Create a change order PDF with proper styling
     */
    public function generateChangeOrderPDF($changeOrder, $items, $barcodeImageData) {
        $html = $this->buildChangeOrderHtml($changeOrder, $items, $barcodeImageData);
        return $this->generateFromHtml($html, 'change-order-' . $changeOrder['barcode'] . '.pdf', 'inline');
    }
    
    /**
     * Build HTML for pullsheet with CSS styling
     */
    private function buildPullsheetHtml($pullsheet, $items, $barcodeImageData) {
        $logoPath = __DIR__ . '/../../uploads/logos/' . getSetting('logo_filename', '');
        $logoImg = '';
        
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoMime = mime_content_type($logoPath);
            $logoImg = '<img src="data:' . $logoMime . ';base64,' . $logoData . '" style="max-width: 150px; max-height: 80px;">';
        }
        
        // Convert barcode image to base64 for embedding
        $barcodeBase64 = base64_encode($barcodeImageData);
        $barcodeImg = '<img src="data:image/png;base64,' . $barcodeBase64 . '" style="max-width: 200px;">';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 0.5in; }
                body { font-family: Arial, sans-serif; font-size: 11pt; }
                .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .header-left { flex: 1; }
                .header-right { text-align: right; }
                h1 { margin: 0; font-size: 24pt; }
                .info-section { margin-bottom: 20px; }
                .info-row { margin-bottom: 5px; }
                .info-label { font-weight: bold; display: inline-block; width: 120px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background-color: #f0f0f0; padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: bold; }
                td { padding: 8px; border: 1px solid #ddd; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .footer { margin-top: 40px; font-size: 9pt; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="header-left">
                    ' . $logoImg . '
                </div>
                <div class="header-right">
                    ' . $barcodeImg . '
                    <div style="font-size: 9pt; margin-top: 5px;">' . htmlspecialchars($pullsheet['barcode']) . '</div>
                </div>
            </div>
            
            <h1>PULLSHEET</h1>
            
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Show:</span>
                    <span>' . htmlspecialchars($pullsheet['show_name']) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created By:</span>
                    <span>' . htmlspecialchars($pullsheet['created_by'] ?? 'N/A') . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span>' . date('m/d/Y g:i A', strtotime($pullsheet['created_at'])) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span>' . strtoupper($pullsheet['status']) . '</span>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">Item</th>
                        <th style="width: 25%;">Barcode</th>
                        <th style="width: 15%; text-align: center;">Qty Needed</th>
                        <th style="width: 20%; text-align: center;">Qty Picked</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($items as $item) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item['item_name']) . '</td>
                        <td><code>' . htmlspecialchars($item['item_barcode']) . '</code></td>
                        <td style="text-align: center;"><strong>' . $item['quantity_needed'] . '</strong></td>
                        <td style="text-align: center;">' . ($item['quantity_picked'] ?? 0) . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>This pullsheet was generated on ' . date('m/d/Y g:i A') . '</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Build HTML for change order with CSS styling
     */
    private function buildChangeOrderHtml($changeOrder, $items, $barcodeImageData) {
        $logoPath = __DIR__ . '/../../uploads/logos/' . getSetting('logo_filename', '');
        $logoImg = '';
        
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoMime = mime_content_type($logoPath);
            $logoImg = '<img src="data:' . $logoMime . ';base64,' . $logoData . '" style="max-width: 150px; max-height: 80px;">';
        }
        
        // Convert barcode image to base64
        $barcodeBase64 = base64_encode($barcodeImageData);
        $barcodeImg = '<img src="data:image/png;base64,' . $barcodeBase64 . '" style="max-width: 200px;">';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 0.5in; }
                body { font-family: Arial, sans-serif; font-size: 11pt; }
                .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .header-left { flex: 1; }
                .header-right { text-align: right; }
                h1 { margin: 0; font-size: 24pt; color: #d63939; }
                .info-section { margin-bottom: 20px; }
                .info-row { margin-bottom: 5px; }
                .info-label { font-weight: bold; display: inline-block; width: 120px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background-color: #f0f0f0; padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: bold; }
                td { padding: 8px; border: 1px solid #ddd; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .action-add { background-color: #e8f5e9 !important; }
                .action-remove { background-color: #ffebee !important; }
                .footer { margin-top: 40px; font-size: 9pt; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="header-left">
                    ' . $logoImg . '
                </div>
                <div class="header-right">
                    ' . $barcodeImg . '
                    <div style="font-size: 9pt; margin-top: 5px;">' . htmlspecialchars($changeOrder['barcode']) . '</div>
                </div>
            </div>
            
            <h1>CHANGE ORDER</h1>
            
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Show:</span>
                    <span>' . htmlspecialchars($changeOrder['show_name']) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created By:</span>
                    <span>' . htmlspecialchars($changeOrder['created_by'] ?? 'N/A') . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span>' . date('m/d/Y g:i A', strtotime($changeOrder['created_at'])) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span>' . strtoupper($changeOrder['status']) . '</span>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Action</th>
                        <th style="width: 40%;">Item</th>
                        <th style="width: 25%;">Barcode</th>
                        <th style="width: 20%; text-align: center;">Quantity</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($items as $item) {
            $actionClass = $item['action'] === 'add' ? 'action-add' : 'action-remove';
            $actionLabel = $item['action'] === 'add' ? 'ADD' : 'REMOVE';
            
            $html .= '
                    <tr class="' . $actionClass . '">
                        <td><strong>' . $actionLabel . '</strong></td>
                        <td>' . htmlspecialchars($item['item_name']) . '</td>
                        <td><code>' . htmlspecialchars($item['item_barcode']) . '</code></td>
                        <td style="text-align: center;"><strong>' . $item['quantity'] . '</strong></td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>This change order was generated on ' . date('m/d/Y g:i A') . '</p>
                <p><strong>Green rows:</strong> Items to ADD to show | <strong>Red rows:</strong> Items to REMOVE from show</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
