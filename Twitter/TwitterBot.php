<?php
namespace Twitter;

require_once (__DIR__ . '/SearchMetaData.php');
require_once (__DIR__ . '/Status.php');
require_once (__DIR__ . '/User.php');

require_once (__DIR__ . '/twitteroauth/twitteroauth.php');

/**
 * A Twitter bot to talk with Twitter's servers.
 */
class TwitterBot
{

    const OAUTH_URL_REQUEST_TOKEN = 'https://api.twitter.com/oauth/request_token';

    const OAUTH_URL_AUTHORIZE = 'https://api.twitter.com/oauth/authorize';

    const OAUTH_URL_ACCESS_TOKEN = 'https://api.twitter.com/oauth/access_token';

    const OAUTH_URL_BEARER_TOKEN = 'https://api.twitter.com/oauth2/token';

    const OAUTH_SIGNATURE_METHOD = 'HMAC-SHA1';

    const TWITTER_URL_SHOW = 'https://api.twitter.com/1.1/statuses/show.json';

    /**
     * https://dev.twitter.com/docs/api/1.1/get/search/tweets
     *
     * @var string url for search
     */
    const TWITTER_URL_SEARCH = 'https://api.twitter.com/1.1/search/tweets.json';

    const TWITTER_URL_TIMELINE = 'https://twitter.com/i/search/timeline';

    const TWITTER_URL_USER_TIMELINE = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

    const TWITTER_URL_RETWEET = 'https://api.twitter.com/1.1/statuses/retweet/:id.json' ;

    const TWITTER_URL_BLOCKS_IDS = 'https://api.twitter.com/1.1/blocks/ids.json' ;

	const TWITTER_URL_USERS_SHOW = 'https://api.twitter.com/1.1/users/show.json' ;

    /**
     * https://api.twitter.com/1.1/search/tweets.json
     *
     * @var number
     */
    const SEARCH_RESULTS_MAX = 100;

    const SEARCH_RESULTS_DEFAULT = 15;

    const TIMELINE_MAX_TWEETS = 3200 ;
    const TIMELINE_MAX_TWEETS_PER_REQUEST = 200 ;
    
    const HTTP_USERAGENT = 'SARB v0.1';

    const HTTP_CONNECTTIMEOUT = 5;

    const HTTP_TIMEOUT = 5;

    const HTTP_SSL_VERIFYPEER = true;

    const HTTP_FOLLOWLOCATION = false;

    const HTTP_PROXY = null;

    const HTTP_ENCODING = 'UTF-8';

    protected $oauthConsumerKey;

    protected $oauthConsumerSecret;

    protected $bearerToken;

    protected $oauthToken;

    protected $oauthTokenSecret;

    protected $userId;

    protected $connection = null ;

    /**
     *
     * @param string $oauthConsumerKey            
     * @param string $oauthConsumerSecret            
     */
    public function __construct($oauthConsumerKey, $oauthConsumerSecret)
    {
        if ($oauthConsumerKey == null || $oauthConsumerSecret == null)
            throw new ErrorException('OAUTH_CONSUMER stuff must be valid');
        
        $this->oauthConsumerKey = $oauthConsumerKey;
        $this->oauthConsumerSecret = $oauthConsumerSecret;
    }

    public function setAccessToken($userId, $oauthToken, $oauthTokenSecret)
    {
    	// reset connection
    	$this->connection = null ;

        $this->userId = $userId;
        $this->oauthToken = $oauthToken;
        $this->oauthTokenSecret = $oauthTokenSecret;
    }

    public function getConnection()
    {
    	if( $this->connection == null )
    	{
    		$this->connection = new \TwitterOAuth($this->oauthConsumerKey, $this->oauthConsumerSecret, $this->oauthToken, $this->oauthTokenSecret);
    	}
    	return $this->connection ;
    }

    /**
     * @return number
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     *
     * @return \Twitter\User
     */
    public function verifyCredentials()
    {
        //$connection = new \TwitterOAuth($this->oauthConsumerKey, $this->oauthConsumerSecret, $this->oauthToken, $this->oauthTokenSecret);
    	$connection = $this->getConnection();
        
        // Returns an HTTP 200 OK response code and a representation of the requesting user if authentication was successful;
        // returns a 401 status code and an error message if not. Use this method to test if supplied user credentials are valid.
        $account = $connection->get('account/verify_credentials');
        $user = User::createFrom($account);
        return $user;
    }

    /**
     */
    public function getRequestToken()
    {
        $connection = new \TwitterOAuth($this->oauthConsumerKey, $this->oauthConsumerSecret);
        $token = $connection->getRequestToken('oob');
        
        $redirect_url = $connection->getAuthorizeURL($token, false);
        
        return array(
            'token' => $token,
            'url' => $redirect_url
        );
    }

    public function getAccessToken($oauthToken, $oauthTokenSecret, $oauthVerifier)
    {
        $connection = new \TwitterOAuth($this->oauthConsumerKey, $this->oauthConsumerSecret, $oauthToken, $oauthTokenSecret);
        $token_credentials = $connection->getAccessToken($oauthVerifier);
        
        return $token_credentials;
    }

    /**
     * Request on twitter within an application-only context.
     *
     * TODO : move this method to twitteroauth package
     *
     * @param string $url            
     * @param string $method            
     * @param array $headers            
     * @param array $data            
     * @throws Exception
     * @return string The server response
     */
    protected function _requestAppContext($url, $method, Array $headers, Array $data = array())
    {
        
        // CURL defaults to setting this to Expect: 100-Continue
        // which Twitter rejects !
        $headers['Expect'] = '';
        
        if ($this->bearerToken != null)
            $headers['Authorization'] = 'Bearer ' . $this->bearerToken;
        
        $httpheaders = array();
        foreach ($headers as $k => $v) {
            $httpheaders[] = trim($k . ': ' . $v);
        }
        
        $c = curl_init();
        curl_setopt_array($c, array(
            CURLOPT_USERAGENT => self::HTTP_USERAGENT,
            CURLOPT_CONNECTTIMEOUT => self::HTTP_CONNECTTIMEOUT,
            CURLOPT_TIMEOUT => self::HTTP_TIMEOUT,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_SSL_VERIFYPEER => self::HTTP_SSL_VERIFYPEER,
            CURLOPT_FOLLOWLOCATION => self::HTTP_FOLLOWLOCATION,
            CURLOPT_PROXY => self::HTTP_PROXY,
            CURLOPT_ENCODING => self::HTTP_ENCODING,
            CURLOPT_HTTPHEADER => $httpheaders,
            CURLINFO_HEADER_OUT => true
        ));
        
        if ($method == 'POST') {
            curl_setopt($c, CURLOPT_POST, true);
            
            $ps = array();
            foreach ($data as $k => $v) {
                $ps[] = "{$k}={$v}";
            }
            curl_setopt($c, CURLOPT_POSTFIELDS, implode('&', $ps));
        } else 
            if ($method == 'GET') {
                $params = array();
                foreach ($data as $k => $v) {
                    $params[] = urlencode($k) . '=' . urlencode($v);
                }
                $qs = implode('&', $params);
                $url = strlen($qs) > 0 ? $url . '?' . $qs : $this->url;
            } else {
                throw new \Exception('Request failed! Unknow method=' . $method);
            }
        
        // echo 'url: ', $url, "\n";
        
        curl_setopt($c, CURLOPT_URL, $url);
        
        $response = curl_exec($c);
        $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($c);
        curl_close($c);

        if ($code != 200) {
            throw new \Exception('Request failed! code=' . $code . ', response= ' . $response);
            // echo 'CODE : '.$code ."\n";
            echo 'INFO: '.var_export($info,true)."\n";
            // echo 'RESPONSE: '.var_export($response, true)."\n";
        }
        return $response;
    }

    /**
     * Allows a registered application to obtain an OAuth 2 Bearer Token,
     * which can be used to make API requests on an application's own behalf,
     * without a user context.
     * This is called Application-only authentication.
     *
     * https://dev.twitter.com/docs/auth/application-only-auth
     * https://dev.twitter.com/docs/api/1.1/post/oauth2/token
     */
    public function getBearerToken()
    {
        if ($this->bearerToken != null)
            return $this->bearerToken;
        
        $creds = base64_encode(urlencode($this->oauthConsumerKey) . ':' . urlencode($this->oauthConsumerSecret));
        
        $headers = array();
        $headers['Authorization'] = 'Basic ' . $creds;
        $headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
        
        $response = $this->_requestAppContext(self::OAUTH_URL_BEARER_TOKEN, 'POST', $headers, array(
            'grant_type' => 'client_credentials'
        ));
        
        // RESPONSE: '
        $response = json_decode($response);
        // echo 'token_type: ', $response->token_type , "\n";
        // echo 'access_token: ', $response->access_token , "\n";
        
        if ($response->token_type != 'bearer') {
            throw new \Exception('Auth failed! Uknow how to handle token type = ' . $response->token_type);
        }
        
        $this->bearerToken = $response->access_token;
        return $this->bearerToken;
    }

    /**
     * Seach for tweets which match the query.
     * Only recents tweets (< 7 days ?!).
     * This method use the API 1.1 with a Bearer Token.
     * Recurcive function.
     *
     * GET search/tweets https://dev.twitter.com/docs/api/1.1/get/search/tweets
     * Using the Twitter Search API https://dev.twitter.com/docs/using-search
     *
     * @param string $query            
     * @param int $count            
     * @param array $statuses            
     * @return \Twitter\Status[]
     */
    public function searchTweets($query, $count = self::SEARCH_RESULTS_DEFAULT, $onlyLang=null, Array &$statuses = array())
    {
        $this->getBearerToken();
        
        // if( $statuses == null )
        // $statuses =array();
        
        $params = array(
            'q' => $query,
            'include_entities' => false,
            'result_type' => 'mixed'
        );

        if( $onlyLang != null )
        {
            $params['lang'] = $onlyLang ;
        }
        
        $statusesCount = count($statuses);
        if ($statusesCount > 0) {
            $maxId = $statuses[$statusesCount - 1]->getId() - 1;
            $params['max_id'] = $maxId;
        }
        
        $tmpCount = $count - $statusesCount;
        if ($tmpCount <= self::SEARCH_RESULTS_MAX)
            $params['count'] = $tmpCount >= 0 ? $tmpCount : 0 ;
        else
        $params['count'] = self::SEARCH_RESULTS_MAX;

        if( $params['count'] == 0 )
        {
            return $statuses;
        }

        $headers = array();
        $response = $this->_requestAppContext(self::TWITTER_URL_SEARCH, 'GET', $headers, $params);
        
        $response = json_decode($response, true);
        // response should containts 2 keys: statuses & search_metadata
        // echo 'RESPONSE: ' . var_export ( $response, true ) . "\n";
        
        $smd = SearchMetaData::createFromArray($response['search_metadata']);
        //echo var_export($smd, true), "\n";
        $respStatusesCount = count($response['statuses']);
        //echo 'respStatusesCount = ', $respStatusesCount, "\n";
        
        // echo 'response statuses count = ', count ( $response ['statuses'] ), "\n";
        
        // $statuses = array ();
        foreach ($response['statuses'] as $status) {
            $statuses[] = Status::createFrom($status);
        }
        // echo 'statuses count = ', count ( $statuses ), "\n";
        
        // if ($smd->asMoreResults())
        if ($respStatusesCount > 0)
            $statuses = $this->searchTweets($query, $count, $onlyLang, $statuses);
        
        return $statuses;
    }

    /**
     * FIXME : À faire pour retrouver tous les tweets sur plusieurs années
     * Cete méthode demande d'être identifiée avec un user (pas de Bearer Token).
     *
     * https://twitter.com/i/search/timeline?q=%23PTCE&src=typd&include_available_features=1&include_entities=1&last_note_ts=0&scroll_cursor=TWEET-422868369579069441-428568485875023872
     *
     * https://twitter.com/i/search/timeline
     * ?q=%23PTCE
     * &src=typd
     * &composed_count=0
     * &include_available_features=1
     * &include_entities=1
     * &include_new_items_bar=true
     * &interval=30000
     * &last_note_ts=0
     * &latent_count=0
     * &refresh_cursor=TWEET-422868369579069441-428568485875023872
     *
     * https://twitter.com/i/search/timeline
     * ?q=%23PTCE
     * &src=typd
     * &include_available_features=1
     * &include_entities=1
     * &last_note_ts=0
     * &scroll_cursor=TWEET-356717097121878016-428568485875023872
     *
     * https://twitter.com/i/search/timeline
     * ?q=%23PTCE
     * &src=typd
     * &include_available_features=1
     * &include_entities=1
     * &last_note_ts=0
     * &oldest_unread_id=0&scroll_cursor=TWEET-229842253957439488-428568485875023872
     *
     * https://twitter.com/i/search/timeline
     * ?q=%23PTCE&src=typd
     * &include_available_features=1
     * &include_entities=1
     * &last_note_ts=0
     * &oldest_unread_id=0
     * &scroll_cursor=TWEET-229842253957439488-428568485875023872
     *
     * @param unknown $query            
     * @param unknown $count            
     * @param array $statuses            
     * @return \Twitter\Status
     */
    public function searchTimelineTweets($query, $count = self::SEARCH_RESULTS_DEFAULT, Array &$statuses = array())
    {
        
        // $this->getBearerToken();
        $connection = new \TwitterOAuth($this->oauthConsumerKey, $this->oauthConsumerSecret, $this->oauthToken, $this->oauthTokenSecret);
        
        // if( $statuses == null )
        // $statuses =array();
        
        $params = array(
            'q' => $query,
            'include_entities' => false
        );
        
        $statusesCount = count($statuses);
        
        $headers = array();
        // $response = $this->_requestAppContext(self::TWITTER_URL_TIMELINE, 'GET', $headers, $params);
        $response = $connection->get(self::TWITTER_URL_TIMELINE, $params);
        // $response = $connection->get('i/search/timeline', $params );
        
        $statuses = array();
        // echo var_export($response,true),"\n";
        foreach ($response as $k => $v) {
           // echo $k, "\n";
            $statuses[] = $v ;
        }

        return $statuses;
    }

    /**
     * Returns a single Tweet, specified by the id parameter.
     * The Tweet's author will also be embedded within the tweet.
     *
     * https://dev.twitter.com/docs/api/1.1/get/statuses/show/%3Aid
     *
     * @param string $id            
     * @return \Twitter\Status
     */
    public function getTweet($id)
    {
        $this->getBearerToken();
        
        $params = array(
            'id' => $id
        );
        $headers = array();
        $response = $this->_requestAppContext(self::TWITTER_URL_SHOW, 'GET', $headers, $params);
        $response = json_decode($response, true);
        $status = Status::createFrom($response);
        return $status;
    }

    /**
     * 
     * @param number $tweetId
     * @return \Twitter\Status
     */
    public function retweet( $tweetId )
    {

        $url = self::TWITTER_URL_RETWEET ;
        $url = str_replace(':id', $tweetId, $url);

        //$connection = new \TwitterOAuth($this->oauthConsumerKey, $this->oauthConsumerSecret, $this->oauthToken, $this->oauthTokenSecret);
        $connection = $this->getConnection();

        $response = $connection->post($url);

        $status = Status::createFrom($response);
        return $status ;
    }

    /**
     * Returns a collection of the most recent Tweets posted by the user indicated by the user_id.
     * This method can only return up to 3,200 of a user's most recent Tweets.
     * Native retweets of other statuses by the user is included in this total, regardless of whether include_rts is set to false when requesting this resource.
     *
     * https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline
     *
     * @param int $maxCount            
     * @return \Twitter\Status[]
     */
    public function getUserTimeline($userId=null, $maxCount = self::TIMELINE_MAX_TWEETS)
    {
        //$connection = new \TwitterOAuth($this->oauthConsumerKey, $this->oauthConsumerSecret, $this->oauthToken, $this->oauthTokenSecret);
        $connection = $this->getConnection();

        if( $userId == null )
            $userId = $this->getUserId();

        $params = array(
            'user_id' => $userId,
            'count' => $maxCount ,
            'include_rts' => 1,
            'trim_user' => 1
        );
        $headers = array();

        $statuses = array();

        $foundCount = 0 ;
        do {
            $response = $connection->get(self::TWITTER_URL_USER_TIMELINE, $params);
            //echo var_export($response, true), "\n";

            $foundCount = 0 ;
            $maxId = PHP_INT_MAX ;
            foreach ($response as $k => $v) {
                // echo $k, "\n";
                $status = Status::createFrom($v);
                $statuses[] = $status;
                $foundCount ++ ;
                $maxId = min( $maxId, $status->getId());
            }
            $params['max_id'] = $maxId-1 ;
            
        } while( $foundCount > 0 );

        return $statuses;
    }

    /**
     * Return the list of blocked users's id.
     * 
     * https://dev.twitter.com/rest/reference/get/blocks/ids
     * 
     * @return string[]:
     */
	public function getBlocksIds()
	{
		//$connection = new \TwitterOAuth($this->oauthConsumerKey, $this->oauthConsumerSecret, $this->oauthToken, $this->oauthTokenSecret);
		$connection = $this->getConnection();

        $headers = array();

        $ids = array();

        $params = array(
            'cursor' => null,
        	'stringify_ids' => true
        );

		$cursor = -1 ;
		do {
			$params['cursor'] = $cursor ;
        	$response = $connection->get(self::TWITTER_URL_BLOCKS_IDS, $params);
			//echo 'RESPONSE: ', var_export($response, true), "\n";

			$ids = array_merge( $ids, $response->ids);
			$cursor = $response->next_cursor;

		} while( $cursor != 0 );
		
		return $ids ;
	}

	public function getUserByScreenName($screenName)
	{
		//$connection = new \TwitterOAuth($this->oauthConsumerKey, $this->oauthConsumerSecret, $this->oauthToken, $this->oauthTokenSecret);
		$connection = $this->getConnection();

		$params = array(
			'screen_name' => $screenName,
			'include_entities' => true
		);

		$response = $connection->get(self::TWITTER_URL_USERS_SHOW, $params);
		echo 'RESPONSE: ', var_export($response, true), "\n";
		$user = User::createFrom($response);
		
		return $user ;
	}

}
