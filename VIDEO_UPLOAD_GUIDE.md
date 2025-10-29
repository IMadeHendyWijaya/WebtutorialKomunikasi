# Panduan Upload Video Tutorial

## Cara Menggunakan Sistem Video

### 1. Masuk ke Admin Panel
- Buka `localhost/ecourse/admin.php`
- Login sebagai admin

### 2. Tambah Tutorial Baru
- Klik "Kelola Tutorial" di sidebar
- Isi form "Tambah Tutorial Baru":
  - **Judul Tutorial**: Nama tutorial
  - **Deskripsi**: Penjelasan tutorial
  - **URL Video**: Masukkan URL video dengan format:

### 3. Format URL Video yang Didukung

#### YouTube
```
https://www.youtube.com/watch?v=VIDEO_ID
https://youtu.be/VIDEO_ID
```

#### Google Drive
```
https://drive.google.com/file/d/FILE_ID/view
```

#### Video MP4 (Direct Link)
```
https://example.com/video.mp4
```

### 4. Fitur Thumbnail Otomatis

- **YouTube**: Thumbnail otomatis diambil dari YouTube
- **Google Drive**: Thumbnail otomatis diambil dari Google Drive
- **MP4**: Menggunakan thumbnail default dengan indikator video

### 5. Indikator Video

Sistem akan menampilkan:
- Badge "Video" di pojok kanan atas thumbnail
- Icon play button di tengah thumbnail
- Background hitam untuk video player

### 6. Tampilan di Halaman

- **kursus.php**: Menampilkan grid tutorial dengan thumbnail dan indikator video
- **detail.php**: Menampilkan video player yang sesuai dengan jenis URL
- **manage-courses.php**: Admin dapat melihat thumbnail di tabel

## Troubleshooting

### Thumbnail Tidak Muncul
1. Pastikan URL video valid
2. Untuk Google Drive, pastikan file dapat diakses publik
3. Untuk MP4, pastikan URL dapat diakses langsung

### Video Tidak Diputar
1. Pastikan URL video dapat diakses
2. Untuk Google Drive, pastikan file dapat diakses publik
3. Untuk MP4, pastikan server mendukung streaming video

## Catatan Penting

- Sistem ini menggunakan URL video, bukan upload file
- Thumbnail dihasilkan otomatis berdasarkan jenis URL
- Video akan ditampilkan dengan player yang sesuai
