<?php

//delete_option('avalon23_manage_rate_alert');//for tests
class AVALON23_RATE_ALERT {

	protected $notes_for_free = true;
	private $show_after_time = 86400 * 2;
	private $meta_key = 'avalon23_manage_rate_alert';

	public function __construct( $for_free ) {
		$this->notes_for_free = $for_free;
		add_action('wp_ajax_avalon23_manage_alert', array($this, 'manage_alert'));
	}

	private function get_time() {
		$time = intval(get_option($this->meta_key, -1));

		if (-1 === $time) {
			add_option($this->meta_key, time());
			$time = time();
		}

		if (-2 === $time) {
			$time = time(); //user already set review
		}

		return $time;
	}

	public function show_alert() {
		$show = false;

		if (( $this->get_time() + $this->show_after_time ) <= time()) {
			$show = true;
		}

		//***

		if ($show) {
			if (isset($_GET['page']) && 'avalon23' == $_GET['page']) {
				$support_link = 'https://#';
				?>
				<div id="avalon23-rate-alert">
					<p>Hi, looks like you using <b>Avalon23</b> for some time and I hope this software helped you with your business. If you satisfied with the plugin functionality, could you please give us BIG favor and give it a 5-star rating to help us spread the word and boost our motivation?<br /><br />
						<strong>~ developers team</strong>
					</p>

					<hr />

					<?php
					$link = 'https://wordpress.org/support/plugin/avalon23-products-filter-for-woocommerce/reviews/#new-post';
					if ($this->notes_for_free) {
						$link = 'https://wordpress.org/support/plugin/avalon23-products-filter-for-woocommerce/reviews/#new-post';
					}
					?>


					<table>
						<tr>
							<td>
								<a href="javascript: avalon23_manage_alert(0);void(0);" class="button button-large dashicons-before dashicons-clock">&nbsp;<?php echo esc_html__('Nope, maybe later!', 'avalon23-products-filter'); ?></a>
							</td>

							<td>
								<a href="<?php echo esc_url($link); ?>" target="_blank" class="avalon23-panel-button dashicons-before dashicons-star-filled">&nbsp;<?php echo esc_html__('Ok, you deserve it', 'avalon23-products-filter'); ?></a>
							</td>

							<td>
								<a href="javascript: avalon23_manage_alert(1);void(0);" class="button button-large dashicons-before dashicons-thumbs-up">&nbsp;<?php echo esc_html__('Thank you, I did it!', 'avalon23-products-filter'); ?></a>
							</td>
						</tr>
					</table>


				</div>
				<script>
					function avalon23_manage_alert(value) {
						//1 - did it, 0 - later
						jQuery('#avalon23-rate-alert').hide(333);
						jQuery.post(ajaxurl, {
							action: "avalon23_manage_alert",
							value: value
						}, function (data) {
							console.log(data);
						});
					}
				</script>

				<?php
			}
		}
	}

	public function manage_alert() {

		if (isset($_REQUEST['value']) && intval($_REQUEST['value'])) {
			update_option($this->meta_key, -2);
		} else {
			update_option($this->meta_key, time());
		}

		die('Thank you!');
	}

}
