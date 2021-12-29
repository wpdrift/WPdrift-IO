<?php
/**
 * [Runn_Tools_Subscription_Reports description]
 */
class Runn_Tools_Subscription_Reports {
	function __construct() {
		add_action( 'wp_ajax_my_action', [ $this, 'my_action' ] );
		add_action( 'edd_subscription_post_create', [ $this, 'subscription_post_create' ], 10, 2 );
	}

	public function my_action() {
		wp_send_json(
			[
				'$this->get_final_mrr()'       => $this->get_final_mrr(),
				'$this->count_mrr_histories()' => $this->count_mrr_histories(),
			]
		);
	}

	public function subscription_post_create( $id, $args ) {
		$history_arr = [
			'customer_id' => $args['customer_id'],
			'created'     => $args['created'],
		];

		$final_mrr = $this->get_final_mrr();

		$this->insert_mrr_history( $history_arr );
	}

	public function count_mrr_histories() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}edd_mrr_history" );
	}

	/**
	 * [add_mrr_history description]
	 */
	public function insert_mrr_history( $history_arr = [] ) {
		global $wpdb;

		$defaults = array(
			'customer_id' => 0,
			'created'     => '0000-00-00 00:00:00',
			'account_mrr' => 0,
			'total_mrr'   => 0,
			'delta'       => 0,
		);

		$history_arr = wp_parse_args( $history_arr, $defaults );

		$table_name = $wpdb->prefix . 'edd_mrr_history';

		$wpdb->insert( $table_name, $history_arr );

		return $wpdb->insert_id;
	}

	/**
	 * [get_final_mrr description]
	 * @return [type] [description]
	 */
	public function get_final_mrr() {
		global $wpdb;

		$final_mrr = 0;

		$this->insert_mrr_histories();

		$mrr_history = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}edd_mrr_history ORDER BY id DESC LIMIT 1" );
		if ( $mrr_history ) {
			$final_mrr = floatval( $mrr_history->total_mrr );
		}

		return $final_mrr;
	}

	/**
	 * [insert_mrr_histories description]
	 * @return [type] [description]
	 */
	public function insert_mrr_histories() {
		if ( $this->count_mrr_histories() ) {
			return;
		}

		$subscriptions_db = new EDD_Subscriptions_DB;
		$subscriptions    = $subscriptions_db->get_subscriptions(
			[
				'number' => -1,
				'order'  => 'ASC',
			]
		);

		if ( $subscriptions ) {
			$account_mrr = [];
			$total_mrr   = 0;

			foreach ( $subscriptions as $subscription ) {
				if ( ! isset( $account_mrr[ $subscription->customer_id ] ) ) {
					$account_mrr[ $subscription->customer_id ] = 0;
				}

				$recurring_amount                           = floatval( $subscription->recurring_amount );
				$account_mrr[ $subscription->customer_id ] += $recurring_amount;
				$delta                                      = $recurring_amount;
				$total_mrr                                 += $recurring_amount;

				$this->insert_mrr_history(
					[
						'customer_id' => $subscription->customer_id,
						'created'     => $subscription->created,
						'account_mrr' => $account_mrr[ $subscription->customer_id ],
						'delta'       => $delta,
						'total_mrr'   => $total_mrr,
					]
				);

				if ( 'cancelled' === $subscription->status ) {
					$account_mrr[ $subscription->customer_id ] -= $recurring_amount;
					$delta                                      = -$recurring_amount;
					$total_mrr                                 -= $recurring_amount;

					$this->insert_mrr_history(
						[
							'customer_id' => $subscription->customer_id,
							'created'     => $subscription->created,
							'account_mrr' => $account_mrr[ $subscription->customer_id ],
							'delta'       => $delta,
							'total_mrr'   => $total_mrr,
						]
					);
				}
			}
		}
	}
}
