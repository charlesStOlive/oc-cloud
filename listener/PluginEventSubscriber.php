<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Waka\Cloud\Listener;

/**
 * Description of PluginEventSubscriber
 *
 * @author Cydrick Nonog <cydrick.dev@gmail.com>
 */
class PluginEventSubscriber
{
    public function onToolsDownloadOption(
        \Backend\Classes\Controller $controller,
        $action,
        array $params = []
    ) {
        //trace_log("onToolsDownloadOption");
    }

    public function subscribe($events)
    {
        $events->listen('tools.download.option', [$this, 'onToolsDownloadOption']);
    }
}
