<?php

namespace RdnUpload;

use RdnUpload\Adapter\AdapterInterface;
use RdnUpload\File\FileInterface;

class Container implements ContainerInterface
{
	/**
	 * Adapter used to perform the actual file operations.
	 *
	 * @var AdapterInterface
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $tempDir;

	/**
	 * @param AdapterInterface $adapter
	 * @param string $tempDir
	 */
	public function __construct(AdapterInterface $adapter = null, $tempDir = null)
	{
		$this->tempDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
		if ($adapter)
		{
			$this->setAdapter($adapter);
		}
		if ($tempDir)
		{
			$this->tempDir = $tempDir;
		}
	}

	public function setAdapter(AdapterInterface $adapter)
	{
		$this->adapter = $adapter;
	}

	public function getAdapter()
	{
		return $this->adapter;
	}

	public function upload($input)
	{
		if (is_array($input))
		{
			$input = new File\Input($input);
		}

		if (!$input instanceof FileInterface)
		{
			throw new \InvalidArgumentException(sprintf(
				"Input must be an object implementing %s"
				, __NAMESPACE__ .'\File\FileInterface'
			));
		}

		$id = $this->generateSequence($input->getBasename());
		if ($this->has($id))
		{
			return $this->upload($input);
		}

		$this->adapter->upload($id, $input);

		return $id;
	}

	public function get($id)
	{
		if (empty($id))
		{
			throw new \InvalidArgumentException('ID cannot be empty');
		}

		return $this->adapter->get($id);
	}

    /**
     * Get public file path and resize/crop
     *
     * @param $id
     * @param array $options
     * @return string
     * @throws \InvalidArgumentException
     * @author Lyubomir Angelov
     */
    public function getPublicFile($id, array $options = array())
    {
        $publicFolder = 'public';
        $obj = $this->adapter->get($id);
        //TODO: create CROP method
        // Resize image
        if (isset($options['resize']) && isset($options['resize']['width']) && isset($options['resize']['height'])) {
            // Set dimensions string to append
            $dimensionsStr = '__' . $options['resize']['width'] . '_' . $options['resize']['height'];
            // Resize file path
            $resizeFile = $publicFolder . strstr($obj->getPublicUrl(),'.', TRUE) . $dimensionsStr . '.' . $obj->getExtension();
            if (!is_file($resizeFile)) {

                // Check is public folders path are created
                $publicFolderPath = $publicFolder . str_replace($obj->getBasename(), '', $obj->getPublicUrl());
                if (!is_dir($publicFolderPath)) {
                    $createdFolders = mkdir($publicFolderPath, 0777, TRUE);
                    // If we still don't have the folders
                    if (!$createdFolders) {
                        throw new \InvalidArgumentException(sprintf(
                            "Cannot create folders %s"
                            , $publicFolderPath
                        ));
                    }
                }
                // If file doesn't exist; create it
                if (!copy($obj->getPath(), $resizeFile)) {
                    throw new \InvalidArgumentException(sprintf(
                        "Image file cannot be copied %s"
                        , $resizeFile
                    ));
                } else {
                    // Resize with ImageMagic
                    $img = new \Imagick($resizeFile);
                    $img->cropThumbnailImage($options['resize']['width'], $options['resize']['height']);
                    $created = $img->writeImage($resizeFile);
                    if (!$created) {
                        throw new \InvalidArgumentException(sprintf(
                            "ImageMagic cannot write the image %s"
                            , $resizeFile
                        ));
                    }
                }
            }
            // Return resized file
            return str_replace($publicFolder, '', $resizeFile);
        }

        return $obj->getPublicUrl();
    }

	public function download($id)
	{
		if (empty($id))
		{
			throw new \InvalidArgumentException('ID cannot be empty');
		}

		$object = $this->adapter->get($id);
		$output = new File\File($object->getBasename(), $this->generateTempPath());

		$this->adapter->download($id, $output);

		return $output;
	}

	public function has($id)
	{
		if (empty($id))
		{
			//throw new \InvalidArgumentException('ID cannot be empty');
            return FALSE;
		}

		return $this->adapter->has($id);
	}

	public function delete($id)
	{
		if (empty($id))
		{
			throw new \InvalidArgumentException('ID cannot be empty');
		}

		return $this->adapter->delete($id);
	}

	/**
	 * Generate a unique/random sequence.
	 *
	 * @param string $basename
	 *
	 * @return string
	 */
	protected function generateSequence($basename)
	{
		$basename = $this->sanitize($basename);

		$hash = hash('sha1', uniqid('', true) . mt_rand() . $basename);
		$prefix = implode(DIRECTORY_SEPARATOR, str_split(substr($hash, 0, 3)));

		return $prefix . DIRECTORY_SEPARATOR . $hash . DIRECTORY_SEPARATOR . $basename;
	}

	/**
	 * @return string
	 */
	protected function generateTempPath()
	{
		return tempnam($this->tempDir, 'rdnu');
	}

	/**
	 * @param string $basename
	 *
	 * @return string
	 */
	protected function sanitize($basename)
	{
		$filename = pathinfo($basename, PATHINFO_FILENAME);
		$extension = pathinfo($basename, PATHINFO_EXTENSION);

		$filename = str_replace(' ', '-', $filename);
		$filename = preg_replace('/[^a-z0-9\.\-\_]/i', '', $filename);
		$filename = substr($filename, 0, 100);

		$filename = trim($filename, '-_.');

		return $filename .'.'. $extension;
	}
}
