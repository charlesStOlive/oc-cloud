<?php namespace Waka\Cloud\Behaviors;

use Waka\Utils\Classes\DataSource;
use Waka\Worder\Behaviors\WordBehavior;
use Waka\Worder\Classes\WordCreator;

class CloudWord extends WordBehavior
{
    /**
     * Appel du popup pour un cloud unique
     */
    public function onLoadCloudWordBehaviorForm()
    {
        $modelClass = post('modelClass');
        $modelId = post('modelId');

        $ds = new DataSource($modelClass, 'class');
        $options = $ds->getProductorOptions('Waka\Worder\Models\Document', $modelId);

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;

        return $this->makePartial('$/waka/cloud/behaviors/cloudword/_popup.htm');
    }
    /**
     * Appel du conteneur popupIndex pour un cloud unique
     */
    public function onLoadCloudWordBehaviorContentForm()
    {
        $modelClass = post('modelClass');
        $modelId = post('modelId');

        $ds = new DataSource($modelClass, 'class');
        $options = $ds->getProductorOptions('Waka\Worder\Models\Document', $modelId);

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;

        return ['#popupActionContent' => $this->makePartial('$/waka/cloud/behaviors/cloudword/_content.htm')];
    }
    /**
     * Appel du conteneur popupLot pour lot
     */
    public function onLoadLotWordBehaviorContentForm()
    {
        $modelClass = post('modelClass');
        $modelId = post('modelId');

        $ds = new DataSource($modelClass, 'class');
        $options = $ds->getProductorOptions('Waka\Worder\Models\Document', $modelId);

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;

        return ['#popupLotContent' => $this->makePartial('$/waka/cloud/behaviors/cloudword/_lot.htm')];
    }

    /**
     * Validation cloud unique
     */
    public function onCloudWordValidation()
    {
        $errors = $this->CheckValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $productorId = post('productorId');
        $modelId = post('modelId');
        return WordCreator::find($productorId)->setModelId($modelId)->renderCloud();
    }

    /**
     * Validation cloud multiple
     */
    public function onCloudLotWordValidation()
    {
    }
}
