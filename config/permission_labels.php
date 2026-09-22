<?php

return [
    'dashboard' => [
        'label' => 'Dashboard',
        'permissions' => [
            'dashboard.view' => 'Akses dashboard admin',
        ],
    ],
    'articles' => [
        'label' => 'Artikel',
        'permissions' => [
            'articles.view' => 'Lihat semua artikel',
            'articles.view-own' => 'Lihat artikel milik sendiri',
            'articles.view-any' => 'Lihat seluruh artikel semua penulis',
            'articles.create' => 'Buat artikel baru',
            'articles.update-own-draft' => 'Edit draft milik sendiri',
            'articles.update-any' => 'Edit artikel milik siapa saja',
            'articles.delete-own-draft' => 'Hapus draft milik sendiri',
            'articles.delete-any' => 'Hapus artikel milik siapa saja',
            'articles.submit' => 'Ajukan artikel untuk direview',
            'articles.approve' => 'Setujui artikel',
            'articles.reject' => 'Tolak artikel',
            'articles.request-revision' => 'Minta revisi artikel',
            'articles.publish' => 'Publikasikan artikel',
            'articles.schedule' => 'Jadwalkan publikasi artikel',
            'articles.unpublish' => 'Batalkan publikasi artikel',
            'articles.archive' => 'Arsipkan artikel',
            'articles.mark-breaking' => 'Tandai sebagai berita breaking',
            'articles.mark-advertorial' => 'Tandai sebagai advertorial',
            'articles.view-history' => 'Lihat riwayat perubahan artikel',
            'articles.assign-categories' => 'Atur kategori artikel',
            'articles.assign-tags' => 'Atur tag artikel',
        ],
    ],
    'seo' => [
        'label' => 'SEO Artikel',
        'permissions' => [
            'seo.update-own-draft' => 'Atur SEO draft milik sendiri',
            'seo.update-any' => 'Atur SEO artikel milik siapa saja',
            'seo.set-indexing' => 'Atur pengindeksan mesin pencari',
        ],
    ],
    'categories' => [
        'label' => 'Kategori',
        'permissions' => [
            'categories.view' => 'Lihat kategori',
            'categories.create' => 'Tambah kategori',
            'categories.update' => 'Edit kategori',
            'categories.delete' => 'Hapus kategori',
            'categories.reorder' => 'Atur urutan kategori',
        ],
    ],
    'tags' => [
        'label' => 'Tag',
        'permissions' => [
            'tags.view' => 'Lihat tag',
            'tags.create' => 'Tambah tag',
            'tags.update' => 'Edit tag',
            'tags.delete' => 'Hapus tag',
        ],
    ],
    'media' => [
        'label' => 'Media Library',
        'permissions' => [
            'media.view' => 'Lihat semua media',
            'media.view-own' => 'Lihat media milik sendiri',
            'media.view-any' => 'Lihat media milik siapa saja',
            'media.upload' => 'Unggah media',
            'media.update-own' => 'Edit media milik sendiri',
            'media.update-any' => 'Edit media milik siapa saja',
            'media.delete-own' => 'Hapus media milik sendiri',
            'media.delete-any' => 'Hapus media milik siapa saja',
        ],
    ],
    'ads' => [
        'label' => 'Iklan',
        'permissions' => [
            'ads.view' => 'Lihat iklan',
            'ads.create' => 'Tambah iklan',
            'ads.update' => 'Edit iklan',
            'ads.delete' => 'Hapus iklan',
            'ads.schedule' => 'Jadwalkan tayang iklan',
            'ads.view-reports' => 'Lihat laporan performa iklan',
        ],
    ],
    'users' => [
        'label' => 'Pengguna & Role',
        'permissions' => [
            'users.view' => 'Lihat daftar pengguna',
            'users.create' => 'Tambah pengguna',
            'users.update' => 'Edit pengguna',
            'users.deactivate' => 'Nonaktifkan pengguna',
            'users.delete' => 'Hapus pengguna',
            'roles.view' => 'Lihat role & izin',
            'roles.assign' => 'Tetapkan role ke pengguna',
            'roles.update-permissions' => 'Ubah izin suatu role',
        ],
    ],
    'activity-log' => [
        'label' => 'Activity Log',
        'permissions' => [
            'activity-log.view' => 'Lihat activity log',
            'activity-log.view-own' => 'Lihat activity log milik sendiri',
            'activity-log.view-any' => 'Lihat seluruh activity log',
            'activity-log.purge' => 'Hapus log lama',
        ],
    ],
    'settings' => [
        'label' => 'Pengaturan',
        'permissions' => [
            'settings.view' => 'Lihat pengaturan situs',
            'settings.update' => 'Ubah pengaturan situs',
        ],
    ],
];