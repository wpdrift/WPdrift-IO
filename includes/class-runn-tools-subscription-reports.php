<?php
/**
 * [Runn_Tools_Subscription_Reports description]
 */
class Runn_Tools_Subscription_Reports {
	function __construct() {
		// add_action( 'wp_ajax_my_action', [ $this, 'my_action' ] );
		add_action( 'edd_subscription_post_create', [ $this, 'subscription_post_create' ], 10, 2 );
		add_action( 'edd_subscription_cancelled', [ $this, 'decrease_total_mmr' ] );
		add_action( 'edd_subscription_completed', [ $this, 'decrease_total_mmr' ] );
		add_action( 'edd_subscription_expired', [ $this, 'decrease_total_mmr' ] );
		add_action( 'edd_subscription_failing', [ $this, 'decrease_total_mmr' ] );
		add_action( 'edd_recurring_set_subscription_status', [ $this, 'edd_recurring_set_subscription_status' ], 10, 3 );
	}

	public function my_action() {
		$sub_id  = 5;
		$edd_sub = new EDD_Subscription( $sub_id );

		wp_send_json(
			[
				'$this->get_account_mrr( 1 )'  => $this->get_account_mrr( 1 ),
				'$this->get_final_mrr()'       => $this->get_final_mrr(),
				'$this->count_mrr_histories()' => $this->count_mrr_histories(),
				'$edd_sub'                     => $edd_sub,
			]
		);
	}

	public function edd_recurring_set_subscription_status( $subscription_id, $status, $subscription ) {
		if ( 'active' === $status ) {
			$this->increase_total_mmr( $subscription_id );
		} else {
			$this->decrease_total_mmr( $subscription_id );
		}
	}

	/**
	 * [increase_total_mmr description]
	 * @param  [type] $subscription_id               [description]
	 * @return [type]                  [description]
	 */
	public function increase_total_mmr( $subscription_id ) {
		$edd_sub = new EDD_Subscription( $subscription_id );

		$history_arr = [
			'customer_id' => $edd_sub->customer_id,
			'created'     => current_time( 'mysql' ),
		];

		$final_mrr   = $this->get_final_mrr();
		$account_mrr = $this->get_account_mrr( $edd_sub->customer_id );

		$recurring_amount           = floatval( $edd_sub->recurring_amount );
		$history_arr['account_mrr'] = $account_mrr + $recurring_amount;
		$history_arr['delta']       = $recurring_amount;
		$history_arr['total_mrr']   = $final_mrr + $recurring_amount;

		$this->insert_mrr_history( $history_arr );
	}

	/**
	 * [decrease_total_mmr description]
	 * @param  [type] $subscription_id               [description]
	 * @return [type]                  [description]
	 */
	public function decrease_total_mmr( $subscription_id ) {
		$edd_sub = new EDD_Subscription( $subscription_id );

		$history_arr = [
			'customer_id' => $edd_sub->customer_id,
			'created'     => current_time( 'mysql' ),
		];

		$final_mrr   = $this->get_final_mrr();
		$account_mrr = $this->get_account_mrr( $edd_sub->customer_id );

		$recurring_amount           = floatval( $edd_sub->recurring_amount );
		$history_arr['account_mrr'] = $account_mrr - $recurring_amount;
		$history_arr['delta']       = -$recurring_amount;
		$history_arr['total_mrr']   = $final_mrr - $recurring_amount;

		$this->insert_mrr_history( $history_arr );
	}

	/**
	 * [subscription_post_create description]
	 * @param  [type] $id                 [description]
	 * @param  [type] $args               [description]
	 * @return [type]       [description]
	 */
	public function subscription_post_create( $id, $args ) {
		$history_arr = [
			'customer_id' => $args['customer_id'],
			'created'     => $args['created'],
		];

		$final_mrr   = $this->get_final_mrr();
		$account_mrr = $this->get_account_mrr( $args['customer_id'] );

		$recurring_amount           = floatval( $args['recurring_amount'] );
		$history_arr['account_mrr'] = $account_mrr + $recurring_amount;
		$history_arr['delta']       = $recurring_amount;
		$history_arr['total_mrr']   = $final_mrr + $recurring_amount;

		$this->insert_mrr_history( $history_arr );
	}

	/**
	 * [subscription_cancelled description]
	 * @param  [type] $subscription_id               [description]
	 * @param  [type] $subscription                  [description]
	 * @return [type]                  [description]
	 */
	public function subscription_cancelled( $subscription_id, $subscription ) {
		$history_arr = [
			'customer_id' => $subscription->customer_id,
			'created'     => current_time( 'mysql' ),
		];

		$final_mrr   = $this->get_final_mrr();
		$account_mrr = $this->get_account_mrr( $subscription->customer_id );

		$recurring_amount           = floatval( $subscription->recurring_amount );
		$history_arr['account_mrr'] = $account_mrr - $recurring_amount;
		$history_arr['delta']       = -$recurring_amount;
		$history_arr['total_mrr']   = $final_mrr - $recurring_amount;

		$this->insert_mrr_history( $history_arr );
	}

	public function count_mrr_histories() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}edd_mrr_history" );
	}

	public function subscription_completed( $id, $subscription ) {
		// error_log( json_encode($args), 0 );
		error_log( json_encode( $subscription ), 0 );
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
	 * [get_account_mrr description]
	 * @param  [type] $customer_id               [description]
	 * @return [type]              [description]
	 */
	public function get_account_mrr( $customer_id ) {
		global $wpdb;

		$account_mrr = 0;

		$mrr_history = $wpdb->get_row(
			$wpdb->prepare(
				"
                SELECT *
                FROM {$wpdb->prefix}edd_mrr_history
                WHERE customer_id = %d
                ORDER BY id DESC LIMIT 1
                ",
				$customer_id
			)
		);

		if ( $mrr_history ) {
			$account_mrr = floatval( $mrr_history->account_mrr );
		}

		return $account_mrr;
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
