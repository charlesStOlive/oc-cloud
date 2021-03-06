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
        $options = $ds->getProductorOptions('Waka\Pdfer\Models\WakaPdf', $modelId);
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
        $options = $ds->getProductorOptions('Waka\Pdfer\Models\WakaPdf', $modelId);

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
        $options = $ds->getProductorOptions('Waka\Pdfer\Models\WakaPdf', $modelId);

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
        return PdfCreator::find($productorId)->setModelId($modelId)->renderCloud();
    }

    /**
     * Validation cloud multiple
     */
    public function onCloudLotPdfValidation()
    {
        $errors = $this->CheckIndexValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $lotType = post('lotType');
        $productorId = post('productorId');
        $listIds = null;
        if ($lotType == 'filtered') {
            $listIds = Session::get('lot.listId');
        } elseif ($lotType == 'checked') {
            $listIds = Session::get('lot.checkedIds');
        }
        Session::forget('lot.listId');
        Session::forget('lot.checkedIds');
        //
        $datas = [
            'listIds' => $listIds,
            'productorId' => $productorId,
        ];
        try {
            $job = new \Waka\Mailer\Jobs\lotPdf($datas);
            $jobManager = \App::make('Waka\Wakajob\Classes\JobManager');
            $jobManager->dispatch($job, "waka.cloud::lang.cloudpdf.job_request");
            $this->vars['jobId'] = $job->jobId;
        } catch (Exception $ex) {
                $this->controller->handleError($ex);
        }
        return ['#popupActionContent' => $this->makePartial('$/waka/wakajob/controllers/jobs/_confirm.htm')];
    }

    public function CheckIndexValidation($inputs)
    {
        $rules = [
            'productorId' => 'required',
        ];

        $validator = \Validator::make($inputs, $rules);

        if ($validator->fails()) {
            return $validator->messages()->first();
        } else {
            return false;
        }
    }
}
