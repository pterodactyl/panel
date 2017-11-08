<?php

return [
    'validation_error' => 'İstekte gönderilen bir ya da birden fazla alan ile ilgili hata oluştu.',
    'errors' => [
        'return' => 'Önceki Sayfaya Dön',
        'home' => 'Anasayfa',
        '403' => [
            'header' => 'Yasak',
            'desc' => 'Bu sayfayı görüntüleme izniniz yok.',
        ],
        '404' => [
            'header' => 'Dosya Bulunamadı',
            'desc' => 'İstenilen dosya sunucuda bulunamadı.',
        ],
        'installing' => [
            'header' => 'Sunucu Yükleniyor',
            'desc' => 'Sunucu hala yüklenme aşamasında. İşlem tamamlandığında email ile bilgilendirileceksiniz.',
        ],
        'suspended' => [
            'header' => 'Sunucu Kapatıldı',
            'desc' => 'Bu sunucu erişime kapatıldı.',
        ],
        '503' => [
            'header' => 'Kısa bir süre sonra geliyoruz.',
            'desc' => 'Kısa bir süre sonra geliyoruz!',
        ],
    ],
    'index' => [
        'header' => 'Sunucularınız',
        'header_sub' => 'Erişiminiz bulunan sunucular.',
        'list' => 'Sunucu Listesi',
    ],
    'api' => [
        'index' => [
            'header' => 'API Erişimi',
            'header_sub' => 'API erişim anahtarlarınızı yönetin.',
            'list' => 'API Anahtarları',
            'create_new' => 'Yeni API Anahtarı Üret',
            'keypair_created' => 'API anahtarı oluşturuldu. API gizli tokeniniz <code>:token</code>. Bir daha gösterilmeyeceği için lütfen not alın.',
        ],
        'new' => [
            'header' => 'Yeni API Anahtarı',
            'header_sub' => 'Yeni API anahtarı üret',
            'form_title' => 'Detaylar',
            'descriptive_memo' => [
                'title' => 'Açıklayıcı Not',
                'description' => 'Bu anahtarın ne amaçla kullanılacağı hakkında, ileride unutmamanız için kısa bir not girin.',
            ],
            'allowed_ips' => [
                'title' => 'İzinli IP Adresleri',
                'description' => 'Bu API anahtarını kullanabilecek IP\'leri her satır bir tane gelecek şekilde listeleyin. CIDR notasyonu kullanabilirsiniz. Tüm IP\'lere erişim izni vermek için boş bırakın.',
            ],
        ],
        'permissions' => [
            'user' => [
                'server_header' => 'Sunucu Yetkileri',
                'server' => [
                    'list' => [
                        'title' => 'Sunucu Listeleme',
                        'desc' => 'Kullanıcının sahibi olduğu ya da alt kullanıcı olarak erişimi bulunduğu sunucuları listeleme yetkisi.',
                    ],
                    'view' => [
                        'title' => 'Sunucuyu Görüntüle',
                        'desc' => 'Kullanıcının erişimi olan sunucuları görüntüleme yetkisi.',
                    ],
                    'power' => [
                        'title' => 'Sunucuyu Açma Kapama',
                        'desc' => 'Sunucuyu başlatıp, durdurabilme yetkisi.',
                    ],
                    'command' => [
                        'title' => 'Komut Gönderme',
                        'desc' => 'Çalışan bir sunucuya, konsol komudu gönderebilme yetkisi (RCON).',
                    ],
                ],
            ],
            'admin' => [
                'server_header' => 'Sunucu Kontrolü',
                'server' => [
                    'list' => [
                        'title' => 'Sunucu Listeleme',
                        'desc' => 'Mevcut sunucuları listeleme yetkisi.',
                    ],
                    'view' => [
                        'title' => 'Sunucu Görüntüleme',
                        'desc' => 'Sunucu detaylarını görüntüleme yetkisi.',
                    ],
                    'delete' => [
                        'title' => 'Sunucu Silme',
                        'desc' => 'Sunucu silme yetkisi.',
                    ],
                    'create' => [
                        'title' => 'Sunucu Oluşturma',
                        'desc' => 'Yeni bir sunucu oluşturma yetkisi.',
                    ],
                    'edit-details' => [
                        'title' => 'Sunucu Bilgileri Düzenleme',
                        'desc' => 'Sunucu adı, sahibi, tanımı, gizli anahtarı gibi bilgileri değiştirme yetkisi.',
                    ],
                    'edit-container' => [
                        'title' => 'Sunucu Kutusu Düzenleme',
                        'desc' => 'Sunucunun içinde çalıştığı Docker kutusunu düzenleme yetkisi.',
                    ],
                    'suspend' => [
                        'title' => 'Sunucu Kapatma',
                        'desc' => 'Sunucu hizmeti kapatıp devam ettirme yetkisi.',
                    ],
                    'install' => [
                        'title' => 'Yükleme Durumunu Değiştirme',
                        'desc' => '',
                    ],
                    'rebuild' => [
                        'title' => 'Sunucu Yeniden Yapılandırma',
                        'desc' => '',
                    ],
                    'edit-build' => [
                        'title' => 'Sunucu Yapılandırması Düzenleme',
                        'desc' => 'CPU, RAM gibi sunucu yapılandırması seçeneklerini düzenleme yetkisi.',
                    ],
                    'edit-startup' => [
                        'title' => 'Sunucu Başlangıç Komudu Düzenleme',
                        'desc' => 'Sunucuların başlangıç komudu değişkenlerini değiştirme yetkisi.',
                    ],
                ],
                'location_header' => 'Lokasyon Kontrolü',
                'location' => [
                    'list' => [
                        'title' => 'Lokasyon Listeleme',
                        'desc' => 'Mevcut tüm lokasyonları ve bu lokasyonlarda bulunan makineleri listeleme yetkisi.',
                    ],
                ],
                'node_header' => 'Makine Kontrolü',
                'node' => [
                    'list' => [
                        'title' => 'Makine Listesi',
                        'desc' => 'Mevcut makineleri listeleme yetkisi.',
                    ],
                    'view' => [
                        'title' => 'Makine Görüntüle',
                        'desc' => 'Makine bilgilerini görüntüleme yetkisi.',
                    ],
                    'view-config' => [
                        'title' => 'Makine Ayarlarını Görüntüle',
                        'desc' => 'DİKKAT. Bu yetki, makine ayarlarının görüntülenmesini sağlar ve bu sebeple gizli bir takım bilgilere erişim sağlar.',
                    ],
                    'create' => [
                        'title' => 'Makine Ekle',
                        'desc' => 'Yeni bir makine oluşturma yetkisi.',
                    ],
                    'delete' => [
                        'title' => 'Makine Sil',
                        'desc' => 'Mevcut bir makineyi silme yetkisi.',
                    ],
                ],
                'user_header' => 'Kullanıcı Kontrolü',
                'user' => [
                    'list' => [
                        'title' => 'Kullanıcıları Listele',
                        'desc' => 'Mevcut kullanıcıları listeleme yetkisi.',
                    ],
                    'view' => [
                        'title' => 'Kullanıcı Görüntüle',
                        'desc' => 'Aktif sunucuları dahil, kullanıcıların bilgilerine erişme yetkisi.',
                    ],
                    'create' => [
                        'title' => 'Kullanıcı Ekle',
                        'desc' => 'Yeni kullanıcı oluşturma yetkisi.',
                    ],
                    'edit' => [
                        'title' => 'Kullanıcı Güncelle',
                        'desc' => 'Kullanıcı bilgileri değiştirme/güncelleme yetkisi.',
                    ],
                    'delete' => [
                        'title' => 'Kullanıcı Sil',
                        'desc' => 'Kullanıcı silme yetkisi.',
                    ],
                ],
                'service_header' => 'Servis Kontrolü',
                'service' => [
                    'list' => [
                        'title' => 'Servis Listeleme',
                        'desc' => 'Mevcut tüm servislerin listelenmesi yetkisi.',
                    ],
                    'view' => [
                        'title' => 'Servis Görüntüleme',
                        'desc' => 'Mevcut tüm servislerin, ayarlar ve değişkenleriyle beraber tüm detaylarının listelenmesi yetkisi.',
                    ],
                ],
                'option_header' => 'Seçenek Kontrolü',
                'option' => [
                    'list' => [
                        'title' => 'Seçenek Listeleme',
                        'desc' => '',
                    ],
                    'view' => [
                        'title' => 'Seçenek Görüntüleme',
                        'desc' => '',
                    ],
                ],
                'pack_header' => 'Pack Kontrolü',
                'pack' => [
                    'list' => [
                        'title' => 'Pack Listeleme',
                        'desc' => '',
                    ],
                    'view' => [
                        'title' => 'Pack Görüntüleme',
                        'desc' => '',
                    ],
                ],
            ],
        ],
    ],
    'account' => [
        'details_updated' => 'Hesap bilgileriniz başarıyla güncellendi.',
        'invalid_password' => 'Girilen şifre bu hesap için geçerli değil.',
        'header' => 'Hesabınız',
        'header_sub' => 'Hesap bilgilerinizi yönetin.',
        'update_pass' => 'Şifre Değiştir',
        'update_email' => 'Email Adresi Değiştir',
        'current_password' => 'Şu Anki Şifreniz',
        'new_password' => 'Yeni Şifre',
        'new_password_again' => 'Yeni Şifre (Tekrar)',
        'new_email' => 'Yeni Email Adresi',
        'first_name' => 'İsim',
        'last_name' => 'Soyisim',
        'update_identitity' => 'Bilgileri Güncelle',
        'username_help' => 'Kullanıcı adınız hesabınıza özel olmalıdır, ve sadece izin verilen karakterlerden oluşmalıdır: :requirements.',
    ],
    'security' => [
        'session_mgmt_disabled' => 'Kullanıcı oturumları yönetimi özelliği kapalı durumda.',
        'header' => 'Hesap Güvenliği',
        'header_sub' => 'Aktif oturum ve iki aşamalı doğrulama (2FA) yönetimi.',
        'sessions' => 'Aktif Oturumlar',
        '2fa_header' => 'İki Aşamalı Doğrulama',
        '2fa_token_help' => 'Uygulamanızın (Google Authenticatior, Authy, vb.) ürettiği 2FA tokenini girin.',
        'disable_2fa' => 'İki Aşamalı Doğrulamayı Devre Dışı Bırak',
        '2fa_enabled' => 'Bu hesapta iki aşamalı doğrulama (2FA) etkin ve panele giriş için zorunludur. 2FA\'yı iptal etmek istiyorsanız, aşağıya geçerli bir 2FA token girin ve göndere tıklayın.',
        '2fa_disabled' => 'İki aşamalı doğrulama (2FA) devre dışı! Hesap güvenliğiniz için 2FA\'yı etkinleştirmelisiniz.',
        'enable_2fa' => 'İki Aşamalı Doğrulamayı Etkinleştir',
        '2fa_qr' => 'Cihazınızda İki Aşamalı Doğrulama Ayarlayın',
        '2fa_checkpoint_help' => 'Telefonunuzdaki 2FA uygulaması ile soldaki QR kodunun resmini çekin, ya da altındaki kodu elle uygulamaya girin. Bu işlemi yaptıktan sonra, uygulamanın ürettiği tokeni aşağıya girin.',
        '2fa_disable_error' => 'Girilen 2FA kodu geçersiz. Koruma bu hesap için devre dışı bırakılmadı.',
        'loading_qr' => 'QR Kodu Yükleniyor...',
    ],
];
