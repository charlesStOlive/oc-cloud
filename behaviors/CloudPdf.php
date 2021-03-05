<?php namespace Waka\Cloud\Behaviors;

use Waka\Pdfer\Behaviors\PdfBehavior;
use Waka\Pdfer\Classes\PdfCreator;
use Waka\Utils\Classes\DataSource;

class CloudPdf extends PdfBehavior
{
    /**
     * Appel du popup pour un cloud unique
     */
    public function onLoadCloudPdfBehaviorForm()
    {
        $modelClass = post('modelClass');
        $modelId = post('modelId');

        $ds = new DataSource($modelClass, 'class');
        $options = $ds->getPartialOptions($modelId, 'Waka\Pdfer\Models\WakaPdf');

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;

        return $this->makePartial('$/waka/cloud/behaviors/cloudpdf/_popup.htm');
    }
    /**
     * Appel du conteneur popupIndex pour un cloud unique
     */
    public function onLoadCloudPdfBehaviorContentForm()
    {
        $modelClass = post('modelClass');
        $modelId = post('modelId');

        $ds = new DataSource($modelClass, 'class');
        $options = $ds->getPartialOptions($modelId, 'Waka\Pdfer\Models\WakaPdf');

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;

        return [
            '#popupActionContent' => $this->makePartial('$/waka/cloud/behaviors/cloudpdf/_content.htm'),
        ];
    }
    /**
     * Appel du conteneur popupLot pour lot
     */
    public function onLoadLotPdfBehaviorContentForm()
    {
        $modelClass = post('modelClass');
        $modelId = post('modelId');

        $ds = new DataSource($modelClass, 'class');
        $options = $ds->getPartialOptions($modelId, 'Waka\Pdfer\Models\WakaPdf');

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;

        return [
            '#popupLotContent' => $this->makePartial('$/waka/cloud/behaviors/cloudpdf/_lot.htm'),
        ];
    }

    /**
     * Validation cloud unique
     */
    public function onCloudPdfValidation()
    {
        //trace_log('onCloudPdfValidation');
        //trace_log(\Input::all());
        $errors = $this->CheckValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $productorId = post('productorId');
        $modelId = post('modelId');
        return PdfCreator::find($productorId)->renderCloud($modelId);
    }

    /**
     * Validation cloud multiple
     */
    public function onCloudLotPdfValidation()
    {
    }
}
