<?php

include_once('includes/config.php');
include_once('functions.php');

if (isset($_POST['phone'])) {
	$phone = $_POST['phone'];
	getInvoicesByPhone($phone);
} else {
	echo '<p class="alert alert-danger">No phone number provided.</p>';
}

?>
