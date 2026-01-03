<?php
namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;
use Daedelus\Support\Filters;

class CustomizeAdmin extends Hook
{
	/** @var array|string[] */
	protected array $hooks = [
		CustomizeLogin::class,
		SafeLogout::class,
		DisableDashboardWidgets::class,
        DisableThemePatterns::class,
		RegisterAdminColumns::class,
	];

    /**
     * @return void
     */
    public function register():void
    {
	    $this->registerAdminStyle();
	    $this->registerFooterCopyright();
	    $this->registerFooterVersion();
    }

    /**
     * Footer copyright style
     *
     * @return void
     */
    protected function registerAdminStyle():void
    {
	    Actions::add( 'admin_head', function () {
            echo '<style>
            .majestic-footer a { vertical-align: middle; margin-top: 1px; display: inline-block; color: #2f2f2f; font-weight: bold;text-decoration: none; transition: color .3s ease-in-out; }
            .majestic-footer a svg { width: auto; height: 35px; opacity: 0.7; transition: opacity .3s ease-out; margin-left: 2.5px; }
            .majestic-footer a:hover svg, .majestic-footer a:focus svg { opacity: 1; }
          </style>';
        } );

        /**
         * Replace the WordPress logo in the Admin Bar to the creator logo
         *
         * @return string
         */
        Actions::add( 'wp_before_admin_bar_render', function () {
            echo '<style type="text/css">
    #wpadminbar #wp-admin-bar-wp-logo > .ab-item {
        padding: 0 10px;
        background-image: url(' . get_stylesheet_directory_uri() . '/logo.svg) !important;
        background-size: 50%;
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0.75;
    }
    #wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
        content: " ";
        top: 2px;
    }
</style>';
        } );
    }

    /**
     * Footer copyright
     *
     * @return void
     */
    protected function registerFooterCopyright():void
    {
        Filters::add( 'update_footer', function () {
            $logo = '<svg viewBox="0 0 40 48" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0)">
<path d="M0 17.6218V10.9043L6.66667 14.269L0 17.6218Z" fill="#B8B8B8"/>
<path d="M6.66667 7.55151V14.269L0 10.9043L6.66667 7.55151Z" fill="#DDDDDD"/>
<path d="M6.66667 14.269V20.9865L0 17.6219L6.66667 14.269Z" fill="#A6A6A6"/>
<path d="M0 24.3037V17.5862L6.66667 20.9508L0 24.3037Z" fill="#A6A6A6"/>
<path d="M0 31.0212V24.3037L6.66667 27.6684L0 31.0212Z" fill="#B8B8B8"/>
<path d="M6.66667 20.9509V27.6684L0 24.3037L6.66667 20.9509Z" fill="#DDDDDD"/>
<path d="M6.66667 27.6685V34.3859L0 31.0213L6.66667 27.6685Z" fill="#A6A6A6"/>
<path d="M0 37.7031V30.9856L6.66667 34.3503L0 37.7031Z" fill="#949494"/>
<path d="M6.66667 34.3503V41.056L0 37.7032L6.66667 34.3503Z" fill="#868686"/>
<path d="M13.3333 10.9042V4.18677L20 7.55143L13.3333 10.9042Z" fill="#A6A6A6"/>
<path d="M20 0.833984V7.55147L13.3333 4.1868L20 0.833984Z" fill="#B8B8B8"/>
<path d="M13.3334 4.18677V10.9042L6.66669 7.55143L13.3334 4.18677Z" fill="#DDDDDD"/>
<path d="M6.66669 14.269V7.55151L13.3334 10.9043L6.66669 14.269Z" fill="#A6A6A6"/>
<path d="M20 14.269V20.9865L13.3333 17.6219L20 14.269Z" fill="#B8B8B8"/>
<path d="M13.3333 24.3037V17.5862L20 20.9508L13.3333 24.3037Z" fill="#A6A6A6"/>
<path d="M20 20.9509V27.6684L13.3333 24.3037L20 20.9509Z" fill="#949494"/>
<path d="M13.3333 37.7387V31.0212L20 34.3859L13.3333 37.7387Z" fill="#DDDDDD"/>
<path d="M20 34.386V41.1035L13.3333 37.7388L20 34.386Z" fill="#B8B8B8"/>
<path d="M13.3333 44.4206V37.7031L20 41.0559L13.3333 44.4206Z" fill="#949494"/>
<path d="M20 7.55147V0.833984L26.6667 4.1868L20 7.55147Z" fill="#DDDDDD"/>
<path d="M20 41.0559V47.7734L13.3333 44.4206L20 41.0559Z" fill="#868686"/>
<path d="M20 47.7734V41.0559L26.6667 44.4206L20 47.7734ZM26.6667 10.9043V4.18677L33.3333 7.55143L26.6667 10.9043Z" fill="#949494"/>
<path d="M26.6667 4.18677V10.9042L20 7.55143L26.6667 4.18677Z" fill="#A6A6A6"/>
<path d="M33.3334 7.55151V14.269L26.6667 10.9043L33.3334 7.55151Z" fill="#868686"/>
<path d="M20 20.9865V14.269L26.6667 17.6219L20 20.9865Z" fill="#DDDDDD"/>
<path d="M26.6667 17.5862V24.3037L20 20.9508L26.6667 17.5862Z" fill="#949494"/>
<path d="M20 27.6684V20.9509L26.6667 24.3037L20 27.6684Z" fill="#868686"/>
<path d="M26.6667 31.0212V37.7387L20 34.3859L26.6667 31.0212Z" fill="#949494"/>
<path d="M20 41.1035V34.386L26.6667 37.7388L20 41.1035Z" fill="#A6A6A6"/>
<path d="M26.6667 37.7031V44.4206L20 41.0559L26.6667 37.7031Z" fill="#DDDDDD"/>
<path d="M33.3333 14.269V7.55151L40 10.9043L33.3333 14.269Z" fill="#949494"/>
<path d="M40 10.9043V17.6218L33.3333 14.269L40 10.9043Z" fill="#868686"/>
<path d="M33.3333 20.9865V14.269L40 17.6219L33.3333 20.9865Z" fill="#A6A6A6"/>
<path d="M40 17.5862V24.3037L33.3333 20.9508L40 17.5862Z" fill="#DDDDDD"/>
<path d="M33.3333 27.6684V20.9509L40 24.3037L33.3333 27.6684Z" fill="#A6A6A6"/>
<path d="M40 24.3037V31.0212L33.3333 27.6684L40 24.3037Z" fill="#949494"/>
<path d="M33.3333 34.3859V27.6685L40 31.0213L33.3333 34.3859Z" fill="#B8B8B8"/>
<path d="M40 30.9856V37.7031L33.3333 34.3503L40 30.9856Z" fill="#A6A6A6"/>
<path d="M33.3333 41.056V34.3503L40 37.7032L33.3333 41.056Z" fill="#868686"/>
</g>
<defs>
<clipPath id="clip0">
<rect y="0.833984" width="40" height="46.9395" fill="white"/>
</clipPath>
</defs>
</svg>';

            return '<span class="majestic-footer">Made by <a href="https://www.anthonypauwels.be/" target="_blank">' . $logo . '</a></span>';
        } );
    }

    /**
     * Footer theme version
     *
     * @return void
     */
    protected function registerFooterVersion():void
    {
        Filters::add( 'admin_footer_text', function () {
            return '<strong>WordPress</strong> ' . get_bloginfo( 'version', 'display' ) . '<br/>' .
                   '<strong>Laravel</strong> ' . app()->version();
        } );

        Filters::add( 'update_footer', fn () => '', 99 );
    }
}