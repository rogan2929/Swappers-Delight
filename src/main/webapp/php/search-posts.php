<?php

require_once 'include/dal.php';

echo json_encode((new CachedFeed())->searchPosts(filter_input(INPUT_GET, 'search'), filter_input(INPUT_GET, 'offset'), filter_input(INPUT_GET, 'limit')));