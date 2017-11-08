<?php

return [
    'index' => [
        'title' => ':name',
        'header' => 'Sunucu Konsolu',
        'header_sub' => 'Sunucunuzu gerçek zamanlı kontrol edin.',
        'start' => 'Başlat',
        'restart' => 'Yeniden Başlat',
        'stop' => 'Durdur',
        'kill' => 'Zorla Durdur',
        'memory_usage' => 'RAM Kullanımı',
        'cpu_usage' => 'İşlemci Kullanımı',
        'console' => 'Konsol',
    ],
    'schedule' => [
        'header' => 'Zamanlı Görev Yöneticisi',
        'header_sub' => 'Sunucu işlemlerini otomatikleştirin.',
        'current' => 'Mevcut Görevler',
        'new' => [
            'header' => 'Yeni Zamanlı Görev',
            'header_sub' => 'Sunucu için yeni zamanlı görev oluşturun.',
            'submit' => 'Oluştur',
        ],
        'manage' => [
            'header' => 'Görevi Yönet',
            'submit' => 'Güncelle',
            'delete' => 'Sil',
        ],
        'task' => [
            'time' => 'Kaç Süre Sonra',
            'action' => 'Yapılacak Aksiyon',
            'payload' => 'Komut',
            'add_more' => 'Yeni Komut Ekle',
        ],
        'actions' => [
            'command' => 'Sunucu Komudu Gönder',
            'power' => 'Güç Komudu Gönder',
        ],
        'unnamed' => 'İsimsiz Görev',
        'setup' => 'Görevin Çalışacağı Zaman',
        'day_of_week' => 'Haftanın Günü',
        'day_of_month' => 'Ayın Günü',
        'hour' => 'Saat',
        'minute' => 'Dakika',
        'time_help' => '',
        'task_help' => '',
        'task_created' => 'Görev başarıyla oluşturuldu.',
        'task_updated' => 'Görev başarıyla güncellendi. Mevcut sıra iptal edilmiştir, görevler yeni tanımlanmış zamanlarında çalıştırılacaktır.',
        'toggle' => 'Durumu Değiştir',
    ],
    'users' => [
        'header' => 'Kullanıcıları Yönet',
        'header_sub' => 'Sunucunuzda kimlerin yetkili olacağını yönetin.',
        'configure' => 'İzinleri Ayarla',
        'list' => 'Erişimi Olan Hesaplar',
        'add' => 'Alt Kullanıcı Ekle',
        'update' => 'Alt Kullanıcı Güncelle',
        'user_assigned' => 'Successfully assigned a new subuser to this server.',
        'user_updated' => 'Successfully updated permissions.',
        'edit' => [
            'header' => 'Alt Kullanıcı Düzenle',
            'header_sub' => 'Kullanıcının sunucuya erişimini düzenleyin.',
        ],
        'new' => [
            'header' => 'Yeni Kullanıcı Ekle',
            'header_sub' => 'Bu sunucuya yetkisi olan yeni bir kullanıcı ekle.',
            'email' => 'Email Adresi',
            'email_help' => 'Sunucuda yetkili olmasını istediğiniz kullanıcının email adresini girin.',
            'power_header' => 'Güç Yönetimi',
            'file_header' => 'Dosya Yönetimi',
            'subuser_header' => 'Alt Kullanıcı Yönetimi',
            'server_header' => 'Sunucu Yönetimi',
            'task_header' => 'Görev Yönetimi',
            'sftp_header' => 'SFTP Yönetimi',
            'database_header' => 'Veritabanı Yönetimi',
            'power_start' => [
                'title' => 'Başlat',
                'description' => 'Kullanıcının sunucuyu başlatmasına izin ver.',
            ],
            'power_stop' => [
                'title' => 'Durdur',
                'description' => 'Kullanıcının sunucuyu durdurmasına izin ver.',
            ],
            'power_restart' => [
                'title' => 'Yeniden Başlat',
                'description' => 'Kullanıcının sunucuyu yeniden başlatmasına izin ver.',
            ],
            'power_kill' => [
                'title' => 'Zorla Durdur',
                'description' => 'Kullanıcının sunucuyu düzgün olmayan bir biçimde, beklemeden durdurmasına izin ver.',
            ],
            'send_command' => [
                'title' => 'Konsol Komudu Gönder',
                'description' => 'Kullanıcının sunucu konsoluna komut gönderebilmesine izin ver. Eğer ki kullanıcının sunucuyu durdurma veya yeniden başlatma izni yok ise, bu işlemlerin komutlarını konsoldan gönderemez.',
            ],
            'list_files' => [
                'title' => 'Dosya Listeleme',
                'description' => 'Kullanıcının sunucudaki tüm dosya ve klasörleri listeleyebilmesini sağlar, ancak içeriklerini göremez.',
            ],
            'edit_files' => [
                'title' => 'Dosya Düzenleme',
                'description' => 'Kullanıcının sadece okuma amaçlı dosya içeriğine ulaşmasını sağlar.',
            ],
            'save_files' => [
                'title' => 'Dosya Kaydetme',
                'description' => 'Kullanıcının dosya içeriğini değiştirip kaydedebilmesini sağlar.',
            ],
            'move_files' => [
                'title' => 'Dosya Adını Değiştirme ve Taşıma',
                'description' => 'Kullanıcının dosya ve klasörlerin adlarını değiştirebilmesini ve istediği yere taşıyabilmesini sağlar.',
            ],
            'copy_files' => [
                'title' => 'Dosya Kopyalama',
                'description' => 'Kullanıcının dosya ve klasörleri kopyalayabilmesini sağlar.',
            ],
            'compress_files' => [
                'title' => 'Dosya Sıkıştırma',
                'description' => 'Kullanıcının dosya ve klasörleri arşivleyebilmesini sağlar.',
            ],
            'decompress_files' => [
                'title' => 'Sıkıştırılmış Dosya Açma',
                'description' => 'Kullanıcının .zip ve .tar(.gz) arşivlerinin içeriklerini açabilmesini sağlar.',
            ],
            'create_files' => [
                'title' => 'Dosya Oluşturma',
                'description' => 'Kullanıcının yeni dosya oluşturmasını sağlar.',
            ],
            'upload_files' => [
                'title' => 'Dosya Yükleme',
                'description' => 'Kullanıcının sunucuya dosya yükleyebilmesini sağlar.',
            ],
            'delete_files' => [
                'title' => 'Dosya Silme',
                'description' => 'Kullanıcının dosya silebilmesini sağlar.',
            ],
            'download_files' => [
                'title' => 'Dosya İndirme',
                'description' => 'Kullanıcının dosya indirebilmesini sağlar. Bu yetkiye sahip olan kullanıcı, panel üzerinde dosya görüntüleme yetkisine sahip olmasa bile dosyayı indirip içeriğine kendi bilgisayarında bakabilir.',
            ],
            'list_subusers' => [
                'title' => 'Alt Kullanıcıları Listeleme',
                'description' => 'Sunucuya tanımlanmış tüm alt kullanıcıların listesinin görüntülenmesi.',
            ],
            'view_subuser' => [
                'title' => 'Alt Kullanıcı Görüntüleme',
                'description' => 'Alt kullanıcılara tanımlanmış yetkilerin görüntülenmesi.',
            ],
            'edit_subuser' => [
                'title' => 'Alt Kullanıcı Düzenle',
                'description' => 'Alt kullanıcılara atanmış yetkiler ile oynanabilmesi.',
            ],
            'create_subuser' => [
                'title' => 'Alt Kullanıcı Ekle',
                'description' => 'Sunucuya tanımlı alt kullanıcı eklenmesi.',
            ],
            'delete_subuser' => [
                'title' => 'Alt Kullanıcı Sil',
                'description' => 'Sunucuya tanımlı alt kullanıcıların silinmesi.',
            ],
            'view_allocations' => [
                'title' => 'Bağlantıları Görüntüle',
                'description' => 'Kullanıcının kullanıma uygun IP:Portları görüntüleyebilmesini sağlar.',
            ],
            'edit_allocation' => [
                'title' => 'Varsayılan Bağlantı Seçimi',
                'description' => 'Kullanıcının varsayılan bağlantıyı (IP:Port) seçebilmesini/değiştirebilmesini sağlar.',
            ],
            'view_startup' => [
                'title' => 'Başlangıç Komudunu Görüntüle',
                'description' => 'Kullanıcının sunucunun başlangıç komudunu ve ilgili değişkenlerini görebilmesini sağlar.',
            ],
            'edit_startup' => [
                'title' => 'Başlangıç Değişkenlerini Değiştirme',
                'description' => 'Kullanıcının sunucunun başlangıç komuduna ait değişkenleri düzenleyebilmesini sağlar.',
            ],
            'list_schedules' => [
                'title' => 'Görev Listeleme',
                'description' => 'Kullanıcının sunucuya tanımlanmış aktif/inaktif tüm görevleri listeyebilmesini sağlar.',
            ],
            'view_schedule' => [
                'title' => 'Görev Görüntüleme',
                'description' => 'Kullanıcının istediği görevin detaylarını görüntüleyebilmesini sağlar.',
            ],
            'toggle_schedule' => [
                'title' => 'Göre Aktifleştirme',
                'description' => 'Kullanıcının görevleri aktifleştirme ve inaktifleştirmesini sağlar.',
            ],
            'queue_schedule' => [
                'title' => 'Görevi Sıraya Koyma',
                'description' => 'Kullanıcının bir görevi sıraya koyup, bir sonraki çalıştırmada işlem görmesini sağlaması.',
            ],
            'edit_schedule' => [
                'title' => 'Edit Schedule',
                'description' => 'Allows a user to edit a schedule including all of the schedule\'s tasks. This will allow the user to remove individual tasks, but not delete the schedule itself.',
            ],
            'create_schedule' => [
                'title' => 'Görev Oluşturma',
                'description' => 'Kullanıcının yeni bir görev oluşturabilmesini sağlar.',
            ],
            'delete_schedule' => [
                'title' => 'Görev Silme',
                'description' => 'Kullanıcının mevcut görevleri silebilmesini sağlar.',
            ],
            'view_sftp' => [
                'title' => 'SFTP Bilgileri Görüntüleme',
                'description' => 'Kullanıcının SFTP bağlantı bilgilerini görüntüleyebilmesini sağlar (Şifre hariç).',
            ],
            'view_sftp_password' => [
                'title' => 'SFTP Şifresi Görüntüleme',
                'description' => 'Kullanıcının SFTP bağlantı şifresini görüntüyelebilmesini sağlar.',
            ],
            'reset_sftp' => [
                'title' => 'SFTP Şifre Sıfırlama',
                'description' => 'Kullanıcının SFTP bağlantı şifresini sıfırlayabilmesini sağlar.',
            ],
            'view_databases' => [
                'title' => 'Veritabanı Bilgileri Görüntüleme',
                'description' => 'Kullanıcının veritabanı bağlantı bilgilerini (kullanıcı adı, şifre vs.) görüntüleyebilmesini sağlar.',
            ],
            'reset_db_password' => [
                'title' => 'Veritabanı Şifre Sıfırlama',
                'description' => 'Kullanıcının veritabanı bağlantı şifresini sıfırlayabilmesini sağlar.',
            ],
        ],
        'delete' => [
            'title' => 'Alt Kullanıcı Sil',
            'text' => 'Bu işlem, kullanıcının bu sunucuda tanımlı yetkilerini ve erişimini anında silecektir.',
            'success' => 'Alt kullanıcı başarıyla silindi.',
        ],
    ],
    'files' => [
        'exceptions' => [
            'invalid_mime' => 'This type of file cannot be edited via the Panel\'s built-in editor.',
            'max_size' => 'This file is too large to edit via the Panel\'s built-in editor.',
        ],
        'header' => 'Dosya Yöneticisi',
        'header_sub' => 'Web üzerinden tüm dosyalarını yönetin.',
        'loading' => 'Dosya yapısı ilk kez yükleniyor, bu işlem bir kaç saniye sürebilir.',
        'path' => 'Webden dosya yöneticisi ile en fazla :size boyutunda dosya yükleyebilirsiniz. Daha büyük dosyalar için lütfen SFTP (FileZilla) kullanın. Dosyalarınızın sunucu üzerinde bulunduğu dizin: :path',
        'seconds_ago' => 'saniye önce',
        'file_name' => 'Dosya Adı',
        'size' => 'Boyut',
        'last_modified' => 'Son Değiştirilme',
        'add_new' => 'Dosya Oluştur',
        'add_folder' => 'Klasör Oluştur',
        'mass_actions' => 'Mass actions',
        'delete' => 'Delete',
        'edit' => [
            'header' => 'Dosya Düzenle',
            'header_sub' => 'Web üzerinden dosya içeriği değiştirin.',
            'save' => 'Dosyayı Kaydet',
            'return' => 'Dosya Yöneticisine Geri Dön',
        ],
        'add' => [
            'header' => 'Yeni Dosya',
            'header_sub' => 'Sunucuda yeni dosya oluşturun.',
            'name' => 'Dosya Adı',
            'create' => 'Dosya Oluştur',
        ],
    ],
    'config' => [
        'startup' => [
            'header' => 'Başlangıç Ayarları',
            'header_sub' => 'Sunucu başlangıç ayarlarını yönetin.',
            'command' => 'Başlangıç Komudu',
            'edit_params' => 'Değişkenleri Düzenle',
            'update' => 'Başlangıç Değişkenlerini Güncelle',
            'startup_regex' => 'Input Rules',
            'edited' => 'Startup variables have been successfully edited. They will take effect the next time this server is started.',
        ],
        'sftp' => [
            'header' => 'SFTP Bilgileri',
            'header_sub' => 'SFTP bağlantısı için hesap bilgileri.',
            'change_pass' => 'SFTP Şifresini Değiştir',
            'details' => 'SFTP Bilgileri',
            'conn_addr' => 'Bağlantı Adresi',
            'warning' => 'FTP istemcinizin (örn. FileZilla) bağlantı tipi ayarının SFTP olduğundan (FTP ya da FTPS olmadığından) emin olun.',
        ],
        'database' => [
            'header' => 'Veritabanları',
            'header_sub' => 'Sunucuya atanmış tüm veritabanları.',
            'your_dbs' => 'Veritabanlarınız',
            'host' => 'MySQL Host',
            'reset_password' => 'Şifre Sıfırla',
            'no_dbs' => 'Sunucuya atanmış veritabanı bulunmamaktadır.',
            'add_db' => 'Yeni veritabanı ekle.',
        ],
        'allocation' => [
            'header' => 'Sunucu Bağlantı Bilgileri',
            'header_sub' => 'Sunucuya tanımlanmış IP ve Port\'ları kontrol edin.',
            'available' => 'Tanımlı Bağlantılar',
            'help' => 'Yardım',
            'help_text' => 'Soldaki listede, sunucunuza tanımlanmış, bağlantı için kullanılabilecek IP ve Port bilgileri bulunmaktadır.',
        ],
    ],
];
