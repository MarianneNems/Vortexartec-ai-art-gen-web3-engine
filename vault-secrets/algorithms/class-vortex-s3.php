<?php
/**
 * AWS S3 integration for storing & retrieving files
 *
 * @package VortexAIEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VortexAIEngine_S3 {
    private static $instance = null;
    private $client;
    private $bucket;
    private $is_available = false;

    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Check if AWS SDK is available
        if ( class_exists( 'Aws\S3\S3Client' ) ) {
            try {
                // Try WordPress options first, fallback to ENV variables
                $access_key = get_option( 'vortex_aws_access_key' ) ?: getenv( 'AWS_ACCESS_KEY_ID' );
                $secret_key = get_option( 'vortex_aws_secret_key' ) ?: getenv( 'AWS_SECRET_ACCESS_KEY' );
                $region = get_option( 'vortex_aws_region' ) ?: getenv( 'AWS_REGION' );
                $bucket = get_option( 'vortex_aws_s3_bucket' ) ?: getenv( 'AWS_S3_BUCKET' );
                
                // Validate all required credentials are present
                if ( empty( $access_key ) || empty( $secret_key ) || empty( $region ) || empty( $bucket ) ) {
                    throw new Exception( 'Missing required AWS credentials. Please configure them in VORTEX AI Engine > API Configuration.' );
                }
                
                $this->bucket = $bucket;
                $this->client = new \Aws\S3\S3Client([
                    'version'     => 'latest',
                    'region'      => $region,
                    'credentials' => [
                        'key'    => $access_key,
                        'secret' => $secret_key,
                    ],
                ]);
                $this->is_available = true;
            } catch ( Exception $e ) {
                error_log( '[VortexAI] AWS S3 client initialization failed: ' . $e->getMessage() );
                $this->is_available = false;
            }
        } else {
            error_log( '[VortexAI] AWS SDK not installed. Run: composer require aws/aws-sdk-php' );
            $this->is_available = false;
        }
    }

    /** Check if S3 is available */
    public function isAvailable() {
        return $this->is_available;
    }

    public function uploadFile( $key, $source, $mime_type = 'application/octet-stream' ) {
        if ( ! $this->is_available ) {
            error_log( '[VortexAI] S3 not available, cannot upload file: ' . $key );
            return false;
        }

        try {
            $res = $this->client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $key,
                'SourceFile'  => $source,
                'ContentType' => $mime_type,
                'ACL'         => 'public-read',
            ]);
            return $res['ObjectURL'];
        } catch ( Exception $e ) {
            error_log( '[VortexAI] S3 Upload Failed: ' . $e->getMessage() );
            return false;
        }
    }

    public function getSignedUrl( $key, $expires = 3600 ) {
        if ( ! $this->is_available ) {
            error_log( '[VortexAI] S3 not available, cannot generate signed URL for: ' . $key );
            return false;
        }

        try {
            $cmd     = $this->client->getCommand( 'GetObject', [ 'Bucket' => $this->bucket, 'Key' => $key ] );
            $request = $this->client->createPresignedRequest( $cmd, "+{$expires} seconds" );
            return (string) $request->getUri();
        } catch ( Exception $e ) {
            error_log( '[VortexAI] S3 SignedURL Failed: ' . $e->getMessage() );
            return false;
        }
    }
} 