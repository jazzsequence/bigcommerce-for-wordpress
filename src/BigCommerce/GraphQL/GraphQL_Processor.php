<?php

namespace BigCommerce\GraphQL;

use BigCommerce\Api\v3\Configuration;
use BigCommerce\Api\v3\ApiException;
use BigCommerce\Container\GraphQL;
use BigCommerce\Import\Processors\Headless_Product_Processor;
use BigCommerce\Logging\Error_Log;

class GraphQL_Processor extends BaseGQL {

	protected GraphQL $query;

	public function __construct( Configuration $config, GraphQL $query) {
		parent::__construct( $config );

		$this->query = $query;
	}

	public function request_terms( $slug, $taxonomy = 'category' ) {
		if ( $taxonomy === 'category' ) {
			$query = $this->query[ GraphQL::TERMS_QUERY ]->get_category_query();
		} else {
			$query = $this->query[ GraphQL::TERMS_QUERY ]->get_brand_query();
		}

		$body = [
			'query'     => $query,
			'variables' => [
				'urlPath' => '/' . $slug,
			],
		];

		$result = $this->make_request( $body );

		if ( ! isset( $result->data ) ) {
			return [];
		}

		// we need only specified taxonomy
		if ( strtolower( $result->data->site->route->node->__typename ) !== $taxonomy ) {
			return [];
		}

		return $result;
	}

	public function request_paginated_products( $size = 50 ) {
		$cursor = get_option( Headless_Product_Processor::HEADLESS_CURSOR, '' );
		$query  = $this->query[ GraphQL::PRODUCT_QUERY ]->get_paginated_products_query();
		$body   = [
			'query'     => $query,
			'variables' => [
				'pageSize' => $size,
				'cursor'   => $cursor,
			],
		];

		$result = $this->make_request( $body );

		if ( ! isset( $result->data ) ) {
			return [];
		}

		return $result;
	}

	public function request_product( $slug ) {
		$query = $this->query[ GraphQL::PRODUCT_QUERY ]->get_product_query();
		$body  = [
			'query'     => $query,
			'variables' => [
				'path' => '/' . $slug,
			],
		];

		$result = $this->make_request( $body );

		if ( ! isset( $result->data ) ) {
			return [];
		}

		return $result;
	}

	public function request_product_reviews( $product_id ) {
		if ( empty( $product_id ) ) {
			return [];
		}

		$query = $this->query[ GraphQL::REVIEWS_QUERY ]->get_product_reviews_query();
		$body  = [
			'query'     => $query,
			'variables' => [
				'productId' => $product_id,
			],
		];

		$result = $this->make_request( $body );

		if ( ! isset( $result->data ) ) {
			return [];
		}

		return $result;
	}

	public function products_loop_request( $limit = 12, $cursor = '') {
		$query = $this->query[ GraphQL::PRODUCT_QUERY ]->get_product_paginated_request_full();
		$body  = [
			'query'     => $query,
			'variables' => [
				'pageSize' => $limit,
				'cursor'   => $cursor,
			],
		];

		$result = $this->make_request( $body );

		if ( ! isset( $result->data ) ) {
			return [];
		}

		return $result;
	}

	public function get_customer_wishlist( $customer_id, $entityIds, $public = false ) {
		if ( empty( $customer_id ) || empty( $entityIds ) ) {
			return [];
		}

		if ($public) {
			$query = $this->query[ GraphQL::CUSTOMER_QUERY ]->get_public_wishlist_query();
		} else {
			$query = $this->query[ GraphQL::CUSTOMER_QUERY ]->get_wishlist_query();
		}

		$body  = [
			'query'     => $query,
			'variables' => [
				'entityIds' => $entityIds
			]
		];

		$headers                     = $this->get_headers( true );
		$headers['X-Bc-Customer-Id'] = $customer_id;
		unset( $headers['Origin'] );

		$result = $this->make_request( $body, $headers );

		if ( ! isset( $result->data ) ) {
			return [];
		}

		return $result;
	}

	public function get_customer_wishlists( $customer_id ) {
		if ( empty( $customer_id ) ) {
			return [];
		}

		$query = $this->query[ GraphQL::CUSTOMER_QUERY ]->get_wishlists_query();
		$body  = [
			'query'     => $query,
			'variables' => [],
		];

		$headers                     = $this->get_headers( true );
		$headers['X-Bc-Customer-Id'] = $customer_id;
		unset( $headers['Origin'] );

		$result = $this->make_request( $body, $headers );

		if ( ! isset( $result->data ) ) {
			return [];
		}

		return $result;
	}

	/**
	 * @return mixed
	 */
	public function get_category_tree() {
		try {
			$query = $this->query[ GraphQL::CATEGORY_TREE_QUERY ]->get_category_tree_query();
			$result = $this->make_request( [
				'query'     => $query,
				'variables' => [],
			] );
			

			return $result;
		} catch( ApiException $e ) {
			do_action( 'bigcommerce/import/error', $e->getMessage(), [
				'response' => $e->getResponseBody(),
				'headers'  => $e->getResponseHeaders(),
			] );

			do_action( 'bigcommerce/log', Error_Log::DEBUG, $e->getTraceAsString(), [] );
		} catch ( \Exception $e ) {
			do_action( 'bigcommerce/log', Error_Log::DEBUG, $e->getTraceAsString(), [] );
		}
	}

	/**
	 * @param string $cursor
	 * @param int    $page_size
	 *
	 * @return mixed
	 */
	public function get_brands( $cursor = '', $page_size = 50 ) {
		try {
			$query = $this->query[ GraphQL::CATEGORY_TREE_QUERY ]->get_category_tree_query();
			$result = $this->make_request( [
				'query'     => $query,
				'variables' => [
					'pageSize' => $page_size,
					'cursor'   => $cursor
				],
			] );
			

			return $result;
		} catch( ApiException $e ) {
			do_action( 'bigcommerce/import/error', $e->getMessage(), [
				'response' => $e->getResponseBody(),
				'headers'  => $e->getResponseHeaders(),
			] );

			do_action( 'bigcommerce/log', Error_Log::DEBUG, $e->getTraceAsString(), [] );
		} catch ( \Exception $e ) {
			do_action( 'bigcommerce/log', Error_Log::DEBUG, $e->getTraceAsString(), [] );
		}
	}

}
