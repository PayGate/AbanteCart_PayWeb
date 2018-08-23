<?php
/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 */
?>
<?php if ($error){ ?>
	<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php } else{ ?>
	<?php if ($sandbox){ ?>
		<div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> <?php echo $text_sandbox; ?></div>
	<?php } ?>
	<form id="confirm_form" action="<?php echo $action; ?>" method="post">
		<input type="hidden" name="PAY_REQUEST_ID" value="<?php echo $PAY_REQUEST_ID; ?>"/>
		<input type="hidden" name="CHECKSUM" value="<?php echo $CHECKSUM; ?>"/>

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

<?php } ?>