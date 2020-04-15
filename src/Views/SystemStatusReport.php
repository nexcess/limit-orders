<?php
/**
 * Template for the WooCommerce system status report.
 *
 * @global \Nexcess\LimitOrders\OrderLimiter $limiter
 */

namespace Nexcess\LimitOrders\Views;

?>

<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Limit Orders"><h2><?php esc_html_e( 'Limit Orders', 'limit-orders' ); ?><?php wp_kses_post( wc_help_tip( __( 'Current configuration for Limit Orders for WooCommerce.', 'limit-orders' ) ) ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Enabled"><?php esc_html_e( 'Enabled', 'limit-orders' ); ?>:</td>
			<td class="help"><?php wp_kses_post( wc_help_tip( __( 'Is order limiting currently enabled on this store?', 'limit-orders' ) ) ); ?></td>
			<td><?php echo $limiter->is_enabled() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Limit"><?php esc_html_e( 'Limit', 'limit-orders' ); ?>:</td>
			<td class="help"><?php wp_kses_post( wc_help_tip( __( 'How many orders may be accepted per interval?', 'limit-orders' ) ) ); ?></td>
			<td><?php echo esc_html( $limiter->get_limit() ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Submitted orders"><?php esc_html_e( 'Submitted Orders', 'limit-orders' ); ?>:</td>
			<td class="help"><?php wp_kses_post( wc_help_tip( __( 'How many orders have been submitted in the current interval?', 'limit-orders' ) ) ); ?></td>
			<td><?php echo esc_html( $limiter->regenerate_transient() ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Remaining orders"><?php esc_html_e( 'Remaining Orders', 'limit-orders' ); ?>:</td>
			<td class="help"><?php wp_kses_post( wc_help_tip( __( 'How many more orders will be accepted in the current interval?', 'limit-orders' ) ) ); ?></td>
			<td><?php echo esc_html( $limiter->get_remaining_orders() ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Interval"><?php esc_html_e( 'Interval', 'limit-orders' ); ?>:</td>
			<td class="help"><?php wp_kses_post( wc_help_tip( __( 'How often is the order limit reset?', 'limit-orders' ) ) ); ?></td>
			<td><?php echo esc_html( $limiter->get_interval() ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Interval start"><?php esc_html_e( 'Interval Start', 'limit-orders' ); ?>:</td>
			<td class="help"><?php wp_kses_post( wc_help_tip( __( 'When the current interval began.', 'limit-orders' ) ) ); ?></td>
			<td><?php echo esc_html( $limiter->get_interval_start()->format( 'c' ) ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Interval resets"><?php esc_html_e( 'Interval Resets', 'limit-orders' ); ?>:</td>
			<td class="help"><?php wp_kses_post( wc_help_tip( __( 'When the next interval will begin.', 'limit-orders' ) ) ); ?></td>
			<td><?php echo esc_html( $limiter->get_next_interval_start()->format( 'c' ) ); ?></td>
		</tr>
	</tbody>
</table>
</table>
