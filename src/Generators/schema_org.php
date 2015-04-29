<?php namespace Lti\Seo\Generators;

use Lti\Seo\Helpers\ICanHelpWithJSONLD;

class Thing
{
    /**
     * @var \Lti\Seo\Helpers\ICanHelpWithJSONLD
     */
    protected static $helper;
    protected $url;
    protected $name;
    protected $logo;
    protected $description;
    protected $alternateName;
    protected $potentialAction;
    protected $sameAs;
    protected static $type;

    public function __construct( Array $properties )
    {
        $this_class = new \ReflectionClass( $this );
        if ( ! empty( $properties )) {
            foreach ($properties as $property => $value) {
                if ($this_class->hasProperty( $property )) {
                    $this->{$property} = $value;
                }
            }
        }
    }

    public function get_type()
    {
        return static::$type;
    }

    public function set_type( $type )
    {
        static::$type = $type;
    }

    public function format()
    {
        $result = array();

        $result["@type"] = $this->get_type();
        $values          = get_object_vars( $this );

        foreach ($values as $key => $value) {
            if ( ! is_null( $value ) && ! empty( $value )) {
                if ($value instanceof Thing) {
                    $result[$key] = $value->format();
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    protected function addPotentialAction()
    {
        $class = static::$helper->get_search_action_type();
        if (class_exists( $class )) {
            $class_test = new \ReflectionClass( $class );
            if ($class_test->implementsInterface( 'Lti\Seo\Generators\ICanSearch' )) {
                $this->potentialAction = new $class( static::$helper );
            }
        }
    }

}

class Action extends Thing
{
    protected $target;
}

class SearchAction extends Action
{

    protected $query;
    protected static $type = 'SearchAction';
}

interface ICanSearch
{
    public function get_query_type();
}

class Person extends Thing
{

    protected static $type = 'Person';

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        static::$helper     = $helper;
        $this->sameAs       = $helper->get_author_social_info();
        $this->name         = $helper->get_schema_org( 'display_name', $helper::USER_SETTING );
        $this->url          = $helper->get_schema_org( 'user_url', $helper::USER_SETTING );
        $this->workLocation = new Place( $helper );
        $this->jobTitle     = $helper->get_schema_org( 'job_title', $helper::USER_META_SETTING );
        $this->email        = $helper->get_schema_org( 'public_email', $helper::USER_META_SETTING );
    }

}

class Organization extends Thing
{

    protected static $type = 'Organization';

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        static::$helper      = $helper;
        $this->sameAs        = $helper->get_social_urls();
        $this->logo          = $helper->get_schema_org( 'type_logo_url', $helper::GENERAL_SETTING );
        $this->name          = $helper->get_schema_org( 'type_name', $helper::GENERAL_SETTING );
        $this->alternateName = $helper->get_schema_org( 'type_alternate_name', $helper::GENERAL_SETTING );
        $this->url           = $helper->get_schema_org( 'type_website_url', $helper::GENERAL_SETTING );
    }

}

abstract class CreativeWork extends Thing
{
    protected $publisher;
    protected $datePublished;
    protected $dateModified;
    protected $copyrightYear;
    protected $headline;
    protected $keywords;
    protected $thumbnailUrl;
    protected $inLanguage;

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        $this->url          = $helper->get_current_url();
        $this->headline     = $helper->get_schema_org( 'title', $helper::HELPER_SETTING );
        $this->keywords     = $helper->get_schema_org( 'tags', $helper::HELPER_SETTING );
        $this->thumbnailUrl = $helper->get_schema_org( 'thumbnail_url', $helper::HELPER_SETTING );
        $this->inLanguage   = $helper->get_schema_org( 'language', $helper::HELPER_SETTING );
    }

    protected function get_author_publisher()
    {
        $helper = static::$helper;
        if ($helper->get_schema_org( 'entity', $helper::GENERAL_SETTING )) {
            $type = $helper->get_schema_org( 'entity_type', $helper::GENERAL_SETTING );
            switch ($type) {
                case "person":
                    $this->author = new Person( $helper );
                    break;
                case "organization":
                    $this->publisher = new Organization( $helper );
                    break;
            }
        }
    }
}

class WebSite extends CreativeWork
{

    protected static $type = 'WebSite';

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        parent::__construct( $helper );
        static::$helper = $helper;

        $this->addPotentialAction( $helper );
        $this->get_author_publisher();

    }

    public function get_type()
    {
        return 'WebSite';
    }
}

class Blog extends CreativeWork
{
    protected static $type = 'Blog';
    protected $blogPosting;

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        parent::__construct( $helper );
        static::$helper = $helper;
        $class          = $helper->get_search_action_type();
        if (class_exists( $class )) {
            $this->potentialAction = new $class( $helper );
        }
        $this->get_author_publisher();

    }
}

class WebPage extends CreativeWork
{

    protected static $type = 'WebPage';

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        parent::__construct( $helper );
        $this->datePublished = $helper->date_conversion( $helper->get_schema_org( 'post_date',
            $helper::POST_SETTING ) );
        $this->dateModified  = $helper->date_conversion( $helper->get_schema_org( 'post_modified',
            $helper::POST_SETTING ) );
        $this->copyrightYear = $helper->date_get_year( $helper->get_schema_org( 'post_modified',
            $helper::POST_SETTING ) );
    }
}

class Article extends CreativeWork
{
    protected static $type = 'Article';
    protected $articleSection;
    protected $wordCount;

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        parent::__construct( $helper );
        $this->articleSection = $helper->get_schema_org( 'categories', $helper::GENERAL_SETTING );
        $this->wordCount      = $helper->get_schema_org( 'word_count', $helper::POST_META_SETTING );
        $user_website         = $helper->get_schema_org( 'user_url', $helper::USER_SETTING );
        if ( ! empty( $user_website ) && ! is_null( $user_website )) {
            $thing = new Thing( array( 'url' => $helper->get_schema_org( 'user_url', $helper::USER_SETTING ) ) );
            $thing->set_type( 'Person' );
            $this->author = $thing;
        }
    }
}

class Place extends Thing
{
    protected $geo;
    protected static $type = 'Place';

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        $this->geo = new GeoCoordinates( $helper );
    }
}

class GeoCoordinates extends Thing
{
    protected $longitude;
    protected $latitude;
    protected static $type = 'GeoCoordinates';

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        $this->longitude = $helper->get_schema_org( 'work_longitude', $helper::USER_META_SETTING );
        $this->latitude  = $helper->get_schema_org( 'work_latitude', $helper::USER_META_SETTING );
    }
}

class BlogPosting extends Article
{
    protected static $type = 'BlogPosting';
}

class NewsArticle extends Article
{
    protected static $type = 'NewsArticle';
}

class ScholarlyArticle extends Article
{
    protected static $type = 'ScholarlyArticle';
}

class TechArticle extends Article
{
    protected static $type = 'TechArticle';
}

class SearchResultsPage extends WebPage
{
    protected static $type = 'SearchResultsPage';

    public function __construct( ICanHelpWithJSONLD $helper )
    {
        Thing::__construct( array( 'url' => $helper->get_current_url() ) );
    }
}