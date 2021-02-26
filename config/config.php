<?php return [
    'packages' => [
    ],
    'btns' => [
        'pdf' => [
            'label' => 'PDF sur le cloud',
            'class' => 'btn-secondary',
            'ajaxCaller' => 'onLoadCloudPdfBehaviorForm',
            'ajaxInlineCaller' => 'onLoadCloudPdfBehaviorContentForm',
            'icon' => 'oc-icon-file-pdf-o',
        ],
        'lot_pdf' => [
            'label' => 'Lots PDF',
            'class' => 'btn-secondary',
            'ajaxInlineCaller' => 'onLoadLotPdfBehaviorContentForm',
            'icon' => 'oc-icon-file-pdf-o',
        ],
        'word' => [
            'label' => 'Word sur le cloud',
            'class' => 'btn-secondary',
            'ajaxCaller' => 'onLoadCloudWordBehaviorForm',
            'ajaxInlineCaller' => 'onLoadCloudWordBehaviorContentForm',
            'icon' => 'oc-icon-file-word-o',
        ],
        'lot_word' => [
            'label' => 'Lots Word',
            'class' => 'btn-secondary',
            'ajaxInlineCaller' => 'onLoadLotWordBehaviorContentForm',
            'icon' => 'oc-icon-file-word-o',
        ],

    ],
];
