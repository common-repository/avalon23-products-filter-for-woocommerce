<?php
if (!defined('ABSPATH')) {
	die('No direct access allowed');
}
?>

<template id="avalon23-popup-template">

	<div class="avalon23-modal">
		<div class="avalon23-modal-inner">
			<div class="avalon23-modal-inner-header">
				<h3 class="avalon23-modal-title">&nbsp;</h3>
				<div class="avalon23-modal-title-info">&nbsp;</div>
				<a href="javascript: void(0);" class="avalon23-modal-close"></a>
			</div>
			<div class="avalon23-modal-inner-content">
				<div class="avalon23-form-element-container"><div class="avalon23-place-loader"><?php echo esc_html(Avalon23_Vocabulary::get(esc_html__('Loading ...', 'avalon23-products-filter'))); ?></div><br /></div>
			</div>
			<div class="avalon23-modal-inner-footer">
				<a href="javascript: void(0);" class="<?php ( is_admin() ) ? 'button button-primary' : ''; ?>avalon23-btn avalon23-modal-button-large-1"><?php esc_html_e('Close', 'avalon23-products-filter'); ?></a>
			</div>
		</div>
	</div>

	<div class="avalon23-modal-backdrop"></div>

</template>

