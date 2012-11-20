<style type="text/css">
    #steps a {
        color: #389;
        font-weight: bold;
    }
    #steps a:hover{
        cursor: pointer;
    }
</style>

<script type="text/javascript">
    var clientId;
    var clientSecret;
    var firstUri;
    var code;
    var redirectUri;

    jQuery(document).ready(function() {
        jQuery('input[type="submit"]').attr('disabled','disabled');
        redirectUri = '<?php echo get_option('beamsc_redirect_uri') ?>';
        if ('<?php echo get_option('beamsc_access_token') ?>' != '') {
            console.log('already have token');
            showDiv('loggedIn');
        }
        else if (getParameterByName('code') == '') {
            console.log('code is blank');
            showDiv('stepOne');
        }
        else {
            console.log('code is there');
            showDiv('stepTwo');
        }
    });

    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
        var regexS = "[\\?&]" + name + "=([^&#]*)";
        var regex = new RegExp(regexS);
        var results = regex.exec(window.location.search);
        if (results == null) {
            return "";
        }
        else {
            return decodeURIComponent(results[1].replace(/\+/g, " "));
        }
    }

    function showDiv(divId) {
        jQuery('#steps').children().hide();
        jQuery('#' + divId).show();
    }

    function getToken() {
        console.log('redirectUri: ' + redirectUri);

        var postBody = {
            'code': getParameterByName('code'),
            'client_id': jQuery('#clientIdTwo').val(),
            'client_secret': jQuery('#clientSecretTwo').val(),
            'redirect_uri': redirectUri,
            'grant_type': 'authorization_code'
        }

        console.log(postBody);

        jQuery.ajax('https://api.soundcloud.com/oauth2/token', {
            type: "POST",
            data: postBody,
            dataType: "json",
            error:function() {
                alert('Are you sure you entered the correct Client ID and Secret Key? If problems persist, please delete your SoundCloud App, uninstall this plugin, and start again.');
            },
            success:function(data) {
                console.log('Got token');
                console.log(data);
                jQuery('#accessToken').val(data.access_token);
                showDiv('stepThree');
                jQuery('input[type="submit"]').removeAttr('disabled');
            }
        });
    }

    function getCode() {
        //clear query
        window.location.search = '';

        //redirect to SoundCloud
        firstUri = 'https://soundcloud.com/connect?client_id=' + jQuery('#clientIdOne').val() + '&response_type=code&scope=non-expiring&display=popup&redirect_uri=' + document.URL;
        console.log(firstUri);
        window.location = firstUri;
    }
</script>

<div class="field">
    <input type="hidden" name="accessToken" id="accessToken">

    <div class "inputs" id="steps">
        <div id="loggedIn">
            <h3>You have logged in. Your token is <?php echo get_option('beamsc_access_token') ?></h3>
            <p>If you would like to log in with a different SoundCloud application, uninstall this plugin and reinstall it using the other application's credentials.</p>
        </div>

        <div id="stepOne">
            <h3>Step-By-Step Setup</h3>
            <p>You must have a SoundCloud account to use this plugin. If you do not have one, sign up for one <a href= "http://soundcloud.com">here</a>.</p>
            <br/>
            <h4>Step 1: Go to your <a href="http://soundcloud.com/you/apps" target="_blank">SoundCloud applications page</a>.</h4>
            <h4>Step 2: Select the "Register a new application" button.</h4>
            <h4>Step 3: Name the app something recognizable (e.g., YourOrganizationNameOmeka), and click "Register".</h4>
            <h4>Step 4: In the "Return URI" field, enter the address of the current page:</h4>
            <div><pre></per><?php echo get_option('beamsc_redirect_uri'); ?></pre></div>
            <h4>Step 5: Click the "Save app" button on the SoundCloud page.</h4>
            <h4 id="setOptionField">Step 6: Enter your Client ID here:
                <input type="text" name="clientIdOne" id="clientIdOne" size='35'>
            </h4>
            <h4>Step 7: Log onto SoundCloud:</h4>
            <a onClick="getCode()"><img src="<?php echo WEB_PLUGIN ?>/BeamMeUpToSoundCloud/libraries/SoundCloud/btn-connect-sc-l.png" alt="Click Here" /></a>
            <br/>
            <p>Don't worry. You will be able to save after you follow all the steps.</p>
        </div>

        <div id="stepTwo">
            <h3>Step-By-Step Setup (cont.)</h3>
            <h4>Step 8: Enter your Client ID again here:
                <input type="text" name="clientIdTwo" id="clientIdTwo" size='35'>
            </h4>
            <h4>Step 9: Enter your Client Secret here:
                <input type="text" name="clientSecretTwo" id="clientSecretTwo" size='35'>
            </h4>
            <h4>Step 10: Click <b><a onClick="getToken()">here</a></b>.</h4>
            <br/>
            <p>Don't worry. You will be able to save after you follow all the steps.</p>
        </div>

        <div id="stepThree">
            <h3>Step-By-Step Setup (cont.)</h3>
            <h4>Step 11: Setup your default upload options:</h4>
            <span><b>Upload to SoundCloud by default</b></span>
            <input type="hidden" name="BeamscPostToSoundCloud" value="0">
            <input type="checkbox" name="BeamscPostToSoundCloud" id="BeamscPostToSoundCloud" value="1" <?php if(get_option('beamsc_post_to_soundcloud') == '1') {echo 'checked';} ?>/>
            <div>You can change this option on a per-item basis.</div>
            <br/>
            <span><b>Make public on SoundCloud by default</b></span>
            <input type="hidden" name="BeamscShareOnSoundCloud" value="0">
            <input type="checkbox" name="BeamscShareOnSoundCloud" id="BeamscShareOnSoundCloud" value="1" <?php if(get_option('beamsc_share_on_soundcloud') == '1') {echo 'checked';} ?>/>
            <div>You can change this option on a per-item basis.</div>
            <br/>
            <h4>Step 12: Hit the "Save Changes" button!</h4>
        </div>
    </div>
</div>
