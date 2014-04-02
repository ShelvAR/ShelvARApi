<?php
// request handler variables
error_log('front_controller: '.print_r($_GET,1));
$path       = $_GET['path'];
$path_arr   = explode('/', $path);

switch ($path_arr[0]) {
    case "book_tags":       handle_bt($path_arr); break;
    case "lc_numbers":      handle_lc($path_arr); break;
    case "book_pings":      handle_bp($path_arr); break;
    case "users":           handle_users($path_arr); break;
    case "institutions":    handle_inst($path_arr); break;
    case "make_tags":       handle_mt($path_arr); break;
    case "oauth":           handle_oauth($path_arr); break;
    case "notifications":   handle_notif($path_arr); break;
    default:                throw_error(404, "404 - not found"); break;
}

/*
    * Function to strip an extension from a string
    * $id is the string with the extension
    * $ext is the extension to strip from the string
    * $ext should include the "."
    * returns the string without the extension
    * (and everything following it), if it is
    * found, otherwise the original string
    */
function strip_ext($id, $ext) {
    // if no extension, return string
    if (!strrpos($id, $ext)) return $id;
    // return stripped string
    return substr($id, 0, strrpos($id, $ext));
}

function get_domain() {
    // use https if not on dev
    if (strpos($_SERVER['HTTP_HOST'], 'api.shelvar.com') === 0) {
        return 'https://'.$_SERVER['HTTP_HOST'].'/';
    } else {
        return 'http://'.$_SERVER['HTTP_HOST'].'/';
    }
}

/*
 * Function to redirect request to
 * appropriate file. Needed whenever
 * a request is made from the website
 * (front end)
 * because it was causing the OAuth
 * signature to be invalid
 */
function redir($uri_path) {
    $server = get_domain();
    header('Location: '.$server.$uri_path);
}

/*
 * ----------
 * book_tags/
 * ----------
 */
function handle_bt($path_arr) {
    $cnt    = count($path_arr);
    $method = $_SERVER['REQUEST_METHOD'];
    $root   = $_SERVER['DOCUMENT_ROOT'].'/';

    include $_SERVER['DOCUMENT_ROOT'].'/path_vars_api.php';

    if ($cnt === 2) { // valid request
        // GET book_tags/{id}
        if ($method === "GET") { 
            $_GET['B64'] = strip_ext($path_arr[1], ".json");
            include $root.$get_book_tags;
        } else {
            throw_error(405, "405 - method not allowed");
        }
    } else {
        throw_error(404, "404 - not found");
    }
}

/*
 * -----------
 * lc_numbers/
 * -----------
 */
function handle_lc($path_arr) {
    $cnt    = count($path_arr);
    $method = $_SERVER['REQUEST_METHOD'];
    $root   = $_SERVER['DOCUMENT_ROOT'].'/';

    include $_SERVER['DOCUMENT_ROOT'].'/path_vars_api.php';

    if ($cnt === 2) { // valid request
        // GET lc_numbers/{call_number}
        if ($method === "GET") { 
            $_GET['call_number'] = strip_ext($path_arr[1], ".json");
            include $root.$get_lc_numbers;
        } else {                    // some method that's not a GET
            throw_error(405, "405 - method not allowed");
        }
    } else {
        throw_error(404, "404 - not found");
    }
}

/*
 * -----------
 * book_pings/
 * -----------
 */
function handle_bp($path_arr) {
    $cnt    = count($path_arr);
    $method = $_SERVER['REQUEST_METHOD'];
    $root   = $_SERVER['DOCUMENT_ROOT'].'/';

    include $_SERVER['DOCUMENT_ROOT'].'/path_vars_api.php';

    if ($cnt === 2) { // valid request 
        if ($method === "GET") {  
            // GET book_pings/count
            if ($path_arr[1] === "count") { 
                // include $root.$get_bp_count;
                redir($get_bp_count);
            // GET book_pings/{id}
            } else if ($path_arr[1] !== "") { 
                $_GET['book_ping_id'] = strip_ext($path_arr[1], ".json");
                include $root.$get_bp_id;
            // GET book_pings/
            } else if ($path_arr[1] === "") { 
                include $root.$get_bp;
            } else {                            // some other path, so throw error
                throw_error(404, "404 - not found");
            }
        } else if ($method === "POST") {
            // POST book_pings/
            if ($path_arr[1] === "") { 
                include $root.$post_bp;
            } else {
                throw_error(404, "404 - not found");
            }
        } else {
            throw_error(405, "405 - method not allowed");
        }
    } else {
        throw_error(404, "404 - not found");
    }
}

/*
 * ------
 * users/
 * ------
 */
function handle_users($path_arr) {
    $cnt    = count($path_arr);
    $method = $_SERVER['REQUEST_METHOD'];
    $root   = $_SERVER['DOCUMENT_ROOT'].'/';
    $web    = isset($_GET['web']); // true if request came from front end
    $server = get_domain();

    include $_SERVER['DOCUMENT_ROOT'].'/path_vars_api.php';

    if ($cnt === 1) { // URI paths with a count of 1,2,3 are valid
        if ($method === 'GET') { // GET users
            if (0) {
                redir($get_user_mult);
            } else {
                $_SERVER['QUERY_STRING'] = 'web=1';
                include $root.$get_user_mult;
            }
        } else if ($method === 'POST') { // POST users
            include $root.$post_users;
        } else {
            throw_error(405, '405 - method not allowed');
        }
    } else if ($cnt === 2) {
        // GET users/something_here
        if ($method === 'GET') { 
            // GET users/activate_email
            if ($path_arr[1] === 'activate_email') { 
                include $root.$get_act_email;
            // GET users/some_user.json
            } else {
                $_GET['user_id'] = strip_ext($path_arr[1], '.json');
                if ($web) {
                    redir($get_user.'?user_id='.$_GET['user_id']);
                } else {
                    include $root.$get_user;
                }
            }
        // POST users/something_here
        } else if ($method === 'POST') { 
            // POST users/edit
            if ($path_arr[1] === 'edit') { 
                if ($web) {
                    redir($post_users_edit);
                } else {
                    include $root.$post_users_edit;
                }
            } else {
                throw_error(404, '404 - not found');
            }
        } else {
            throw_error(405, '405 - not found');
        }
    } else if ($cnt === 3) {
        // GET users/something/something_else
        if ($method === 'GET') { 
            // GET users/{id}/permissions
            if ($path_arr[2] === 'permissions') { 
                $_GET['user_id'] = $path_arr[1];
                if (0) {
                    redir($get_user_perm.'?user_id='.$_GET['user_id']);
                } else {
                    include $root.$get_user_perm;
                }

            // GET users/available/{id}.json
            } else if ($path_arr[1] === 'available') {
                $_GET['user_id'] = strip_ext($path_arr[2], '.json');
                include $root.$get_users_avail;
            // GET users/email_registered/{id}
            } else if ($path_arr[1] === 'email_registered') {
                redir($get_email_reg.'?email='.strip_ext($path_arr[2], '.json'));
            } else {
                throw_error(404, '404 - not found');
            }
        } else if ($method === 'POST') {
            // POST users/{id}/permissions
            if ($path_arr[2] === 'permissions') { 
                $_POST['user_id'] = $path_arr[1];
                if (0) {
                    redir($post_users_perm.'?user_id='.$_POST['user_id']);
                } else {
                    include $root.$post_users_perm;
                }
            } else {
                throw_error(404, '404 - not found');
            }
        } else {
            throw_error(405, '405 - method not allowed');
        }
    } else {
        throw_error(404, '404 - not found');
    }
}

/*
 * -------------
 * institutions/
 * -------------
 */
function handle_inst($path_arr) {
    $cnt    = count($path_arr);
    $method = $_SERVER['REQUEST_METHOD'];
    $root   = $_SERVER['DOCUMENT_ROOT'].'/';
    $web    = isset($_GET['web']); // true if request came from front end
    $server = get_domain();

    include $_SERVER['DOCUMENT_ROOT'].'/path_vars_api.php';

    if ($cnt === 2) {
        // GET institutions/'something here maybe'
        if ($method === 'GET') {
            // GET institutions/
            if ($path_arr[1] === '') {
                if ($web) {
                    redir($get_inst_mult);
                } else {
                    include $root.$get_inst_mult;
                }
            // GET institutions/activate_inst
            } else if ($path_arr[1] === 'activate_inst') {
                include $root.$get_act_inst;
            // GET institutions/{id}
            } else {
                $_GET['inst_id'] = strip_ext($path_arr[1], '.json');
                if ($web) {
                    redir($get_inst.'?inst_id='.$_GET['inst_id']); 
                } else {
                    include $root.$get_inst;
                }
            }
        } else if ($method === 'POST') {
            // POST institutions/
            if ($path_arr[1] === '') {
                include $root.$post_inst_reg;
            // POST institutions/edit
            } else if ($path_arr[1] === 'edit') {
                if ($web) {
                    redir($post_inst_edit);
                } else {
                    include $root.$post_inst_edit;
                }
            } else {
                throw_error(404, '404 - not found');
            }
        }
    } else if ($cnt === 3) {
        // GET institutions/something/somethingelse
        if ($method === 'GET') {
            // GET institutions/available/{id}
            if ($path_arr[1] === 'available') {
                $_GET['inst_id'] = strip_ext($path_arr[2], '.json');
                include $root.$get_inst_avail;
            } else {
                throw_error(404, '404 - not found');
            }
        } else {
            throw_error(405, '405 - not found');
        }
    } else {
        throw_error(404, '404 - not found');
    }
}

/*
 * ----------
 * make_tags/
 * ----------
 */
function handle_mt($path_arr) {
    $cnt    = count($path_arr);
    $method = $_SERVER['REQUEST_METHOD'];
    $root   = $_SERVER['DOCUMENT_ROOT'].'/';

    include $_SERVER['DOCUMENT_ROOT'].'/path_vars_api.php';

    if ($cnt === 2) { // valid request
        // GET make_tags/something_here
        if ($method === "GET") { 
            // GET make_tags/paper_formats
            if ($path_arr[1] === "paper_formats") {
                include $root.$get_formats;
            // GET make_tags/something_else
            } else {
                $_GET['type'] = strip_ext($path_arr[1], ".pdf");
                include $root.$get_tags;
            }
        } else {
            throw_error(405, "405 - method not allowed");
        }
    } else {
        throw_error(404, "404 - not found");
    }
}

/*
* ------
* oauth/
* ------
*/
function handle_oauth($path_arr) {
    $root = $_SERVER['DOCUMENT_ROOT']."/";
    $method = $_SERVER['REQUEST_METHOD'];

    if (count($path_arr) === 2) { // valid request
        if ($method === "GET") { // GET oauth/something_here
            switch($path_arr[1]) { // determine the path and dispatch
            case "get_request_token": // necessary file
                redir("oauth/request_token.php?oauth_callback=".$_GET['oauth_callback'].'&scope='.$_GET['scope']);
                break;
            case "login":
                redir("oauth/login.php?oauth_token=".$_GET['oauth_token']);
                break;
            case "get_access_token":
                redir("oauth/access_token.php?oauth_verifier=".$_GET['oauth_verifier']);
                break;
            case "whoami": redir("api/oauth/whoami.php"); break;
            case "post_login":
                redir("oauth/post-login.php?oauth_token=".$_GET['oauth_token']);
                break;
            default: throw_error(404, "404 - not found"); break;
            }
        } else if ($method === "POST") {
            if ($path_arr[1] === "oauth/login") {
                redir("oauth/login.php");
            }
        } else {
            throw_error(405, "405 - method not allowed");
        }
    } else {
        throw_error(404, "404 - not found");
    }
}

/*
 * --------------
 * notifications/
 * --------------
 */
function handle_notif($path_arr) {
    $cnt    = count($path_arr);
    $method = $_SERVER['REQUEST_METHOD'];
    $root   = $_SERVER['DOCUMENT_ROOT'].'/';

    include $_SERVER['DOCUMENT_ROOT'].'/path_vars_api.php';

    if ($cnt === 2) { // valid request
        // GET notifications/{inst_id}
        if ($method === "GET") { 
            $_GET['inst_id'] = $path_arr[1];
            include $root.$get_notif;
        } else {                    // some method that's not a GET
            throw_error(405, "405 - method not allowed");
        }
    } else {
        throw_error(404, "404 - not found");
    }
}

/*
 * Throws a json formatted error
 */
function throw_error($code, $out_string) {
    http_response_code($code);

    $result = array('result' => "ERROR ".$out_string);
    echo json_encode($result);
}

