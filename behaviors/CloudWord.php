<?php namespace Waka\Cloud\Behaviors;

use Waka\Utils\Classes\DataSource;
use Waka\Worder\Behaviors\WordBehavior;
use Waka\Worder\Classes\WordCreator;
use Session;

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

        return ['#popupActionContent' => $this->makePartial('$/waka/cloud/behaviors/cloudword/_lot.htm')];
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
            $job = new \Waka\Cloud\Jobs\LotWord($datas);
            $jobManager = \App::make('Waka\Wakajob\Classes\JobManager');
            $jobManager->dispatch($job, "waka.cloud::lang.word.job_request");
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
