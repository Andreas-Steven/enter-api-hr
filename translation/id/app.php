<?php

$core_translation = require __DIR__ . '/core.php';

return array_merge($core_translation, [
    'responseLanguage' => 'Bahasa Indonesia',

    // general
    'id' => 'ID',
    'ok' => 'OK',
    'success' => 'Sukses',
    'method not allowed' => 'Metode tidak diizinkan',

    // auth
    'username' => 'Username',
    'password' => 'Kata Sandi',
    'invalid username or password.' => 'Nama pengguna atau kata sandi tidak valid.',
    'no refresh token found.' => 'Token refresh tidak ditemukan.',
    'the refresh token no longer exists.' => 'Token refresh tidak ada lagi.',
    'the user no longer exists.' => 'Pengguna tidak ada lagi.',
    'failed to delete the refresh token.' => 'Gagal menghapus token refresh.',
    'invalid token provided' => 'Token tidak valid.',
    'username and password are required' => 'Nama pengguna dan kata sandi diperlukan.',
    'login success.' => 'Login berhasil.',
    'token not found.' => 'Token tidak ditemukan.',
    'token expired.' => 'Token kadaluarsa.',

    // Attendance Permission custom messages
    'approveRecordSuccess' => 'Data telah disetujui dengan sukses.',
    'approveRecordFailed' => 'Gagal menyetujui data.',
    'rejectRecordSuccess' => 'Data telah ditolak dengan sukses.',
    'rejectRecordFailed' => 'Gagal menolak data.',

    // custom error messages
    'array' => '{label} harus berupa array.',
    'float' => '{label} harus berupa float.',
    'mustLogin' => 'Anda harus login untuk melakukan aksi ini.',
    'superadminOnly' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
    'updatePermission' => 'Anda tidak memiliki izin untuk mengupdate {label} dari {tableName} karena data ini di referensi oleh data lain.',
    'invalidImage' => 'Format gambar tidak valid.',
    'null' => '{label} tidak boleh kosong.',
    'invalidCondition' => '{label} tidak valid.',

    // Attendance custom error messages
    'defaultRecordSuccess' => 'Data telah disimpan dengan sukses.',
    'defaultRecordFailed' => 'Gagal menyimpan data.',
    'attendanceFailed' => 'Absensi gagal.',
    'endTimeNotValid' => 'Waktu akhir absensi tidak valid.',
    'locationRequired' => 'Lokasi harus diisi untuk aplikasi device.',
    'startTimeNotValid' => 'Waktu awal absensi tidak valid.',
    'emptyStartTimeEndTime' => 'Waktu awal atau akhir absensi harus diisi.',
    'attendanceNotFound' => 'Data absensi tidak ditemukan untuk tanggal {date}.',

    // General Affair custom error messages
    'generalAffairFailed' => 'Surat-menyurat umum gagal.',
    'dateInvalid' => '{label} harus berupa tanggal dalam format YYYY-MM-DD.',
    'datePattern' => 'Tanggal harus berupa YYYY-MM-DD.',
    'codeFormatInvalid' => 'Kode format tidak valid. Kode harus berupa string antara 10 dan 25 karakter',

    // Employee custom error messages
    'phonePrefix' => 'Nomor telepon harus diawali dengan 62 dan maksimal 15 digit.',
    'invalidNik' => 'NIK harus berupa 16 digit.',

    // Location Rules
    'invalidLatFormat' => 'Format latitude tidak valid.',
    'invalidLngFormat' => 'Format longitude tidak valid.',
    'invalidLat' => 'Latitude harus berupa antara -90 dan 90',
    'invalidLng' => 'Longitude harus berupa antara -180 dan 180',

    // Attendance Permission Rules
    'attendancePermissionFailed' => 'Izin absensi gagal.',
    'revisionEmpty' => 'Waktu awal dan akhir revisi tidak boleh kosong untuk {value} request.',
    'invalidTimeFormat' => '{label} harus berupa format HH:MM:SS.',
    'revisionRestriction' => 'Untuk request revisi, Tipe Izin hanya boleh di set untuk Terlambat atau Pulang Awal.',
    'revisionRequired' => '{label} diperlukan untuk {value} request.',
]);