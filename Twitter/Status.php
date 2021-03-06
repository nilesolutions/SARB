<?php
namespace Twitter;

require_once (__DIR__ . '/ObjectBase.php');

/**
 * https://dev.twitter.com/docs/platform-objects/tweets
 */
class Status extends ObjectBase
{

    /**
     *
     * @var \stdClass Unused. Future/beta home for status annotations
     */
    protected $annotations;

    /**
     * https://dev.twitter.com/docs/platform-objects/tweets#obj-contributors
     *
     * @var Contributor[]
     */
    protected $contributors;

    /**
     * https://dev.twitter.com/docs/platform-objects/tweets#obj-coordinates
     *
     * @var Coordinate[]
     */
    protected $coordinates;

    /**
     *
     * @var string UTC time when this Tweet was created.
     */
    protected $created_at;

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Perspectival.
     * Only surfaces on methods supporting the include_my_retweet parameter, when set to true. Details the Tweet ID of the user's own retweet (if existent) of this Tweet.
     *
     * @var \stdClass
     */
    protected $current_user_retweet;

    /**
     * https://dev.twitter.com/docs/platform-objects/entities
     * https://dev.twitter.com/docs/entities
     *
     * @var Entities Entities which have been parsed out of the text of the Tweet.
     */
    protected $entities;

    /**
     *
     * @var number Nullable. Indicates approximately how many times this Tweet has been "favorited" by Twitter users.
     */
    protected $favorite_count;

    /**
     *
     * @var boolean Nullable. Perspectival. Indicates whether this Tweet has been favorited by the authenticating user.
     */
    protected $favorited;

    /**
     * Indicates the maximum value of the filter_level parameter which may be used and still stream this Tweet.
     * So a value of medium will be streamed on none, low, and medium streams.
     *
     * @var string
     */
    protected $filter_level;

    /**
     * Deprecated.
     * Nullable. Use the "coordinates" field instead.
     * 
     * @var \stdClass
     */
    protected $geo;

    /**
     * If the represented Tweet is a reply, this field will contain the screen name of the original Tweet's author.
     * Nullable.
     *
     * @var string
     */
    protected $in_reply_to_screen_name;

    /**
     * If the represented Tweet is a reply, this field will contain the integer representation of the original Tweet's ID.
     * Nullable.
     *
     * @var int64
     */
    protected $in_reply_to_status_id;

    /**
     * If the represented Tweet is a reply, this field will contain the string representation of the original Tweet's ID.
     * Nullable.
     *
     * @var string
     */
    protected $in_reply_to_status_id_str;

    /**
     * If the represented Tweet is a reply, this field will contain the integer representation of the original Tweet's author ID.
     * This will not necessarily always be the user directly mentioned in the Tweet.
     * Nullable.
     *
     * @var int64
     */
    protected $in_reply_to_user_id;

    /**
     *
     * @var string
     */
    protected $in_reply_to_user_id_str;

    /**
     *
     * @var Place
     */
    protected $place;

    /**
     * Nullable.
     * This field only surfaces when a tweet contains a link. The meaning of the field doesn't pertain to the tweet content itself, but instead it is an indicator that the URL contained in the tweet may contain content or media identified as sensitive content.
     *
     * @var boolean
     */
    protected $possibly_sensitive;

    /**
     * A set of key-value pairs indicating the intended contextual delivery of the containing Tweet.
     * Currently used by Twitter's Promoted Products.
     *
     * @var \stdClass
     */
    protected $scopes;

    /**
     * Number of times this Tweet has been retweeted.
     * This field is no longer capped at 99 and will not turn into a String for "100+".
     *
     * @var int
     */
    protected $retweet_count;

    /**
     * Perspectival.
     * Indicates whether this Tweet has been retweeted by the authenticating user.
     *
     * @var boolean
     */
    protected $retweeted;

    /**
     * Users can amplify the broadcast of tweets authored by other users by retweeting.
     * Retweets can be distinguished from typical Tweets by the existence of a retweeted_status attribute.
     * This attribute contains a representation of the original Tweet that was retweeted.
     * Note that retweets of retweets do not show representations of the intermediary retweet, but only the original tweet.
     * (Users can also unretweet a retweet they created by deleting their retweet.)
     *
     * @var Status
     */
    protected $retweeted_status;

    public function getRetweetedStatus()
    {
        return $this->retweeted_status;
    }

    public function isRetweet()
    {
        return $this->retweeted_status != null;
    }

    /**
     * Utility used to post the Tweet, as an HTML-formatted string.
     * Tweets from the Twitter website have a source value of web.
     *
     * @var string
     */
    protected $source;

    /**
     *
     * @var string
     */
    protected $text;

    /**
     * The actual UTF-8 text of the status update.
     * See twitter-text for details on what is currently considered valid characters.
     *
     * twitter-text : hub.com/twitter/twitter-text-rb/blob/master/lib/twitter-text/regex.rb
     * 
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Indicates whether the value of the text parameter was truncated, for example, as a result of a retweet exceeding the 140 character Tweet length.
     * Truncated text will end in ellipsis, like this ... Since Twitter now rejects long Tweets vs truncating them, the large majority of Tweets will have this set to false.
     * Note that while native retweets may have their toplevel text property shortened, the original text will be available under the retweeted_status object and the truncated parameter will be set to the value of the original status (in most cases, false).
     *
     * @var boolean
     */
    protected $truncated;

    /**
     * The user who posted this Tweet.
     * Perspectival attributes embedded within this object are unreliable.
     *
     * @var User
     */
    protected $user;

    /**
     *
     * @return \Twitter\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * When present and set to "true", it indicates that this piece of content has been withheld due to a DMCA complaint.
     *
     * @var boolean
     */
    protected $withheld_copyright;

    /**
     * When present, indicates a list of uppercase two-letter country codes this content is withheld from.
     * See New Withheld Content Fields in API Responses. As announced in More changes to withheld content fields, Twitter supports the following non-country values for this field:
     * "XX" - Content is withheld in all countries
     * "XY" - Content is withheld due to a DMCA request.
     *
     * @var string[]
     */
    protected $withheld_in_countries;

    /**
     * When present, indicates whether the content being withheld is the "status" or a "user."
     *
     * @var string
     */
    protected $withheld_scope;

    /**
     *
     * @param array $object            
     * @return \Twitter\Status
     */
    public static function createFrom($data)
    {
        $status = new Status();
        parent::initWith($status, $data);
        if ($status->retweeted_status != null) {
            $s2 = new Status();
            parent::initWith($s2, $status->retweeted_status);
            $status->retweeted_status = $s2;
        }
        if ($status->user != null) {
            $status->user = User::createFrom($status->user);
        }
        return $status;
    }
}
