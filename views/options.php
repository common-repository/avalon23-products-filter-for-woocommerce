<?php
if (!defined('ABSPATH')) {
	die('No direct access allowed');
}
?>

<div class="avalon23-admin-preloader">
	<div class="cssload-loader">
		<div class="cssload-inner cssload-one"></div>
		<div class="cssload-inner cssload-two"></div>
		<div class="cssload-inner cssload-three"></div>
	</div>
</div>

<svg class="hidden">
<defs>
<path id="tabshape" d="M80,60C34,53.5,64.417,0,0,0v60H80z"/>
</defs>
</svg>

<?php avalon23()->rate_alert->show_alert(); ?>

<div class="wrap nosubsub avalon23-options-wrapper">



	<h2 class="avalon23-plugin-name">
	<?php
	
		echo esc_html__('Avalon23 - WooCommerce Products Filters v.', 'avalon23-products-filter') . esc_html(AVALON23_VERSION);
	?>
		</h2>
	<i>
	<?php

		echo esc_html__('Actualized for WooCommerce v.', 'avalon23-products-filter') . esc_html(WOOCOMMERCE_VERSION);
	?>
	</i><br />
	<br />


	<div class="avalon23-tabs avalon23-tabs-style-shape">

		<nav>
			<ul>

				<li class="tab-current">
					<a href="#tabs-filters">
						<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
						<span><?php esc_html_e('Filters', 'avalon23-products-filter'); ?></span>
					</a>
				</li>


				<li>
					<a href="#tabs-settings">
						<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
						<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
						<span><?php esc_html_e('Settings', 'avalon23-products-filter'); ?></span>
					</a>
				</li>

				<?php if (Avalon23_Vocabulary::is_enabled()) : ?>
					<li>
						<a href="#tabs-vocabulary">
							<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
							<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
							<span><?php esc_html_e('Vocabulary', 'avalon23-products-filter'); ?></span>
						</a>
					</li>
				<?php endif; ?>
				<li>
					<a href="#tabs-seo">
						<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
						<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
						<span><?php esc_html_e('SEO', 'avalon23-products-filter'); ?></span>
					</a>
				</li>					
				<li>
					<a href="#tabs-info">
						<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
						<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
						<span><?php esc_html_e('Info', 'avalon23-products-filter'); ?></span>
					</a>
				</li> 
			</ul>
		</nav>

		<div class="content-wrap">
			<section id="tabs-filters" class="content-current">
				<a class = 'button avalon23-dash-btn' href="<?php echo esc_js('javascript: avalon23_main_table.create();void(0);'); ?>">
					<span class="dashicons-before dashicons-plus"></span> <?php esc_html_e('Create filter', 'avalon23-products-filter'); ?>
				</a>

				<br /><br />
				<?php
				do_action('avalon23_draw_main_table');
				?>

			</section>

			<section id="tabs-settings">
				<?php
				do_action('avalon23_draw_settings_table');
				?>

				<hr />

				<a href='javascript: new Popup23({title: "<?php echo esc_html__('Export Data', 'avalon23-products-filter'); ?>", what: "export"}); void(0);' class="avalon23-btn"><?php echo esc_html__('Export Data', 'avalon23-products-filter'); ?></a>&nbsp;
				<a href='javascript: new Popup23({title: "<?php echo esc_html__('Import Data', 'avalon23-products-filter'); ?>", what: "import"}); void(0);' class="avalon23-btn"><?php echo esc_html__('Import Data', 'avalon23-products-filter'); ?></a>

			</section>

			<?php if (Avalon23_Vocabulary::is_enabled()) : ?>
				<section id="tabs-vocabulary">
					<div class="avalon23-notice">
						<?php esc_html_e('This vocabulary is not for interface words, which you can translate for example by Loco Translate, but for the arbitrary words which you applied. Taxonomies terms also possible to translate here.', 'avalon23-products-filter'); ?>
						
						<a target='_blank' href='https://wordpress.org/plugins/loco-translate/' >
							Loco Translate
						</a>

					</div>
					
					<a class = 'button avalon23-dash-btn' href="<?php echo esc_js('javascript: avalon23_vocabulary_table.create();void(0);'); ?>" ><span class="dashicons-before dashicons-plus"></span> <?php esc_html_e('Create', 'avalon23-products-filter'); ?></a>

					<br /><br />
					<?php avalon23()->vocabulary->draw_table(); ?>
					<div class="clearfix"></div>
				</section>
			<?php endif; ?>
			<section id="tabs-seo">
				<?php
					do_action('avalon23_draw_seo_tab');
				?>
			</section>			
			<section id="tabs-info">
				<p>	
					<?php esc_html_e('If you have any problems read', 'avalon23-products-filter'); ?>
					<a target='_blank' href="https://avalon23.dev/faq/">
						<?php esc_html_e('FAQ', 'avalon23-products-filter'); ?>
					</a>

				</p>  
				<p>	
					<?php esc_html_e('A short', 'avalon23-products-filter'); ?>
					<a target='_blank' href="https://avalon23.dev/getting-started-with-avalon23/">
						<?php esc_html_e('Guide', 'avalon23-products-filter'); ?>
					</a>

				</p>  								 
				<p>	
					<?php esc_html_e('Read about shortcodes:', 'avalon23-products-filter'); ?>
					<a target='_blank' href="https://avalon23.dev/document/avalon23/">
						[avalon23]
					</a>	
					<a target='_blank' href="https://avalon23.dev/document/avalon23_button/">
						[avalon23_button]
					</a>

				</p>  
				<p>	
					<?php esc_html_e('Read more about the  plugin ', 'avalon23-products-filter'); ?>
					<a target='_blank' href="https://avalon23.dev/">
						<?php esc_html_e('here', 'avalon23-products-filter'); ?>
					</a>

				</p>	
				<?php if (!defined ('AVALON23_EXTEND_VERSION')) { ?>
				<p>	
					<?php esc_html_e('Additional features ', 'avalon23-products-filter'); ?>
					<a target='_blank' href="https://avalon23.dev/document/avalon23-extension-pack/">
						<?php esc_html_e('here', 'avalon23-products-filter'); ?>
					</a>
				</p>
				<?php } ?>
				
				<?php do_action('avalon23_draw_infotab'); ?>
			</section>  
		</div>
		<div>			
			<h2 >
				You can get the full version
				<a href="https://woocommerce.com/products/avalon23-products-filter-for-woocommerce/?quid=680f780906698c1f7013b62da75d710b">here
				<image style="height: 60px; margin-left: 10px; vertical-align: middle;" src="<?php echo AVALON23_ASSETS_LINK ?>img/logo_avolon23.png">
				</a>
			</h2>
			
		</div>	

	</div>





	<div id="avalon23-popup-filters-template" style="display: none;">

		<div class="avalon23-modal">
			<div class="avalon23-modal-inner">
				<div class="avalon23-modal-inner-header">
					<h3 class="avalon23-modal-title">&nbsp;</h3>

					<div class="avalon23-modal-title-info"><a href="https://avalon23.dev/document/filters/" id="main-table-help-link" target="_blank"><?php echo esc_html__('Help', 'avalon23-products-filter'); ?></a></div>

					<a href="javascript: avalon23_filter_items_table.close_popup(); void(0)" class="avalon23-modal-close"></a>


				</div>
				<div class="avalon23-modal-inner-content">
					<div class="avalon23-form-element-container">

						<div class="avalon23-tabs avalon23-tabs-style-shape">

							<nav>
								<ul>

									<li class="tab-current">
										<a href="#tabs-filter-items">
											<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
											<span><?php esc_html_e('Columns', 'avalon23-products-filter'); ?></span>
										</a>
									</li>


									<li>
										<a href="#tabs-meta">
											<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
											<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
											<span><?php esc_html_e('Meta', 'avalon23-products-filter'); ?></span>
										</a>
									</li>


									<li>
										<a href="#tabs-predefinition">
											<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
											<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
											<span><?php esc_html_e('Predefinition', 'avalon23-products-filter'); ?></span>
										</a>
									</li>


									<li>
										<a href="#tabs-custom-css">
											<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
											<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
											<span><?php esc_html_e('Custom CSS', 'avalon23-products-filter'); ?></span>
										</a>
									</li>


									<li>
										<a href="#tabs-options">
											<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
											<svg viewBox="0 0 80 60" preserveAspectRatio="none"><use xlink:href="#tabshape"></use></svg>
											<span><?php esc_html_e('Options', 'avalon23-products-filter'); ?></span>
										</a>
									</li>


								</ul>
							</nav>

							<div class="content-wrap">
								<section id="tabs-filter-items" class="content-current">

									<div>
										<a class="button avalon23-dash-btn" href="<?php echo esc_js('javascript: avalon23_filter_items_table.create();void(0);'); ?>">
											<span class="dashicons-before dashicons-welcome-add-page"></span> <?php echo esc_html__('Prepend item', 'avalon23-products-filter'); ?>
										</a>
									</div>
									<br />

									<div class="avalon23-filters-table-zone"></div>

									<br />

									<div>
										<a class="button avalon23-dash-btn avalon23-dash-btn-rotate" href="<?php echo esc_js('javascript: avalon23_filter_items_table.create(false);void(0);'); ?>">
											<span class="dashicons-before dashicons-welcome-add-page"></span> <?php echo esc_html__('Append item', 'avalon23-products-filter'); ?>
										</a>										

									</div>

								</section>

								<section id="tabs-custom-css">

									<table style="width: 100%;">
										<tr>
											<td>
												<div class="avalon23-notice">
													<?php echo esc_html__('You can use custom CSS for small changes, but for quite big the table restyling its recommended to use filter skins. Use hotkey combination CTRL+S for CSS code saving!', 'avalon23-products-filter'); ?>
												</div>
											</td>
											<td style="width: 1px; padding-left: 4px;">
												<a href="javascript: avalon23_main_table.save_custom_css(); void(0)" class="button avalon23-dash-btn-single">SAVE</a>
											</td>
										</tr>
									</table>

									<div class="avalon23-options-custom-css-zone"></div>									

								</section>

								<section id="tabs-options">

									<div class="avalon23-options-filters-table-zone"></div>
								</section>


								<section id="tabs-meta">

									<div class="avalon23-notice">
										
										<a href='https://avalon23.dev/document/meta-data/' target='_blank'>
											<?php esc_html_e('Read more here', 'avalon23-products-filter'); ?>
										</a>
										<?php esc_html_e('about how to use meta fields in the filter effectively!', 'avalon23-products-filter'); ?> 
										
										
									</div>
									<a class="button avalon23-dash-btn" href="<?php echo esc_js('javascript: avalon23_meta_fields_table.create();void(0);'); ?>">
										<span class="dashicons-before dashicons-plus"></span> <?php esc_html_e('Add meta field', 'avalon23-products-filter'); ?>
									</a>
									<br /><br />

									<div class="avalon23-meta-filters-table-zone"></div>
									<p><span class="dashicons dashicons-info"></span><b><?php esc_html_e('Meta Items:', 'avalon23-products-filter'); ?> </b><?php esc_html_e('Insert meta values (comma separated) by which you want to filter. An example test1,test2. Use the ^ character to add a title. An example: test1^Test 1,test2^Test 2', 'avalon23-products-filter'); ?> </p>
								</section>

								<section id="tabs-predefinition">
									<div class="avalon23-notice">
										<?php esc_html_e('The filtration will work with the predefined products as with basic ones.', 'avalon23-products-filter'); ?>
										<a href='https://avalon23.dev/document/predefinition/' target='_blank'>
											<?php esc_html_e('Read more here', 'avalon23-products-filter'); ?>
										</a>										

										
									</div>
									<div class="avalon23-predefinition-table-zone"></div>
								</section>
							</div>

						</div>


					</div>
				</div>
				<div class="avalon23-modal-inner-footer">
					<a href="javascript: avalon23_filter_items_table.close_popup(); void(0)" class="button button-primary avalon23-modal-button-large-1"><?php esc_html_e('Close', 'avalon23-products-filter'); ?></a>
					<!-- <a href="javascript:void(0)" class="avalon23-modal-save button button-primary button-large-2"><?php esc_html_e('Apply', 'avalon23-products-filter'); ?></a>-->
				</div>
			</div>
		</div>

		<div class="avalon23-modal-backdrop"></div>

	</div>

	<?php do_action('avalon23_draw_popup');	?>	

</div>

