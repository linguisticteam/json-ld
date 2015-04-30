<?php namespace Lti\Seo\Helpers;

interface ICanHelp
{
}

interface ICanHelpWithJSONLD
{
    public function get_social_urls();

    public function get_schema_org( $setting );

    public function get_author_social_info();

    public function get_search_action_type();

    public function get_current_url();

    public function date_conversion( $value );

    public function date_get_year( $value );

    public function get_thumbnail_url();

    public function set_schema($object);

}

abstract class Generic_Helper implements ICanHelpWithJSONLD
{
    protected $settings;
    protected $schema;

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


}
