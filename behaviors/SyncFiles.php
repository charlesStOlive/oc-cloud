<?php namespace Waka\Cloud\Behaviors;

use App;
use Backend\Classes\ControllerBehavior;
use Config;
use Flash;
use Lang;
use Session;
use Storage;
use Waka\Utils\Classes\DataSource;

class SyncFiles extends ControllerBehavior
{
    public $model;

    public function __construct($controller)
    {
        parent::__construct($controller);
    }

    //ci dessous tous les calculs pour permettre l'import excel.

    public function onLotPopup()
    {
        $lists = $this->controller->makeLists();
        $widget = $lists[0] ?? reset($lists);
        $query = $widget->prepareQuery();
        $results = $query->get();

        $checkedIds = post('checked');

        $countCheck = null;
        if (is_countable($checkedIds)) {
            $countCheck = count($checkedIds);
        }
        Session::put('lotCloud.listId', $results->lists('id'));
        Session::put('lotCloud.checkedIds', $checkedIds);

        $model = post('model');
        $ds = new DataSource($model, 'class');

        $publications = $ds->publications;
        $options = $publications['types'] ?? [];

        //$options = $ds->getPartialIndexOptions('Waka\Mailer\Models\WakaMail');

        $this->vars['options'] = $options;
        $this->vars['all'] = $model::count();
        $this->vars['model'] = $model;
        $this->vars['filtered'] = $query->count();
        $this->vars['countCheck'] = $countCheck;

        return $this->makePartial('$/waka/cloud/behaviors/syncfiles/_lot.htm');
    }

    public function onSelectCloudType()
    {
        $model = post('model');
        $ds = new DataSource($model, 'class');
        $class = post('classType');
        $options = $ds->getPartialIndexOptions($class);

        $this->vars['options_prod'] = $options;

        return [
            '#publicationDataWidget' => $this->makePartial('$/waka/cloud/behaviors/syncfiles/_widget_data.htm'),
        ];
    }

    public function onCreateLot()
    {
        $errors = $this->CheckIndexValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        //trace_log(\Input::all());

        $class = post('classType');
        $productorId = post('productorId');
        $lotType = post('lotType');

        $listIds = null;
        if ($lotType == 'filtered') {
            $listIds = Session::get('lotCloud.listId');
        } elseif ($lotType == 'checked') {
            $listIds = Session::get('lotCloud.checkedIds');
        }
        Session::forget('lotCloud.listId');
        Session::forget('lotCloud.checkedIds');

        $datas = [
            'listIds' => $listIds,
            'productorId' => $productorId,
            'lot' => post('lot') == 'on' ? true : false,
        ];
        //trace_log($datas);
        if ($class == "Waka\Pdfer\Models\WakaPdf") {
            $jobId = \Queue::push('\Waka\Pdfer\Classes\PdfQueueCreator', $datas);
            \Event::fire('job.create.imp', [$jobId, 'Import en attente ']);
        }
        if ($class == "Waka\Worder\Models\Document") {
            $jobId = \Queue::push('\Waka\Worder\Classes\WordQueueCreator', $datas);
            \Event::fire('job.create.imp', [$jobId, 'Import en attente ']);
        }
        Flash::info(Lang::get('waka.cloud::lang.popup.flash'));
        return \Redirect::refresh();
    }

    public function CheckIndexValidation($inputs)
    {
        $rules = [
            'productorId' => 'required',
            'classType' => 'required',
        ];

        $validator = \Validator::make($inputs, $rules);

        if ($validator->fails()) {
            return "Vous devez choisir un type de doc et son modèle";
        } else {
            return false;
        }
    }

    public function onCallSync()
    {
        $sync_type = post('sync_type');
        $syncOpt = Config::get('wcli.wconfig::cloud.sync.' . $sync_type);
        $cloudFolder = $syncOpt['cloud_folder'];
        $appFolder = $syncOpt['app_folder'];

        $cloudSystem = App::make('cloudSystem');
        $cloudFolderDir = $cloudSystem->findOrCreateDir($cloudFolder);

        $docs = $cloudSystem->listFolderItems($cloudFolder, '/', false);

        $this->vars['docs'] = $docs;

        //\Storage::cloud()->put($lastFolderDir['path'] . '/' . $filename, $fileData);

        return $this->makePartial('$/waka/cloud/behaviors/syncfiles/_popup.htm');
    }
    // public function fire($job, $datas)
    // {
    //     if ($job) {
    //         \Event::fire('job.create.agg', [$job->id, 'Synchronisation de X fichiers']);
    //     }

    //     $syncOpt = Config::get('wcli.wconfig::cloud.sync.word');
    //     $cloudSystem = App::make('cloudSystem');
    //     $appFolder = $syncOpt['app_folder'];
    //     $docToSync = $datas['docToSync'];
    //     foreach ($docToSync as $path => $docName) {
    //         $rawData = Storage::cloud()->get($path);
    //         Storage::put('media/' . $appFolder . '/' . $docName, $rawData);

    //     }

    //     if ($job) {
    //         \Event::fire('job.end.agg', [$job]);
    //         $job->delete();
    //     }

    // }
    public function onSyncCloud()
    {
        $docToSync = post('docToSync');
        $jobId = \Queue::push('\Waka\Cloud\Classes\Queue\QueueCloud@syncFiles', ['docToSync' => $docToSync]);
        \Event::fire('job.create.sync', [$jobId, 'Sync en attente ']);
    }

    public function onSyncOne()
    {
        $syncOpt = Config::get('wcli.wconfig::cloud.sync.word');
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
