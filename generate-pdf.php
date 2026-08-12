<?php
require_once('includes/config.php');
require_once('vendor/autoload.php'); // Assuming you're using a library like Dompdf

use Dompdf\Dompdf;

// Start output buffering to prevent unwanted output
ob_start();

// Check if invoice ID is provided
if (isset($_GET['id'])) {
    $invoiceId = intval($_GET['id']);

    // Connect to the database
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

    if ($mysqli->connect_error) {
        die('Error : ('.$mysqli->connect_errno .') '. $mysqli->connect_error);
    }

    // Fetch invoice details with customer name
    $query = "SELECT i.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone 
              FROM invoices i 
              JOIN customers c ON i.invoice = c.invoice 
              WHERE i.invoice = $invoiceId";
    $result = $mysqli->query($query);

    if ($result && $result->num_rows > 0) {
        $invoice = $result->fetch_assoc();

        // Generate PDF content
        $html = '<style>
                    body { font-family: Arial, sans-serif; color: #333; font-size: 12px; margin: 0; padding: 0; }
                    .header { display: flex; justify-content: space-between; align-items: center; padding: 20px; background-color: #f8f9fa; border-bottom: 2px solid #007bff; }
                    .header .logo { font-size: 24px; font-weight: bold; color: #007bff; }
                    .header .logo img { max-height: 50px; }
                    .header .invoice-title { font-size: 28px; font-weight: bold; color: #333; }
                    .details-container { display: flex; justify-content: space-between; margin: 20px; }
                    .details-section { width: 48%; }
                    .details-section h3 { margin-bottom: 10px; font-size: 18px; color: #007bff; }
                    .details-section table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    .details-section th, .details-section td { padding: 8px; border: 1px solid #ddd; text-align: left; }
                    .details-section th { background-color: #f2f2f2; }
                    .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    .items-table th, .items-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                    .items-table th { background-color: #007bff; color: #fff; }
                    .total-row { font-weight: bold; background-color: #f9f9f9; }
                    .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #666; }
                </style>';

        $html .= '<div class="header">
                    <div class="logo">
                        <img src="https://via.placeholder.com/150x50?text=LOGO" alt="Company sa Logo">
                    </div>
                  </div>';

        $html .= '<div class="details-container" style="display: flex; justify-content: space-between; gap: 20px;">
                <div class="details-section" style="flex: 1;">
                <h3>Customer Details</h3>
                <table>
                    <tr><th>Name:</th><td>'.$invoice['customer_name'].'</td></tr> 
                    <tr><th>Phone:</th><td>'.$invoice['customer_phone'].'</td></tr>
                </table>
                </div>
                <div class="details-section" style="flex: 1;">
                <h3>Invoice Details</h3>
                <table>
                    <tr><th>Invoice Number:</th><td>#'. $invoice['invoice'] . '</td></tr>
                    <tr><th>Invoice Date:</th><td>'.$invoice['invoice_date'].'</td></tr> 
                    <tr><th>Status:</th><td>'.($invoice['status'] == 'paid' ? 'Paid' : 'Unpaid').'</td></tr>
                </table>
                </div>
              </div>';

        $html .= '<h3 style="margin: 20px;">Invoice Items</h3>';
        $html .= '<table class="items-table">
                    <thead>
                        <tr> 
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Rate</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>';

        // Fetch invoice items
        $itemsQuery = "SELECT * FROM invoice_items WHERE invoice = $invoiceId";
        $itemsResult = $mysqli->query($itemsQuery);

        $grandTotal = 0;
        if ($itemsResult && $itemsResult->num_rows > 0) {
            while ($item = $itemsResult->fetch_assoc()) {
                $itemTotal = $item['quantity'] * $item['price'];
                $grandTotal += $itemTotal;

                $html .= '<tr> 
                            <td>'.$item['product'].'</td>
                            <td>'.$item['qty'].'</td>
                            <td>'.$item['price'].'.00</td>
                            <td>'.$item['subtotal'].'</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="5" style="text-align: center;">No items found</td></tr>';
        }

        $html .= '<tr class="total-row">
                    <td colspan="3" style="text-align: right;">Grand Total:</td>
                    <td>$'.$invoice['subtotal'].'.00</td>
                  </tr>';

        $html .= '</tbody></table>';

        $html .= '<div class="footer">
                    <p>Thank you for your business!</p>
                  </div>';

        // Initialize Dompdf
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Save the PDF to a temporary file
        $output = $dompdf->output();
        $filePath = 'temp/'.$invoice['customer_name'].'-'.date('Y-m-d').'-'.$invoiceId.'.pdf';

        if (!file_exists('temp')) {
            mkdir('temp', 0777, true); // Create the temp directory if it doesn't exist
        }

        if (file_put_contents($filePath, $output)) {
            // Redirect to the preview page
            header('Location: preview-pdf.php?file='.$filePath);
            exit;
        } else {
            die('Failed to save PDF file.');
        }
    } else {
        echo "Invoice not found.";
    }

    $mysqli->close();
} else {
    echo "Invalid request.";
}
?>
