<?php

namespace RdnUpload\Adapter;

use RdnUpload\File;
use RdnUpload\File\FileInterface;
use RdnUpload\Object;
use Zend\Stdlib\ErrorHandler;
use RdnUpload\Hydrator\Strategy;
use RdnUpload\Adapter\Filesystem;

/**
 * Sync local filesystem with remote storage for uploaded files.
 */
class SyncFilesystem extends Filesystem
{

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var array
     */
    protected $access;

    /**
     * @var array
     */
    protected $events;

    /**
     * @param string $uploadPath
     * @param string $publicPath
     * @param string $host
     * @param string $accessToken
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct($uploadPath, $publicPath, $host, $accessToken, $access, $events)
    {
        if (empty($uploadPath)) {
            throw new \InvalidArgumentException('Must provide an upload directory');
        }

        if (!is_writeable($uploadPath)) {
            throw new \RuntimeException("Cannot write to directory ($uploadPath)");
        }

        $this->uploadPath = rtrim($uploadPath, DIRECTORY_SEPARATOR);
        $this->publicPath = rtrim($publicPath, DIRECTORY_SEPARATOR);
        $this->host = $host;
        $this->accessToken = $accessToken;
        $this->access = $access;
        $this->events = $events;
    }

    /**
     * @throws \RuntimeException if move operation is unsuccessful
     */
    public function upload($id, FileInterface $input)
    {
        parent::upload($id, $input);

        if (in_array('upload', $this->events)) {
            $targetPath = $this->getFilepath($id);

            // send file to remote server
            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => $this->prepareHeaders(['RDN-FILE' => $id]),
                    'content' => file_get_contents($targetPath),
                )
            );

            $context = stream_context_create($opts);

            try {
                file_get_contents("{$this->host}/upload", FALSE, $context);
            } catch (\Exception $e) {
                
            }
        }
    }

    /**
     * Get file
     *
     * @param   string $id
     * @return  Object\Local|Object\ObjectInterface
     * @throws  \RuntimeException
     */
    public function get($id)
    {
        if (in_array('get', $this->events) && !$this->hasLocal($id) && !$this->hasRemote($id)) {
            throw new \RuntimeException("File does not exist ($id)");
        }

        $file = parent::get($id);

        return $file;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        if (in_array('get', $this->events)) {
            $result = $this->hasLocal($id) || $this->hasRemote($id);
        } else {
            $result = parent::has($id);
        }

        return $result;
    }

    /**
     * @throws \RuntimeException if file does not exist or delete operation fails
     */
    public function delete($id, $deletePublic = FALSE)
    {
        parent::delete($id, $deletePublic);

        if (in_array('delete', $this->events)) {
            // remove file from remote server
            $postdata = http_build_query(array(
                'file' => $id,
                'deletePublic' => $deletePublic,
            ));

            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => $this->prepareHeaders(),
                    'content' => $postdata
                )
            );

            $context = stream_context_create($opts);

            try {
                file_get_contents("{$this->host}/delete", FALSE, $context);
            } catch (\Exception $e) {
                
            }
        }
    }

    /**
     * Check file is exists in local file system
     * 
     * @param string $id
     * @return bool
     */
    public function hasLocal($id)
    {
        return parent::has($id);
    }

    /**
     * Check file is exists in remote file system
     * 
     * @param string $id
     * @return bool
     */
    public function hasRemote($id)
    {
        $postdata = http_build_query(array(
            'file' => $id,
        ));

        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => $this->prepareHeaders(),
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);
        $result = FALSE;

        try {
            $response = file_get_contents("{$this->host}/get", FALSE, $context);
            $headers = $this->parseHeaders($http_response_header);

            if (array_key_exists('RDN-FILE', $headers) && $response) {
                $targetPath = $this->getFilepath($id);
                $targetDir = dirname($targetPath);

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                file_put_contents($targetPath, $response);
                chmod($targetPath, 0660);

                $result = TRUE;
            } else {
                $response = json_decode($response, FALSE);
                $result = $response->success;
            }
        } catch (\Exception $e) {
            $result = FALSE;
        }

        return $result;
    }

    /**
     * @param array $headers
     * @return string
     */
    private function prepareHeaders(array $headers = NULL)
    {
        $result = "Content-type: application/x-www-form-urlencoded" .
                "\r\nRDN-ACCESS-TOKEN: {$this->accessToken}";

        if ($this->access) {
            $headers['Authorization'] = "Basic " . base64_encode("{$this->access['username']}:{$this->access['password']}");;
        }

        foreach ($headers as $key => $val) {
            $result .= "\r\n{$key}: {$val}";
        }

        return $result;
    }

    /**
     * @param array $headers
     * @return array
     */
    private function parseHeaders(array $headers = NULL)
    {
        $head = array();
        foreach ($headers as $k => $v) {
            $t = explode(':', $v, 2);
            if (isset($t[1]))
                $head[trim($t[0])] = trim($t[1]);
            else {
                $head[] = $v;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out))
                    $head['reponse_code'] = intval($out[1]);
            }
        }
        return $head;
    }

}
