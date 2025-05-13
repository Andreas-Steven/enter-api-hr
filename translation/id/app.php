<?php

$core_translation = require __DIR__ . '/core.php';

return array_merge($core_translation, [
    'responseLanguage' => 'Bahasa Indonesia',

    #General
    'id' => 'ID',
    'ok' => 'OK',
    'success' => 'OK',
    'method not allowed' => 'Metode tidak diizinkan',

    #Auth
    'username' => 'Nama Pengguna',
    'password' => 'Kata Sandi',
    'token expired.' => 'Token telah kedaluwarsa.',
    'login success.' => 'Berhasil masuk.',
    'token not found.' => 'Token tidak ditemukan.',
    'invalid token provided' => 'Token yang diberikan tidak valid',
    'no refresh token found.' => 'Token pembaruan tidak ditemukan.',
    'mustLogin' => 'Anda harus masuk untuk melakukan aksi ini.',
    'the user no longer exists.' => 'Pengguna tidak lagi tersedia.',
    'invalid username or password.' => 'Nama pengguna atau kata sandi tidak valid.',
    'superadminOnly' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
    'username and password are required' => 'Nama pengguna dan kata sandi wajib diisi',
    'the refresh token no longer exists.' => 'Token pembaruan tidak lagi tersedia.',
    'failed to delete the refresh token.' => 'Gagal menghapus token pembaruan.',
    'updatePermission' => 'Anda tidak memiliki izin untuk memperbarui {label} dari {tableName} ini karena digunakan di data lain.',

    #Attendance Permission Scenario
    'defaultRecordSuccess' => 'Izin berhasil disimpan.',
    'defaultRecordFailed' => 'Gagal menyimpan izin.',
    'approveRecordSuccess' => 'Izin berhasil disetujui.',
    'approveRecordFailed' => 'Gagal menyetujui izin.',
    'rejectRecordSuccess' => 'Izin berhasil ditolak.',
    'rejectRecordFailed' => 'Gagal menolak izin.',

    #Location Error
    'invalidLatFormat' => 'Format latitude tidak valid.',
    'invalidLngFormat' => 'Format longitude tidak valid.',
    'invalidLat' => 'Latitude harus berada di antara -90 dan 90.',
    'invalidLng' => 'Longitude harus berada di antara -180 dan 180.',

    #Custom Error Messages
    'array' => '{label} harus berupa array.',
    'float' => '{label} harus berupa angka desimal.',
    'invalidCondition' => '{label} tidak valid.',
    'invalidImage' => 'Format {label} tidak valid.',
    'null' => '{label} tidak boleh kosong atau null.',

    #Attendance Validation
    'endTimeNotValid' => 'Waktu pulang absensi tidak valid.',
    'attendanceFailed' => 'Gagal memproses data absensi.',
    'startTimeNotValid' => 'Waktu masuk absensi tidak valid.',
    'locationRequired' => 'Lokasi wajib diisi untuk melakukan absensi melalui aplikasi.',
    'emptyStartTimeEndTime' => 'Waktu masuk atau waktu pulang absensi harus diisi.',

    #General Affair Validation
    'dateInvalid' => 'Format {label} harus YYYY-MM-DD.',
    'datePattern' => 'Tanggal harus dalam format YYYY-MM-DD.',
    'generalAffairFailed' => 'Gagal memproses data General Affair.',
    'codeFormatInvalid' => 'Format kode tidak valid. Kode harus berupa text antara 10 sampai 25 karakter.',

    #Employee Custom Error Messages
    'invalidNik' => 'NIK harus berupa text dan terdiri dari 16 karakter.',
    'invalidPhoneFormat' => 'Nomor Telepon wajib diisi, harus diawali dengan 62, minimal 9 karakter, dan maksimal 15 karakter.',

    #Attendance Permission Rules
    'revisionRequired' => '{label} wajib diisi untuk permintaan {value}.',
    'invalidTimeFormat' => '{label} harus dalam format HH:MM:SS.',
    'attendancePermissionFailed' => 'Gagal memproses data Izin Absensi.',
    'attendanceNotFound' => 'Data absensi tidak ditemukan untuk tanggal {date}.',
    'revisionRestriction' => 'Untuk permintaan Revisi, Tipe Izin hanya dapat diatur untuk Terlambat atau Pulang Awal.',
    'revisionEmpty' => 'Revisi waktu masuk dan pulang tidak boleh kosong bersamaan untuk permintaan {value}.',
]);