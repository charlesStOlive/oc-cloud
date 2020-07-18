<?php namespace Waka\Cloud\FormWidgets;

use App;
use Backend\Classes\FormWidgetBase;
use Config;

/**
 * MontagesList Form Widget
 */
class CloudList extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_cloud_cloud_list';

    /**
     * @inheritDoc
     */
    public function init()
    {
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        $this->getControllerCloudOptions();

        return $this->makePartial('list-cklist_fieldoptions');
        //list
        //list-cklist_cloudeable
        //list-cklist_fieldoptions
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
        $this->vars['cloudeables'] = $this->getControllerCloudOptions();
    }

    public function getDataSourceFromModel(String $model)
    {
        $modelClassDecouped = explode('\\', $model);
        $modelClassName = array_pop($modelClassDecouped);
        return \Waka\Utils\Models\DataSource::where('model', '=', $modelClassName)->first();
    }

    public function getControllerCloudOptions()
    {

        $options = Config::get('waka.crsm::cloud.controller');
        if (!$options) {
            throw new SystemException('Config waka.crsm::cloud.controller manquant');
        }
        $dataSource = $this->getDataSourceFromModel(get_class($this->model));

        $cloudeables = [];

        foreach ($options as $typeOption => $option) {
            if ($option['show'] ?? false) {
                if ($typeOption != 'images' && $typeOption != 'montages') {
                    $potentialProds = $dataSource->getPartialOptions($this->model->id, $option['class']);
                    foreach ($potentialProds as $key => $value) {
                        $obj = [
                            'modelId' => $key,
                            'label' => $value,
                            'key' => $typeOption,
                            'configuration' => $option,
                        ];
                        array_push($cloudeables, $obj);
                    }

                }
                if ($typeOption == 'images' || $typeOption == 'montages') {
                    $groupedImages = new \Waka\Cloudis\Classes\GroupedImages($this->model);

                    if ($typeOption == 'images') {
                        $allImages = $groupedImages->getModelImages();
                    }
                    if ($typeOption == 'montages') {
                        $allImages = $groupedImages->getModelMonntages();
                    }
                    foreach ($allImages as $key => $value) {
                        $obj = [
                            'modelId' => $value['id'] ?? $value['field'],
                            'type' => $value['type'],
                            'label' => $value['name'],
                            'key' => $typeOption,
                            'configuration' => $option,
                        ];
                        array_push($cloudeables, $obj);
                    }

                }

            }
        }
        return $cloudeables;
    }

    public function getMontageList()
    {
        //return $this->model->montages;
    }

    public function onLaunchCloud()
    {
        $cloudeables = $this->getControllerCloudOptions();
        $cloudSelecteds = post('checked');

        trace_log($cloudSelecteds);

        foreach ($cloudSelecteds as $cloudSelected) {
            $selection = explode('*', $cloudSelected);
            $modelId = array_pop($selection);
            $productor = $selection[0];
            $cloudeable = $this->findCloud($cloudeables, $modelId, $productor);
            if ($productor == 'images') {
                $this->downloadImage($modelId, $cloudeable);
            } elseif ($productor == 'montages') {
                $this->downloadMontage($modelId, $cloudeable);
            } else {
                // $this->launchCloudeable($cloudeable);
            }

        }

    }

    public function downloadImage($imageName, $cloudSelected)
    {
        $filename = str_slug($cloudSelected['label']) . '.png';
        $url = $this->model->{$imageName}->getUrl([]);
        $this->copytocloud($url, $filename);

    }

    public function downloadMontage($modelId, $cloudSelected)
    {
        $filename = str_slug($cloudSelected['label']) . '.png';
        $montage = $this->model->montages->find($modelId);
        $url = $this->model->getCloudiModelUrl($montage);
        $this->copytocloud($url, $filename);
    }

    public function copytocloud($url, $filename)
    {

        $filePath = $url;
        $fileData = file_get_contents($url);

        $folderOrg = new \Waka\Cloud\Classes\FolderOrganisation();
        $folders = $folderOrg->getFolder($this->model);

        $cloudSystem = App::make('cloudSystem');
        $lastFolderDir = $cloudSystem->createDirFromArray($folders);

        \Storage::cloud()->put($lastFolderDir['path'] . '/' . $filename, $fileData);
    }

    public function launchCloudeable($cloudeable)
    {
        trace_log("launchCloudeable");
        //Recuperation de var
        $productorClass = $cloudeable['configuration']['constructor'];
        $modelId = $this->model->id;
        $productorId = $cloudeable['modelId'];
        //Instanciation de la classe de production
        $refl = new \ReflectionClass($productorClass);
        $productorClass = $refl->newInstanceArgs([$productorId]);
        $productorClass->renderCloud($modelId);
    }

    public function findCloud($cloudeables, $modelId, $productor)
    {
        trace_log('modelId ' . $modelId);
        trace_log('productor' . $productor);
        trace_log($cloudeables);
        foreach ($cloudeables as $cloudeable) {
            if ($cloudeable['modelId'] == $modelId && $cloudeable['key'] == $productor) {
                return $cloudeable;
            }
        }
    }

    public function onRefreshList()
    {

        // $this->vars['montages'] = $this->getMontageList();
        // return [
        //     '#listMontage' => $this->makePartial('list'),
        // ];
        //return $this->makePartial('montageslist');
    }

    public function onShowCloudiImage()
    {
        // $montage = $this->model->montages->find(post('id'));
        // $url = $this->model->getCloudiModelUrl($montage);
        // $this->vars['url'] = $url;
        // return $this->makePartial('popup');
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->addJs('js/cloudlist.js', 'Waka.Cloud');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return \Backend\Classes\FormField::NO_SAVE_DATA;
    }
}
