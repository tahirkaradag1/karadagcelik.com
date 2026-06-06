# Karadag Celik Web Platform

Karadag Celik icin WordPress/WooCommerce sinirlarindan cikarak ozel tasarimli online teklif ve magaza sistemi kurma projesi.

## Mevcut Durum

Bu repo calisan PHP/MySQL MVP'sini icerir. Test kurulumu Hostinger uzerinde
`/kc-test/` altinda calisir; formlar, e-posta bildirimleri ve yonetim paneli
canli veritabanina baglidir.

Ana prototip:

```text
prototype/index.html
```

## Vizyon

Sitenin ana deneyimi iki kapi uzerine kuruldu:

- Online Teklif Olustur: Musteri CAD/teknik cizim dosyasini yukler, iletisim bilgilerini girer, Karadag Celik ekibi manuel teklif hazirlar.
- Magaza: Sabit fiyatli endustriyel parcalar ve urunler sergilenir, sepet ve odeme akisiyle satin alma hedeflenir.

## Prototipte Olanlar

- Iki kapili ana giris ekrani
- Apple esintili premium arayuz dili
- Alt dock navigasyon
- Online teklif formu arayuzu
- Dosya yukleme arayuzu
- Magaza ve urun kartlari
- Urun detay sayfasi altyapisi
- Urun bilgi/ansiklopedi bolumu altyapisi
- Sepete ekleme
- Sagdan acilan sepet paneli
- Google ile giris ve musteri hesabi altyapisi
- Iletisim sayfasi
- PHP endpointleri:
  - `prototype/api/quote.php`
  - `prototype/api/contact.php`
  - `prototype/api/order.php`
- MySQL veritabani semasi ve kayit katmani
- Tek seferlik guvenli veritabani kurulum sihirbazi
- Firma ici yonetim paneli:
  - Teklif talepleri
  - Siparis talepleri
  - Iletisim mesajlari
  - Dosya indirme ve durum takibi
  - Urun ekleme, duzenleme ve yayindan kaldirma
  - Musteri listesi
- Veritabanindan yuklenen urun katalogu
- Kayitli musteri adresleri, teklifleri ve siparisleri icin hesap API'si
- Siparis fiyatlarini veritabanindaki guncel katalogdan dogrulama

## Sonraki Evre

Siradaki ana baglantilar:

- Google Cloud OAuth bilgilerini canli sunucuya ekleme
- PayTR veya iyzico odeme entegrasyonu
- Gercek urun gorselleri ve icerikleri
- SEO, yapilandirilmis veri, sitemap ve robots.txt
- Test kurulumundan ana domaine kontrollu gecis

## Dokumanlar

- `docs/PROJECT_BRIEF.md`
- `docs/MVP_SCOPE.md`
- `docs/NEXT_STEPS.md`
- `docs/HOSTINGER_DEPLOYMENT.md`

## Guvenlik

Bu repoya asagidaki dosya ve bilgiler eklenmemelidir:

- `.env` dosyalari
- API anahtarlari
- PayTR/iyzico anahtarlari
- Hostinger sifreleri veya FTP bilgileri
- Database sifreleri
- Codex/ChatGPT auth dosyalari
