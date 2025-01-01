<?php
$config = [];

$config['tasks'] = [
    'TransportProvidersTask',
    'InstallPackagesTask',
    'SetOptionsTask',
    'GrantAccessUserTask',
    'MiniShop2Task',
];

$config['transport_providers'] = [
    'modstore.pro' => [
        'name' => 'modstore.pro',
        'service_url' => 'https://modstore.pro/extras/',
        'username' => '',
        'api_key' => '',
    ],
    'modxkit.com' => [
        'name' => 'modxkit.com',
        'service_url' => 'https://modxkit.com/extras/',
        'username' => '',
        'api_key' => '',
    ],
];
$config['install_packages'] = [
    'ace' => [
        'name' => 'Ace',
        'version' => '',
        'provider' => 'modx.com',
    ],
    'controlerrorlog' => [
        'name' => 'controlErrorLog',
    ],
    'console' => [
        'name' => 'Console',
    ],
    'VersionX' => [
        'name' => 'versionx',
    ],
    'clientconfig' => [
        'name' => 'ClientConfig',
    ],
    'debugparser' => [
        'name' => 'debugParser',
    ],
    'tagelementplugin' => [
        'name' => 'tagElementPlugin',
    ],
    'upgrademodx' => [
        'name' => 'UpgradeMODX',
    ],
    'translit' => [
        'name' => 'translit',
    ],
    'collections' => [
        'name' => 'Collections',
    ],
    'tinymcerte' => [
        'name' => 'TinyMCE Rich Text Editor',
    ],
    'admintools' => [
        'name' => 'AdminTools',
        'provider' => 'modstore.pro',
    ],
    'moddevtools' => [
        'name' => 'modDevTools',
        'provider' => 'modstore.pro',
    ],
    'frontendmanager' => [
        'name' => 'frontendManager',
        'provider' => 'modstore.pro',
    ],
    'staticelementslive' => [
        'name' => 'StaticElementsLive',
        'provider' => 'modstore.pro',
    ],
    'theme.bootstrap' => [
        'name' => 'Theme.Bootstrap',
        'provider' => 'modstore.pro',
    ],
    'minishop2' => [
        'name' => 'miniShop2',
        'provider' => 'modstore.pro',
    ],
];

$config['set_options'] = [
    'friendly_urls' => 1,
    'use_alias_path' => 1,
    'friendly_alias_translit' => 'russian',
];

$config['grant_access_user'] = [
    'manager' => [
        'users' => [[
            'username' => 'manager',
            'password' => '',
            'email' => '',
        ]],
        'context_key' => 'mgr',
        'group_name' => 'Manager',
        'access_role_name' => '',
        'access_role_authority' => 9,
        'access_policy_name' => '',
        'access_policy_template_name' => '',
        'access_policy_template_override' => 'AdministratorTemplate',
        // see more (en) https://gist.github.com/Prihod/09ff88088c67dff4a48290344a46ec9b
        // see more (ru) https://gist.github.com/Prihod/ce52b1cb0e12ac9b5f14bc94dfcd03a5
        'permissions' => [
            'frames' => 1, // To use the MODX Manager UI at all.
            'home' => 1, // To view the Welcome page.
            'menu_user' => 1, // Show the top menu item "User".
            'view_template' => 1, // To view any Templates.
            'class_map' => 1, // To view a list of classes in the Class Map.
            'change_password' => 1, // The user can change their password.
            'change_profile' => 1, // The user can edit their profile.
            'countries' => 1, // View the list of countries.
            'create' => 1, // Ability to "create" new objects.
            'delete_document' => 1, // Delete and move resources.
            'delete_static_resource' => 1, // Permission "delete_document" is required to delete or unpublish a static resource.
            'delete_symlink' => 1, // Permission "delete_document" is required to delete or unpublish a symbolic link.
            'delete_user' => 1, // Disable and delete users.
            'delete_weblink' => 1, // Permission "delete_document" is required to delete or unpublish a web link.
            'directory_create' => 1, // Create directories in the file system.
            'directory_list' => 1, // View the list of subdirectories in a file system directory.
            'directory_remove' => 1, // Delete directories in the file system.
            'directory_update' => 1, // Rename directories in the file system.
            'edit_document' => 1, // Edit resources.
            'edit_locked' => 1, // Allows the user to edit locked resources.
            'edit_static_resource' => 1, // Permission "edit_document" is required to edit static resources.
            'edit_symlink' => 1, // Permission "edit_document" is required to edit symbolic links.
            'edit_user' => 1, // Edit users.
            'edit_weblink' => 1, // Permission "edit_document" is required to edit web links.
            'empty_cache' => 1, // Clear the site cache.
            'file_create' => 1, // Create files.
            'file_list' => 1, // View the list of files in folders.
            'file_remove' => 1, // Delete files.
            'file_tree' => 1, // View the file tree in the left navigation panel.
            'file_update' => 1, // Modify files.
            'file_upload' => 1, // Upload files to a folder.
            'file_view' => 1, // View file content.
            'list' => 1, // Ability to "list" any object. "List" means retrieving a collection of objects.
            'load' => 1, // Ability to load objects or return them as object instances in general.
            'logout' => 1, // Ability to log out as a user.
            'menu_trash' => 1, // Display the "Deleted Resources Management" item in the top menu.
            'new_document' => 1, // Create resources.
            'new_document_in_root' => 1, // Create resources at the root level.
            'new_static_resource' => 1, // Create static resources.
            'new_symlink' => 1, // Create symbolic links.
            'new_weblink' => 1, // Create new web links.
            'publish_document' => 1, // Publish and unpublish resources.
            'purge_deleted' => 1, // Empty the trash bin.
            'remove' => 1, // Ability to delete objects.
            'remove_locks' => 1, // Remove all locks on the site.
            'resource_quick_create' => 1, // Use "Quick Create Resource" in the resource tree on the left.
            'resource_quick_update' => 1, // Use "Quick Update Resource" in the resource tree on the left.
            'resource_tree' => 1, // View the Resource Tree in the left navigation panel.
            'save' => 1, // Ability to save objects.
            'save_document' => 1, // Save resources.
            'search' => 1, // Use the "Search" page.
            'settings' => 0, // View and edit System Settings.
            'source_view' => 1, // View file sources.
            'tree_show_resource_ids' => 1, // Show IDs in the Resource Tree.
            'undelete_document' => 1, // Ability to undelete resources.
            'unpublish_document' => 1, // Unpublish resources.
            'view' => 1, // Ability to "view" objects.
            'view_document' => 1, // View resources.
            'view_tv' => 1, // View TVs (Template Variables).
            'view_unpublished' => 1, // View unpublished resources.
            'view_user' => 1, // View users.
        ],
        'media_source' => [
            'name' => 'Manager',
            'bind_tvs' => true,
            'source_path' => 'assets/uploads/',
            'access' => [
                'authority' => 9,
                'policy' => 8,
                'context_key' => '',
            ],
        ]
    ],
    'seo' => [
        'users' => [[
            'username' => 'seo',
            'password' => '',
            'email' => '',
        ]],
        'context_key' => 'mgr',
        'group_name' => 'Seo',
        'access_role_authority' => 9,
        'access_policy_template_inherit' => 'Manager',
        'permissions' => [
            'components' => 1, // View the "Packages" menu.
            'delete_document' => 1, // Delete and move resources.
            'view_snippet' => 1, // View snippets.
            'new_snippet' => 0, // To create a new Snippet.
            'edit_snippet' => 0, // To edit any Snippets.
            'save_snippet' => 0, // To save any Snippets.
            'view_chunk' => 1, // View chunks.
            'edit_chunk' => 1, // Edit chunks.
            'save_chunk' => 1, // To save any Chunks.
            'edit_locked' => 1, // Allows the user to edit locked resources.
            'edit_template' => 1, // Edit templates.
            'save_template' => 1, // To save any Templates.
            'edit_tv' => 1, // Edit TVs (Template Variables).
            'element_tree' => 1, // View the element tree in the left navigation panel.
            'error_log_view' => 1, // View the error log.
            'events' => 1, // View system events.
            'flush_sessions' => 1, // Reset all site sessions.
            'lexicons' => 1, // View or edit "Lexicon Management."
            'logs' => 1, // View the "System Management Log."
            'menu_tools' => 1, // Display the "Tools" menu item in the top menu.
            'menu_trash' => 1, // Display the "Deleted Resources Management" menu item in the top menu.
            'remove_locks' => 1, // Remove all locks on the site.
            'resourcegroup_resource_list' => 1, // View resources in a resource group.
            'resourcegroup_view' => 1, // View resource groups.
            'settings' => 0, // View and edit system settings.
            'steal_locks' => 1, // "Steal" resource locks to gain control.
            'tree_show_element_ids' => 1, // Show IDs in the element tree.
            'usergroup_user_list' => 1, // View the list of users in a user group.
            'usergroup_view' => 1, // View user groups.
            'view_category' => 1, // View categories.
            'view_context' => 1, // View contexts.
            'view_eventlog' => 1, // View the event log.
            'view_offline' => 1, // View the site when it is in offline mode.
            'view_plugin' => 1, // View plugins.
            'view_propertyset' => 1, // View property sets.
            'view_tv' => 1, // View TVs (Template Variables).
        ],
        'media_source' => [
            'join' => 'Manager'
        ]
    ]

];

$config['ms2'] = [
    'demo' => [
        'reset' => false,
        'enable' => true,
        'vendors' => true,
        'products' => true,
        'categories' => true,
    ],
    'templates' => [
        'cart',
        'product',
        'category',
    ],
    'pages' => [
        'category' => [
            'pagetitle' => 'Category',
            'template' => 'category',
            'class_key' => 'msCategory',
            'content' => '',
            'hidemenu' => 0,
            'publishedon' => 1,
        ],
        'cart' => [
            'pagetitle' => 'Cart',
            'template' => 'cart',
            'hidemenu' => 0,
            'publishedon' => 1,
        ],
    ],
];

return $config;