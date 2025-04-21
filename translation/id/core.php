<?php

return [
    // General Rules
    'badRequest' => 'Permintaan buruk.',
    'emptyParams' => 'Minimal satu input harus disediakan kecuali "id" untuk mengupdate data.',
    'dataNotFound' => 'Data tidak ditemukan.',
    'exceptionOccured' => 'Terjadi kesalahan.',
    'unauthorizedAccess' => 'Akses tidak sah.',
    'serverError' => 'Terjadi kesalahan server.',
    'lockVersionOutdated' => 'Data yang diupdate sudah tidak up to date. Silakan refresh halaman dan coba lagi.',
    'unknownError' => 'Terjadi kesalahan yang tidak diketahui.',

    // General Records Rules
    'createRecordSuccess' => 'Data berhasil disimpan.',
    'createRecordFailed' => 'Gagal menyimpan data.',
    'updateRecordSuccess' => 'Data berhasil diupdate.',
    'updateRecordFailed' => 'Gagal mengupdate data.',
    'deleteRecordSuccess' => 'Data berhasil dihapus.',
    'deleteRecordFailed' => 'Gagal menghapus data.',
    'noRecordDeleted' => 'Gagal, data sudah dihapus.',
    'noRecordUpdated' => 'Gagal, data tidak diupdate.',

    'create_shipment_doRecordSuccess' => 'Data berhasil disimpan.',
    'create_shipment_doRecordFailed' => 'Gagal menyimpan data.',
    'scheduledRecordSuccess' => 'Data berhasil dijadwalkan.',
    'scheduledRecordFailed' => 'Gagal mengjadwalkan data.',
    'rescheduledRecordSuccess' => 'Data berhasil dijadwalkan ulang.',
    'rescheduledRecordFailed' => 'Gagal mengjadwalkan ulang data.',
    'departedRecordSuccess' => 'Data berhasil diberangkatkan.',
    'departedRecordFailed' => 'Gagal menerbangkan data.',
    'deliveredRecordSuccess' => 'Data berhasil dikirimkan.',
    'deliveredRecordFailed' => 'Gagal mengirimkan data.',
    'delayRecordSuccess' => 'Data berhasil ditunda.',
    'delayRecordFailed' => 'Gagal menunda data.',
    'canceledRecordSuccess' => 'Data berhasil dibatalkan.',
    'canceledRecordFailed' => 'Gagal membatalkan data.',

    // Field Validation Rules
    'required' => '{label} harus diisi.',
    'integer' => '{label} harus berupa angka.',
    'array' => '{label} harus berupa array.',
    'number' => '{label} harus berupa angka.',
    'validationFailed' => 'Validasi field gagal.',
    'invalidField' => 'Field {label} tidak valid.',
    'fieldDataNotFound' => 'Data {label} tidak ditemukan.',
    'extraField' => 'Field tambahan ditemukan di {label}: {field}. Field yang diperbolehkan: {value}.',
    'extraFieldFound' => 'Field tambahan ditemukan di {label}.',
    'missingField' => 'Field {field} wajib diisi.',
    'missingFieldFound' => 'Field {field} wajib diisi di {label}.',
    'nullField' => '{label} field: {field} harus diisi.',
    'allowedField' => '{field} hanya dapat berisi field {value}.',
    'integerNoZero' => '{label} harus berupa angka dan lebih besar dari 0.',
    'invalidStatusTransition' => 'Perubahan status tidak valid.',

    // General Pagination Rules
    'pageMustBeGreaterThanZero' => 'Halaman harus lebih besar dari 0.',
    
    // Status Update Rules
    'disallowedStatusUpdate' => 'Tidak dapat mengubah status karena data sudah {value}.',
    'cannotChangeStatus' => 'Tidak dapat mengubah status dari {value} ke {newValue}.',
    'deletedStatusChanged' => 'Anda tidak memiliki hak akses untuk mengubah status dari {value} ke status lain. Hak akses admin dibutuhkan.',
    
    // Superadmin Rights Rules
    'superadminOnly' => 'Anda tidak memiliki hak akses untuk melakukan tindakan ini.',
    'updatePermission' => 'Anda tidak memiliki hak akses untuk mengupdate {label} dari {tableName} karena data tersebut di referensikan di data lain.',
];