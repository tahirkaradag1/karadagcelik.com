# Katalog ve Musteri Hesabi Guncellemesi

Paketin icindeki tum dosya ve klasorleri Hostinger'da su klasore yukleyin:

```text
public_html/kc-test/
```

Ayni isimli dosyalar icin uzerine yazmayi onaylayin.

Sunucudaki su dosyayi silmeyin veya degistirmeyin:

```text
public_html/kc-test/api/database.local.php
```

Paket gizli veritabani dosyasini ve `config.php` dosyasini icermez.

Yukleme tamamlaninca:

1. `https://karadagcelik.com/kc-test/admin/index.php` adresini acin.
2. Sol menude `Urunler` ve `Musteriler` bolumlerini kontrol edin.
3. `https://karadagcelik.com/kc-test/api/products.php` adresini acin.
4. JSON icinde `"ok":true` ve urun listesi gorundugunu dogrulayin.
5. Magaza sayfasini yenileyip urun kartlarini kontrol edin.

Google giris dugmesi, Google Cloud OAuth bilgileri eklenene kadar bilerek pasif
kalir. Bu guncellemeden sonraki adim Google baglantisini kurmaktir.
