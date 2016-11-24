<?php
/*
    $s3 = new S3(array(
        "key_id" => "xxxxxx",
        "secret_key" => "xxxxxx",
        "bucket_name" => "xxxxxx",
        "region" => 'ap-northeast-1',
        "sdk_version" => "latest",
    ));
    $s3->put("xxx","xxx");
    $s3->get("xxx");
*/
require_once __DIR__.'/aws.phar';
/**
 *
 */
class S3
{
    private $config;
    private $last_error = null;
    /**
     * [__construct description]
     * @param [type] $config [description]
     */
    public function __construct ($config)
    {
        $this->config = $config;
    }
    /**
     * [put description]
     * @param  [type] $local_file [description]
     * @param  [type] $s3_file    [description]
     * @return [type]             [description]
     */
    public function put ($local_file, $s3_file)
    {
        try {
            return $this->getS3Client()->putObject(array(
                'Bucket' => $this->config["bucket_name"],
                'Key' => $s3_file,
                'SourceFile' => $local_file,
                'ContentType' => mime_content_type($local_file),
            ));
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $this->last_error = $e;
            report_warning("S3へのファイル書き込みが失敗しました: ".$e->getMessage(),array(
                "local_file" => $local_file,
                "s3_file" => $s3_file,
                "S3Exception" => $e,
                "config" => $this->config,
            ));
        }
        return null;
    }
    /**
     * [get description]
     * @param  [type] $s3_file [description]
     * @return [type]          [description]
     */
    public function get ($s3_file)
    {
        try {
            return $this->getS3Client()->getObject(array(
                'Bucket' => $this->config["bucket_name"],
                'Key' => $s3_file,
            ));
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $this->last_error = $e;
            report_warning("S3からのファイル読み込みが失敗しました: ".$e->getMessage(),array(
                "s3_file" => $s3_file,
                "S3Exception" => $e,
                "config" => $this->config,
            ));
        }
        return null;
    }
    /**
     * [getS3ClientInstance description]
     * @return [type] [description]
     */
    private function getS3Client ()
    {
        return \Aws\S3\S3Client::factory(array(
            'credentials' => array(
                'key' => $this->config["key_id"],
                'secret' => $this->config["secret_key"],
            ),
            'region' => $this->config["region"],
            'version' => $this->config["sdk_version"],
        ));
    }
}
