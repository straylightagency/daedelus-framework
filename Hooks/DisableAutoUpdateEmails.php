<?php
namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Filters;

class DisableAutoUpdateEmails extends Hook
{
	/**
	 * @return void
	 */
    public function register():void
    {
		Filters::add( 'auto_core_update_send_email', $this->noop() );
		Filters::add( 'auto_plugin_update_send_email', $this->noop() );
		Filters::add( 'auto_theme_update_send_email', $this->noop() );
    }
}