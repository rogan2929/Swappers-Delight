<?php

header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

require_once 'facebook.php';

if (!session_id()) {
    session_start();
}

/**
 * Wrapper for the Facebook object.
 */
class GraphApiClient {

    /** Constants * */
    // Prod
    //const APP_ID = '1401018793479333';
    //const APP_SECRET = '603325411a953e21ccbc29d2c7d50e7e';
    // Test
    const APP_ID = '652991661414427';
    const APP_SECRET = 'b8447ce73d2dcfccde6e30931cfb0a90';

    private $facebook;
    private $appSecretProof;

    function __construct() {

        $this->facebook = new Facebook(array(
            'appId' => self::APP_ID,
            'secret' => self::APP_SECRET,
            'cookie' => true
        ));

        // Look up an existing access token, if need be.
        if ($this->facebook->getAccessToken() === null) {
            $this->facebook->setAccessToken($_SESSION['accessToken']);
        } else {
            $_SESSION['accessToken'] = $this->facebook->getAccessToken();
        }

        // Set the AppSecretProof, which is required for FB api calls.
        $this->appSecretProof = hash_hmac('sha256', $this->facebook->getAccessToken(), self::APP_SECRET);

        // Test the facebook object that was created successfully.
        $this->api('/me', 'GET');
    }

    /**
     * A wrapper for $facebook->api. Error handling is built in.
     * @return object
     */
    public function api(/* polymorphic */) {
        $args = func_get_args();

        if (is_array($args[0])) {
            // Array was passed as an argument.
            $args[0]['appsecret_proof'] = $this->appSecretProof;
        } else {
            // Array was not passed as an argument.
            if (is_array($args[1]) && empty($args[2])) {
                $args[2] = $args[1];
                $args[1] = 'GET';
            }

            // Insert appsecret_proof into each API call.
            $args[2]['appsecret_proof'] = $this->appSecretProof;
        }

        try {
            // Call the facebook->api function.
            return call_user_func_array(array($this->facebook, 'api'), $args);
        } catch (FacebookApiException $ex) {
            // https://developers.facebook.com/docs/graph-api/using-graph-api/#errors
            // Set a 400 response code and then exit with the FB exception message.
            http_response_code(400);

            die($ex->getMessage());
        }
    }

    /**
     * Returns the UID of the currently logged in user.
     * @return string
     */
    public function getMe() {
        return $this->api('/me')['id'];
    }

    /**
     * Delete a Facebook Object.
     * @param type $id
     */
    public function deleteObject($id) {
        $this->api('/' . $id, 'DELETE');
    }

    /**
     * Like a post.
     * @param type $postId
     * @param type $userLikes
     * @return type
     */
    public function likePost($postId, $userLikes) {
        if ($userLikes == true) {
            // Like the post.
            $this->api('/' . $postId . '/likes', 'POST', array('user_likes' => true));
        } else {
            // Delete the post's like.
            $this->api('/' . $postId . '/likes', 'DELETE');
        }

//        // Update the cached post stream.
//        for ($i = 0; $i < count($this->stream); $i++) {
//            if ($this->stream[$i]['post_id'] == $postId) {
//                $this->stream[$i]['user_likes'] = (int) $userLikes;
//            }
//        }
//
//        // Save the updated stream.
//        $_SESSION['stream'] = $this->stream;

        return $userLikes;
    }

    /**
     * Create a new post.
     */
    public function newPost() {
        
    }

    /**
     * Post a comment on a post.
     * @param type $postId
     * @param type $comment
     * @return type
     */
    public function postComment($postId, $comment) {
        // Post the comment and get the response
        $id = $this->api('/' . $postId . '/comments', 'POST', array('message' => $comment));

        // Get the comment and associated user data...
        $queries = array(
            'commentQuery' => 'SELECT fromid,text,text_tags,attachment,time,id FROM comment WHERE id=' . $id['id'],
            'commentUserQuery' => 'SELECT uid,last_name,first_name,pic_square,profile_url FROM user WHERE uid IN (SELECT fromid FROM #commentQuery)'
        );

        // Query Facebook's servers for the necessary data.
        $response = $this->api(array(
            'method' => 'fql.multiquery',
            'queries' => $queries
        ));

        // Construct a return object.
        $newComment = $response[0]['fql_result_set'][0];
        $newComment['user'] = $response[1]['fql_result_set'][0];

        // Replace any line breaks with <br/>
        if ($newComment['text']) {
            $newComment['text'] = nl2br($newComment['text']);
        }

        return $newComment;
    }

}

/**
 * Class for managing a user's saved group data.
 */
class GroupManager {

    private $sqlConnectionInfo;
    private $sqlServer;
    private $graphApiClient;

    function __construct() {
        $this->sqlConnectionInfo = array("UID" => "rogan2929@lreuagtc6u", "pwd" => "Revelation19:11", "Database" => "swapperAGiJRLgvy", "LoginTimeout" => 30, "Encrypt" => 1);
        $this->sqlServer = "tcp:lreuagtc6u.database.windows.net,1433";

        $this->graphApiClient = new GraphApiClient();
    }

    /**
     * Look up group membership information for the current user.
     * @return array
     */
    public function getGroupInfo() {
        $queries = array(
            'memberQuery' => 'SELECT gid,bookmark_order FROM group_member WHERE uid=me() ORDER BY bookmark_order',
            'groupQuery' => 'SELECT gid,name,icon FROM group WHERE gid IN (SELECT gid FROM #memberQuery)'
        );

        $response = $this->graphApiClient->api(array(
            'method' => 'fql.multiquery',
            'queries' => $queries
        ));

        // Grab the results of the query and return it.
        return $response[1] ['fql_result_set'];
    }

    /**
     * Queries the Swapper's Delight SQL backend for groups that the user has marked as 'hidden'.
     * @return string
     */
    public function getHiddenGroups() {
        $uid = $this->graphApiClient->getMe();

        $conn = sqlsrv_connect($this->sqlServer, $this->sqlConnectionInfo);

        if ($conn === false) {
            die(print('Could not connect to database.'));
        }

        $sql = 'SELECT HiddenGroup FROM HiddenGroups WHERE UID=\'' . $uid . '\'';

        // Execute the query.
        $result = sqlsrv_query($conn, $sql);

        $hiddenGroups = '';

        while ($row = sqlsrv_fetch_array($result)) {
            $hiddenGroups .= $row['HiddenGroup'] . ' ';
        }

        return $hiddenGroups;
    }

    /**
     * Mark the group with provided gid as hidden.
     * @param type $gid
     */
    public function hideGroup($gid) {
        $uid = $this->graphApiClient->getMe();

        $conn = sqlsrv_connect($this->sqlServer, $this->sqlConnectionInfo);

        if ($conn === false) {
            die(print('Could not connect to database.'));
        }

        $sql = 'INSERT INTO HiddenGroups (UID, HiddenGroup) VALUES (\'' . $uid . '\', \'' . $gid . '\')';

        // Execute the query.
        sqlsrv_query($conn, $sql);
    }

    /**
     * Removes all of the current user's hidden groups from the Swapper's Delight backend.
     */
    public function restoreGroups() {
        $uid = $this->graphApiClient->getMe();

        $conn = sqlsrv_connect($this->sqlServer, $this->sqlConnectionInfo);

        if ($conn === false) {
            die(print('Could not connect to database.'));
        }

        $sql = 'DELETE FROM HiddenGroups WHERE UID=\'' . $uid . '\'';

        // Execute the query.
        sqlsrv_query($conn, $sql);
    }

}

/**
 * Class that manages the cached version of the Facebook group's feed.
 */
class CachedFeed {

    private $graphApiClient;
    private $gid;
    private $stream;

    function __construct() {

        // Retrieve the stream if it's there.
        if (isset($_SESSION['stream'])) {
            $this->stream = $_SESSION['stream'];
        } else {
            $this->stream = null;
        }

        $this->graphApiClient = new GraphApiClient();
    }

    /**
     * Set the currently loaded group's gid.
     * @param type $gid
     */
    public function setGid($gid) {
        $this->gid = $gid;
    }

    /**
     * Getter for $this->stream.
     * @return type
     */
    public function getStream() {
        return $this->stream;
    }

    /**
     * Retrieve posts that are liked by the current user.
     * @param type $offset
     * @param type $limit
     * @return type
     */
    public function getLikedPosts($offset, $limit) {
        $posts = array();

        $this->waitForFetchStreamCompletion();

        // Look through the cached stream for liked posts.
        for ($i = 0; $i < count($this->stream); $i++) {
            if ($this->stream[$i]['user_likes'] == 1) {
                $posts[] = $this->stream[$i];
            }
        }

        return $this->getPostData($posts, $offset, $limit);
    }

    /**
     * Retrieves posts owned by the current user.
     * @param type $offset
     * @param type $limit
     * @return type
     */
    public function getMyPosts($offset, $limit) {
        $uid = $this->graphApiClient->getMe();
        $posts = array();

        $this->waitForFetchStreamCompletion();

        // Look through the cached stream, match by uid => actor_id
        for ($i = 0; $i < count($this->stream); $i++) {
            if ($this->stream[$i]['actor_id'] == $uid) {
                $posts[] = $this->stream[$i];
            }
        }

        return $this->getPostData($posts, $offset, $limit);
    }

    /**
     * Retrieves posts newly created in the specified group. Optionally can trigger a refresh of the stream cache.
     * @param type $refresh
     * @param type $offset
     * @param type $limit
     * @return type
     */
    public function getNewPosts($refresh, $offset, $limit) {
        // Get a new stream if necessary.
        if ($refresh == 1) {
            $this->fetchStream($refresh);
        }

        return $this->getPostData($this->stream, $offset, $limit);
    }

    /**
     * Retrieves additional data for the given post id.
     * @param type $postId
     * @return type
     * @throws Exception
     */
    public function getPostDetails($postId) {
        $queries = array(
            'detailsQuery' => 'SELECT post_id,message,actor_id,permalink,like_info,share_info,comment_info,tagged_ids,attachment,created_time FROM stream WHERE post_id="' . $postId . '"',
            'imageQuery' => 'SELECT object_id,images FROM photo WHERE object_id IN (SELECT attachment FROM #detailsQuery)',
            'userQuery' => 'SELECT last_name,first_name,pic,profile_url FROM user WHERE uid IN (SELECT actor_id FROM #detailsQuery)',
            'commentsQuery' => 'SELECT fromid,text,text_tags,attachment,time,id FROM comment WHERE post_id IN (SELECT post_id FROM #detailsQuery) ORDER BY time ASC',
            'commentUserQuery' => 'SELECT uid,last_name,first_name,pic_square,profile_url FROM user WHERE uid IN (SELECT fromid FROM #commentsQuery)'
        );

        // Run the query.
        $response = $this->graphApiClient->api(array(
            'method' => 'fql.multiquery',
            'queries' => $queries
        ));

        // Check to ensure that post data was actually returned.
        // This is done by checking for post_id in the fql_result_set.
        if (!isset($response[0]['fql_result_set'][0]['post_id'])) {
            $e = new Exception('Sorry, but this post couldn\'t be loaded. It may have been deleted.');
            echo $e->getMessage();
            throw $e;
        }

        try {
            // Begin parsing the returned data.
            $post = $response[0]['fql_result_set'][0];
            $post['comments'] = $response[1]['fql_result_set'];
            $images = $response[2]['fql_result_set'];
            $post['user'] = $response[3]['fql_result_set'][0];
        } catch (Exception $ex) {
            echo 'Sorry, but this post couldn\'t be loaded. It may have been deleted.';
            http_response_code(500);
        }

        if (strlen($post['message']) > 0) {
            // Replace new line characters with <br/>
            $post['message'] = nl2br($post['message']);
        }

        // Extract image data for the post.
        $post['image_url'] = $this->getImageUrlArray($post, $images, false);

        // Extract link data.
        $post['link_data'] = $this->getLinkData($post);

        // Determine type of post.
        $post['post_type'] = $this->getPostType($post);

        // Erase attachment data (to make the object smaller), since this has already been parse.
        unset($post['attachment']);

        // Begin parsing comment data.
        for ($i = 0; $i < count($post['comments']); $i++) {
            // Replace any line breaks with <br/>
            if ($post['comments'][$i]['text']) {
                $post['comments'][$i]['text'] = nl2br($post['comments'][$i]['text']);
            }

            // Set image urls.
            $post['comments'][$i]['image_url'] = array();

            if ($post['comments'][$i]['attachment'] && $post['comments'][$i]['attachment']['media']) {
                //echo var_dump($post['comments'][$i]['attachment']['media']['image']) . "<br/>";
                $post['comments'][$i]['image_url'][] = $post['comments'][$i]['attachment']['media']['image']['src'];
            }

            unset($post['comments'][$i]['attachment']);

            // For each comment, attach user data to it.
            for ($j = 0; $j < count($response[4]['fql_result_set']); $j++) {
                $userDataObject = $response[4]['fql_result_set'][$j];

                // See if the comment is from the user.
                if ($post['comments'][$i]['fromid'] == $userDataObject['uid']) {
                    $post['comments'][$i]['user'] = $userDataObject;
                    break;
                }
            }
        }

        return $post;
    }

    /**
     * Retrieves refreshed stream data for the given post ids.
     * @param type $postIds
     */
    public function getRefreshedStreamData($postIds) {
        $posts = array();

        for ($i = 0; $i < count($postIds); $i++) {
            for ($j = 0; $j < count($this->stream); $j++) {
                if ($postIds[$i] == $this->stream[$j]['post_id']) {
                    $posts[] = $this->stream[$j];
                }
            }
        }

        // Return the updated stream data.
        return $posts;
    }

    /**
     * Performs an update the local stream cache.
     * @return boolean
     */
    public function refreshStream($gid) {
        $this->gid = $gid;

        // Fetch the new stream.
        $this->fetchStream(false);

        return count($this->stream);
    }

    /**
     * Search the stream cache for posts that match the search string. Search is based on message and poster's name.
     * @param type $search
     * @param type $offset
     * @param type $limit
     * @return type
     */
    public function searchPosts($search, $offset, $limit) {
        $posts = array();

        $this->waitForFetchStreamCompletion();

        // Look through the cached stream for posts whose message or user matches the search term.
        for ($i = 0; $i < count($this->stream); $i++) {
            if (stripos($this->stream[$i]['message'], $search) !== false || stripos($this->stream[$i]['actor_name'], $search) !== false) {
                $posts[] = $this->stream[$i];
            }
        }

        return $this->getPostData($posts, $offset, $limit);
    }

    /** Private Methods * */

    /**
     * Fetches the group's post stream.
     */
    private function fetchStream($prefetchOnly) {
        // Wait for other threads to finish updating the cached FQL stream.
        $this->waitForFetchStreamCompletion();

        // Refresh the FQL stream.
        $_SESSION['refreshing'] = true;
        $_SESSION['stream'] = $this->queryStream($prefetchOnly);
        $_SESSION['refreshing'] = false;

        $this->stream = $_SESSION['stream'];
    }

    /**
     * Determine the optimal window size to use in batch queries.
     */
    public function getOptimalWindowSize() {
        $startTime = time();
        $endTime = time() - 3600;

        $request = '/' . $this->gid . '/feed?fields=id&since=' . $endTime . '&until=' . $startTime . ' LIMIT 100';

        $response = $this->graphApiClient->api($request);

        $count = count($response);

        // These values were reached through trial and error.
        switch ($count) {
            case $count < 65:
                $windowSize = 4;
                break;
            case $count >= 65 && $count < 85:
                $windowSize = 3.5;
                break;
            case $count >= 85 && $count < 115:
                $windowSize = 3.5;
                break;
            case $count >= 115 && $count < 150:
                $windowSize = 2.5;
                break;
            case $count >= 150 && $count < 225:
                $windowSize = 2;
                break;
            default:
                $windowSize = 1;
                break;
        }

        return 3600 * $windowSize;
    }

    /*
     * Retrieve additional data for the posts in the provided array.
     */

    private function getPostData($posts, $offset, $limit) {
        $queries = array();
        $result = array();

        if (!isset($limit)) {
            $limit = 50;        // Max batch size.
        }

        // Slice and dice the array.
        $page = array_slice($posts, $offset, $limit);

        // Build a multiquery for each post in the provided array.
        // If count($page) > 50, then it has to be broken up, since the maximum batch size is only 50.
        for ($i = 0; $i < count($page); $i++) {
            $queries[] = array(
                'method' => 'POST',
                'relative_url' => 'method/fql.multiquery?queries=' . json_encode(array(
                    'streamQuery' => 'SELECT post_id,actor_id,updated_time,message,attachment,comment_info,created_time FROM stream WHERE post_id="' . $page[$i]['post_id'] . '"',
                    'imageQuery' => 'SELECT object_id,images FROM photo WHERE object_id IN (SELECT attachment FROM #streamQuery)',
                    'userQuery' => 'SELECT uid,last_name,first_name,pic_square,profile_url,pic FROM user WHERE uid IN (SELECT actor_id FROM #streamQuery)'
                ))
            );
        }

        $processed = 0;

        // Execute the batch queries in chunks of 50.
        while ($processed < count($page)) {
            $response = $this->graphApiClient->api('/', 'POST', array(
                'batch' => json_encode(array_slice($queries, $processed, 50)),
                'include_headers' => false
            ));

            // Sift through the results.
            for ($i = 0; $i < count($response); $i++) {
                $body = json_decode($response[$i]['body'], true);
                $result = array_merge($result, $this->processStreamQuery($body[0]['fql_result_set'], $body[1]['fql_result_set'], $body[2]['fql_result_set']));
            }

            $processed += count($response);
        }

        // If there are no posts to load, then insert an terminating post.
        if ($offset + $limit >= count($posts)) {
            $result[] = array('post_id' => 'terminator');
        }

        return $result;
    }

    /*
     * Take a response and construct post objects out of it.
     */

    private function processStreamQuery($stream, $images, $users) {
        $posts = array();

        for ($i = 0; $i < count($stream); $i++) {
            $post = $stream[$i];

            // Parse associated data from the query.
            $post['image_url'] = $this->getImageUrlArray($post, $images, true);
            $post['link_data'] = $this->getLinkData($post);
            $post['user'] = $this->getUserData($post, $users);

            // Erase any attachment data to save on object size.
            // This has already been parsed out.
            unset($post['attachment']);

            $post['post_type'] = $this->getPostType($post);

            // Determine which kind of post this is.
            // Replace any line breaks with <br/>
            if (strlen($post['message']) > 0) {
                $post['message'] = nl2br($post['message']);
            }

            // Add to the posts array.
            $posts[] = $post;
        }

        return $posts;
    }

    /*
     * For posts with an image, look for associated image data.
     */

    private function getImageUrlArray($post, $images, $thumbnails = true) {
        $imageUrls = array();

        if ($post['attachment'] && $post['attachment']['media']) {
            // For posts with an image, look for associated image data.
            for ($i = 0; $i < count($post['attachment']); $i++) {
                if ($post['attachment']['media'][$i]) {
                    // Determine if this attachment is a photo or a link.
                    if ($post['attachment']['media'][$i]['type'] == 'photo' && $post['attachment']['media'][$i]['photo']) {
                        // Get image's unique Facebook Id
                        $fbid = $post['attachment']['media'][$i]['photo']['fbid'];

                        // Find the image url from the given Facebook ID
                        $imageUrls[] = $this->getImageUrlFromFbId($fbid, $images, $thumbnails);
                    }
                }
            }
        }

        return $imageUrls;
    }

    /*     * *
     * Function to parse FQL attachment data for links.
     */

    private function getLinkData($post) {
        $linkData = array();

        // Loop through media attachments, looking for type 'link'.
        if ($post['attachment'] && $post['attachment']['media'] && $post['attachment']['media'][0] &&
                $post['attachment']['media'][0]['type'] == 'link') {
            $linkData = $post['attachment'];
        }

        return $linkData;
    }

    /*
     * Function to parse FQL user data.
     */

    private function getUserData($post, $users) {
        $user = array();

        for ($i = 0; $i < count($users); $i++) {
            if ($post['actor_id'] == $users[$i]['uid']) {
                $user = $users[$i];
            }
        }

        return $user;
    }

    private function getImageUrlFromFbId($fbid, $images, $thumbnails = true) {
        $imageUrl = null;

        for ($i = 0; $i < count($images); $i++) {
            if ($fbid == $images[$i]['object_id']) {
                // See if we are trying to retrieve a small image. (Usually last in the array.)
                if ($thumbnails) {
                    $imageUrl = $this->getSmallImageUrl($images[$i]['images']);
                } else {
                    //$imageUrl = $images[$i]['images'][$index]['source'];
                    $imageUrl = $this->getLargeImageUrl($images[$i]['images']);
                }


                break;
            }
        }

        return $imageUrl;
    }

    /*
     * Determines the post type:
     *  1. Image Posts (text and non-text, doesn't matter.) ('image')
     *  2. Text Only Posts ('text')
     *  3. Link Only Posts ('link')
     *  4. Link + Text Posts ('textlink')
     */

    private function getPostType($post) {
        $postType = 'unknown';

        // The logic below should catch everything. If it does, then we have for some reason picked up a post with no visible content.
        if (count($post['image_url']) > 0) {
            $postType = 'image';       // Image Post
        } else if (strlen($post['message']) > 0) {
            $postType = 'text';        // Assume text post, but this might change to link.
        }

        if (strlen($post['message']) == 0 && count($post['link_data']) > 0) {
            $postType = 'link';        // Link post.
        }

        if (strlen($post['message']) > 0 && count($post['link_data']) > 0) {
            $postType = 'textlink';    // Link + Text post.
        }

        return $postType;
    }

    /*     * *
     * In an array, find the largest Facebook image.
     */

    private function getLargeImageUrl($image) {
        return $image[0]['source'];
    }

    /*     * *
     * In an array, find the smallest Facebook image.
     */

    private function getSmallImageUrl($image) {
        // Grab the 'middle' image for a scaled version of the full size image.
        $index = intval(floor((count($image) / 2)));

        // Try to ensure a minimum width. If it is too small, then proceed to the next largest
        // image in the image collection. (0 being the largest).
        do {
            $imageSize = getimagesize($image[$index]['source']);
            $index--;

            if ($index < 0) {
                $index = 0;
                break;
            }
        } while ($imageSize[0] < 250 && $imageSize[1] < 150);

        return $image[$index]['source'];
    }

    /*
     * Query the FQL stream table for some basic data that will be cached.
     */

    private function queryStream($prefetchOnly) {
        $windowStart = time();
        $windowSize = $this->getOptimalWindowSize();

        // Check to see if this this is only prefetching the stream data.
        if ($prefetchOnly) {
            $stream = $this->getFeedData($windowSize, $windowStart, 14, 1);
            $windowStart = $windowStart - ($windowSize * 14 * 1);
            $stream = array_merge($stream, $this->getFeedData(3600 * 24 * 30, $windowStart, 1, 1));
        } else {
            // TODO: Somehow offload this onto a background thread.
            $stream = $dal->getFeedData($windowSize, $windowStart, 50, 1);
            $windowStart = $windowStart - ($windowSize * 50 * 1);
            $stream = array_merge($stream, $dal->getFeedData($windowSize * 2, $windowStart, 13, 1));
            $windowStart = $windowStart - ($windowSize * 2 * 13 * 1);
            $stream = array_merge($stream, $dal->getFeedData($windowSize * 3, $windowStart, 11, 1));
            $windowStart = $windowStart - ($windowSize * 3 * 11 * 1);
            $stream = array_merge($stream, $dal->getFeedData(3600 * 24 * 30, $windowStart, 1, 1));
        }

        return $stream;
    }

    /**
     * Execute a batch request against the selected group's feed.
     * 
     * @param type $windowSize
     * @param type $windowStart
     * @param type $batchSize
     * @param type $iterations
     * @return array
     */
    public function getFeedData($windowSize, $windowStart, $batchSize, $iterations = 1) {
        $windowEnd = $windowStart - $windowSize;

        $stream = array();
        $users = array();

        // Pull the feed for stream data.
        for ($i = 0; $i < $iterations; $i++) {
            $queries = array();

            for ($j = 0; $j < $batchSize; $j++) {
                //$query = '/' . $this->gid . '/feed?fields=id,message,from,likes,comments&limit=5000&since=' . $windowEnd . '&until=' . $windowStart;
                $query = array(
                    'streamQuery' => 'SELECT post_id,message,actor_id,like_info,comment_info FROM stream WHERE source_id=' . $this->gid . ' AND updated_time <= ' . $windowStart . ' AND updated_time >= ' . $windowEnd . ' LIMIT 5000',
                    'userQuery' => 'SELECT first_name,last_name,uid FROM user WHERE uid IN (SELECT actor_id FROM #streamQuery)'
                );

                $windowStart -= $windowSize;
                $windowEnd -= $windowSize;

                $queries[] = array(
                    'method' => 'POST',
                    'relative_url' => 'method/fql.multiquery?queries=' . json_encode($query)
                );
            }

            // Execute a batch query.
            $response = $this->graphApiClient->api('/', 'POST', array(
                'batch' => json_encode($queries),
                'include_headers' => false
            ));

            // Parse the response.
            for ($k = 0; $k < count($response); $k++) {
                $body = json_decode($response[$k]['body'], true);

                $stream = array_merge($stream, $body[0]['fql_result_set']);
                $users = array_merge($users, $body[1]['fql_result_set']);
            }
        }

        // Clean up the response a little bit for our own purposes.
        for ($i = 0; $i < count($stream); $i++) {
            if (isset($stream[$i]['comment_info'])) {
                $stream[$i]['comment_count'] = $stream[$i]['comment_info']['comment_count'];
                unset($stream[$i]['comment_info']);
            }

            if (isset($stream[$i]['like_info'])) {
                $stream[$i]['user_likes'] = (int) $stream[$i]['like_info']['user_likes'];
                unset($stream[$i]['like_info']);
            }

            for ($j = 0; $j < count($users); $j++) {
                if ($stream[$i]['actor_id'] == $users[$j]['uid']) {
                    $stream[$i]['actor_name'] = $users[$j]['first_name'] . ' ' . $users[$j]['last_name'];
                }
            }
        }

        return $stream;
    }

    /*
     * Forcibly pause the thread in order for fetchStream to complete.
     */

    private function waitForFetchStreamCompletion() {
        while ($_SESSION['refreshing'] == true) {
            sleep(3);
        }
    }

}
