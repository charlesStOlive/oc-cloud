<?php namespace Waka\Cloud;

use App;
use Backend;
use Config;
use Event;
use System\Classes\PluginBase;
use View;
use Waka\Cloud\Listener\PluginEventSubscriber;


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

    public $require = [
    ];

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

        if (Config::get('wcli.wconfig::cloud.class')) {
            App::bind('cloudSystem', function ($app) {
                $cloudClass = Config::get('wcli.wconfig::cloud.class');
                return new $cloudClass;
            });
        }

        \Waka\Worder\Controllers\Documents::extend(function ($controller) {

            // Implement behavior if not already implemented
            if (!$controller->isClassExtendedWith('Waka.cloud.Behaviors.SyncFiles')) {
                $controller->implement[] = 'Waka.cloud.Behaviors.SyncFiles';
            }
        });

        /**
         * EVENEMENTS POUR LA SYNCRONISATION
         */

        Event::listen('popup.list.tools', function ($controller, $sync_source) {
            if (get_class($controller) == 'Waka\Worder\Controllers\Documents' && $sync_source == 'word') {
                $user = \BackendAuth::getUser();
                if (!$user->hasAccess('waka.cloud.sync_word')) {
                    return;
                }

                $syncOpt = Config::get('wcli.wconfig::cloud.sync.word');
                $data = [
                    'type' => 'word',
                    'label' => $syncOpt['label'],
                ];
                return View::make('waka.cloud::syncbutton')->withData($data);
                ;
            }
        });

        Event::listen('backend.update.prod.word', function ($controller) {

            if (in_array('Waka.cloud.Behaviors.SyncFiles', $controller->implement)) {
                $user = \BackendAuth::getUser();
                if (!$user->hasAccess('waka.cloud.sync_word')) {
                    return;
                }

                $syncOpt = Config::get('wcli.wconfig::cloud.sync.word');
                $data = [

                    'model' => $modelClass = str_replace('\\', '\\\\', get_class($controller->formGetModel())),
                    'modelId' => $controller->formGetModel()->id,
                    'label' => $syncOpt['label'],
                ];
                return View::make('waka.cloud::synconebutton')->withData($data);
                ;
            }
        });

        // Event::listen('backend.top.index', function ($controller) {

        //     $user = \BackendAuth::getUser();
        //     if (!$user->hasAccess('waka.cloud.lot')) {
        //         return;
        //     }

        //     if (in_array('Waka.cloud.Behaviors.SyncFiles', $controller->implement)) {
        //         // $syncOpt = Config::get('wcli.wconfig::cloud.sync.word');
        //         $data = [

        //             'model' => $modelClass = str_replace('\\', '\\\\', $controller->listGetConfig()->modelClass),
        //             'label' => 'Lot sur le Cloud',
        //         ];
        //         return View::make('waka.cloud::syncLot')->withData($data);;
        //     }
        // });

        // CETTE FONCTION MARCHE MAIS PAS LA VERSION POUR WORD MIT EN STANDBY POUR LE MOMENT
        //Création des méthodes et des boutons pour le PDF.

        //trace_log("la classe existe");
        // \Waka\Pdfer\Classes\PdfCreator::extend(function ($creator) {
        //     //trace_log("class : ");
        //     //trace_log(get_class($creator));
        //     $creator->addDynamicMethod('renderCloud', function ($modelId, $lot = false) use ($creator) {
        //         $data = $creator->prepareCreatorVars($modelId);
        //         $pdf = $creator->createPdf($data);
        //         $pdfContent = $pdf->output();
        //         $cloudSystem = App::make('cloudSystem');
        //         $lastFolderDir = null;
        //         if ($lot) {
        //             $lastFolderDir = $cloudSystem->createDirFromArray(['lots']);
        //         } else {
        //             $folderOrg = new \Waka\Cloud\Classes\FolderOrganisation();
        //             $folders = $folderOrg->getFolder($creator->ds->model);
        //             $lastFolderDir = $cloudSystem->createDirFromArray($folders);
        //         }
        //         \Storage::cloud()->put($lastFolderDir['path'] . '/' . $data['fileName'], $pdfContent);
        //     });
        // });

        // \Waka\Pdfer\Behaviors\PdfBehavior::extend(function ($behavior) {
        //     //trace_log($behavior->methodExists('onCloudPdfValidation'));
        //     if (!$behavior->methodExists('onCloudPdfValidation')) {
        //         $behavior->addDynamicMethod('onCloudPdfValidation', function () use ($behavior) {
        //             $errors = $behavior->CheckValidation(\Input::all());
        //             if ($errors) {
        //                 throw new \ValidationException(['error' => $errors]);
        //             }
        //             $wakaPdfId = post('wakaPdfId');
        //             $modelId = post('modelId');
        //             return PdfCreator::find($wakaPdfId)->renderCloud($modelId);
        //         });
        //     }
        //     $behavior->methodExists('onCloudPdfValidation');

        // });
        // Event::listen('backend.creator.popup.validation.pdf', function ($controller, $modelId) {
        //     $user = \BackendAuth::getUser();
        //     if (!$user->hasAccess('waka.cloud.create_file')) {
        //         return;
        //     }
        //     if (Config('wcli.wconfig::cloud.controller.pdf.show')) {
        //         $data = [
        //             'modelId' => $modelId,
        //         ];
        //         return View::make('waka.cloud::pdf_cloud_btn')->withData($data);;
        //     }
        // });
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
        return [
            'waka.cloud.lot' => [
                'tab' => 'cloud',
                'label' => 'Droit sur la création de lots',
            ],
            'waka.cloud.sync_word' => [
                'tab' => 'cloud',
                'label' => 'Droit sur la synchronisation des docs word',
            ],
            'waka.cloud.create_file' => [
                'tab' => 'cloud',
                'label' => 'Droit de création sur le cloud',
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
