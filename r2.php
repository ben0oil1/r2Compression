<?php

namespace App\Controllers;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;

class R2 extends BaseController
{
    protected $r2Client;
    protected $bucketName; 

    public function __construct()
    { 
      // R2的上传，用到S3的SDK，自行使用composer安装并引用
        $this->r2Client = new S3Client([
            'region' => 'auto', // e.g., 'us-east-1'
            'version' => 'latest',
            'endpoint' => 'https://****.r2.cloudflarestorage.com/',
            'credentials' => [
                'key'    => '9fda77049d020d1****',
                'secret' => '63c912d98bbd5a15d*****d968',
            ],
        ]);

        $this->bucketName = 'bucketName'; 
    }

    public function index()
    {
        return view('news/view');
    }

    public function getPresignedUrl()
    {
        $filename = uniqid() . '_' . $_GET['filename']; // Generate a unique filename
        $cmd = $this->r2Client->getCommand('PutObject', [
            'Bucket' => $this->bucketName,
            'Key' => $filename,
            'ContentType' => $_GET['filetype'], // Example: 'image/jpeg'
        ]);

        $request = $this->r2Client->createPresignedRequest($cmd, '+10 minutes');

        return $this->response->setJSON([
            'url' => (string) $request->getUri(),
            'fields' => [
                'key' => $filename
            ]
        ]);
    }

    public function getPresignedUrls()
    {
        $files = $this->request->getVar('files'); // Array of file names and types 
        $presignedUrls = [];

        foreach ($files as $file) {
            $filename = uniqid() . '_' . $file->name; 
            $cmd = $this->s3_client->getCommand('PutObject', [
                'Bucket' => $this->bucketName,
                'Key' => 'f/'.$filename,
                'ContentType' => $file->type, // Example: 'image/jpeg'
            ]); 
            // print_r($cmd);  
            $request = $this->s3_client->createPresignedRequest($cmd, '+10 minutes'); 

            $presignedUrls[] = [
                'url' => (string) $request->getUri(),
                'fields' => ['key' => $filename]
            ];
        }

        return $this->response->setJSON($presignedUrls);
    }

    // 通过json数据，写入数据库
    public function saveComment()
    {
        // Handle the form submission, save the comment, and associated image URL
        $commentData = $this->request->getPost();
        // Save to the database logic here...
        return $this->response->setJSON($commentData); 
    }
 
}
