<?php
/**
 * Posts items to SoundCloud as they are saved.
 *
 * @see README.md
 *
 * @copyright Daniel Berthereau for Pop Up Archive, 2012
 * @copyright Daniel Vizzini for Pop Up Archive, 2012
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package BeamMeUpToSoundCloud
 *
 * Contains SoundCloud library:
 * @see libraries/SoundCloud
 * @see https://github.com/mptre/php-soundcloud
 * 
 * @todo Add MVC implementation
 * @todo Check out array-to-XML parsers
 * @todo Check OAIPMH harverster plugin for code that loads status to db , see indexcontroller.php #jobdispatcher to get onto other thread 
 * @todo Look at paths.php for better way to get file path
 * @todo make jQuery in config_form.php work 
 * @todo bind jQuery to "Add Item" and "Save Changes" buttons to confirm upload 
 */

/**
 * Contains code used to integrate the plugin into Omeka.
 *
 * @package BeamMeUpToSoundCloud
 */

class BeamMeUpToSoundCloudPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'install',
        'uninstall',
        'upgrade',
        'config_form',
        'config',
        'after_save_item',
//        'admin_items_show_sidebar',
        'admin_items_form_files',
    );

    protected $_filters = array(
//        'admin_items_form_tabs',
    );

    protected $_options = array(
        'beamsc_post_to_soundcloud' => true,
        'beamsc_share_on_soundcloud' => true,
        'beamsc_client_id' => '',
        'beamsc_client_secret' => '',
        'beamsc_access_token' => '',
        'beamsc_redirect_uri' => '',
    );

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_installOptions();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Upgrades the plugin.
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];

        switch ($oldVersion) {
            case '0.1':
                set_option('beamsc_post_to_soundcloud', get_option('post_to_soundcloud_default_bool'));
                set_option('beamsc_share_on_soundcloud', get_option('soundcloud_public_default_bool'));
                set_option('beamsc_client_id', get_option('client_id'));
                set_option('beamsc_client_secret', get_option('client_secret'));
                set_option('beamsc_access_token', get_option('access_token'));
                set_option('beamsc_redirect_uri', get_option('redirect_uri'));

                delete_option('post_to_soundcloud_default_bool');
                delete_option('soundcloud_public_default_bool');
                delete_option('access_token');
                delete_option('client_id');
                delete_option('client_secret');
                delete_option('redirect_uri');
        }
    }

    /**
     * Displays configuration form.
     *
     * @return void
     */
    public function hookConfigForm()
    {
        // Redirect uri can't be updated (uninstall if you want to reset it).
        $pageUrl = 'http';

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $pageUrl .= 's';
        }

        $pageUrl .= '://';

        if ($_SERVER['SERVER_PORT'] != '80') {
            $pageUrl .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
        }
        else {
            $pageUrl .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        }

        $pageUrl = preg_replace('/\?([a-zA-Z0-9_]*)/i', '', $pageUrl);

        if (get_option('beamsc_redirect_uri') == '') {
            set_option('beamsc_redirect_uri', $pageUrl);
        }

        // Config form for other options.
        include('config_form.php');
    }

    /**
     * Saves plugin configuration.
     *
     * @return void
     */
    public function hookConfig()
    {
        set_option('beamsc_post_to_soundcloud', $_POST['BeamscPostToSoundCloud']);
        set_option('beamsc_share_on_soundcloud', $_POST['BeamscShareOnSoundCloud']);
        set_option('beamsc_client_id', $_POST['clientIdTwo']);
        set_option('beamsc_client_secret', $_POST['clientSecretTwo']);
        set_option('beamsc_access_token', $_POST['accessToken']);
    }

    /**
     * Post Files and metadata of an Omeka Item to SoundCloud.
     *
     * The process occurs only when all files are saved, at the end of the
     * creation of item.
     *
     * @return void
     */
    public function hookAfterSaveItem($args)
    {
        if ($_POST['BeamscPostToSoundCloud'] == '1') {
            $item = $args['record'];

            // Beam up files only if there are files. 
            if ($item->fileCount() > 0) {
                // Keep only primitive data types of files to be uploaded.
                $files = $item->getFiles();
                foreach ($files as $key => $file) {
                    $files[$key] = $file->id;
                }

                // Prepare and run job for this item.
                $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');
                // TODO Long running jobs needs php cli compiled with curl.
                // $jobDispatcher->setQueueNameLongRunning('beamsc_uploads');
                // $jobDispatcher->sendLongRunning('Job_BeamscUpload', array(
                $jobDispatcher->setQueueName('beamsc_uploads');
                $jobDispatcher->send('Job_BeamscUpload', array(
                    'itemId' => $item->id,
                    'files' => $files,
                ));
            }
        }
    }

    /**
     * Displays SoundCloud links in admin/show section.
     *
     * @return void
     */
    public function hookAdminItemsShowSidebar($args) {
        echo '<div class="info panel">';
        echo '<h4>Beam me up to SoundCloud</h4>';
        echo $this->_listSoundCloudLinks();
        echo '</div>';
    }

    /**
     * Gives user the option to post to SoundCloud.
     *
     * @return void
     */
    public function hookAdminItemsFormFiles($args) {
        echo '<div class="field">';
        echo   '<div id="BeamscPostToSoundCloud_label" class="one columns alpha">';
        echo     get_view()->formLabel('BeamscPostToSoundCloud', __('Upload to SoundCloud'));
        echo   '</div>';
        echo   '<div class="inputs">';
        echo     get_view()->formCheckbox('BeamscPostToSoundCloud', true, array('checked' => (boolean) get_option('beamsc_post_to_soundcloud')));
        echo     '<p class="explanation">';
        echo       __("Note that checking this box will increase the item's upload time.");
        echo     '</p>';
        echo   '</div>';
        echo '</div>';

        // TODO Must files be uniquely named? If, so warn here.

        echo '<div class="field">';
        echo   '<div id="BeamscShareOnSoundCloud_label" class="one columns alpha">';
        echo     get_view()->formLabel('BeamscShareOnSoundCloud', __('Make public on SoundCloud'));
        echo   '</div>';
        echo   '<div class="inputs">';
        echo     get_view()->formCheckbox('BeamscShareOnSoundCloud', true, array('checked' => (boolean) get_option('beamsc_share_on_soundcloud')));
        echo     '<p class="explanation">';
        echo       __("Your item will be available for share and it will appear in search engine results.");
        echo     '</p>';
        echo   '</div>';
        echo '</div>';
    }
}
