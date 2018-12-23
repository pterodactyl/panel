<?php

return [
    'account' => [
        'current_password' => 'Mevcut Şifre',
        'delete_user' => 'Kullanıcıyı Sil',
        'details_updated' => 'Hesap ayarlarınız başarıyla güncellendi.',
        'exception' => 'Hesabınız güncellenirken bir hata oluştu.',
        'first_name' => 'İsim',
        'header' => 'HESAP AYARLARI',
        'header_sub' => 'Hesap ayarlarınızı düzenleyin.',
        'invalid_password' => 'Girdiğiniz şifre doğru değil.',
        'last_name' => 'Soy İsim',
        'new_email' => 'Yeni Eposta Adresi',
        'new_password' => 'Yeni Şifre',
        'new_password_again' => 'Tekrar Yeni Şifre',
        'totp_disable' => '2-Aşamalı Doğrulamayı Devre Dışı Bırak',
        'totp_enable' => '2-Aşamalı Doğrulamayı Etkinleştir',
        'totp_enabled_error' => 'Tek seferlik doğrulama kodunuz yanlış. Lütfen sonra tekrar deneyin.',
        'totp_enable_help' => 'Görünüşe göre 2-Aşamalı doğrulama devre dışı. Bu doğrulama metodu, hesabınıza yetkisiz girişleri engellemek için ek bir önlem oluşturur. Eğer etkinleştirirseniz, hesabınıza bağlanırken telefonunuzda veya tek seferlik doğrulama kodu destekleyen bir cihazda oluşturulan kodu girmeniz gerekecektir.',
        'update_email' => 'Güncelle',
        'update_identitity' => 'Güncelle',
        'update_pass' => 'Güncelle',
        'update_user' => 'Güncelle',
        'username_help' => 'Kullanıcı adınız hesabınıza özgün olmalı ve belirtilen karakterleri barındırmalıdır. :requirements.',
    ],
    'api' => [
        'index' => [
            'create_new' => 'Yeni Yetki Oluştur',
            'header' => 'Yetki Paylaşımı',
            'header_sub' => 'Yetki anahtarlarınızı düzenleyin',
            'keypair_created' => 'Yetki Anahtarı başarıyla oluşturuldu ve listelendi.',
            'list' => 'Yetki Anahtarlarınız',
        ],
        'new' => [
            'allowed_ips' => [
                'description' => 'Enter a line delimitated list of IPs that are allowed to access the API using this key. CIDR notation is allowed. Leave blank to allow any IP.',
                'title' => 'Allowed IPs',
            ],
            'base' => [
                'information' => [
                    'title' => 'Ana Bilgileri Görüntüleme',
                ],
                'title' => 'Ana Bilgiler',
            ],
            'descriptive_memo' => [
                'description' => 'Enter a brief description of this key that will be useful for reference.',
                'title' => 'Description',
            ],
            'form_title' => 'Details',
            'header' => 'Yeni Yetki Anahtarı',
            'header_sub' => 'Create a new account access key.',
            'location_management' => [
                'list' => [
                    'title' => 'Lokasyonları listele',
                ],
                'title' => 'Lokasyon yönetimi',
            ],
            'server_management' => [
                'config' => [
                    'title' => 'Konfigürasyon Güncelleme',
                ],
                'create' => [
                    'title' => 'Sunucu Oluşturma',
                ],
                'delete' => [
                    'title' => 'Sunucuyu Silme',
                ],
                'list' => [
                    'title' => 'Sunucuları Listeleme',
                ],
                'server' => [
                    'title' => 'Sunucu Bilgisi',
                ],
                'title' => 'Sunucu Yönetimi',
            ],
            'service_management' => [
                'title' => 'Servis Yönetimi',
            ],
            'user_management' => [
                'create' => [
                    'description' => 'Sistemde yeni bir kullanıcı oluşturulmasına izin verir.',
                    'title' => 'Kullanıcı Oluşturma',
                ],
                'delete' => [
                    'title' => 'Kullanıcı Silme',
                ],
                'list' => [
                    'description' => 'Sistemdeki kullanıcıların listelenmesine izin verir.',
                    'title' => 'Kullanıcıları Listeleme',
                ],
                'title' => 'Kullanıcı Yönetimi',
                'update' => [
                    'description' => 'Kullanıcı detaylarının değiştirilmesini sağlar. (E-posta, şifre)',
                    'title' => 'Kullanıcı Güncelleme',
                ],
            ],
        ],
    ],
    'confirm' => 'Emin misin?',
    'errors' => [
        '403' => [
            'desc' => 'Bu sunucudaki kaynaklara ulaşma yetkiniz yok.',
            'header' => 'Yasak',
        ],
        '404' => [
            'desc' => 'We were unable to locate the requested resource on the server.',
            'header' => 'Dosya Bulunamadı',
        ],
        'home' => 'Anasayfaya Dön',
        'installing' => [
            'desc' => 'The requested server is still completing the install process. Please check back in a few minutes, you should receive an email as soon as this process is completed.',
            'header' => 'Sunucu Yükleniyor',
        ],
        'return' => 'Önceki Sayfaya Dön',
        'suspended' => [
            'desc' => 'This server has been suspended and cannot be accessed.',
            'header' => 'Sunucu Askıya Alındı',
        ],
    ],
    'form_error' => 'Bu isteği işlerken aşağıdaki hatayi karşılaşıldı.',
    'index' => [
        'header' => 'Server Console',
        'header_sub' => 'Control your server in real time.',
        'list' => 'Sunucu Listesi',
    ],
    'security' => [
        '2fa_checkpoint_help' => 'Use the 2FA application on your phone to take a picture of the QR code to the left, or manually enter the code under it. Once you have done so, generate a token and enter it below.',
        '2fa_disabled' => '2-Factor Authentication is disabled on your account! You should enable 2FA in order to add an extra level of protection on your account.',
        '2fa_disable_error' => 'The 2FA token provided was not valid. Protection has not been disabled for this account.',
        '2fa_enabled' => '2-Factor Authentication is enabled on this account and will be required in order to login to the panel. If you would like to disable 2FA, simply enter a valid token below and submit the form.',
        '2fa_header' => '2-Aşamalı Doğrulama',
        '2fa_qr' => 'Confgure 2FA on Your Device',
        '2fa_token_help' => 'Uygulamanız tarafından oluşturulan 2AD kodunuzu girin (Google Doğrulama, Authy, vs.).',
        'disable_2fa' => 'Disable 2-Factor Authentication',
        'enable_2fa' => 'Enable 2-Factor Authentication',
        'header' => 'Hesap Güvenliği',
        'header_sub' => 'Control active sessions and 2-Factor Authentication.',
        'sessions' => 'Aktif Oturumlar',
        'session_mgmt_disabled' => 'Your host has not enabled the ability to manage account sessions via this interface.',
    ],
    'server_name' => 'Sunucu Adı',
    'validation_error' => 'There was an error with one or more fields in the request.',
    'view_as_admin' => 'Sunucu listesini yönetici olarak görüyorsunuz. Bu sebeple, sistemde kurulu bütün sunucular gösteriliyor. Size ait olarak belirlenmiş sunucular, isimlerinin solunda mavi bir nokta ile işaretlendi.',
];
