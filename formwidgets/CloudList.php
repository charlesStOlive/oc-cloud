<?php namespace Waka\Cloud\FormWidgets;

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

        return $this->makePartial('list');
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

        foreach ($cloudSelecteds as $cloudSelected) {
            $selection = explode('-', $cloudSelected);
            $modelId = array_pop($selection);
            $productor = $selection[0];
            $cloudeable = $this->findCloud($cloudeables, $modelId, $productor);
            $this->launchCloudeable($cloudeable);
        }

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
        foreach ($cloudeables as $cloudeable) {
            if ($cloudeable['modelId'] == $modelId && $cloudeable['modelId']) {
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
