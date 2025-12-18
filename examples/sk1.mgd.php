<?php

return [
  [
    'name' => 'SavedSearch_Queues',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Queues',
        'label' => ts('Queues (SavedSearch label with ts)'),
        'api_entity' => 'Queue',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'type:label',
            'status:label',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Queues_SearchDisplay_Queues_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Queues_Table_1',
        'label' => ts('Queues Table (SearchDisplay label with ts)'),
        'saved_search_id.name' => 'Queues',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'label' => 'System Queue ID (label)',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'name',
              'label' => 'Name (label)',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status:label',
              'label' => 'Status (label)',
              'sortable' => TRUE,
              'rewrite' => '',
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'created_id.display_name',
              'label' => 'Created By (label)',
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'created_id',
                'target' => '_blank',
              ],
              'title' => 'View Queue Contact (title)',
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'Queue',
                  'action' => 'view',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-external-link',
                  'text' => 'View Queue (text)',
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
              ],
            ],
          ],
          'toolbar' => [
            [
              'entity' => 'Queue',
              'action' => 'add',
              'target' => 'crm-popup',
              'icon' => 'fa-plus',
              'text' => 'Add Queue (text)',
              'style' => 'primary',
            ],
          ],
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];

