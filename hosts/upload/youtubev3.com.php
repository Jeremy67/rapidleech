<?php
/////////////////////////////////////////////////
$not_done = true;
$continue_up = false;

$categories = Array('22' => 'People & Blogs',
            '1' => 'Film & Animation',
            '2' => 'Autos & Vehicles',
            '10' => 'Music',
            '15' => 'Pets & Animals',
            '17' => 'Sports',
            '19' => 'Travel & Events',
            '20' => 'Gaming',
            '23' => 'Comedy',
            '24' => 'Entertainment',
            '27' => 'Education',
            '28' => 'Science & Technology');

if ($is_authenticate)
{
    if ($_REQUEST['action'] == "FORM")
    {
	    $continue_up = true;
    }
    else
    {
        echo "<table border='0' style='width:270px;' cellspacing='0' align='center'>
	    <form method='POST'>
	    <input type='hidden' name='action' value='FORM' />";
        echo "<tr><td colspan='2' align='center'><br />Video options *<br /><br /></td></tr>
	    <tr><td style='white-space:nowrap;'>Title:</td><td>&nbsp;<input type='text' name='up_title' value='$lname' style='width:160px;' /></td></tr>
	    <tr><td style='white-space:nowrap;'>Description:</td><br /><td><textarea rows='5' name='up_description' style='width:160px;'></textarea></td></tr>
	    <tr><td style='white-space:nowrap;'>Tags: </td><td>&nbsp;<input type='text' name='up_tags' value='' style='width:160px;' /></td></tr>
	    <tr><td style='white-space:nowrap;'>Category:</td><td>&nbsp;<select name='up_category' style='width:160px;height:20px;'>\n";
        foreach($categories as $n => $v)
        {
            echo "\t<option value='$n' ";
            if($v == "Gaming") echo "selected='selected'";
            echo ">$v</option>\n";
        }
        echo "</select></td></tr>\n";
        echo "<tr><td style='white-space:nowrap;'>Privacy:</td><td>&nbsp;<select name='up_access' style='width:8em;height:20px;'><option value='public'>Public</option><option value='unlisted'>Unlisted</option><option value='private' selected='selected'>Private</option></select></td></tr>";
        echo "<tr><td colspan='2' align='center'><br /><small>By clicking 'Upload', you certify that you own all rights to the content or that you are authorized by the owner to make the content publicly available on YouTube, and that it otherwise complies with the YouTube Terms of Service located at <a href='http://www.youtube.com/t/terms' target='_blank'>http://www.youtube.com/t/terms</a></small><br /><br /><input type='submit' value='Upload' /></td></tr>\n";
        //echo "<tr><td colspan='2' align='center'><small>*You can set it as default in <b>{$page_upload["youtubev3.com"]}</b></small></td></tr>\n";
        echo "</table>\n</form>\n";
        echo "<script type='text/javascript'>self.resizeTo(700,580);</script>\n"; //Resize upload window
    }

    if ($continue_up)
    {
        $not_done = false;       
	    $vtitle = trim($_REQUEST['up_title']);
	    $vtitle = empty($vtitle) ? $lname : rtags($vtitle);
	    $vdescription = trim($_REQUEST['up_description']);
	    $vdescription = !empty($vdescription) ? rtags($vdescription) : '';
	    $vtags = trim($_REQUEST['up_tags']);
	    if (!empty($vtags)) {
		    $vtag = explode(',', $vtags);
		    $vtags = '';
		    foreach ($vtag as $tag) {
			    $vtags .= rtags($tag, '') . ', ';
		    }
		    $vtags = substr($vtags, 0, -2);
	    }
	    if (array_key_exists($_REQUEST['up_category'], $categories)) $vcategory = $_REQUEST['up_category'];
	    else $vcategory = "20";

        // Uploading
	    echo "<script>document.getElementById('info').style.display='none';</script>\n";

        try
        {
            // REPLACE this value with the path to the file you are uploading.
            $videoPath = $lfile;

            // Create a snippet with title, description, tags and category ID
            // Create an asset resource and set its snippet metadata and type.
            // This example sets the video's title, description, keyword tags, and
            // video category.
            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($vtitle);
            $snippet->setDescription($vdescription);
            $snippet->setTags($vtags);

            // Numeric video category. See
            // https://developers.google.com/youtube/v3/docs/videoCategories/list
            $snippet->setCategoryId($vcategory);

            // Set the video's status to "public". Valid statuses are "public",
            // "private" and "unlisted".
            $status = new Google_Service_YouTube_VideoStatus();
            $status->privacyStatus = "private";

            // Associate the snippet and status objects with a new video resource.
            $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            // Specify the size of each chunk of data, in bytes. Set a higher value for
            // reliable connection as fewer chunks lead to faster uploads. Set a lower
            // value for better recovery on less reliable connections.
            $chunkSizeBytes = 1 * 1024 * 1024;

            // Setting the defer flag to true tells the client to return a request which can be called
            // with ->execute(); instead of making the API call immediately.
            $client->setDefer(true);

            // Create a request for the API's videos.insert method to create and upload the video.
            $insertRequest = $youtube->videos->insert("status,snippet", $video);

            // Create a MediaFileUpload object for resumable uploads.
            $media = new Google_Http_MediaFileUpload(
                $client,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($videoPath));


            // Read the media file and upload it chunk by chunk.
            $status = false;
            $handle = fopen($videoPath, "rb");
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }

            fclose($handle);

            // If you want to make other calls after the file upload, set setDefer back to false
            $client->setDefer(false);

            $download_link = 'http://www.youtube.com/watch?v=' . $status['id'];
            echo "<script>document.getElementById('progressblock').style.display='none';</script>";

        }
        catch (Google_Service_Exception $e) {
            $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));
        }
        catch (Google_Exception $e) {
            $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));
        }
    }
}
else
{
    $authUrl = $client->createAuthUrl();

    echo <<<EOF
     <h3>Authorization Required</h3>
     <p>You need to <a href="$authUrl">authorise access</a> before proceeding.<p>
EOF;
}


//sslcurl function moved to http.php
// written by kaox 26/05/09
//updated by szalinski 12-Oct-2010
?>