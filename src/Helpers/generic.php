<?php namespace Lti\Seo\Helpers;

/**
 * Interface ICanHelpWithJSONLD
 * @package Lti\Seo\Helpers
 */
interface ICanHelpWithJSONLD
{
    public function get_schema_org( $setting );

    public function get_search_action_type();

    public function get_current_url();

    public function set_schema($object);

    public function set_target_property($property);

    public function get_target_property();

}

/**
 * Class JSONLD_Helper
 * @package Lti\Seo\Helpers
 */
abstract class JSONLD_Helper implements ICanHelpWithJSONLD
{
    protected $settings;
    protected $schema;
    /**
     * Used when a schema.org object may have multiple objects of the same type within it
     * i.e a Person entity having a workLocation and homeLocation which are instances of Place.
     * This property  and its getter & setter prevent namespace collision
     *
     * @var string
     */
    protected $target_property;

    public function __construct( $settings )
    {
        $this->settings = $settings;
    }

    public function get_settings()
    {
        return $this->settings;
    }

    public function set_schema($object){
        $this->schema = $object;
    }

    public function get_schema(){
        return $this->schema;
    }

    /**
     * @see Generic_Helper::$target_property
     * @return string
     */
    public function get_target_property(){
        return $this->target_property;
    }

    public function set_target_property($property){
        $this->target_property = $property;
    }

}
