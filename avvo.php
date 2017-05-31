<?php

require_once( 'config.php' );

define( 'API_BASE', 'https://api.avvo.com/api/4' );
define( 'OAUTH_URL', 'https://api.avvo.com/api/2/oauth2/authorize' );

// OAuth credential placeholders that must be filled in by users.
// You can find them on
// https://www.yelp.com/developers/v3/manage_app
define( 'CLIENT_ID', API_KEY );
define( 'CLIENT_SECRET', API_SECRET );

// Complain if credentials haven't been filled out.
//assert( CLIENT_ID, "Please supply your client_id." );
//assert( CLIENT_SECRET, "Please supply your client_secret." );

session_start();

$endpoint = @$_REQUEST['_ep'];
header( 'Content-Type: application/json' );

if ( !empty( $endpoint ) ) {
//	$args = http_build_query( $_REQUEST );

	$bearer_token = defined('HARDCODED_OAUTH_BEARER_TOKEN') ? HARDCODED_OAUTH_BEARER_TOKEN : '';
	if (empty($bearer_token)) {
		$bearer_token = obtain_bearer_token();
	}

	echo request( $bearer_token, API_BASE, $endpoint, $_GET );

} else {
	error( 'No endpoint requested' );
}

function error( $message ) {
	http_response_code( 400 );
	echo json_encode( array( 'error' => $message ) );
	die;
}

/**
 * Yelp Fusion API code sample.
 *
 * This program demonstrates the capability of the Yelp Fusion API
 * by using the Business Search API to query for businesses by a
 * search term and location, and the Business API to query additional
 * information about the top result from the search query.
 *
 * Please refer to http://www.yelp.com/developers/v3/documentation
 * for the API documentation.
 *
 * Sample usage of the program:
 * `php sample.php --term="dinner" --location="San Francisco, CA"`
 */


/**
 * Given a bearer token, send a GET request to the API.
 *
 * @return   OAuth bearer token, obtained using client_id and client_secret.
 */

$oauth_response_headers = array();
function oauth_token_from_header( $curl, $header ) {
	global $oauth_response_headers;

	$len    = strlen( $header );
	$header = explode( ':', $header, 2 );
	if ( count( $header ) < 2 ) {
		return $len;
	}

	$oauth_response_headers[ strtolower( trim( $header[0] ) ) ] = trim( $header[1] );

	return $len;
}


function obtain_bearer_token() {

	global $oauth_response_headers;

	try {
		# Using the built-in cURL library for easiest installation.
		# Extension library HttpRequest would also work here.
		$curl = curl_init();
		if ( false === $curl ) {
			throw new Exception( 'Failed to initialize' );
		}

//		redirect_uri=<the direct URI from my app>&client_id=<the avvo app client id>&client_secret=<the avvo app secret I only got to see once when I first registered the app>' https://api.avvo.com/api/2/oauth2/authorize

		$postfields = "client_id=" . CLIENT_ID .
		              "&client_secret=" . CLIENT_SECRET .
		              "&response_type=code_and_token" .
		              "&redirect_uri=" . OAUTH_CALLBACK;

		curl_setopt_array( $curl, array(
			CURLOPT_URL            => OAUTH_URL,
			CURLOPT_RETURNTRANSFER => true,  // Capture response.
			CURLOPT_ENCODING       => "",  // Accept gzip/deflate/whatever.
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_HEADERFUNCTION => 'oauth_token_from_header',
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => "POST",
			CURLOPT_POSTFIELDS     => $postfields,
			CURLOPT_HTTPHEADER     => array(
				"cache-control: no-cache",
				"content-type: application/x-www-form-urlencoded",
			),
		) );

		$response = curl_exec( $curl );

		if ( false === $response ) {
			throw new Exception( curl_error( $curl ), curl_errno( $curl ) );
		}
//		$http_status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
//		if ( 200 != $http_status ) {
//			throw new Exception( $response, $http_status );
//		}

		$next_url = $oauth_response_headers['location'];
		if ( $next_url ) {
			curl_setopt_array( $curl, array(
				CURLOPT_URL            => $next_url,
				CURLOPT_RETURNTRANSFER => true,  // Capture response.
				CURLOPT_ENCODING       => "",  // Accept gzip/deflate/whatever.
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_HEADERFUNCTION => 'oauth_token_from_header',
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => "GET",
				CURLOPT_HTTPHEADER     => array(
					"cache-control: no-cache",
					"content-type: application/x-www-form-urlencoded",
				),
			) );

			$response = curl_exec( $curl );
		}

		curl_close( $curl );
	} catch ( Exception $e ) {
		trigger_error( sprintf(
			'Curl failed with error #%d: %s',
			$e->getCode(), $e->getMessage() ),
			E_USER_ERROR );
	}

	$body         = json_decode( $response );
	$bearer_token = $body->access_token;

	return $bearer_token;
}


/**
 * Makes a request to the Yelp API and returns the response
 *
 * @param    $bearer_token   API bearer token from obtain_bearer_token
 * @param    $host    The domain host of the API
 * @param    $path    The path of the API after the domain.
 * @param    $url_params    Array of query-string parameters.
 *
 * @return   The JSON response from the request
 */
function request( $bearer_token, $host, $path, $url_params = array() ) {
	// Send Yelp API Call
	try {
		$curl = curl_init();
		if ( false === $curl ) {
			throw new Exception( 'Failed to initialize' );
		}

		$url = $host . $path . "?" . http_build_query( $url_params );
		curl_setopt_array( $curl, array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,  // Capture response.
			CURLOPT_ENCODING       => "",  // Accept gzip/deflate/whatever.
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => "GET",
			CURLOPT_HTTPHEADER     => array(
				"authorization: Bearer " . $bearer_token,
				"cache-control: no-cache",
			),
		) );

		$response = curl_exec( $curl );

		if ( false === $response ) {
			throw new Exception( curl_error( $curl ), curl_errno( $curl ) );
		}
		$http_status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		if ( 200 != $http_status ) {
			throw new Exception( $response, $http_status );
		}

		curl_close( $curl );
	} catch ( Exception $e ) {
		trigger_error( sprintf(
			'Curl failed with error #%d: %s',
			$e->getCode(), $e->getMessage() ),
			E_USER_ERROR );
	}

	return $response;
}

