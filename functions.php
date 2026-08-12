<?php


include_once("includes/config.php");

// get invoice list
function getInvoices() {

	// Connect to the database
	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

	// output any connection error
	if ($mysqli->connect_error) {
		die('Error : ('.$mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	// the query
    $query = "SELECT *
		FROM invoices i
		JOIN customers c
		ON c.invoice = i.invoice
		WHERE i.invoice = c.invoice
		ORDER BY i.invoice";

	// mysqli select query
	$results = $mysqli->query($query);

	// mysqli select query
	if($results) {

		print '<table class="table table-striped table-hover table-bordered" id="data-table" cellspacing="0"><thead><tr>

				<th>Invoice</th>
				<th>Customer</th>
				<th>Issue Date</th>
				<th>Due Date</th>
				<th>Type</th>
				<th>Status</th>
				<th>Actions</th>

			  </tr></thead><tbody>';

		while($row = $results->fetch_assoc()) {

			print '
				<tr>
					<td>'.$row["invoice"].'</td>
					<td>'.$row["name"].'</td>
				    <td>'.$row["invoice_date"].'</td>
				    <td>'.$row["invoice_due_date"].'</td>
				    <td>'.$row["invoice_type"].'</td>
				';

				if($row['status'] == "open"){
					print '<td><span class="label label-primary">'.$row['status'].'</span></td>';
				} elseif ($row['status'] == "paid"){
					print '<td><span class="label label-success">'.$row['status'].'</span></td>';
				}

			print '
				    <td>
				        <a href="invoice-edit.php?id='.$row["invoice"].'" class="btn btn-primary btn-xs">
				            <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
				        </a>
				        <a href="#" data-invoice-id="'.$row['invoice'].'" data-email="'.$row['email'].'" data-invoice-type="'.$row['invoice_type'].'" data-custom-email="'.$row['custom_email'].'" class="btn btn-success btn-xs email-invoice">
				            <span class="glyphicon glyphicon-envelope" aria-hidden="true"></span>
				        </a>
				        <a href="generate-pdf.php?id='.$row["invoice"].'" class="btn btn-info btn-xs">
				            <span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
				        </a>
				        <a data-invoice-id="'.$row['invoice'].'" class="btn btn-danger btn-xs delete-invoice">
				            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
				        </a>
				    </td>
			    </tr>
			';

		}

		print '</tr></tbody></table>';

	} else {

		echo "<p>There are no invoices to display.</p>";

	}

	// Frees the memory associated with a result
	$results->free();

	// close connection
	$mysqli->close();

}

// Get all invoices - flat list view
function getInvoicesTable() {

	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
	if ($mysqli->connect_error) {
		die('Error : ('.$mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	$query = "SELECT i.*, c.name, c.phone, c.email
	          FROM invoices i
	          JOIN customers c ON c.invoice = i.invoice
	          ORDER BY STR_TO_DATE(i.invoice_date, '%d/%m/%Y') DESC, i.invoice DESC";

	$results = $mysqli->query($query);
	if (!$results) {
		echo '<p>Unable to load invoices.</p>';
		$mysqli->close();
		return;
	}

	if ($results->num_rows == 0) {
		echo '<p>No invoices found.</p>';
		$results->free();
		$mysqli->close();
		return;
	}

	echo '<table class="table table-striped table-hover table-bordered" id="invoices-table">';
	echo '<thead><tr>';
	echo '<th>Invoice</th>';
	echo '<th>Date</th>';
	echo '<th>Due Date</th>';
	echo '<th>Type</th>';
	echo '<th>Total</th>';
	echo '<th>Status</th>';
	echo '<th>Actions</th>';
	echo '</tr></thead><tbody>';

	while ($row = $results->fetch_assoc()) {
		$invId = htmlspecialchars($row['invoice']);
		$invDate = htmlspecialchars($row['invoice_date']);
		$dueDate = htmlspecialchars($row['invoice_due_date']);
		$invType = htmlspecialchars($row['invoice_type']);
		$total = number_format(floatval($row['total']), 2);
		$status = htmlspecialchars($row['status']);
		$statusClass = ($status === 'paid') ? 'label-success' : 'label-warning';
		$customerName = htmlspecialchars($row['name']);
		$phone = htmlspecialchars($row['phone']);
		$email = htmlspecialchars($row['email']);

		echo '<tr>';
		echo '<td><strong>' . $invId . '</strong></td>';
		echo '<td>' . $invDate . '</td>';
		echo '<td>' . $dueDate . '</td>';
		echo '<td>' . ucfirst($invType) . '</td>';
		echo '<td><strong>' . CURRENCY . $total . '</strong></td>';
		echo '<td><span class="label ' . $statusClass . '">' . ucfirst($status) . '</span></td>';
		echo '<td>';
		echo '<a href="invoice-edit.php?id=' . $invId . '" class="btn btn-info btn-xs" title="View Invoice"><i class="fa fa-eye"></i></a> ';
		echo '<a href="generate-pdf.php?id=' . $invId . '" class="btn btn-primary btn-xs" title="Download PDF"><i class="fa fa-download"></i></a> ';
		echo '<a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) . '?text=Invoice%20' . $invId . '%20for%20%24' . str_replace('.', '', $total) . '" target="_blank" class="btn btn-success btn-xs" title="Share on WhatsApp"><i class="fa fa-whatsapp"></i></a> ';
		echo '<a href="#" data-invoice-id="' . $invId . '" class="btn btn-danger btn-xs delete-invoice" title="Delete Invoice"><i class="fa fa-trash"></i></a>';
		echo '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	$results->free();
	$mysqli->close();
}

// Get invoice summary by customer phone (grouped view)
function getInvoiceSummaryTable() {

	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
	if ($mysqli->connect_error) {
		die('Error : ('.$mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	// First, get the summary: customer name, phone, total bills count
	$query = "SELECT c.name, c.phone, COUNT(i.invoice) as total_bills
	          FROM invoices i
	          JOIN customers c ON c.invoice = i.invoice
	          GROUP BY c.phone, c.name
	          ORDER BY c.name, c.phone";

	$results = $mysqli->query($query);
	if (!$results) {
		echo '<p>Unable to load invoices.</p>';
		$mysqli->close();
		return;
	}

	if ($results->num_rows == 0) {
		echo '<p>No invoices found.</p>';
		$results->free();
		$mysqli->close();
		return;
	}

	echo '<table class="table table-striped table-hover table-bordered" id="summary-table">';
	echo '<thead><tr>';
	echo '<th>Customer Name</th>';
	echo '<th>Mobile Number</th>';
	echo '<th>Total Bills</th>';
	echo '<th>Last Bill Amount</th>';
	echo '</tr></thead><tbody>';

	while ($row = $results->fetch_assoc()) {
		$phone = htmlspecialchars($row['phone'] ?: 'N/A');
		$name = htmlspecialchars($row['name']);
		$total = intval($row['total_bills']);

		// Get last bill amount for this phone
		$lastBillQuery = "SELECT total FROM invoices i
		                  JOIN customers c ON c.invoice = i.invoice
		                  WHERE c.phone = ?
		                  ORDER BY STR_TO_DATE(i.invoice_date, '%d/%m/%Y') DESC, i.invoice DESC
		                  LIMIT 1";

		$stmt = $mysqli->prepare($lastBillQuery);
		$stmt->bind_param('s', $row['phone']);
		$stmt->execute();
		$billResult = $stmt->get_result();
		$billRow = $billResult->fetch_assoc();
		$lastAmount = $billRow ? floatval($billRow['total']) : 0;
		$lastAmountFormatted = number_format($lastAmount, 2);
		$stmt->close();

		echo '<tr class="invoice-summary-row" data-phone="' . $phone . '" data-customer-name="' . $name . '">';
		echo '<td>' . $name . '</td>';
		echo '<td>' . $phone . '</td>';
		echo '<td><a href="#" class="expand-bills" data-phone="' . $phone . '" data-toggle="modal" data-target="#billsModal">' . $total . '</a></td>';
		echo '<td>' . CURRENCY . $lastAmountFormatted . '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	$results->free();
	$mysqli->close();
}

// Get all invoices for a specific customer phone (for modal)
function getInvoicesByPhone($phone) {
	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
	if ($mysqli->connect_error) {
		die('Error : ('.$mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	$query = "SELECT i.*, c.name, c.phone
	          FROM invoices i
	          JOIN customers c ON c.invoice = i.invoice
	          WHERE c.phone = ?
	          ORDER BY STR_TO_DATE(i.invoice_date, '%d/%m/%Y') DESC, i.invoice DESC";

	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('s', $phone);
	$stmt->execute();
	$results = $stmt->get_result();

	if ($results->num_rows == 0) {
		echo '<p>No invoices found for this customer.</p>';
		$results->free();
		$stmt->close();
		$mysqli->close();
		return;
	}

	echo '<table class="table table-striped table-hover table-bordered" id="modal-invoices-table">';
	echo '<thead><tr>';
	echo '<th>Invoice</th>';
	echo '<th>Date</th>';
	echo '<th>Due Date</th>';
	echo '<th>Type</th>';
	echo '<th>Total</th>';
	echo '<th>Status</th>';
	echo '<th>Actions</th>';
	echo '</tr></thead><tbody>';

	while ($row = $results->fetch_assoc()) {
		$invId = htmlspecialchars($row['invoice']);
		$invDate = htmlspecialchars($row['invoice_date']);
		$dueDate = htmlspecialchars($row['invoice_due_date']);
		$invType = htmlspecialchars($row['invoice_type']);
		$total = number_format(floatval($row['total']), 2);
		$status = htmlspecialchars($row['status']);
		$statusClass = ($status === 'paid') ? 'label-success' : 'label-warning';
		$customerPhone = htmlspecialchars($row['phone']);

		echo '<tr>';
		echo '<td><strong>' . $invId . '</strong></td>';
		echo '<td>' . $invDate . '</td>';
		echo '<td>' . $dueDate . '</td>';
		echo '<td>' . ucfirst($invType) . '</td>';
		echo '<td><strong>' . CURRENCY . $total . '</strong></td>';
		echo '<td><span class="label ' . $statusClass . '">' . ucfirst($status) . '</span></td>';
		echo '<td>';
		echo '<a href="invoice-edit.php?id=' . $invId . '" class="btn btn-info btn-xs" title="View Invoice"><i class="fa fa-eye"></i></a> ';
		echo '<a href="generate-pdf.php?id=' . $invId . '" class="btn btn-primary btn-xs" title="Download PDF"><i class="fa fa-download"></i></a> ';
		echo '<a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $customerPhone) . '?text=Invoice%20' . $invId . '%20for%20%24' . str_replace('.', '', $total) . '" target="_blank" class="btn btn-success btn-xs" title="Share on WhatsApp"><i class="fa fa-whatsapp"></i></a> ';
		echo '<a href="#" data-invoice-id="' . $invId . '" class="btn btn-danger btn-xs delete-invoice" title="Delete Invoice"><i class="fa fa-trash"></i></a>';
		echo '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	$results->free();
	$stmt->close();
	$mysqli->close();
}

// Initial invoice number
function getInvoiceId() {

	// Connect to the database
	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

	// output any connection error
	if ($mysqli->connect_error) {
	    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	$query = "SELECT invoice FROM invoices ORDER BY invoice DESC LIMIT 1";

	if ($result = $mysqli->query($query)) {

		$row_cnt = $result->num_rows;

	    $row = mysqli_fetch_assoc($result);

	    //var_dump($row);

	    if($row_cnt == 0){
			echo INVOICE_INITIAL_VALUE;
		} else {
			echo $row['invoice'] + 1;
		}

	    // Frees the memory associated with a result
		$result->free();

		// close connection
		$mysqli->close();
	}

}

// populate product dropdown for invoice creation
function popProductsList() {

	// Connect to the database
	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

	// output any connection error
	if ($mysqli->connect_error) {
	    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	// the query
	$query = "SELECT * FROM products ORDER BY product_name ASC";

	// mysqli select query
	$results = $mysqli->query($query);

	if($results) {
		echo '<select class="form-control item-select">';
		while($row = $results->fetch_assoc()) {

		    print '<option value="'.$row['product_price'].'">'.$row["product_name"].' - '.$row["product_desc"].'</option>';
		}
		echo '</select>';

	} else {

		echo "<p>There are no products, please add a product.</p>";

	}

	// Frees the memory associated with a result
	$results->free();

	// close connection
	$mysqli->close();

}

function getProductsJson() {

	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

	if ($mysqli->connect_error) {
		die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	$query = "SELECT * FROM products ORDER BY product_name ASC";
	$results = $mysqli->query($query);
	$products = array();

	if ($results) {
		while ($row = $results->fetch_assoc()) {
			$key = strtolower(trim($row['product_name']));
			$products[$key] = array(
				'price' => $row['product_price'],
				'desc' => $row['product_desc']
			);
		}
	}

	$results->free();
	$mysqli->close();

	return json_encode($products);

}

// populate product dropdown for invoice creation
function popCustomersList() {

	// Connect to the database
	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

	// output any connection error
	if ($mysqli->connect_error) {
	    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	// the query
	$query = "SELECT * FROM store_customers ORDER BY name ASC";

	// mysqli select query
	$results = $mysqli->query($query);

	if($results) {

		print '<table class="table table-striped table-hover table-bordered" id="data-table"><thead><tr>

				<th>Name</th>
				<th>Email</th>
				<th>Phone</th>
				<th>Action</th>

			  </tr></thead><tbody>';

		while($row = $results->fetch_assoc()) {

		    print '
			    <tr>
					<td>'.$row["name"].'</td>
				    <td>'.$row["email"].'</td>
				    <td>'.$row["phone"].'</td>
				    <td><a href="#" class="btn btn-primary btn-xs customer-select" data-customer-name="'.$row['name'].'" data-customer-email="'.$row['email'].'" data-customer-phone="'.$row['phone'].'" data-customer-address-1="'.$row['address_1'].'" data-customer-address_2="'.$row['address_2'].'" data-customer-town="'.$row['town'].'" data-customer-county="'.$row['county'].'" data-customer-postcode="'.$row['postcode'].'" data-customer-name-ship="'.$row['name_ship'].'" data-customer-address-1-ship="'.$row['address_1_ship'].'" data-customer-address-2-ship="'.$row['address_2_ship'].'" data-customer-town-ship="'.$row['town_ship'].'" data-customer-county-ship="'.$row['county_ship'].'" data-customer-postcode-ship="'.$row['postcode_ship'].'">Select</a></td>
			    </tr>
		    ';
		}

		print '</tr></tbody></table>';

	} else {

		echo "<p>There are no customers to display.</p>";

	}

	// Frees the memory associated with a result
	$results->free();

	// close connection
	$mysqli->close();

}

// get products list
function getProducts() {

	// Connect to the database
	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

	// output any connection error
	if ($mysqli->connect_error) {
	    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	// the query
	$query = "SELECT * FROM products ORDER BY product_name ASC";

	// mysqli select query
	$results = $mysqli->query($query);

	if($results) {

		print '<table class="table table-striped table-hover table-bordered" id="data-table"><thead><tr>

				<th>Product</th>
				<th>Description</th>
				<th>Price</th>
				<th>Action</th>

			  </tr></thead><tbody>';

		while($row = $results->fetch_assoc()) {

		    print '
			    <tr>
					<td>'.$row["product_name"].'</td>
				    <td>'.$row["product_desc"].'</td>
				    <td>$'.$row["product_price"].'</td>
				    <td><a href="product-edit.php?id='.$row["product_id"].'" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a> <a data-product-id="'.$row['product_id'].'" class="btn btn-danger btn-xs delete-product"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a></td>
			    </tr>
		    ';
		}

		print '</tr></tbody></table>';

	} else {

		echo "<p>There are no products to display.</p>";

	}

	// Frees the memory associated with a result
	$results->free();

	// close connection
	$mysqli->close();
}

// get user list
function getUsers() {

	// Connect to the database
	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

	// output any connection error
	if ($mysqli->connect_error) {
	    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	// the query
	$query = "SELECT * FROM users ORDER BY username ASC";

	// mysqli select query
	$results = $mysqli->query($query);

	if($results) {

		print '<table class="table table-striped table-hover table-bordered" id="data-table"><thead><tr>

				<th>Name</th>
				<th>Username</th>
				<th>Email</th>
				<th>Phone</th>
				<th>Action</th>

			  </tr></thead><tbody>';

		while($row = $results->fetch_assoc()) {

		    print '
			    <tr>
			    	<td>'.$row['name'].'</td>
					<td>'.$row["username"].'</td>
				    <td>'.$row["email"].'</td>
				    <td>'.$row["phone"].'</td>
				    <td><a href="user-edit.php?id='.$row["id"].'" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a> <a data-user-id="'.$row['id'].'" class="btn btn-danger btn-xs delete-user"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a></td>
			    </tr>
		    ';
		}

		print '</tr></tbody></table>';

	} else {

		echo "<p>There are no users to display.</p>";

	}

	// Frees the memory associated with a result
	$results->free();

	// close connection
	$mysqli->close();
}

// get user list
function getCustomers() {

	// Connect to the database
	$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

	// output any connection error
	if ($mysqli->connect_error) {
	    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
	}

	// the query
	$query = "SELECT * FROM store_customers ORDER BY name ASC";

	// mysqli select query
	$results = $mysqli->query($query);

	if($results) {

		print '<table class="table table-striped table-hover table-bordered" id="data-table"><thead><tr>

				<th>Name</th>
				<th>Email</th>
				<th>Phone</th>
				<th>Action</th>

			  </tr></thead><tbody>';

		while($row = $results->fetch_assoc()) {

		    print '
			    <tr>
					<td>'.$row["name"].'</td>
				    <td>'.$row["email"].'</td>
				    <td>'.$row["phone"].'</td>
				    <td><a href="customer-edit.php?id='.$row["id"].'" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a> <a data-customer-id="'.$row['id'].'" class="btn btn-danger btn-xs delete-customer"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a></td>
			    </tr>
		    ';
		}

		print '</tr></tbody></table>';

	} else {

		echo "<p>There are no customers to display.</p>";

	}

	// Frees the memory associated with a result
	$results->free();

	// close connection
	$mysqli->close();
}

?>
