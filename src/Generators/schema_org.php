<?php namespace Lti\Seo\Generators;

use Lti\Seo\Helpers\ICanHelpWithJSONLD;

interface ICanBecomeJSONLD
{
    public function format();

}

class Thing implements ICanBecomeJSONLD
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
        $result     = array();
        $this_class = new \ReflectionClass( $this );

        $type = $this_class->getShortName();

        //Sometimes we want to create an object on the fly, so we create a Thing and assign a type manually
        //In that case, our type is taken from the type attribute rather than from the classname
        if ($type == 'Thing') {
            $type = $this::$type;
        }

        $result["@type"] = $type;
        $values          = array_filter( get_object_vars( $this ) );

        foreach ($values as $key => $value) {
            if ($value instanceof ICanBecomeJSONLD) {
                $formatted = $value->format();
                if ( ! empty( $formatted )) {
                    $result[$key] = $formatted;
                }
            } else {
                $result[$key] = $value;
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

class Thing_Collection implements \Iterator, ICanBecomeJSONLD
{
    private $key = 0;
    private $val = array();

    public function current()
    {
        return $this->val[$this->$key];
    }

    public function next()
    {
        ++ $this->$key;
    }

    public function key()
    {
        return $this->key;
    }

    public function valid()
    {
        return isset( $this->val[$this->key] );
    }

    public function rewind()
    {
        $this->key = 0;
    }

    public function add( Thing $thing )
    {
        $this->val[] = $thing;
    }

    public function format()
    {
        $vals = array();
        foreach ($this->val as $val) {
            $formatted = $val->format();

            //If the formatting doesn't add values to the array, no need to add an empty json-ld object.
            if (count( $formatted ) > 1) {
                $vals[] = $formatted;
            }
        }

        return $vals;
    }

}

class Action extends Thing
{
    protected $target;
}

class SearchAction extends Action
{
    protected $query;
}

interface ICanSearch
{
    public function get_query_type();
}

class Person extends Thing
{
    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        static::$helper = $helper;
        static::$helper->set_schema( 'Person' );
        $this->sameAs = $helper->get_schema_org( 'sameAs' );
        $this->name   = $helper->get_schema_org( 'name' );
        $this->url    = $helper->get_schema_org( 'url' );
        if ( ! is_null( $helper->get_schema_org( 'workLocation:longitude' ) ) && ! is_null( $helper->get_schema_org( 'workLocation:latitude' ) )) {
            static::$helper->set_target_property( 'workLocation' );
            $this->workLocation = new Place( $helper );
        }
        $this->jobTitle = $helper->get_schema_org( 'jobTitle' );
        $this->email    = $helper->get_schema_org( 'email' );
    }

}

class Organization extends Thing
{
    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        static::$helper = $helper;
        static::$helper->set_schema( 'Organization' );
        $this->sameAs        = $helper->get_schema_org( 'sameAs' );
        $this->logo          = $helper->get_schema_org( 'logo' );
        $this->name          = $helper->get_schema_org( 'name' );
        $this->alternateName = $helper->get_schema_org( 'alternateName' );
        $this->url           = $helper->get_schema_org( 'url' );
    }
}

abstract class CreativeWork extends Thing
{
    protected $author;
    protected $publisher;
    protected $contributor;
    protected $translator;
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
        static::$helper = $helper;
        static::$helper->set_schema( 'CreativeWork' );
        $this->url           = $helper->get_current_url();
        $this->headline      = $helper->get_schema_org( 'headline' );
        $this->keywords      = $helper->get_schema_org( 'keywords' );
        $this->thumbnailUrl  = $helper->get_schema_org( 'thumbnailUrl' );
        $this->inLanguage    = $helper->get_schema_org( 'inLanguage' );
        $this->datePublished = $helper->get_schema_org( 'datePublished' );
        $this->dateModified  = $helper->get_schema_org( 'dateModified' );
        $this->copyrightYear = $helper->get_schema_org( 'copyrightYear' );
        $this->get_author();
        $this->get_publisher();
        $this->get_contributor();
        $this->get_translator();
    }

    public function __call( $name, $arguments )
    {
        if (strpos( $name, "get_" ) !== false) {
            $entity = substr( $name, 4 );
            $data   = static::$helper->get_schema_org( $entity );
            if (is_array( $data )) {
                $this->{$entity} = new Thing_Collection();
                foreach ($data as $type => $helper) {
                    $class = __NAMESPACE__ . "\\" . $type;
                    if (class_exists( $class )) {
                        $this->{$entity}->add( new $class( $helper ) );
                    }
                }
            }
        }
    }

    protected function get_authors()
    {
        $helper  = static::$helper;
        $authors = $helper->get_schema_org( 'author' );

        if (is_array( $authors )) {
            $this->author = new Thing_Collection();
            foreach ($authors as $type => $helper) {
                $class = __NAMESPACE__ . "\\" . $type;
                if (class_exists( $class )) {
                    $this->author->add( new $class( $helper ) );
                }
            }
        }
    }

    protected function get_publishers()
    {
        $helper     = static::$helper;
        $publishers = $helper->get_schema_org( 'publisher' );
        if (is_array( $publishers )) {
            $this->publisher = new Thing_Collection();
            foreach ($publishers as $helper) {
                $this->publisher->add( new Organization( $helper ) );
            }
        }
    }

    protected function get_contributors()
    {
        $helper     = static::$helper;
        $publishers = $helper->get_schema_org( 'publisher' );
        if (is_array( $publishers )) {
            $this->publisher = new Thing_Collection();
            foreach ($publishers as $helper) {
                $this->publisher->add( new Organization( $helper ) );
            }
        }
    }
}

class WebSite extends CreativeWork
{
    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        parent::__construct( $helper );
        $this->addPotentialAction( $helper );
    }

}

class Blog extends CreativeWork
{
    protected $blogPosting;

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        parent::__construct( $helper );

        $this->addPotentialAction( $helper );
    }
}

class WebPage extends CreativeWork
{

}

class Article extends CreativeWork
{
    protected $articleSection;
    protected $wordCount;

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        parent::__construct( $helper );
        static::$helper->set_schema( 'Article' );
        $this->articleSection = $helper->get_schema_org( 'articleSection' );
        $this->wordCount      = $helper->get_schema_org( 'wordCount' );
        $user_website         = $helper->get_schema_org( 'Person:url' );
        if ( ! empty( $user_website ) && ! is_null( $user_website )) {
            $thing = new Thing( array( 'url' => $user_website ) );
            $thing->set_type( 'Person' );
            $this->author = $thing;
        }
    }
}

class Place extends Thing
{
    protected $geo;

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

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        $this->longitude = $helper->get_schema_org( sprintf( '%s:%s', $helper->get_target_property(), 'longitude' ) );
        $this->latitude  = $helper->get_schema_org( sprintf( '%s:%s', $helper->get_target_property(), 'latitude' ) );
    }
}

class BlogPosting extends Article
{
}

class NewsArticle extends Article
{
}

class ScholarlyArticle extends Article
{
}

class TechArticle extends Article
{
}

class SearchResultsPage extends WebPage
{
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        Thing::__construct( array( 'url' => $helper->get_current_url() ) );
    }
}