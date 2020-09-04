<?php namespace Waka\Cloud\Behaviors;

use App;
use Backend\Classes\ControllerBehavior;
use Config;
use Storage;

class SyncFiles extends ControllerBehavior
{
    public $model;

    public function __construct($controller)
    {
        parent::__construct($controller);

    }

    //ci dessous tous les calculs pour permettre l'import excel.

    public function onCallSync()
    {
        $sync_type = post('sync_type');
        $syncOpt = Config::get('waka.crsm::cloud.sync.' . $sync_type);
        $cloudFolder = $syncOpt['cloud_folder'];
        $appFolder = $syncOpt['app_folder'];

        $cloudSystem = App::make('cloudSystem');
        $cloudFolderDir = $cloudSystem->findOrCreateDir($cloudFolder);

        $docs = $cloudSystem->listFolderItems($cloudFolder, '/', false);

        $this->vars['docs'] = $docs;

        //\Storage::cloud()->put($lastFolderDir['path'] . '/' . $filename, $fileData);

        return $this->makePartial('$/waka/cloud/behaviors/syncfiles/_popup.htm');
    }
    public function onSyncCloud()
    {
        $syncOpt = Config::get('waka.crsm::cloud.sync.word');

        $cloudSystem = App::make('cloudSystem');

        $appFolder = $syncOpt['app_folder'];

        $docToSync = post('docToSync');
        foreach ($docToSync as $path => $docName) {
            $rawData = Storage::cloud()->get($path);
            Storage::put('media/' . $appFolder . '/' . $docName, $rawData);

        }
    }

    public function onSyncOne()
    {
        $syncOpt = Config::get('waka.crsm::cloud.sync.word');
        $cloudFolder = $syncOpt['cloud_folder'];
        $appFolder = $syncOpt['app_folder'];
        //

        $modelClass = post('model');
        $model = $modelClass::find(post('modelId'));
        $modelPath = $model->path;
        $array = explode('/', $modelPath);
        $fileName = array_pop($array);

        $cloudSystem = App::make('cloudSystem');
        $cloudFolderDir = $cloudSystem->findOrCreateDir($cloudFolder);
        $rawData = $cloudSystem->getRawFile($fileName, $cloudFolderDir['path']);
        Storage::put('media/' . $appFolder . '/' . $fileName, $rawData);
        \Flash::success("Fichier synchronisé");

    }
}