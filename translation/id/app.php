<?php

$core_translation = require __DIR__ . '/core.php';

return array_merge($core_translation, [
    'responseLanguage' => 'Bahasa Indonesia',

    // general
    'id' => 'ID',
    'ok' => 'OK',
    'success' => 'Berhasil',
    'method not allowed' => 'Metode tidak diizinkan',

    // auth
    'username' => 'Username',
    'password' => 'Password',
    'invalid username or password.' => 'Username atau password tidak valid.',
    'no refresh token found.' => 'Tidak ada refresh token yang ditemukan.',
    'the refresh token no longer exists.' => 'Refresh token tidak ada lagi.',
    'the user no longer exists.' => 'Pengguna tidak ada lagi.',
    'failed to delete the refresh token.' => 'Gagal menghapus refresh token.',
    'invalid token provided' => 'Token tidak valid.',
    'username and password are required' => 'Username dan password diperlukan',
    'login success.' => 'Login berhasil.',
    'token not found.' => 'Token tidak ditemukan.',
    'token expired.' => 'Token telah kadaluarsa.',

    // custom error messages
    'mustArray' => '{field} harus berupa array.',
    'float' => '{field} harus berupa float.',
    'mustLogin' => 'Anda harus login untuk melakukan aksi ini.',
    'superadminOnly' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
    'updatePermission' => 'Anda tidak memiliki izin untuk mengupdate {label} dari {tableName} karena data ini di referensikan di tempat lain.',
    'invalidImage' => 'Format gambar tidak valid.',
    'null' => '{label} tidak boleh kosong.',
    'invalidCondition' => '{label} tidak valid.',

    // Attendance custom error messages
    'attendanceFailed' => 'Absensi gagal.',
    'endTimeNotValid' => 'Waktu akhir absensi tidak valid.',
    'locationRequired' => 'Lokasi harus diisi untuk aplikasi perangkat.',
    'startTimeNotValid' => 'Waktu awal absensi tidak valid.',
    'emptyStartTimeEndTime' => 'Waktu awal atau akhir absensi harus diisi.',
    'attendanceNotFound' => 'Data absensi tidak ditemukan untuk tanggal {date}.',

    // General Affair custom error messages
    'generalAffairFailed' => 'Kejadian umum gagal.',
    'dateInvalid' => '{field} format harus YYYY-MM-DD.',

    // Employee custom error messages
    'phonePrefix' => 'Nomor telepon harus diawali dengan 62 dan maksimal 15 digit.',
    'invalidNik' => 'NIK harus 16 digit.',

    // Location Rules
    'invalidLatFormat' => 'Format latitude tidak valid.',
    'invalidLngFormat' => 'Format longitude tidak valid.',
    'invalidLat' => 'Latitude harus antara -90 dan 90',
    'invalidLng' => 'Longitude harus antara -180 dan 180',

    // Attendance Permission Rules
    'attendancePermissionFailed' => 'Izin absensi gagal.',
    'revisionEmpty' => 'Waktu awal dan akhir perubahan tidak boleh kosong untuk {value} request.',
    'invalidTimeFormat' => '{field} harus dalam format HH:MM:SS.',
    'revisionRestriction' => 'Untuk request perubahan, tipe izin hanya dapat di set untuk Keterlambatan atau Pulang Awal.',
    'revisionRequired' => '{field} diperlukan untuk {value} request.',
]);