<?php namespace Lti\Seo\Generators;

use Lti\Seo\Helpers\ICanHelpWithJSONLD;

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

    public static function json_ld_output( $output )
    {
        if ( ! empty( $output )) {
            echo sprintf( '<script type="application/ld+json">%s</script>' . PHP_EOL, $output );
        }
    }

    public function __call( $name, $arguments )
    {
        if (strpos( $name, "make_" ) !== false) {
            $this->json_ld_output( $this->maker->make( substr( $name, 5 ) ) );
        }
    }
}

class JSON_LD_Maker
{
    /**
     * @var \Lti\Seo\Helpers\ICanHelpWithJSONLD
     */
    private $helper;

    public function __construct( ICanHelpWithJSONLD $helper )
    {
        $this->helper = $helper;
        $this->types  = new \ReflectionClass( $this );
    }

    public function make( $type )
    {
        $type = __NAMESPACE__ . "\\" . $type;
        if (class_exists( $type )) {
            $object = new $type( $this->helper );
            $result = $object->format();
            if (is_array( $result ) && ! empty( $result )) {
                return json_encode( array_merge( array( '@context' => 'http://schema.org' ), $result ) );
            }
        }

        return null;
    }
}