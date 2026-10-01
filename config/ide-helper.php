<?php

return [

    








    'filename' => '_ide_helper.php',

    








    'models_filename' => '_ide_helper_models.php',

    









    'meta_filename' => '.phpstorm.meta.php',

    








    'include_fluent' => false,

    











    'include_factory_builders' => false,

    








    'write_model_magic_where' => false,

    








    'write_model_external_builder_methods' => true,

    









    'write_model_relation_count_properties' => false,
    'write_model_relation_exists_properties' => false,

    












    'write_eloquent_model_mixins' => false,

    









    'include_helpers' => false,

    'helper_files' => [
        base_path() . '/vendor/laravel/framework/src/Illuminate/Support/helpers.php',
        base_path() . '/vendor/laravel/framework/src/Illuminate/Foundation/helpers.php',
    ],

    












    'model_locations' => [
        'app/Models',
    ],

    








    'ignored_models' => [
         
    ],

    










    'model_hooks' => [
         
    ],

    








    'extra' => [
        'Eloquent' => ['Illuminate\Database\Eloquent\Builder', 'Illuminate\Database\Query\Builder'],
        'Session' => ['Illuminate\Session\Store'],
    ],

    'magic' => [],

    









    'interfaces' => [
         
    ],

    /*
     |--------------------------------------------------------------------------
     | Support for camel cased models
     |--------------------------------------------------------------------------
     |
     | There are some Laravel packages (such as Eloquence) that allow for accessing
     | Eloquent model properties via camel case, instead of snake case.
     |
     | Enabling this option will support these packages by saving all model
     | properties as camel case, instead of snake case.
     |
     | For example, normally you would see this:
     |
     |  * @property \Illuminate\Support\Carbon $created_at
     |  * @property \Illuminate\Support\Carbon $updated_at
     |
     | With this enabled, the properties will be this:
     |
     |  * @property \Illuminate\Support\Carbon $createdAt
     |  * @property \Illuminate\Support\Carbon $updatedAt
     |
     | Note, it is currently an all-or-nothing option.
     |
     */
    'model_camel_case_properties' => false,

    







    'type_overrides' => [
        'integer' => 'int',
        'boolean' => 'bool',
    ],

    








    'include_class_docblocks' => false,

    









    'force_fqn' => true,

    








    'use_generics_annotations' => true,

    









    'macro_default_return_types' => [
        Illuminate\Http\Client\Factory::class => Illuminate\Http\Client\PendingRequest::class,
    ],

    









    'additional_relation_types' => [],

    











    'additional_relation_return_types' => [],

    /*
    |--------------------------------------------------------------------------
    | Enforce nullable Eloquent relationships on not null columns
    |--------------------------------------------------------------------------
    |
    | When set to true (default), this option enforces nullable Eloquent relationships.
    | However, in cases where the application logic ensures the presence of related
    | records it may be desirable to set this option to false to avoid unwanted null warnings.
    |
    | Default: true
    | A not null column with no foreign key constraint will have a "nullable" relationship.
    |  * @property int $not_null_column_with_no_foreign_key_constraint
    |  * @property-read BelongsToVariation|null $notNullColumnWithNoForeignKeyConstraint
    |
    | Option: false
    | A not null column with no foreign key constraint will have a "not nullable" relationship.
    |  * @property int $not_null_column_with_no_foreign_key_constraint
    |  * @property-read BelongsToVariation $notNullColumnWithNoForeignKeyConstraint
    |
    */

    'enforce_nullable_relationships' => true,

    







    'post_migrate' => [
        // 'ide-helper:models --nowrite',
    ],

];
