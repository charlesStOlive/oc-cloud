<?php namespace Waka\Cloud;

use App;
use Backend;
use Config;
use Event;
use System\Classes\PluginBase;
use Waka\Cloud\Listener\PluginEventSubscriber;
use Waka\Worder\Controllers\Documents as DocumentController;
use Waka\Worder\Models\Document as DocumentModel;

/**
 * cloud Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'cloud',
            'description' => 'No description provided yet...',
            'author' => 'Waka',
            'icon' => 'icon-leaf',
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Event::subscribe(new PluginEventSubscriber());

        if (Config::get('waka.crsm::cloud.class')) {

            App::bind('cloudSystem', function ($app) {
                $cloudClass = Config::get('waka.crsm::cloud.class');
                return new $cloudClass;
            });

        }

        Event::listen('backend.form.extendFields', function ($widget) {
            $templateFolder = Config::get('waka.crsm::cloud.word_folder');
            if (!$templateFolder) {
                return;
            }
            // Only for the User controller
            if (!$widget->getController() instanceof DocumentController) {
                //tracelog("erreur controller");
                //tracelog(get_class($widget->getController()));
                return;
            }

            // Only for the User model
            if (!$widget->model instanceof DocumentModel) {
                //tracelog("erreur model");
                return;
            }
            if ($widget->isNested) {return;}

            // This includes the fields from the parent form instead instead...

            $path = $widget->getField('path');
            if ($path) {
                $path->type = 'dropdown';
                $cloudSystem = App::make('cloudSystem');

                $options = $cloudSystem->listFolderItems($templateFolder);
                $path->options = $options->toArray();
            }

        });

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

    }

    public function registerFormWidgets()
    {
        return [
            'Waka\Cloud\FormWidgets\CloudList' => 'cloudlist',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'waka.cloud.some_permission' => [
                'tab' => 'cloud',
                'label' => 'Some permission',
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'cloud' => [
                'label' => 'cloud',
                'url' => Backend::url('waka/cloud/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['waka.cloud.*'],
                'order' => 500,
            ],
        ];
    }
}
