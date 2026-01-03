<?php
namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Filters;

class DisableAutoUpdate extends Hook
{
	/**
	 * @return void
	 */
    public function register():void
    {
		Filters::add( 'auto_update_plugin', $this->noop() );
		Filters::add( 'auto_update_theme', $this->noop() );
    }
}