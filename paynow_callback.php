<?php include( dirname(__FILE__).'/modules/paynow/validation.php' ); ?>

<?php //if( isset($_POST['TransactionAccepted']) && $_POST['TransactionAccepted'] == 'true' ): ?>
	<form action='order-confirmation' method='GET' name='paynowform'>
	<?php
	foreach ($_GET as $a => $b) {
		if( $a == 'controller' ) continue;
		echo "<input type='hidden' name='".htmlentities($a)."' value='".htmlentities($b)."'>";
	}
	?>
	</form>
<?php //endif; ?>
<script> document.paynowform.submit(); </script>
