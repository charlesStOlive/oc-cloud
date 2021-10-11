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

        $ds = \DataSources::findByClass($modelClass);
        $options = $ds->getProductorOptions('Waka\Worder\Models\Document', $modelId);

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;
        $this->vars['modelClass'] = $modelClass;

        if($options) {
            return $this->makePartial('$/waka/cloud/behaviors/cloudword/_popup.htm');
        } else {
            return $this->makePartial('$/waka/utils/views/_popup_no_model.htm');
        }

        
    }
    /**
     * Appel du conteneur popupIndex pour un cloud unique
     */
    public function onLoadCloudWordBehaviorContentForm()
    {
        $modelClass = post('modelClass');
        $modelId = post('modelId');

        $ds = \DataSources::findByClass($modelClass);
        $options = $ds->getProductorOptions('Waka\Worder\Models\Document', $modelId);

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;
        $this->vars['modelClass'] = $modelClass;

        return ['#popupActionContent' => $this->makePartial('$/waka/cloud/behaviors/cloudword/_content.htm')];
    }
    /**
     * Appel du conteneur popupLot pour lot
     */
    public function onLoadLotWordBehaviorContentForm()
    {
        $modelClass = post('modelClass');

        $ds = \DataSources::findByClass($modelClass);
        $options = $ds->getLotProductorOptions('Waka\Worder\Models\Document');

        $this->vars['options'] = $options;
        $this->vars['modelClass'] = $modelClass;

        if($options) {
            return ['#popupActionContent' => $this->makePartial('$/waka/cloud/behaviors/cloudword/_lot.htm')];
        } else {
            return ['#popupActionContent' => $this->makePartial('$/waka/utils/views/_content_no_model.htm')];
        }

        
    }

    /**
     * Validation cloud unique
     */
    public function onCloudWordValidation()
    {
        $datas = post();
        $errors = $this->CheckValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $productorId = post('productorId');
        $modelId = post('modelId');
        $asks = $datas['asks_array'] ?? [];
        
        return WordCreator::find($productorId)->setModelId($modelId)->setAsksResponse($asks)->renderCloud();
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
