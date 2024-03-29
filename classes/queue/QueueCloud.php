<?php namespace Waka\Cloud\Classes\Queue;

use App;
use Config;
use Storage;

class QueueCloud
{
    public function syncFiles($job, $datas)
    {
        $docToSync = $datas['docToSync'];

        if ($job) {
            \Event::fire('job.start.sync', [$job, 'Synchronisation de ' . count($docToSync) . ' fichiers']);
        }

        $syncOpt = Config::get('wcli.wconfig::cloud.sync.word');
        $cloudSystem = App::make('cloudSystem');
        $appFolder = $syncOpt['app_folder'];

        foreach ($docToSync as $path => $docName) {
            $rawData = $cloudSystem->get($path);
            Storage::put('media/' . $appFolder . '/' . $docName, $rawData);
        }

        if ($job) {
            \Event::fire('job.end.sync', [$job]);
            $job->delete();
        }
    }
    public function createFromModel($job, $data)
    {

        $cloudeables = $data['cloudeables'];
        $cloudSelecteds = $data['cloudSelecteds'];
        $modelClass = $data['srcModelClass'];
        $model = $modelClass::find($data['srcModelId']);

        if ($job) {
            \Event::fire('job.start.sync', [$job, 'Création de doc pour  ' . $model->name . ' en cours']);
        }

        foreach ($cloudSelecteds as $cloudSelected) {
            $selection = explode('*', $cloudSelected);
            $modelId = array_pop($selection);
            $productor = $selection[0];
            $cloudeable = $this->findCloud($cloudeables, $modelId, $productor);
            if ($productor == 'images') {
                $this->downloadImage($model, $modelId, $cloudeable);
            } elseif ($productor == 'montages') {
                $this->downloadMontage($model, $modelId, $cloudeable);
            } else {
                $this->launchCloudeable($model, $cloudeable);
            }
        }

        if ($job) {
            \Event::fire('job.end.sync', [$job]);
            $job->delete();
        }
        $cloudeables = null;
        $cloudSelecteds = null;
        $model = null;
    }

    public function downloadImage($model, $imageName, $cloudSelected)
    {
        $filename = str_slug($cloudSelected['label']) . '.png';
        $url = $model->{$imageName}->getUrl([]);
        $this->copytocloud($model, $url, $filename);
    }

    public function downloadMontage($model, $modelId, $cloudSelected)
    {
        $filename = str_slug($cloudSelected['label']) . '.png';
        $montage = $model->montages->find($modelId);
        $url = $model->getMontage($montage);
        $this->copytocloud($model, $url, $filename);
    }

    public function launchCloudeable($model, $cloudeable)
    {
        //trace_log("launchCloudeable");
        //Recuperation de var
        $productorClass = $cloudeable['configuration']['constructor'];
        $modelId = $model->id;
        $productorId = $cloudeable['modelId'];
        //Instanciation de la classe de production
        $refl = new \ReflectionClass($productorClass);
        $productorClass = $refl->newInstanceArgs([$productorId]);
        $productorClass->renderCloud($modelId);
    }

    public function copytocloud($model, $url, $filename)
    {

        $filePath = $url;
        $fileData = file_get_contents($url);

        $folderOrg = new \Waka\Cloud\Classes\FolderOrganisation();
        $foldersPath = $folderOrg->getPath($model);

        $cloudSystem = App::make('cloudSystem');
        $cloudSystem->put($foldersPath . '/' . $filename, $fileData);
    }

    public function findCloud($cloudeables, $modelId, $productor)
    {
        foreach ($cloudeables as $cloudeable) {
            if ($cloudeable['modelId'] == $modelId && $cloudeable['key'] == $productor) {
                return $cloudeable;
            }
        }
    }
}
