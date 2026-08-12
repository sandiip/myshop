<?php


include('header.php');
include('functions.php');

?>

<div class="row">

	<div class="col-xs-12">

		<div id="response" class="alert alert-success" style="display:none;">
			<a href="#" class="close" data-dismiss="alert">&times;</a>
			<div class="message"></div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading">
				<h4>Manage Invoices</h4>
			</div>
			<div class="panel-body form-group form-group-sm">
				<?php getInvoiceSummaryTable(); ?>
			</div>
		</div>
	</div>
<div>

<div id="delete_invoice" class="modal fade">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Delete Invoice</h4>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this invoice? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" data-dismiss="modal" class="btn btn-danger" id="delete">Delete</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div id="billsModal" class="modal fade" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Invoices for <span id="modal-customer-name"></span></h4>
      </div>
      <div class="modal-body" id="bills-list-container">
        <p>Loading...</p>
      </div>
      <div class="modal-footer">
        <button type="button" data-dismiss="modal" class="btn btn-default">Close</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
$(document).on('click', '.expand-bills', function(e) {
	e.preventDefault();
	var phone = $(this).data('phone');
	var customerName = $(this).closest('tr').find('td:first').text();

	$('#modal-customer-name').text(customerName);
	$('#bills-list-container').html('<p>Loading invoices...</p>');

	$.ajax({
		url: 'get-bills.php',
		type: 'POST',
		data: { phone: phone },
		success: function(html) {
			$('#bills-list-container').html(html);
		},
		error: function() {
			$('#bills-list-container').html('<p class="alert alert-danger">Error loading bills. Please try again.</p>');
		}
	});
});
</script>

<?php
	include('footer.php');
?>