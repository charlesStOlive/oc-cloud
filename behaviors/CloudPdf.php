<?php namespace Waka\Cloud\Behaviors;

use Waka\Pdfer\Behaviors\PdfBehavior;
use Waka\Pdfer\Classes\PdfCreator;

class CloudPdf extends PdfBehavior
{
    public function onCloudPdfValidation()
    {
        $errors = $this->CheckValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $wakaPdfId = post('wakaPdfId');
        $modelId = post('modelId');
        return PdfCreator::find($wakaPdfId)->renderCloud($modelId);
    }
}
