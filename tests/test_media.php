<?php
/* Media Endpoint and Upload Tests inspired by REST API Attachment Endpoint */

class Micropub_Media_Test extends Micropub_UnitTestCase {
	
	public function setUp() {
		$orig_file       = DIR_MEDIATESTDATA . '/canola.jpg';
		$this->test_file = '/tmp/canola.jpg';
	        copy( $orig_file, $this->test_file );
	        $orig_file2       = DIR_MEDIATESTDATA . '/codeispoetry.png';
	        $this->test_file2 = '/tmp/codeispoetry.png';
	        copy( $orig_file2, $this->test_file2 );
		parent::setUp();
	}

	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( Micropub_Media::get_route( true ), $routes );
		$this->assertCount( 2, $routes[ Micropub_Media::get_route( true ) ] );
	}

	public function upload_request() {
		$request = new WP_REST_Request( 'POST', Micropub_Media::get_route( true ) );
		$request->set_header( 'Content-Type', 'image/jpeg' );
		$request->set_file_params( 
			array(
				'file' => array(
					'file'     => file_get_contents( $this->test_file ),
					'name'     => 'canola.jpg',
					'size'     => filesize( $this->test_file ),
					'tmp_name' => $this->test_file,
				),
			)
		);
		return $request;
	}

	public function test_media_handle_upload() {
		$file_array = array(
			'file' => file_get_contents( $this->test_file ),
			'name' => 'canola.jpg',
			'size' => filesize( $this->test_file ),
			'tmp_name' => $this->test_file
		);
		$id = Micropub_Media::media_handle_upload( $file_array );
		$this->assertInternalType( "int", $id );
	}

	public function test_upload_file() {
		$response = $this->dispatch( self::upload_request(), self::$author_id );
		$data     = $response->get_data();
		$this->assertEquals( 201, $response->get_status(), wp_json_encode( $data ) );
		// Test that a valid URL is returned in the JSON Body
		$this->assertNotEquals( 0, attachment_url_to_postid( $data['url'] ), sprintf( '%1$s is not an attachment', $data['url'] ) );
		// Test that a valid URL is returned in the Location Header
		$headers = $response->get_headers();
		$attachment_id = attachment_url_to_postid( $headers['Location'] );
		$this->assertNotEquals( 0, $attachment_id, sprintf( '%1$s is not an attachment', $headers['Location'] ) );
		$this->assertEquals( 'image/jpeg', get_post_mime_type( $attachment_id ) );
	}

	public function test_empty_upload() {
		$request = new WP_REST_Request( 'POST', Micropub_Media::get_route( true ) );
		$response = $this->dispatch( $request, self::$author_id );
		$data     = $response->get_data();
		$this->assertEquals( 400, $response->get_status(), wp_json_encode( $data ) );
	}

	public function test_upload_file_without_scope() {
		$response = $this->dispatch( self::upload_request(), self::$subscriber_id );
		$data     = $response->get_data();
		$this->assertEquals( 403, $response->get_status(), wp_json_encode( $data ) );
	}

}
