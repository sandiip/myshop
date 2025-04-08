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
                    .header { text-align: center; padding: 20px; background-color: #f8f9fa; border-bottom: 1px solid #ddd; }
                    .header h1 { margin: 0; font-size: 24px; color: #007bff; }
                    .invoice-details, .customer-details { margin: 20px; }
                    .invoice-details table, .customer-details table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    .invoice-details th, .customer-details th { text-align: left; padding: 8px; background-color: #f2f2f2; }
                    .invoice-details td, .customer-details td { padding: 8px; border: 1px solid #ddd; }
                    .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    .items-table th, .items-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                    .items-table th { background-color: #f2f2f2; }
                    .total-row { font-weight: bold; background-color: #f9f9f9; }
                    .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #666; }
                </style>';

        $html .= '<div class="header">
                    <h1>Invoice</h1>
                  </div>';

        $html .= '<div class="customer-details">
                    <h3>Customer Details</h3>
                    <table>
                        <tr><th>Name:</th><td>'.$invoice['customer_name'].'</td></tr>
                        <tr><th>Email:</th><td>'.$invoice['customer_email'].'</td></tr>
                        <tr><th>Phone:</th><td>'.$invoice['customer_phone'].'</td></tr>
                    </table>
                  </div>';

        $html .= '<div class="invoice-details">
                    <h3>Invoice Details</h3>
                    <table>
                        <tr><th>Invoice Number:</th><td>'.$invoice['invoice'].'</td></tr>
                        <tr><th>Issue Date:</th><td>'.$invoice['invoice_date'].'</td></tr>
                        <tr><th>Due Date:</th><td>'.$invoice['invoice_due_date'].'</td></tr>
                        <tr><th>Status:</th><td>'.($invoice['status'] == 'paid' ? 'Paid' : 'Unpaid').'</td></tr>
                    </table>
                  </div>';

        $html .= '<h3 style="margin: 20px;">Invoice Items</h3>';
        $html .= '<table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Price</th>
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
                            <td>'.$item['item_name'].'</td>
                            <td>'.$item['item_description'].'</td>
                            <td>'.$item['quantity'].'</td>
                            <td>$'.$item['price'].'</td>
                            <td>$'.$itemTotal.'</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="5" style="text-align: center;">No items found</td></tr>';
        }

        $html .= '<tr class="total-row">
                    <td colspan="4" style="text-align: right;">Grand Total:</td>
                    <td>$'.$grandTotal.'</td>
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
        $filePath = 'temp/invoice_'.$invoiceId.'.pdf';

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
