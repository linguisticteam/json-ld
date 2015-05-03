<?php namespace Lti\Seo\Generators;

use Lti\Seo\Helpers\ICanHelpWithJSONLD;

/**
 * Class JSON_LD
 * @package Lti\Seo\Generators
 *
 * Our JSON-LD markup generator class.
 */
class JSON_LD
{
    /**
     * @param $helper \Lti\Seo\Helpers\ICanHelpWithJSONLD
     */
    protected $helper;

    /**
     * @param \Lti\Seo\Helpers\ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        $this->maker  = new JSON_LD_Maker( $helper );
        $this->helper = $helper;
    }

    /**
     * Prints the JSON-LD tag
     *
     * @param string $output The JSONed string
     */
    public static function json_ld_output( $output )
    {
        if ( ! empty( $output )) {
            echo sprintf( '<script type="application/ld+json">%s</script>' . PHP_EOL, $output );
        }
    }

    /**
     * Implementing classes might make lots of different Schema.org entities, so any method starting with "make_"
     * plus the Schema.org entity name (i.e make_WebSite, make_Person) will trigger this method
     *
     * @param string $name
     * @param array $arguments
     * @link https://php.net/manual/en/language.oop5.overloading.php#object.call
     */
    public function __call( $name, $arguments )
    {
        if (strpos( $name, "make_" ) !== false) {
            $this->json_ld_output( $this->maker->make( substr( $name, 5 ) ) );
        }
    }
}

/**
 * Makes instances of classes defined in schema_org.php
 *
 * Class JSON_LD_Maker
 * @package Lti\Seo\Generators
 */
class JSON_LD_Maker
{
    /**
     * @var \Lti\Seo\Helpers\ICanHelpWithJSONLD
     */
    private $helper;

    /**
     * @param ICanHelpWithJSONLD $helper
     */
    public function __construct( ICanHelpWithJSONLD $helper )
    {
        $this->helper = $helper;
    }

    /**
     * Our schema.org object factory
     *
     * Makes a new object provided it can find the class
     *
     * @param string $type The type of object to create
     *
     * @return null|string json encoded json-ld string, ready for output
     */
    public function make( $type )
    {
        $type = __NAMESPACE__ . "\\" . $type;
        if (class_exists( $type )) {
            /**
             * @var \Lti\Seo\Generators\Thing $object
             */
            $object = new $type( $this->helper );
            $result = $object->format();
            if (is_array( $result ) && ! empty( $result )) {
                return json_encode( array_merge( array( '@context' => 'http://schema.org' ), $result ) );
            }
        }

        return null;
    }
}