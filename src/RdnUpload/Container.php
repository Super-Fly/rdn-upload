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
     * @var array
     */
    protected $config;

    /**
     * @param AdapterInterface  $adapter
     * @param array             $config
     */
    public function __construct(AdapterInterface $adapter = null, $config = null)
    {
        $this->config = $config;

        if ($adapter) {
            $this->setAdapter($adapter);
        }

        $this->tempDir = ini_get('upload_tmp_dir') ? : sys_get_temp_dir();
        if (isset($config['rnd_upload']['temp_dir']) && $config['rnd_upload']['temp_dir']) {
            $this->tempDir = $config['rnd_upload']['temp_dir'];
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

    /**
     * Upload
     *
     * @param   array|FileInterface     $input
     * @return  string
     * @throws  \InvalidArgumentException
     */
    public function upload($input)
    {
        if (is_array($input)) {
            $input = new File\Input($input);
        }

        if (!$input instanceof FileInterface) {
            throw new \InvalidArgumentException(sprintf(
                "Input must be an object implementing %s"
                , __NAMESPACE__ . '\File\FileInterface'
            ));
        }

        $id = $this->generateSequence($input->getBasename());
        if ($this->has($id)) {
            return $this->upload($input);
        }

        $this->adapter->upload($id, $input);

        return $id;
    }

    /**
     * String (Base64) Upload
     *
     * @param   string  $base64
     * @param   string  $name
     * @return  bool|string
     */
    public function stringUpload($base64, $name = NULL)
    {
        if (is_string($base64)) {
            preg_match('#^data:image/(\w+);base64,#i', $base64, $matches);
            $img = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            // Name create
            if (empty($name)) {
                $name = time();
            }
            $imageName = $name;
            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $imageName .= '.' . $matches[1];
            }

            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
            $filePath = $path . $imageName;
            // Save it to tmp file
            $saved = file_put_contents($filePath, $data);
            // Success
            if ($saved && file_exists($filePath)) {
                // Upload
                $imageId = $this->fakeUpload($imageName, $path);
                // Remove tmp file
                unlink($filePath);
                // Return image id
                return $imageId;
            }
        }

        return FALSE;
    }

    /**
     * URL Upload
     *
     * @param   string  $url
     * @param   string  $name
     * @return  bool|string
     */
    public function urlUpload($url, $name = null)
    {
        // Get The Picture
        $picture = file_get_contents($url);
        if ($picture === FALSE) {
            return FALSE;
        }

        $urlParts = pathinfo(strtok($url, '?'));
        // Create tmp file
        if (!is_null($name)) {
            $pictureName = $name . '.' . $urlParts['extension'];
        } else {
            $pictureName = $urlParts['basename'];
        }

        // Path and Filename
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        $file = $path . $pictureName;
        // Save it to tmp file
        $saved = file_put_contents($file, $picture);
        // Success
        if ($saved && file_exists($file)) {
            // Upload
            $imageId = $this->fakeUpload($pictureName, $path);
            // Remove tmp file
            unlink($file);
            // Return image id
            return $imageId;
        }
        return FALSE;
    }

    /**
     * Fake Upload
     *
     * @param   string    $filename
     * @param   string    $path
     * @return  string
     */
    public function fakeUpload($filename, $path)
    {
        $id = $this->generateSequence($filename);
        if ($this->has($id)) {
            return $this->fakeUpload($filename, $path);
        }

        $this->adapter->fakeUpload($id, $filename, $path);

        return $id;
    }

    /**
     * Get object
     *
     * @param   string  $id
     * @return  Object\ObjectInterface
     * @throws  \InvalidArgumentException
     */
    public function get($id)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('ID cannot be empty');
        }

        return $this->adapter->get($id);
    }

    /**
     * Get public file path and resize/crop
     *
     * @param   string  $id
     * @param   array   $options
     * @return  string
     * @throws  \InvalidArgumentException
     * @author  Lyubomir Angelov
     */
    public function getPublicFile($id, array $options = array())
    {
        $publicFolder = isset($this->config['rdn_upload_adapters']['public_folder']) ? $this->config['rdn_upload_adapters']['public_folder'] : 'public';

        // Set Default Image
        if (!$this->has($id)) {
            if (!isset($options['default']) && !isset($options['df'])) {
                return FALSE;
            }
            $options['default'] = (isset($options['default']) ? $options['default'] : $options['df']);
            $id = 'default_' . ($options['default'] == 'user_' ? 'user_male' : $options['default']) . '.png';

            if (!$this->has($id)) {
                throw new \InvalidArgumentException(sprintf(
                    "Default image does not exists %s"
                    , $id
                ));
                return FALSE;
            }
        }

        // Get the File Object
        $obj = $this->adapter->get($id);

        // Resize image
        if (isset($options['resize']) && isset($options['resize']['width'])) {
            // If we want height to be dynamic, set it to 0
            $options['resize']['height'] = isset($options['resize']['height']) ? $options['resize']['height'] : 0;

            // Set dimensions string to append
            $dimensionsStr = '__' . $options['resize']['width'] . '_' . $options['resize']['height'];

            // Resize file path
            $resizeFile = strstr($obj->getPublicUrl(), '.', TRUE) . $dimensionsStr . '.' . $obj->getExtension();
            if (!is_file($publicFolder . $resizeFile)) {
                // Generate file and folders
                if (!$this->generatePublicFile($resizeFile, $obj, $publicFolder)) {
                    return FALSE;
                } else {
                    $fPath = realpath($publicFolder . $resizeFile);
                    // Resize with ImageMagic
                    try {
                        $img = new \Imagick($fPath);
                    } catch (Exception $e) {
                        return FALSE;
                    }
                    if ($obj->getExtension() == 'gif') {
                        $img = $img->coalesceImages();

                        foreach ($img as $frame) {
                            $frame->thumbnailImage($options['resize']['width'], $options['resize']['height']);
                            $frame->setImagePage($options['resize']['width'], $options['resize']['height'], 0, 0);
                        }

                        $img = $img->deconstructImages();
                        $created = $img->writeImages($fPath, true);
                    } else {
//                        $img->setimagecompose(\Imagick::COMPOSITE_COPY);

                        // If you want to CROP image from the CENTER
                        if (isset($options['crop']) && $options['crop'] == true) {
                            $img->cropThumbnailImage($options['resize']['width'], $options['resize']['height']);
                        } else {
                            $img->thumbnailimage($options['resize']['width'], $options['resize']['height']);
//                            $img->thumbnailimage($options['resize']['width'], $options['resize']['height'], TRUE, TRUE);
                        }
//                        $img->resizeImage($options['resize']['width'], $options['resize']['height'], \Imagick::FILTER_LANCZOS, 1);

                        // Compression and remove unused data
                        $img->setImageCompression(\Imagick::COMPRESSION_JPEG);
                        $img->setImageCompressionQuality(75);
                        $img->stripImage();

                        // Round corners
                        if (isset($options['round'])) {
                            // Round corners only works on PNG files
                            $img->setImageFormat("png");

                            $xRounding = (isset($options['round']['x']) ? $options['round']['x'] : 5);
                            $yRounding = (isset($options['round']['y']) ? $options['round']['y'] : 3);
                            $img->roundCorners($xRounding, $yRounding);
                            $fPath = str_replace($obj->getExtension(), 'png', $fPath);
                        }

                        // Save Progressive image
                        $img->setInterlaceScheme(\Imagick::INTERLACE_PLANE);

                        // Add Watermark to the image
                        if (isset($this->config['rdn_upload_adapters']['apply_watermark'])) {
                            $this->addWatermark($img);
                        }

                        // Change Image orientation to correct one
                        $this->autoRotateImage($img);

                        $created = $img->writeImage($fPath);
                        $img->destroy();
                    }
                    if (!$created) {
                        throw new \InvalidArgumentException(sprintf(
                            "ImageMagic cannot write the image %s"
                            , $publicFolder . $resizeFile
                        ));
                    }
                }
            }

            // Return resized file
            $readyImage = str_replace($publicFolder, '', $resizeFile);
            if (isset($this->config['global']['media']['url'])) {
                return $this->config['global']['media']['url'] . ltrim($readyImage, '/');
            }
            return $readyImage;
        } else {
            if (!$this->generatePublicFile($obj->getPublicUrl(), $obj, $publicFolder)) {
                return FALSE;
            }
        }
        return $obj->getPublicUrl();
    }

    /**
     * Download File
     *
     * @param   string  $id
     * @return  File\File
     */
    public function download($id)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('ID cannot be empty');
        }

        $object = $this->adapter->get($id);
        $output = new File\File($object->getBasename(), $this->generateTempPath());

        $this->adapter->download($id, $output);

        return $output;
    }

    /**
     * Is file an image
     *
     * @param   string  $id
     * @return  bool
     */
    public function isImage($id)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('ID cannot be empty');
        }

        /** @var \RdnUpload\Adapter\ObjectInterface $obj */
        $obj = $this->adapter->get($id);

        $mimeType = mime_content_type($obj->getPath());

        if (strpos($mimeType, 'image') !== FALSE) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Has this ID
     *
     * @param   string  $id
     * @return  bool
     */
    public function has($id)
    {
        if (empty($id)) {
            return FALSE;
        }

        return $this->adapter->has($id);
    }

    /**
     * Delete file
     *
     * @param   string  $id
     * @param   bool    $deletePublic
     * @return  bool
     */
    public function delete($id, $deletePublic = FALSE)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('ID cannot be empty');
        }

        return $this->adapter->delete($id, $deletePublic);
    }

    /**
     * Generate public file and folders
     *
     * @param   string $publicFile
     * @param   object $obj
     * @param   string $publicFolder
     * @return  bool
     * @throws  \InvalidArgumentException
     */
    protected function generatePublicFile($publicFile, $obj, $publicFolder)
    {
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
                return FALSE;
            }
        }

        // Copy to public folder
        if (!copy($obj->getPath(), $publicFolder . $publicFile)) {
            throw new \InvalidArgumentException(sprintf(
                "Image file cannot be copied %s"
                , ($publicFolder . $publicFile)
            ));
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Auto Rotate an Image to the correct orientation
     *
     * @param \Imagick $image
     */
    protected function autoRotateImage(\Imagick &$image)
    {
        $orientation = $image->getImageOrientation();

        switch($orientation) {
            case \Imagick::ORIENTATION_BOTTOMRIGHT:
                $image->rotateimage("#000", 180); // rotate 180 degrees
                break;

            case \Imagick::ORIENTATION_RIGHTTOP:
                $image->rotateimage("#000", 90); // rotate 90 degrees CW
                break;

            case \Imagick::ORIENTATION_LEFTBOTTOM:
                $image->rotateimage("#000", -90); // rotate 90 degrees CCW
                break;
        }

        // Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image!
        $image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
    }

    /**
     * Add watermark to the image
     *
     * @param \Imagick $image
     * @throws \Exception
     */
    protected function addWatermark(\Imagick &$image)
    {
        // Not set watermark image, no game
        if (!isset($this->config['rdn_upload_adapters']['watermark_image'])) {
            throw new \Exception('In order to use watermark, please update your config file');
        }

        // Open the watermark
        $watermarkPath = $obj->getPath() . $this->config['rdn_upload_adapters']['watermark_image'];
        if (!file_exists($watermarkPath)) {
            throw new \Exception('Watermark image does not exists!');
        }

        $watermark = new \Imagick();
        //$obj->getPath() ?
        $watermark->readImage($watermarkPath);

        // how big are the images?
        $iWidth = $image->getImageWidth();
        $iHeight = $image->getImageHeight();
        $wWidth = $watermark->getImageWidth();
        $wHeight = $watermark->getImageHeight();

        if ($iHeight < $wHeight || $iWidth < $wWidth) {
            // resize the watermark
            $watermark->scaleImage($iWidth, $iHeight);

            // get new size
            $wWidth = $watermark->getImageWidth();
            $wHeight = $watermark->getImageHeight();
        }

        // calculate the position
        $x = ($iWidth - $wWidth) / 2;
        $y = ($iHeight - $wHeight) / 2;

        // Overlay the watermark on the original image
        $image->compositeImage($watermark, \Imagick::COMPOSITE_OVER, $x, $y);
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

        return mb_strtolower($filename . '.' . $extension);
    }
}
