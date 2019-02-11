<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'header' => [
        'title' => 'List Packs',
        'overview' => 'Packs<small>All service packs available on the system.</small>',
        'admin' => 'Admin',
        'packs' => 'Packs',
    ],
    'content' => [
        'pack_list' => 'Pack List',
        'create_new' => 'Create New',
        'id' => 'ID',
        'pack_name' => 'Pack Name',
        'version' => 'Version',
        'description' => 'Description',
        'egg' => 'Egg',
        'servers' => 'Servers',
    ],
    'modal' => [
        'install' => 'Install Pack from Template',
        'associated_egg' => 'Associated Egg:',
        'associated_egg_hint' => 'The Egg that this pack is associated with. Only servers that are assigned this Egg will be able to access this pack.',
        'package_archive' => 'Package Archive:',
        'package_archive_hintStart' => '<small>This file should be either the <code>.json</code> template file, or a <code>.zip</code> pack archive containing <code>archive.tar.gz</code> and <code>import.json</code> within.<br /><br />This server is currently configured with the following limits: ',
        'and' => 'and',
        'package_archive_hintEnd' => '. If your file is larger than either of those values this request will fail.</small>',
        'btn_install' => 'Install',
        'cancel' => 'Cancel',
    ],
    'new' => [
        'header' => [
            'title' => 'Packs &rarr; New',
            'overview' => 'New Pack<small>Create a new pack on the system.</small>',
            'new' => 'New',
        ],
        'content' => [
            'manual' => 'Configure Manually',
            'template' => 'Install From Template',
            'pack_details' => 'Pack Details',
            'name' => 'Name',
            'name_hint' => 'A short but descriptive name of what this pack is. For example, <code>Counter Strike: Source</code> if it is a Counter Strike package.',
            'version' => 'The version of this package, or the version of the files contained within the package.',
            'associated_egg' => 'The option that this pack is associated with. Only servers that are assigned this option will be able to access this pack.',
            'pack_config' => 'Pack Configuration',
            'selectable' => 'Selectable',
            'selectable_hint' => 'Check this box if user should be able to select this pack to install on their servers.',
            'visible' => 'Visible',
            'visible_hint' => 'Check this box if this pack is visible in the dropdown menu. If this pack is assigned to a server it will be visible regardless of this setting.',
            'locked' => 'Locked',
            'locked_hint' => 'Check this box if servers assigned this pack should not be able to switch to a different pack.',
            'pack_archive' => 'Pack Archive',
            'pack_archive_hint' => 'This package file must be a <code>.tar.gz</code> archive of pack files to be decompressed into the server folder.</p>
            <p class="text-muted small">If your file is larger than <code>50MB</code> it is recommended to upload it using SFTP. Once you have added this pack to the system, a path will be provided where you should upload the file.',
            'max_sizeStart' => '<strong>This server is currently configured with the following limits:</strong>',
            'max_sizeEnd' => 'If your file is larger than either of those values this request will fail.',
            'create_pack' => 'Create Pack',
        ],
    ],
    'view' => [
        'header' => [
            'title' => 'Packs &rarr; View &rarr;',
        ],
        'content' => [
            'storage_location' => 'Storage Location',
            'storage_location_hint' => 'If you would like to modify the stored pack you will need to upload a new <code>archive.tar.gz</code> to the location defined above.',
            'association_option' => 'Associated Option',
            'association_option_hint' => 'The option that this pack is associated with. Only servers that are assigned this option will be able to access this pack. This assigned option <em>cannot</em> be changed if servers are attached to this pack.',
            'save' => 'Save',
            'servers' => 'Servers Using This Pack',
            'server_name' => 'Server Name',
            'node' => 'Node',
            'owner' => 'Owner',
            'export' => 'Export',
            'export_with_archive' => 'Export with Archive',
        ],
    ],
];
