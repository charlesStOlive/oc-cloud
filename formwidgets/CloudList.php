<?php namespace Waka\Cloud\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Config;
use Waka\Utils\Classes\DataSource;

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

    public function getDataSource()
    {
        return new DataSource(get_class($this->model), 'class');
    }

    // public function getDataSourceFromModel(String $model)
    // {
    //     $modelClassDecouped = explode('\\', $model);
    //     $modelClassName = array_pop($modelClassDecouped);
    //     return \Waka\Utils\Models\DataSource::where('model', '=', $modelClassName)->first();
    // }

    public function getControllerCloudOptions()
    {

        $options = Config::get('wcli.wconfig::cloud.controller');
        if (!$options) {
            throw new \SystemException('Config wcli.wconfig::cloud.controller manquant');
        }
        $ds = $this->getDataSource();
        $ds->instanciateModel($this->model->id);

        $cloudeables = [];

        foreach ($options as $typeOption => $option) {
            //trace_log($option);
            if ($option['show'] ?? false) {
                if ($typeOption != 'images' && $typeOption != 'montages' && $typeOption != 'cloudis') {
                    $potentialProds = $ds->getProductorOptions($option['class'], $this->model->id);
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
                if ($typeOption == 'images' || $typeOption == 'montages' || $typeOption == 'cloudis') {
                    //$groupedImages = new \Waka\Cloudis\Classes\GroupedImages($this->model);

                    if ($typeOption == 'cloudis') {
                        $allImages = $ds->wimages->listCloudis($this->model);
                    }
                    if ($typeOption == 'montages') {
                        $allImages = $ds->wimages->listMontages($this->model);
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

        $datas = [
            'cloudeables' => $this->getControllerCloudOptions(),
            'cloudSelecteds' => post('checked'),
            'srcModelClass' => get_class($this->model),
            'srcModelId' => $this->model->id,
        ];
        //trace_log($datas);
        // $coin = new \Waka\Cloud\Classes\Queue\QueueCloud();
        // $coin->createFromModel(null, $datas);
        $jobId = \Queue::push('\Waka\Cloud\Classes\Queue\QueueCloud@createFromModel', $datas);
        \Event::fire('job.create.sync', [$jobId, 'Création por ' . $this->model->name . ' en attente ']);
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
