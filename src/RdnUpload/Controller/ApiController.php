<?php

namespace RdnUpload\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class ApiController extends AbstractActionController
{

    /**
     * @var array
     */
    private $config;

    /**
     * Construct
     * 
     * @param array $config
     */
    public function __construct(array $config = NULL)
    {
        $this->config = $config;
    }

    /**
     * Get a file
     * 
     * @return \Zend\View\Model\JsonModel
     */
    public function getAction()
    {
        // check access
        if ($response = $this->checkAccessToken()) {
            return $response;
        }

        $file = $this->params()->fromPost('file');

        $result = [
            'success' => FALSE,
            'code' => '404',
        ];

        if (!$file) {
            return $result;
        }

        $isFile = $this->uploads()->has($file);

        if ($isFile) {
            $result['success'] = TRUE;
            $result['code'] = '200';
        }

        $filename = $this->uploads()->get($file);
        $filepath = $filename->getPath();

        try {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimetype = finfo_file($finfo, $filepath);
            finfo_close($finfo);
        } catch (\Exception $e) {
            $result = [
                'success' => FALSE,
                'code' => '500',
            ];
            return new JsnoModel($result);
        }

        header("Content-Type: " . $mimetype);
        header("RDN-FILE: " . $mimetype);
        echo readfile($filepath);
        exit;
    }

    /**
     * Upload a file
     * 
     * @return \Zend\View\Model\JsonModel
     */
    public function uploadAction()
    {
        // check access
        if ($response = $this->checkAccessToken()) {
            return $response;
        }

        $file = $token = $this->params()->fromHeader('RDN-FILE') ?
                $this->params()->fromHeader('RDN-FILE')->getFieldValue() :
                FALSE;

        $result = [
            'success' => FALSE,
            'code' => '404',
        ];

        if (!$file) {
            return $result;
        }
        
        try {
            $uploadPath = $this->config['rdn_upload_adapters']['configs']['Filesystem']['upload_path'];

            $filename = "{$uploadPath}/{$file}";
            $filepath = dirname($filename);

            if (file_exists($filename)) {
                $result['success'] = TRUE;
                $result['code'] = '200';

                return $result;
            }

            if (!is_dir($filepath)) {
                mkdir($filepath, 0777, true);
            }

            $data = file_get_contents('php://input');
            file_put_contents($filename, $data);

            $result['success'] = TRUE;
            $result['code'] = '201';
        } catch (\Exception $e) {
            $result['code'] = '503';
        }

        return new JsonModel($result);
    }

    /**
     * Remove a file
     * 
     * @return \Zend\View\Model\JsonModel
     */
    public function deleteAction()
    {
        // check access
        if ($response = $this->checkAccessToken()) {
            return $response;
        }

        $file = $this->params()->fromPost('file');
        $deletePublic = boolval($this->params()->fromPost('deletePublic', FALSE));

        $result = [
            'success' => FALSE,
            'code' => '404',
        ];

        if (!$file) {
            return $result;
        }

        $isFile = $this->uploads()->has($file);

        if ($isFile) {
            $this->uploads()->delete($file, $deletePublic);
            $result['success'] = TRUE;
            $result['code'] = '200';
        }

        return new JsonModel($result);
    }

    /**
     * Check client access token
     * 
     * @return \Zend\View\Model\JsonModel|NULL
     */
    private function checkAccessToken()
    {
        try {
            $clientToken = $token = $this->params()->fromHeader('RDN-ACCESS-TOKEN') ?
                    $this->params()->fromHeader('RDN-ACCESS-TOKEN')->getFieldValue() :
                    FALSE;
            $localToken = $this->config['rdn_upload_remote']['access_token'];

            if ($clientToken && $localToken === $clientToken) {
                return NULL;
            }
        } catch (\Exception $ex) {
            
        }

        return new JsonModel([
            'success' => FALSE,
            'code' => '403',
        ]);
    }

}
