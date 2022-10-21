<?php
/**
 * Copyright (c) 2018 Viamage Limited
 * All Rights Reserved
 */

namespace Waka\Cloud\Jobs;

use Waka\Wakajob\Classes\JobManager;
use Waka\Wakajob\Classes\RequestSender;
use Waka\Wakajob\Contracts\WakajobQueueJob;
use Winter\Storm\Database\Model;
use Viamage\CallbackManager\Models\Rate;
use Waka\Pdfer\Classes\PdfCreator;
use Waka\Utils\Classes\DataSource;

/**
 * Class SendRequestJob
 *
 * Sends POST requests with given data to multiple target urls. Example of Wakajob Job.
 *
 * @package Waka\Wakajob\Jobs
 */
class LotPdf implements WakajobQueueJob
{
    /**
     * @var int
     */
    public $jobId;

    /**
     * @var JobManager
     */
    public $jobManager;

    /**
     * @var array
     */
    private $data;

    /**
     * @var bool
     */
    private $updateExisting;

    /**
     * @var int
     */
    private $chunk;

    /**
     * @var string
     */
    private $table;

    /**
     * @param int $id
     */
    public function assignJobId(int $id)
    {
        $this->jobId = $id;
    }

    /**
     * SendRequestJob constructor.
     *
     * We provide array with stuff to send with post and array of urls to which we want to send
     *
     * @param array  $data
     * @param string $model
     * @param bool   $updateExisting
     * @param int    $chunk
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->updateExisting = true;
        $this->chunk = 1;
    }

    /**
     * Job handler. This will be done in background.
     *
     * @param JobManager $jobManager
     */
    public function handle(JobManager $jobManager)
    {
        /**
         * travail preparatoire sur les donnes
         */
        $productorId = $this->data['productorId'];
        //
        $targets = $this->data['listIds'];
        $lot = $this->data['lot_folder'] ?? false;
        /**
         * We initialize database job. It has been assigned ID on dispatching,
         * so we pass it together with number of all elements to proceed (max_progress)
         */
        $loop = 1;
        $jobManager->startJob($this->jobId, \count($targets));
        $create = 0;
        $scopeError = 0;
        $idsError = [];
        // Fin inistialisation

        //Travail sur les données
        $targets = array_chunk($targets, $this->chunk);
        
        try {
            foreach ($targets as $chunk) {
                foreach ($chunk as $targetId) {
                    // TACHE DU JOB
                    if ($jobManager->checkIfCanceled($this->jobId)) {
                        $jobManager->failJob($this->jobId);
                        break;
                    }
                    $jobManager->updateJobState($this->jobId, $loop);
                    /**
                     * DEBUT TRAITEMENT **************
                     */
                    $pdfCreator = PdfCreator::find($productorId);
                    $modelDataSource = $pdfCreator->getProductor()->data_source;
                    
                    $pdfCreator->setModelId($targetId);
                    $scopeIsOk = $pdfCreator->checkConditions();
                    if (!$scopeIsOk) {
                        $scopeError++;
                        continue;
                    }
                    try {
                        $pdfCreator->renderCloud($lot);
                        ++$create;
                    }  catch(\Exception $ex) {
                        array_push($idsError, $targetId);
                        continue;

                    }
                    /**
                     * FIN TRAITEMENT **************
                     */
                }
                $loop += $this->chunk;
            }
            $jobManager->updateJobState($this->jobId, $loop);
            $jobManager->completeJob(
                $this->jobId,
                [
                'Message' => \count($targets).' '. \Lang::get('waka.cloud::lang.pdf.job_title'),
                'waka.cloud::lang.pdf.job_create' => $create,
                'waka.cloud::lang.pdf.job_scoped' => $scopeError,
                'waka.cloud::lang.pdf.job_ids_errors' => implode(',',$idsError),
                ]
            );
        } catch (\Exception $ex) {
            //trace_log("Exception");
            /**/trace_log($ex->getMessage());
            $jobManager->failJob($this->jobId, ['error' => $ex->getMessage()]);
        }
    }
}
