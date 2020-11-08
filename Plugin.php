<?php namespace Waka\Cloud;

use App;
use Backend;
use Config;
use Event;
use System\Classes\PluginBase;
use View;
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
        \Storage::extend('google', function ($app, $config) {
            $client = new \Google_Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);
            $service = new \Google_Service_Drive($client);
            $adapter = new \Hypweb\Flysystem\GoogleDrive\GoogleDriveAdapter($service, $config['folderId']);

            return new \League\Flysystem\Filesystem($adapter);
        });

        Event::subscribe(new PluginEventSubscriber());

        if (Config::get('waka.wconfig::cloud.class')) {

            App::bind('cloudSystem', function ($app) {
                $cloudClass = Config::get('waka.wconfig::cloud.class');
                return new $cloudClass;
            });

        }

        \Waka\Worder\Controllers\Documents::extend(function ($controller) {

            // Implement behavior if not already implemented
            if (!$controller->isClassExtendedWith('Waka.cloud.Behaviors.SyncFiles')) {
                $controller->implement[] = 'Waka.cloud.Behaviors.SyncFiles';
            }

        });

        Event::listen('backend.form.extendFields', function ($widget) {
            $templateFolder = Config::get('waka.wconfig::cloud.word_folder');
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

        /**
         * EVENEMENTS POUR LA SYNCRONISATION
         */

        Event::listen('popup.list.tools', function ($controller, $sync_source) {
            if (get_class($controller) == 'Waka\Worder\Controllers\Documents' && $sync_source == 'word') {

                $syncOpt = Config::get('waka.wconfig::cloud.sync.word');
                $data = [
                    'type' => 'word',
                    'label' => $syncOpt['label'],
                ];
                return View::make('waka.cloud::syncbutton')->withData($data);;
            }

        });

        Event::listen('backend.update.prod', function ($controller) {

            if (in_array('Waka.cloud.Behaviors.SyncFiles', $controller->implement)) {
                $syncOpt = Config::get('waka.wconfig::cloud.sync.word');
                $data = [

                    'model' => $modelClass = str_replace('\\', '\\\\', get_class($controller->formGetModel())),
                    'modelId' => $controller->formGetModel()->id,
                    'label' => $syncOpt['label'],
                ];
                return View::make('waka.cloud::synconebutton')->withData($data);;
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
