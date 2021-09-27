services:
    _defaults:
        public: false
        autoconfigure: true
        autowire: true

    <?= $bundle_namespace ?>\:
        resource: '../../*'
        exclude:
            - '../../{Entity,Migrations,Tests,Resources,Model}'

    <?= $bundle_namespace ?>\Controller\:
        resource: '../../Controller/*'
        tags:
            - controller.service_arguments