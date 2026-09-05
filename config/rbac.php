<?php

/**
 * FR-USR-03, SRS v3 sections 3 and 7. Role aliases: Editor = Redaktur,
 * Penulis = Reporter. Scoped permissions require ownership/status checks in
 * the corresponding feature policy; they never grant access to every record.
 * "Terbatas" for activity logs means the editor's own activity only.
 * Section 7 governs media management: Ads Manager has no library access.
 */
$writer = [
    'dashboard.view',
    'articles.view', 'articles.view-own', 'articles.create',
    'articles.update-own-draft', 'articles.delete-own-draft', 'articles.submit',
    'articles.assign-categories', 'articles.assign-tags',
    'media.view', 'media.view-own', 'media.upload', 'media.update-own', 'media.delete-own',
    'seo.update-own-draft',
];

$editor = array_merge($writer, [
    'articles.view-any', 'articles.update-any', 'articles.approve', 'articles.reject',
    'articles.request-revision', 'articles.publish', 'articles.schedule',
    'articles.unpublish', 'articles.archive', 'articles.mark-breaking',
    'articles.mark-advertorial', 'articles.view-history',
    'categories.view', 'categories.create', 'categories.update', 'categories.delete', 'categories.reorder',
    'tags.view', 'tags.create', 'tags.update', 'tags.delete',
    'media.view-any', 'media.update-any', 'media.delete-any',
    'seo.update-any', 'seo.set-indexing',
    'activity-log.view', 'activity-log.view-own',
]);

$adsManager = [
    'dashboard.view', 'ads.view', 'ads.create', 'ads.update', 'ads.delete',
    'ads.schedule', 'ads.view-reports',
];

$administrator = [
    'articles.delete-any',
    'users.view', 'users.create', 'users.update', 'users.deactivate', 'users.delete',
    'roles.view', 'roles.assign', 'roles.update-permissions',
    'activity-log.view-any', 'settings.view', 'settings.update',
];

return [
    'guard' => 'web',
    'roles' => [
        'Super Admin' => array_values(array_unique(array_merge($editor, $adsManager, $administrator))),
        'Editor' => $editor,
        'Penulis' => $writer,
        'Ads Manager' => $adsManager,
    ],
];
