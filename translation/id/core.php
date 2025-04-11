<?php

return [
    // General Rules
    'badRequest' => 'Permintaan salah.',
    'emptyParams' => 'Setidaknya satu input harus diisi kecuali "id" untuk mengupdate data.',
    'dataNotFound' => 'Data tidak ditemukan.',
    'exceptionOccured' => 'Terjadi kesalahan.',
    'unauthorizedAccess' => 'Akses tidak sah.',
    'serverError' => 'Kesalahan server.',
    'lockVersionOutdated' => 'Data yang diupdate sudah kadaluarsa. Silakan refresh halaman dan coba lagi.',
    'unknownError' => 'Terjadi kesalahan yang tidak diketahui.',

    // General Records Rules
    'createRecordSuccess' => 'Data berhasil disimpan.',
    'createRecordFailed' => 'Gagal menyimpan data.',
    'updateRecordSuccess' => 'Data berhasil diupdate.',
    'updateRecordFailed' => 'Gagal mengupdate data.',
    'deleteRecordSuccess' => 'Data berhasil dihapus.',
    'deleteRecordFailed' => 'Gagal menghapus data.',
    'noRecordDeleted' => 'Gagal, data sudah dihapus.',
    'noRecordUpdated' => 'Gagal, tidak ada data yang diupdate.',

    'create_shipment_doRecordSuccess' => 'Data berhasil disimpan.',
    'create_shipment_doRecordFailed' => 'Gagal menyimpan data.',
    'scheduledRecordSuccess' => 'Data berhasil dijadwalkan.',
    'scheduledRecordFailed' => 'Gagal menjadwalkan data.',
    'rescheduledRecordSuccess' => 'Data berhasil direschedule.',
    'rescheduledRecordFailed' => 'Gagal mereschedule data.',
    'departedRecordSuccess' => 'Data berhasil diberangkatkan.',
    'departedRecordFailed' => 'Gagal memberangkatkan data.',
    'deliveredRecordSuccess' => 'Data berhasil dikirimkan.',
    'deliveredRecordFailed' => 'Gagal mengirimkan data.',
    'delayRecordSuccess' => 'Data berhasil ditunda.',
    'delayRecordFailed' => 'Gagal menunda data.',
    'canceledRecordSuccess' => 'Data berhasil dibatalkan.',
    'canceledRecordFailed' => 'Gagal membatalkan data.',

    'approveRecordSuccess' => 'Data berhasil disetujui.',
    'approveRecordFailed' => 'Gagal menyetujui data.',
    'rejectRecordSuccess' => 'Data berhasil ditolak.',
    'rejectRecordFailed' => 'Gagal menolak data.',

    // Field Validation Rules
    'required' => '{label} tidak boleh kosong.',
    'integer' => '{label} harus berupa angka bulat.',
    'array' => '{label} harus berupa array.',
    'number' => '{label} harus berupa angka.',
    'validationFailed' => 'Validasi gagal.',
    'invalidField' => 'Field {label} tidak valid.',
    'fieldDataNotFound' => 'Data {label} tidak ditemukan.',
    'extraField' => 'Field {label} tidak valid. Field yang diizinkan: {value}.',
    'extraFieldFound' => 'Field tidak valid ditemukan di {label}.',
    'missingField' => 'Field {label} tidak ditemukan.',
    'missingFieldFound' => 'Field tidak ditemukan di {label}.',
    'nullField' => 'Field {label} tidak boleh kosong.',
    'allowedField' => 'Field {label} hanya dapat berisi field {value}.',
    'integerNoZero' => '{label} harus berupa angka bulat dan tidak boleh 0.',

    // General Pagination Rules
    'pageMustBeGreaterThanZero' => 'Halaman harus lebih dari 0.',
    
    // Status Update Rules
    'disallowedStatusUpdate' => 'Tidak dapat mengubah status karena data sudah {value}.',
    'cannotChangeStatus' => 'Tidak dapat mengubah status dari {value} ke {newValue}.',
    'deletedStatusChanged' => 'Anda tidak memiliki izin untuk mengubah status dari {value} ke status lain. Hak akses admin diperlukan.',
    
    // Superadmin Rights Rules
    'superadminOnly' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
    'updatePermission' => 'Anda tidak memiliki izin untuk mengupdate {label} dari {tableName} karena sudah digunakan di data lain.',
];