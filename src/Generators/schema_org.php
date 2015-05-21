<?php namespace Lti\Seo\Generators;

use Lti\Seo\Helpers\ICanHelpWithJSONLD;

/**
 * Interface ICanBecomeJSONLD
 * @package Lti\Seo\Generators
 */
interface ICanBecomeJSONLD
{
    public function format();

}

/**
 * The most generic type of item in the schema.org namespace.
 *
 * Class Thing
 * @package Lti\Seo\Generators
 * @link http://schema.org/Thing
 */
class Thing implements ICanBecomeJSONLD
{
    //Properties that aren't a part of schema.org, used internally
    /**
     * @var \Lti\Seo\Helpers\ICanHelpWithJSONLD
     */
    protected static $helper;
    private $type;
    /*
     * @var string Stores the object's type as it will appear within the json-ld markup
     * (only used in implementing classes, if necessary)
     */
    protected $realType;

    //schema.org properties
    protected $url;
    protected $name;
    protected $logo;
    protected $description;
    protected $alternateName;
    /**
     * @var \Lti\Seo\Generators\ICanSearch
     */
    protected $potentialAction;
    protected $sameAs;

    /**
     * Can receive an array of proprties that are filled in if they are declared properties
     *
     * @param array $properties
     */
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
        return $this->type;
    }

    public function set_type( $type )
    {
        $this->type = $type;
    }

    /**
     * In most cases, the name of the object withing json-ld markup will be the class name.
     *
     * Sometimes though we create a custom object on the fly with an array of properties, but we can't have
     * the object appear as a "Thing" so we have to set the type manually.
     *
     * When classes are extended by implementing code, the namespace might be entirely different so we need
     * a mechanism to only create classes whose names exist within the schema.org namespace. The code doesn't
     * do namespace checks, we assume that implementing devs know what they're doing.
     *
     * @return string
     */
    private function check_type(){
        $this_class = new \ReflectionClass( $this );

        $type = $this_class->getShortName();
        if ($type == 'Thing') {
            $type = $this->get_type();
            unset($this->type);
        }

        if(!is_null($this->realType)){
            $type = $this->realType;
            unset($this->realType);
        }
        return $type;
    }

    /**
     * Creates an array of schema.org attribute ready to be json encoded.
     *
     * Grabs all properties from the class and uses those that aren't empty.
     *
     *
     * @return array
     */
    public function format()
    {
        $result     = array();

        $result["@type"] = $this->check_type();
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

    /**
     * Adds a search potential action if the website supports it.
     */
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

/**
 * Used for certain Thing properties that can have multiple objects within it
 * i.e contributor, translator, author, publisher can contain multiple Person or Organization entities
 *
 * Class Thing_Collection
 * @package Lti\Seo\Generators
 */
class Thing_Collection implements \Iterator, ICanBecomeJSONLD
{
    private $key = 0;
    private $val = array();

    public function current()
    {
        return $this->val[$this->key];
    }

    public function next()
    {
        ++ $this->key;
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
            /**
             * @var Thing $val
             */
            $formatted = $val->format();

            //If the formatting doesn't add values to the array, no need to add an empty json-ld object.
            if (count( $formatted ) > 1) {
                $vals[] = $formatted;
            }
        }

        return $vals;
    }

}

/**
 * Class Action
 * @package Lti\Seo\Generators
 * @link http://schema.org/Action
 */
class Action extends Thing
{
    protected $target;
}

/**
 * Class SearchAction
 * @package Lti\Seo\Generators
 * @link http://schema.org/SearchAction
 */
class SearchAction extends Action
{
    protected $query;
}

/**
 * Interface ICanSearch
 * @package Lti\Seo\Generators
 */
interface ICanSearch
{
    public function get_query_type();
}

/**
 * Class Person
 * @package Lti\Seo\Generators
 * @link http://schema.org/Person
 */
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
            //We make sure to define a distinct property name so implementing classes don't confuse different "Place" objects
            static::$helper->set_target_property( 'workLocation' );
            $this->workLocation = new Place( static::$helper );
        }
        $this->jobTitle = $helper->get_schema_org( 'jobTitle' );
        $this->email    = $helper->get_schema_org( 'email' );
    }

}

/**
 * Class Organization
 * @package Lti\Seo\Generators
 * @link http://schema.org/Organization
 */
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

/**
 * Class CreativeWork
 * @package Lti\Seo\Generators
 * @link http://schema.org/CreativeWork
 */
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

    /**
     * The "get_" methods are supposed to be written in implementing classes.
     * If not, we look for the type in the helper settings array and create our schema.org objects in a collection
     *
     * @param $name
     * @param $arguments
     */
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
}

/**
 * Class WebSite
 * @package Lti\Seo\Generators
 * @link http://schema.org/WebSite
 */
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

/**
 * Class Blog
 * No difference between WebSite and Blog right now, but that could change.
 *
 * @package Lti\Seo\Generators
 * @link http://schema.org/Blog
 */
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

/**
 * Class WebPage
 * @package Lti\Seo\Generators
 * @link http://schema.org/WebPage
 */
class WebPage extends CreativeWork
{

}

/**
 * Class Article
 * @package Lti\Seo\Generators
 * @link http://schema.org/Article
 */
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
    }
}


/**
 * Class Place
 * @package Lti\Seo\Generators
 * @link http://schema.org/Place
 */
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

/**
 * Class GeoCoordinates
 * @package Lti\Seo\Generators
 * @link http://schema.org/GeoCoordinates
 */
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

/**
 * Class BlogPosting
 * @package Lti\Seo\Generators
 * @link http://schema.org/BlogPosting
 */
class BlogPosting extends Article
{
}

/**
 * Class NewsArticle
 * @package Lti\Seo\Generators
 * @link http://schema.org/NewsArticle
 */
class NewsArticle extends Article
{
}

/**
 * Class ScholarlyArticle
 * @package Lti\Seo\Generators
 * @link http://schema.org/ScholarlyArticle
 */
class ScholarlyArticle extends Article
{
}

/**
 * Class TechArticle
 * @package Lti\Seo\Generators
 * @link http://schema.org/TechArticle
 */
class TechArticle extends Article
{
}

/**
 * Class SearchResultsPage
 * @package Lti\Seo\Generators
 * @link http://schema.org/SearchResultsPage
 */
class SearchResultsPage extends WebPage
{
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        Thing::__construct( array( 'url' => $helper->get_current_url() ) );
    }
}