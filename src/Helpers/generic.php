<?php namespace Lti\Seo\Helpers;

interface ICanHelp
{
}

interface ICanHelpWithJSONLD
{
    const USER_SETTING = 1;
    const USER_META_SETTING = 2;
    const GENERAL_SETTING = 3;
    const POST_SETTING = 4;
    const POST_META_SETTING = 5;
    const HELPER_SETTING = 6;

    public function get_social_urls();

    public function get_schema_org( $setting, $setting_type );

    public function get_author_social_info();

    public function get_search_action_type();

    public function get_current_url();

    public function date_conversion( $value );

    public function date_get_year( $value );

    public function get_thumbnail_url();
}

abstract class Generic_Helper implements ICanHelpWithJSONLD
{
    protected $settings;

    public function __construct( $settings )
    {
        $this->settings = $settings;
    }

    public function get_settings()
    {
        return $this->settings;
    }
}
