<?php

/**
 * @todo Use ZendS3?
 */
class Job_BeamscUpload extends Omeka_Job_AbstractJob
{
    private $_item;

    // List of fileId of files to be uploaded.
    private $_files = array();

    private $_client_id = '';
    private $_client_secret = '';
    private $_redirect_uri = '';
    private $_access_token = '';

    private $_allowedMimeTypes = array(
        // From php-soundcloud library.
        'audio/mp4',
        'video/mp4',
        'audio/x-aiff',
        'audio/flac',
        'audio/mpeg',
        'audio/ogg',
        'audio/x-wav',
        // From http://help.soundcloud.com/customer/portal/articles/247477-what-formats-can-i-upload-
        'audio/alac',
        'audio/wav',
        'audio/mp2',
        'audio/mp3',
        'audio/aac',
        'audio/amr',
        'audio/wma',
    );

    public function perform()
    {
        $this->_client_id = get_option('beamsc_client_id');
        $this->_client_secret = get_option('beamsc_client_secret');
        $this->_redirect_uri = get_option('beamsc_redirect_uri');
        $this->_access_token = get_option('beamsc_access_token');

        $this->_item = get_record_by_id('item', $this->_options['itemId']); 
        $this->_files = $this->_options['files'];
        $files = $this->_item->getFiles();

        require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'php-soundcloud' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'Soundcloud.php';

        foreach ($files as $file) {
            // Check if file should be uploaded.
            if (in_array($file->id, $this->_files)) {
                if ($this->_check($file)) {
                    $this->_send($file);
                }
            }
        }
    }

    private function _check($file) 
    {
        // Check type of file.
        if (!in_array($file->mime_type, $this->_allowedMimeTypes)) {
            return false;
        }

        return true;
    }

    private function _send($file) 
    {
        // Create client object and set access token.
        $client = new Services_Soundcloud($this->_client_id, $this->_client_secret, $this->_redirect_uri);
        $client->setAccessToken($this->_access_token);

        // Upload file.
        $track = array(
            // Title is the original filename because there is no file element 
            // when we save an item. File metadata can be updated later.
            'track[title]' => $file->original_filename,
            'track[asset_data]' => '@' . FILES_DIR . '/original/' . $file->filename,
            'track[sharing]' => ($_POST['BeamscShareOnSoundCloud'] == '1') ? 'public' : 'private',
        );

        try {
            $response = $client->post('tracks', $track);
        } catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
            throw new Exception('Beam me up to Soundcloud error: ' . $e->getMessage());
        }

        $track = json_decode($response);
    }
}
