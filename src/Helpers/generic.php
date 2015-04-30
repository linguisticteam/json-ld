<?php namespace Lti\Seo\Helpers;

interface ICanHelp
{
}

interface ICanHelpWithJSONLD
{
    public function get_schema_org( $setting );

    public function get_search_action_type();

    public function get_current_url();

    public function set_schema($object);

    public function set_target_property($property);

    public function get_target_property();

}

abstract class Generic_Helper implements ICanHelpWithJSONLD
{
    protected $settings;
    protected $schema;
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

    public function get_target_property(){
        return $this->target_property;
    }

    public function set_target_property($property){
        $this->target_property = $property;
    }



}
