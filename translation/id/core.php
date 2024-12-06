<?php

return [
    // General Rules
    'badRequest' => 'Permintaan tidak valid.',
    'emptyParams' => 'Input tidak boleh kosong.',
    'dataNotFound' => 'Data tidak ditemukan.',
    'exceptionOccured' => 'Terjadi kesalahan.',
    'unauthorizedAccess' => 'Akses tidak sah.',
    'serverError' => 'Terjadi kesalahan server.',

    // General Records Rules
    'createRecordSuccess' => 'Data berhasil disimpan.',
    'createRecordFailed' => 'Gagal menyimpan data.',
    'updateRecordSuccess' => 'Data berhasil diperbarui.',
    'updateRecordFailed' => 'Gagal memperbarui data.',
    'deleteRecordSuccess' => 'Data berhasil dihapus.',
    'deleteRecordFailed' => 'Gagal menghapus data.',
    'noRecordDeleted' => 'Gagal, data sudah dihapus.',
    'noRecordUpdated' => 'Gagal, data tidak diperbarui.',

    // Field Validation Rules
    'required' => 'Input {field} tidak boleh kosong.',
    'integer' => 'Input {field} harus berupa bilangan bulat.',
    'validationFailed' => 'Validasi Input gagal.',
    'invalidField' => 'Input {field} tidak valid.',
    'fieldDataNotFound' => 'Data {field} tidak ditemukan.',
    'extraFields' => 'Input tambahan ditemukan dalam {label}: {fields}. Input yang diizinkan: {allowed}.',
    'missingFields' => 'Input yang harus disediakan: {fields}.',
    'nullField' => '{label} Input: {fields} tidak boleh null atau kosong',
    'allowedField' => '{field} hanya dapat berisi input berikut: {value}.',

    // General Pagination Rules
    'pageMustBeGreaterThanZero' => 'Page harus lebih besar dari 0.',
    
    // Status Update Rules
    'disallowedStatusUpdate' => 'Tidak dapat mengubah status karena data sudah {value}.',
    'cannotChangeStatus' => 'Tidak dapat mengubah status dari {value} ke {newValue}.',
    'deletedStatusChanged' => 'Anda tidak memiliki izin untuk mengubah status dari {value} ke status lainnya. Hak admin diperlukan.',
    
    // Superadmin Rights Rules
    'superadminOnly' => 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
    'updatePermission' => 'Anda tidak memiliki izin untuk memperbarui {label} dari {tableName} karena data tersebut digunakan di tempat lain.',
];

