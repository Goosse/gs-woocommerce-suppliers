<?php
/**
 * Admin View: Stock report by supplier template
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div id="poststuff" class="woocommerce-reports-wide">
	<div class="postbox">
		<div class="stats_range">
			<?php $this->get_export_button(); ?>
			<ul></ul> <!--This is just a placeholder to act as a header since this template does not use dates. -->
		</div>
		<?php if ( empty( $hide_sidebar ) ) : ?>
			<div class="inside chart-with-sidebar">
				<div class="chart-sidebar">
					<?php if ( $legends = $this->get_chart_legend() ) : ?>
						<ul class="chart-legend">
							<?php foreach ( $legends as $legend ) : ?>
								<li style="border-color: <?php echo $legend['color']; ?>" <?php if ( isset( $legend['highlight_series'] ) ) echo 'class="highlight_series ' . ( isset( $legend['placeholder'] ) ? 'tips' : '' ) . '" data-series="' . esc_attr( $legend['highlight_series'] ) . '"'; ?> data-tip="<?php echo isset( $legend['placeholder'] ) ? $legend['placeholder'] : ''; ?>">
									<?php echo $legend['title']; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<ul class="chart-widgets">
						<?php foreach ( $this->get_chart_widgets() as $widget ) : ?>
							<li class="chart-widget">
								<?php if ( $widget['title'] ) : ?><h4><?php echo $widget['title']; ?></h4><?php endif; ?>
								<?php call_user_func( $widget['callback'] ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="main">
					<?php $this->get_main_chart(); ?>
				</div>
			</div>
		<?php else : ?>
			<div class="inside">
				<?php $this->get_main_chart(); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
