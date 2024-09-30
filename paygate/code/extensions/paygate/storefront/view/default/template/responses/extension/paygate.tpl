<?php
/*
 * Copyright (c) 2024 Payfast (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */
?>
<?php if ($error) { ?>
<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php } else { ?>
<?php if ($sandbox) { ?>
<div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> <?php echo $text_sandbox; ?></div>
<?php } ?>
<form id="confirm_form" action="<?php echo $action; ?>" method="post">

	<div class="form-group">
		<?php if (count($paymentMethods) > 0) { ?>
		<label>Select Payment Method:</label>
		<table class="table table-borderless">
			<?php foreach ($paymentMethods as $method) { ?>
			<tr id="payment_method_<?php echo strtolower(str_replace(' ', '_', $method['name'])); ?>_wrapper">
				<td class="form-check">
					<input class="form-check-input" type="radio" name="payment_method" id="payment_method_<?php echo strtolower(str_replace(' ', '_', $method['name'])); ?>" value="<?php echo $method['name']; ?>">
				</td>
				<td>
					<label class="form-check-label" for="payment_method_<?php echo strtolower(str_replace(' ', '_', $method['name'])); ?>">
						<?php echo $method['name']; ?>
					</label>
				</td>
				<td >
					<img src="<?php echo $method['image']; ?>" alt="<?php echo $method['name']; ?>" style="height: 24px; align-items: end" class="float-end">
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php } ?>
	</div>

	<div class="form-group action-buttons text-center">
		<a id="<?php echo $back->name ?>" href="<?php echo $back->href; ?>" class="btn btn-default mr10"
		   title="<?php echo $back->text ?>">
			<i class="fa fa-arrow-left"></i>
			<?php echo $back->text ?>
		</a>
		<button id="<?php echo $button_confirm->name ?>" class="btn btn-orange lock-on-click"
				title="<?php echo $button_confirm->name ?>" type="submit">
			<i class="fa fa-check"></i>
			<?php echo $button_confirm->name; ?>
		</button>
	</div>
</form>
<script type="text/javascript">
	$(document).ready(function () {

		//load first tab
		if (window.ApplePaySession === undefined) {
			// Apple Pay is not supported, remove Apple Pay from payment methods
			var applePayWrapper = document.getElementById('payment_method_applepay_wrapper');
			if (applePayWrapper) {
				console.log('Removing Apple Pay wrapper');
				applePayWrapper.remove();
			}
		}
	});
</script>
<?php } ?>
