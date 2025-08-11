# API hujjatlari

## Umumiy tavsif
Ushbu API azot mahsulotlari, aksessuarlar va qo‘shimcha xizmatlar savdosi uchun onlayn platformani boshqarishga mo‘ljallangan. Foydalanuvchilar ro‘yxatdan o‘tishi, savatga mahsulotlar qo‘shishi, buyurtma berishi va promocode’lardan foydalanishi mumkin. Adminlar esa mahsulotlar, xizmatlar, foydalanuvchilar va sozlamalarni boshqaradi. API Laravel framework’ida qurilgan bo‘lib, RESTful usulda ishlaydi va JSON formatida javob qaytaradi.

## Autentifikatsiya
API’dan foydalanishning ikki turi mavjud:
- **Ommaviy (Public) endpointlar**: Autentifikatsiya talab qilinmaydi, har kim foydalanishi mumkin.
- **Admin endpointlari**: Laravel Sanctum token autentifikatsiyasidan foydalanadi. Adminlar `/api/v1/auth/login` orqali login qiladi va olingan Bearer token barcha admin endpointlarida `Authorization` sarlavhasida yuborilishi kerak.

### Token olish misoli
**Metod va URL**: `POST /api/v1/auth/login`

**So‘rov parametrlari**:
```json
{
  "identifier": "admin_username",
  "password": "admin_password"
}
```

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "tg_id": "123456789",
      "username": "admin_username",
      "phone": "998901234567",
      "address": "Toshkent sh., Chilanzor",
      "role": "admin",
      "status": "active"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

**Eslatma**:
- `identifier` sifatida `username` yoki `phone` ishlatilishi mumkin.
- Token `Authorization: Bearer <token>` sifatida admin endpointlariga yuboriladi.
- Foydalanuvchi `status` holati `active` bo‘lmasa, kirish taqiqlanadi (403).

## Endpointlar ro‘yxati

### 1. Ommaviy (Public) endpointlar
#### 1.1. Ping
**Metod va URL**: `GET /api/v1/public/ping`

**Tavsif**: API ishlayotganligini tekshirish uchun test endpointi.

**So‘rov parametrlari**: Yo‘q

**Javob misoli** (200 OK):
```json
{
  "message": "pong"
}
```

#### 1.2. Sozlamalarni olish
**Metod va URL**: `GET /api/v1/public/updates`

**Tavsif**: Sayt sozlamalarini (masalan, promocode yoqilganligi, yetkazib berish narxi) olish.

**So‘rov parametrlari**: Yo‘q

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "enable_promocode": true,
    "require_phone_on_order": true,
    "site_title": "My Site",
    "site_logo": "http://example.com/storage/images/default-logo.png",
    "cargo_price": 500
  }
}
```

#### 1.3. Foydalanuvchi mavjudligini tekshirish
**Metod va URL**: `POST /api/v1/public/user-exists`

**Tavsif**: Berilgan Telegram ID orqali foydalanuvchi mavjudligini tekshiradi.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789"
}
```

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "exists": true,
  "data": {
    "id": 1,
    "tg_id": "123456789",
    "username": "user_name",
    "phone": "998901234567",
    "address": "Toshkent sh., Chilanzor",
    "role": "user",
    "status": "active"
  }
}
```

**Eslatma**:
- `tg_id` majburiy va string bo‘lishi kerak.
- Agar foydalanuvchi topilmasa, `"exists": false` qaytadi.

#### 1.4. Foydalanuvchi ro‘yxatdan o‘tish
**Metod va URL**: `POST /api/v1/public/register`

**Tavsif**: Yangi foydalanuvchi ro‘yxatdan o‘tkazadi.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789",
  "phone": "998901234567",
  "username": "user_name",
  "address": "Toshkent sh., Chilanzor"
}
```

**Javob misoli** (201 Created):
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 1,
    "tg_id": "123456789",
    "username": "user_name",
    "phone": "998901234567",
    "address": "Toshkent sh., Chilanzor",
    "role": "user",
    "status": "active"
  }
}
```

**Eslatma**:
- `tg_id` majburiy va noyob bo‘lishi kerak.
- `phone` va `username` noyob bo‘lishi kerak (agar berilsa).
- `address` ixtiyoriy.

#### 1.5. Azot mahsulotlari ro‘yxati
**Metod va URL**: `GET /api/v1/public/azots`

**Tavsif**: Faol azot mahsulotlari ro‘yxatini olish.

**So‘rov parametrlari** (query):
- `per_page` (ixtiyoriy, integer, 1-100): Sahifadagi elementlar soni
- `search` (ixtiyoriy, string): Qidiruv so‘zi
- `type` (ixtiyoriy, string): Azot turi
- `country` (ixtiyoriy, string): Ishlab chiqaruvchi davlat
- `sort_by` (ixtiyoriy, id|title|type|country|created_at): Saralash maydoni
- `sort_order` (ixtiyoriy, asc|desc): Saralash tartibi

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Azot 40L",
        "type": "Medical",
        "image": "azots/image.jpg",
        "image_url": "http://example.com/storage/azots/image.jpg",
        "description": "Tibbiy azot",
        "country": "Uzbekistan",
        "status": "active",
        "price_types": [
          {
            "id": 1,
            "azot_id": 1,
            "name": "obmen",
            "price": 100000
          }
        ]
      }
    ],
    "per_page": 10,
    "total": 1
  }
}
```

**Eslatma**:
- Faqat `status=active` bo‘lgan azotlar qaytariladi.
- `search` parametri `title`, `type`, `country` yoki `description` bo‘yicha qidiradi.

#### 1.6. Muayyan azot mahsuloti
**Metod va URL**: `GET /api/v1/public/azots/{azot}`

**Tavsif**: Muayyan azot mahsuloti haqida ma’lumot olish.

**So‘rov parametrlari** (path):
- `azot`: Azot ID’si

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Azot 40L",
    "type": "Medical",
    "image": "azots/image.jpg",
    "image_url": "http://example.com/storage/azots/image.jpg",
    "description": "Tibbiy azot",
    "country": "Uzbekistan",
    "status": "active",
    "price_types": [
      {
        "id": 1,
        "azot_id": 1,
        "name": "obmen",
        "price": 100000
      }
    ]
  }
}
```

**Eslatma**:
- Faqat `status=active` bo‘lgan azotlar uchun ishlaydi, aks holda 404 qaytariladi.

#### 1.7. Azot narx turlari
**Metod va URL**: `GET /api/v1/public/azots/{azot}/price-types`

**Tavsif**: Muayyan azot mahsulotining narx turlarini olish.

**So‘rov parametrlari** (path):
- `azot`: Azot ID’si

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "azot_id": 1,
      "name": "obmen",
      "price": 100000
    },
    {
      "id": 2,
      "azot_id": 1,
      "name": "arenda",
      "price": 150000
    }
  ]
}
```

**Eslatma**:
- Faqat `status=active` bo‘lgan azotlar uchun ishlaydi, aks holda 404 qaytariladi.

#### 1.8. Aksessuarlar ro‘yxati
**Metod va URL**: `GET /api/v1/public/accessories`

**Tavsif**: Faol aksessuarlar ro‘yxatini olish.

**So‘rov parametrlari** (query):
- `per_page` (ixtiyoriy, integer, 1-100)
- `search` (ixtiyoriy, string)
- `sort_by` (ixtiyoriy, id|title|price|created_at)
- `sort_order` (ixtiyoriy, asc|desc)

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Regulyator",
        "price": 50000,
        "image": "accessories/reg.jpg",
        "image_url": "http://example.com/storage/accessories/reg.jpg",
        "description": "Azot regulyatori",
        "status": "active"
      }
    ],
    "per_page": 10,
    "total": 1
  }
}
```

**Eslatma**:
- Faqat `status=active` bo‘lgan aksessuarlar qaytariladi.
- `search` parametri `title` yoki `description` bo‘yicha qidiradi.

#### 1.9. Muayyan aksessuar
**Metod va URL**: `GET /api/v1/public/accessories/{accessory}`

**Tavsif**: Muayyan aksessuar haqida ma’lumot olish.

**So‘rov parametrlari** (path):
- `accessory`: Aksessuar ID’si

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Regulyator",
    "price": 50000,
    "image": "accessories/reg.jpg",
    "image_url": "http://example.com/storage/accessories/reg.jpg",
    "description": "Azot regulyatori",
    "status": "active"
  }
}
```

**Eslatma**:
- Faqat `status=active` bo‘lgan aksessuarlar uchun ishlaydi, aks holda 404 qaytariladi.

#### 1.10. Qo‘shimcha xizmatlar ro‘yxati
**Metod va URL**: `GET /api/v1/public/services`

**Tavsif**: Faol qo‘shimcha xizmatlar ro‘yxatini olish.

**So‘rov parametrlari** (query):
- `per_page` (ixtiyoriy, integer, 1-100)
- `search` (ixtiyoriy, string)
- `sort_by` (ixtiyoriy, id|name|price|created_at)
- `sort_order` (ixtiyoriy, asc|desc)

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Yetkazib berish",
        "price": 30000,
        "status": "active"
      }
    ],
    "per_page": 10,
    "total": 1
  }
}
```

**Eslatma**:
- Faqat `status=active` bo‘lgan xizmatlar qaytariladi.
- `search` parametri `name` bo‘yicha qidiradi.

#### 1.11. Muayyan xizmat
**Metod va URL**: `GET /api/v1/public/services/{service}`

**Tavsif**: Muayyan qo‘shimcha xizmat haqida ma’lumot olish.

**So‘rov parametrlari** (path):
- `service`: Xizmat ID’si

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Yetkazib berish",
    "price": 30000,
    "status": "active"
  }
}
```

**Eslatma**:
- Faqat `status=active` bo‘lgan xizmatlar uchun ishlaydi, aks holda 404 qaytariladi.

#### 1.12. Promocode tekshirish
**Metod va URL**: `POST /api/v1/public/promocode/check`

**Tavsif**: Promocode’ning haqiqiyligini va amal qilish muddatini tekshiradi.

**So‘rov parametrlari**:
```json
{
  "promocode": "SUMMER2025"
}
```

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "message": "Promocode is valid",
  "data": {
    "id": 1,
    "promocode": "SUMMER2025",
    "amount": 10000,
    "type": "fixed-term"
  }
}
```

**Javob misoli** (400 Bad Request):
```json
{
  "success": false,
  "message": "Promocode expired or not yet valid"
}
```

**Eslatma**:
- Promocode `status=active` bo‘lishi kerak.
- `countable` turdagi promocode’lar uchun `used_count` `countable` dan kam bo‘lishi kerak.
- `fixed-term` turdagi promocode’lar uchun `start_date` va `end_date` joriy sanada bo‘lishi kerak.

#### 1.13. Buyurtmalar ro‘yxati (foydalanuvchi uchun)
**Metod va URL**: `POST /api/v1/public/orders`

**Tavsif**: Foydalanuvchining buyurtmalari ro‘yxatini olish.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789"
}
```

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "promocode_id": null,
      "promo_price": 0,
      "cargo_price": 500,
      "all_price": 150000,
      "total_price": 150500,
      "address": "Toshkent sh., Chilanzor",
      "phone": "998901234567",
      "comment": "Tez yetkazib bering",
      "status": "new",
      "azots": [
        {
          "id": 1,
          "order_id": 1,
          "azot_id": 1,
          "count": 1,
          "price": 100000,
          "total_price": 100000
        }
      ],
      "accessories": [],
      "services": [
        {
          "id": 1,
          "order_id": 1,
          "additional_service_id": 1,
          "count": 1,
          "price": 50000,
          "total_price": 50000
        }
      ]
    }
  ]
}
```

**Eslatma**:
- `tg_id` majburiy va mavjud bo‘lishi kerak.
- Faqat `status != deleted` buyurtmalar qaytariladi.

#### 1.14. Buyurtma yaratish (savatdan)
**Metod va URL**: `POST /api/v1/public/orders/create`

**Tavsif**: Foydalanuvchi savatidagi mahsulotlardan buyurtma yaratadi.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789",
  "promocode": "SUMMER2025",
  "phone": "998901234567",
  "address": "Toshkent sh., Chilanzor",
  "comment": "Tez yetkazib bering",
  "cargo_with": true
}
```

**Javob misoli** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "promocode_id": 1,
    "promo_price": 10000,
    "cargo_price": 500,
    "all_price": 150000,
    "total_price": 140500,
    "address": "Toshkent sh., Chilanzor",
    "phone": "998901234567",
    "comment": "Tez yetkazib bering",
    "status": "new",
    "azots": [
      {
        "id": 1,
        "order_id": 1,
        "azot_id": 1,
        "count": 1,
        "price": 100000,
        "total_price": 100000
      }
    ],
    "accessories": [],
    "services": [
      {
        "id": 1,
        "order_id": 1,
        "additional_service_id": 1,
        "count": 1,
        "price": 50000,
        "total_price": 50000
      }
    ],
    "promocode": {
      "id": 1,
      "promocode": "SUMMER2025",
      "amount": 10000,
      "status": "active",
      "type": "fixed-term"
    },
    "user": {
      "id": 1,
      "tg_id": "123456789",
      "username": "user_name",
      "phone": "998901234567",
      "address": "Toshkent sh., Chilanzor",
      "role": "user",
      "status": "active"
    }
  }
}
```

**Eslatma**:
- `tg_id` majburiy va mavjud bo‘lishi kerak.
- Savat bo‘sh bo‘lsa, 400 xatosi qaytariladi.
- `promocode` ixtiyoriy, lekin agar berilsa, faol bo‘lishi kerak.
- `cargo_with=true` bo‘lsa, yetkazib berish narxi qo‘shiladi (standart 500).

#### 1.15. Savat operatsiyalari
##### 1.15.1. Savatni ko‘rish
**Metod va URL**: `POST /api/v1/public/cart`

**Tavsif**: Foydalanuvchi savatidagi mahsulotlarni ko‘rish.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789"
}
```

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "azots": [
      {
        "product_id": 1,
        "name": "Azot 40L",
        "price_type": "obmen",
        "price": 100000,
        "quantity": 2
      }
    ],
    "accessories": [
      {
        "product_id": 1,
        "name": "Regulyator",
        "price": 50000,
        "quantity": 1
      }
    ],
    "services": [
      {
        "service_id": 1,
        "name": "Yetkazib berish",
        "price": 30000
      }
    ],
    "total_price": 250000
  }
}
```

**Eslatma**:
- `tg_id` majburiy va mavjud bo‘lishi kerak.

##### 1.15.2. Azot qo‘shish
**Metod va URL**: `POST /api/v1/public/cart/add/azot`

**Tavsif**: Savatga azot mahsuloti qo‘shish.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789",
  "product_id": 1,
  "price_type_id": 1,
  "quantity": 2
}
```

**Javob misoli** (200 OK): Yuqoridagi savat ko‘rish javobi bilan bir xil.

**Eslatma**:
- `product_id` va `price_type_id` mavjud bo‘lishi kerak.
- `quantity` ixtiyoriy, standart 1.

##### 1.15.3. Aksessuar qo‘shish
**Metod va URL**: `POST /api/v1/public/cart/add/accessuary`

**Tavsif**: Savatga aksessuar qo‘shish.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789",
  "product_id": 1,
  "quantity": 1
}
```

**Javob misoli** (200 OK): Yuqoridagi savat ko‘rish javobi bilan bir xil.

**Eslatma**:
- `product_id` mavjud bo‘lishi kerak.
- `quantity` ixtiyoriy, standart 1.

##### 1.15.4. Xizmat qo‘shish
**Metod va URL**: `POST /api/v1/public/cart/add/service`

**Tavsif**: Savatga qo‘shimcha xizmat qo‘shish.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789",
  "product_id": 1
}
```

**Javob misoli** (200 OK): Yuqoridagi savat ko‘rish javobi bilan bir xil.

**Eslatma**:
- `product_id` mavjud bo‘lishi kerak.
- Xizmatlar uchun `quantity` har doim 1.

##### 1.15.5. Azotni kamaytirish
**Metod va URL**: `POST /api/v1/public/cart/minus/azot`

**Tavsif**: Savatdagi azot mahsuloti sonini kamaytirish.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789",
  "product_id": 1,
  "price_type_id": 1,
  "quantity": 1
}
```

**Javob misoli** (200 OK): Yuqoridagi savat ko‘rish javobi bilan bir xil.

**Eslatma**:
- `quantity` ixtiyoriy, standart 1.
- Agar son 0 yoki undan kichik bo‘lsa, mahsulot savatdan o‘chiriladi.

##### 1.15.6. Aksessuarni kamaytirish
**Metod va URL**: `POST /api/v1/public/cart/minus/accessuary`

**Tavsif**: Savatdagi aksessuar sonini kamaytirish.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789",
  "product_id": 1,
  "quantity": 1
}
```

**Javob misoli** (200 OK): Yuqoridagi savat ko‘rish javobi bilan bir xil.

**Eslatma**:
- `quantity` ixtiyoriy, standart 1.
- Agar son 0 yoki undan kichik bo‘lsa, aksessuar savatdan o‘chiriladi.

##### 1.15.7. Xizmatni kamaytirish
**Metod va URL**: `POST /api/v1/public/cart/minus/service`

**Tavsif**: Savatdagi xizmatni o‘chirish.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789",
  "product_id": 1
}
```

**Javob misoli** (200 OK): Yuqoridagi savat ko‘rish javobi bilan bir xil.

**Eslatma**:
- Xizmatlar uchun `quantity` har doim 1, shuning uchun butunlay o‘chiriladi.

##### 1.15.8. Savatni tozalash
**Metod va URL**: `POST /api/v1/public/cart/clear`

**Tavsif**: Foydalanuvchi savatini to‘liq tozalash.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789"
}
```

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "azots": [],
    "accessories": [],
    "services": [],
    "total_price": 0
  }
}
```

**Eslatma**:
- `tg_id` majburiy va mavjud bo‘lishi kerak.

### 2. Admin endpointlari
#### 2.1. Autentifikatsiya
##### 2.1.1. Admin kirish
**Metod va URL**: `POST /api/v1/auth/login`

**Tavsif**: Admin tizimga kirish uchun token olish.

**So‘rov parametrlari**:
```json
{
  "identifier": "admin_username",
  "password": "admin_password"
}
```

**Javob misoli** (200 OK): Yuqoridagi autentifikatsiya bo‘limida keltirilgan.

**Eslatma**:
- `identifier` sifatida `username` yoki `phone` ishlatilishi mumkin.
- Foydalanuvchi `role=admin` va `status=active` bo‘lishi kerak.

##### 2.1.2. Admin ma’lumotlari
**Metod va URL**: `GET /api/v1/auth/me`

**Tavsif**: Autentifikatsiya qilingan admin ma’lumotlarini olish.

**So‘rov parametrlari**: Yo‘q

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "message": "Authenticated admin data fetched",
  "data": {
    "id": 1,
    "tg_id": "123456789",
    "username": "admin_username",
    "phone": "998901234567",
    "address": "Toshkent sh., Chilanzor",
    "role": "admin",
    "status": "active"
  }
}
```

**Eslatma**:
- Bearer token talab qilinadi.
- Faqat autentifikatsiya qilingan adminlar uchun ishlaydi.

##### 2.1.3. Chiqish
**Metod va URL**: `POST /api/v1/auth/logout`

**Tavsif**: Adminning barcha tokenlarini bekor qilish.

**So‘rov parametrlari**: Yo‘q

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "message": "Token revoked successfully"
}
```

**Eslatma**:
- Bearer token talab qilinadi.

#### 2.2. Foydalanuvchilar
##### 2.2.1. Foydalanuvchilar ro‘yxati
**Metod va URL**: `GET /api/v1/users`

**Tavsif**: Foydalanuvchilar ro‘yxatini olish.

**So‘rov parametrlari** (query):
- `per_page` (ixtiyoriy, integer, 1-100)
- `search` (ixtiyoriy, string)
- `role` (ixtiyoriy, admin|user)
- `status` (ixtiyoriy, active|inactive|blocked)
- `sort_by` (ixtiyoriy, id|tg_id|username|phone|address|created_at)
- `sort_order` (ixtiyoriy, asc|desc)

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "tg_id": "123456789",
        "username": "user_name",
        "phone": "998901234567",
        "address": "Toshkent sh., Chilanzor",
        "role": "user",
        "status": "active"
      }
    ],
    "per_page": 10,
    "total": 1
  }
}
```

**Eslatma**:
- `search` parametri `tg_id`, `username`, `phone` yoki `address` bo‘yicha qidiradi.
- Faqat `status != deleted` foydalanuvchilar qaytariladi.

##### 2.2.2. Foydalanuvchi yaratish
**Metod va URL**: `POST /api/v1/users`

**Tavsif**: Yangi foydalanuvchi yaratish.

**So‘rov parametrlari**:
```json
{
  "tg_id": "123456789",
  "username": "user_name",
  "phone": "998901234567",
  "address": "Toshkent sh., Chilanzor",
  "password": "password123",
  "role": "user",
  "status": "active"
}
```

**Javob misoli** (201 Created):
```json
{
  "success": true,
  "message": "User created",
  "data": {
    "id": 1,
    "tg_id": "123456789",
    "username": "user_name",
    "phone": "998901234567",
    "address": "Toshkent sh., Chilanzor",
    "role": "user",
    "status": "active"
  }
}
```

**Eslatma**:
- `tg_id` majburiy va noyob bo‘lishi kerak.
- `username` va `phone` noyob bo‘lishi kerak (agar berilsa).
- `password` ixtiyoriy, agar berilsa, shifrlanadi.

##### 2.2.3. Foydalanuvchi ma’lumotlari
**Metod va URL**: `GET /api/v1/users/{user}`

**Tavsif**: Muayyan foydalanuvchi haqida ma’lumot olish.

**So‘rov parametrlari** (path):
- `user`: Foydalanuvchi ID’si

**Javob misoli** (200 OK):
```json
{
  "id": 1,
  "tg_id": "123456789",
  "username": "user_name",
  "phone": "998901234567",
  "address": "Toshkent sh., Chilanzor",
  "role": "user",
  "status": "active"
}
```

**Eslatma**:
- `status=deleted` bo‘lgan foydalanuvchilar uchun 404 qaytariladi.

##### 2.2.4. Foydalanuvchi yangilash
**Metod va URL**: `PUT /api/v1/users/{user}`

**Tavsif**: Foydalanuvchi ma’lumotlarini yangilash.

**So‘rov parametrlari**:
```json
{
  "username": "new_user_name",
  "phone": "998901234568",
  "address": "Toshkent sh., Yunusobod",
  "password": "newpassword123",
  "role": "user",
  "status": "active"
}
```

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "message": "User updated",
  "data": {
    "id": 1,
    "tg_id": "123456789",
    "username": "new_user_name",
    "phone": "998901234568",
    "address": "Toshkent sh., Yunusobod",
    "role": "user",
    "status": "active"
  }
}
```

**Eslatma**:
- `status=deleted` bo‘lgan foydalanuvchilar uchun 404 qaytariladi.
- `username` va `phone` noyob bo‘lishi kerak.

##### 2.2.5. Foydalanuvchi o‘chirish
**Metod va URL**: `DELETE /api/v1/users/{user}`

**Tavsif**: Foydalanuvchini soft delete qilish (status=deleted).

**So‘rov parametrlari** (path):
- `user`: Foydalanuvchi ID’si

**Javob misoli** (200 OK):
```json
{
  "message": "User soft deleted"
}
```

**Eslatma**:
- `status=deleted` bo‘lgan foydalanuvchilar uchun 404 qaytariladi.

#### 2.3. Azot mahsulotlari
##### 2.3.1. Azot mahsulotlari ro‘yxati
**Metod va URL**: `GET /api/v1/azots`

**Tavsif**: Barcha azot mahsulotlari ro‘yxatini olish.

**So‘rov parametrlari** (query):
- `per_page` (ixtiyoriy, integer, 1-100)
- `search` (ixtiyoriy, string)
- `type` (ixtiyoriy, string)
- `country` (ixtiyoriy, string)
- `status` (ixtiyoriy, active|archive)
- `sort_by` (ixtiyoriy, id|title|type|country|created_at)
- `sort_order` (ixtiyoriy, asc|desc)

**Javob misoli** (200 OK): Ommaviy azot ro‘yxati bilan bir xil, lekin `status=archive` ham qaytariladi.

**Eslatma**:
- `status=deleted` bo‘lgan azotlar qaytarilmaydi.

##### 2.3.2. Azot yaratish
**Metod va URL**: `POST /api/v1/azots`

**Tavsif**: Yangi azot mahsuloti yaratish.

**So‘rov parametrlari**:
```json
{
  "title": "Azot 40L",
  "type": "Medical",
  "image": "file.jpg",
  "description": "Tibbiy azot",
  "country": "Uzbekistan",
  "status": "active",
  "price_types": [
    {
      "name": "obmen",
      "price": 100000
    },
    {
      "name": "arenda",
      "price": 150000
    }
  ]
}
```

**Javob misoli** (201 Created):
```json
{
  "id": 1,
  "title": "Azot 40L",
  "type": "Medical",
  "image": "azots/file.jpg",
  "image_url": "http://example.com/storage/azots/file.jpg",
  "description": "Tibbiy azot",
  "country": "Uzbekistan",
  "status": "active",
  "price_types": [
    {
      "id": 1,
      "azot_id": 1,
      "name": "obmen",
      "price": 100000
    },
    {
      "id": 2,
      "azot_id": 1,
      "name": "arenda",
      "price": 150000
    }
  ]
}
```

**Eslatma**:
- `image` fayli 5MB dan kichik va jpeg,png,jpg,gif,webp formatida bo‘lishi kerak.
- `price_types` ixtiyoriy, lekin berilsa, har birida `name` va `price` majburiy.

##### 2.3.3. Azot ma’lumotlari
**Metod va URL**: `GET /api/v1/azots/{azot}`

**Tavsif**: Muayyan azot mahsuloti haqida ma’lumot olish.

**So‘rov parametrlari** (path):
- `azot`: Azot ID’si

**Javob misoli** (200 OK): Ommaviy azot ma’lumotlari bilan bir xil, lekin `status=archive` ham qaytariladi.

**Eslatma**:
- `status=deleted` bo‘lgan azotlar uchun 404 qaytariladi.

##### 2.3.4. Azot yangilash
**Metod va URL**: `PUT /api/v1/azots/{azot}`

**Tavsif**: Azot mahsuloti ma’lumotlarini yangilash.

**So‘rov parametrlari**:
```json
{
  "title": "Azot 50L",
  "type": "Industrial",
  "image": "newfile.jpg",
  "description": "Sanoat azoti",
  "country": "Uzbekistan",
  "status": "active",
  "price_types": [
    {
      "id": 1,
      "name": "obmen",
      "price": 120000
    },
    {
      "name": "arenda",
      "price": 180000
    }
  ]
}
```

**Javob misoli** (200 OK): Yuqoridagi yaratish javobi bilan bir xil.

**Eslatma**:
- `image` fayli yangilansa, eski fayl o‘chiriladi.
- `price_types` dagi mavjud `id` lar yangilanadi, yangi qo‘shiladi, qolganlari o‘chiriladi.

##### 2.3.5. Azot o‘chirish
**Metod va URL**: `DELETE /api/v1/azots/{azot}`

**Tavsif**: Azot mahsulotini soft delete qilish.

**So‘rov parametrlari** (path):
- `azot`: Azot ID’si

**Javob misoli** (200 OK):
```json
{
  "message": "Azot soft deleted"
}
```

**Eslatma**:
- `status=deleted` bo‘lgan azotlar uchun 404 qaytariladi.

##### 2.3.6. Azot narx turlari ro‘yxati
**Metod va URL**: `GET /api/v1/azots/{azot}/price-types`

**Tavsif**: Muayyan azotning narx turlarini olish.

**So‘rov parametrlari** (path):
- `azot`: Azot ID’si

**Javob misoli** (200 OK): Ommaviy narx turlari bilan bir xil.

**Eslatma**:
- `status=deleted` bo‘lgan azotlar uchun 404 qaytariladi.

##### 2.3.7. Narx turi qo‘shish
**Metod va URL**: `POST /api/v1/azots/{azot}/price-types/add`

**Tavsif**: Azotga yangi narx turi qo‘shish.

**So‘rov parametrlari**:
```json
{
  "name": "obmen",
  "price": 100000
}
```

**Javob misoli** (201 Created):
```json
{
  "id": 1,
  "azot_id": 1,
  "name": "obmen",
  "price": 100000
}
```

**Eslatma**:
- `status=deleted` bo‘lgan azotlar uchun 404 qaytariladi.

##### 2.3.8. Narx turi yangilash
**Metod va URL**: `PUT /api/v1/azots/{azot}/price-types/{type}`

**Tavsif**: Azotning narx turini yangilash.

**So‘rov parametrlari**:
```json
{
  "name": "obmen",
  "price": 120000
}
```

**Javob misoli** (200 OK):
```json
{
  "id": 1,
  "azot_id": 1,
  "name": "obmen",
  "price": 120000
}
```

**Eslatma**:
- `type` azotga tegishli bo‘lishi kerak, aks holda 404 qaytariladi.

##### 2.3.9. Narx turi o‘chirish
**Metod va URL**: `DELETE /api/v1/azots/{azot}/price-types/{type}`

**Tavsif**: Azotning narx turini o‘chirish.

**So‘rov parametrlari** (path):
- `azot`: Azot ID’si
- `type`: Narx turi ID’si

**Javob misoli** (200 OK):
```json
{
  "message": "Price type deleted successfully"
}
```

**Eslatma**:
- `type` azotga tegishli bo‘lishi kerak, aks holda 404 qaytariladi.

#### 2.4. Aksessuarlar
##### 2.4.1. Aksessuarlar ro‘yxati
**Metod va URL**: `GET /api/v1/accessories`

**Tavsif**: Barcha aksessuarlar ro‘yxatini olish.

**So‘rov parametrlari** (query):
- `per_page` (ixtiyoriy, integer, 1-100)
- `search` (ixtiyoriy, string)
- `status` (ixtiyoriy, active|archive)
- `sort_by` (ixtiyoriy, id|title|price|created_at)
- `sort_order` (ixtiyoriy, asc|desc)

**Javob misoli** (200 OK): Ommaviy aksessuarlar ro‘yxati bilan bir xil, lekin `status=archive` ham qaytariladi.

##### 2.4.2. Aksessuar yaratish
**Metod va URL**: `POST /api/v1/accessories`

**Tavsif**: Yangi aksessuar yaratish.

**So‘rov parametrlari**:
```json
{
  "title": "Regulyator",
  "price": 50000,
  "image": "file.jpg",
  "description": "Azot regulyatori",
  "status": "active"
}
```

**Javob misoli** (201 Created):
```json
{
  "id": 1,
  "title": "Regulyator",
  "price": 50000,
  "image": "accessories/file.jpg",
  "image_url": "http://example.com/storage/accessories/file.jpg",
  "description": "Azot regulyatori",
  "status": "active"
}
```

**Eslatma**:
- `image` fayli 5MB dan kichik va jpeg,png,jpg,gif,webp formatida bo‘lishi kerak.

##### 2.4.3. Aksessuar ma’lumotlari
**Metod va URL**: `GET /api/v1/accessories/{accessory}`

**Tavsif**: Muayyan aksessuar haqida ma’lumot olish.

**So‘rov parametrlari** (path):
- `accessory`: Aksessuar ID’si

**Javob misoli** (200 OK): Ommaviy aksessuar ma’lumotlari bilan bir xil.

##### 2.4.4. Aksessuar yangilash
**Metod va URL**: `PUT /api/v1/accessories/{accessory}`

**Tavsif**: Aksessuar ma’lumotlarini yangilash.

**So‘rov parametrlari**:
```json
{
  "title": "Regulyator PRO",
  "price": 60000,
  "image": "newfile.jpg",
  "description": "Yangi model regulyator",
  "status": "active"
}
```

**Javob misoli** (200 OK): Yuqoridagi yaratish javobi bilan bir xil.

**Eslatma**:
- `image` fayli yangilansa, eski fayl o‘chiriladi.

##### 2.4.5. Aksessuar o‘chirish
**Metod va URL**: `DELETE /api/v1/accessories/{accessory}`

**Tavsif**: Aksessuarni soft delete qilish.

**So‘rov parametrlari** (path):
- `accessory`: Aksessuar ID’si

**Javob misoli** (200 OK):
```json
{
  "message": "Accessory soft deleted"
}
```

**Eslatma**:
- `status=deleted` bo‘lgan aksessuarlar uchun 404 qaytariladi.

#### 2.5. Qo‘shimcha xizmatlar
##### 2.5.1. Xizmatlar ro‘yxati
**Metod va URL**: `GET /api/v1/services`

**Tavsif**: Barcha qo‘shimcha xizmatlar ro‘yxatini olish.

**So‘rov parametrlari** (query):
- `per_page` (ixtiyoriy, integer, 1-100)
- `search` (ixtiyoriy, string)
- `status` (ixtiyoriy, active|archive)
- `sort_by` (ixtiyoriy, id|name|price|created_at)
- `sort_order` (ixtiyoriy, asc|desc)

**Javob misoli** (200 OK): Ommaviy xizmatlar ro‘yxati bilan bir xil, lekin `status=archive` ham qaytariladi.

##### 2.5.2. Xizmat yaratish
**Metod va URL**: `POST /api/v1/services`

**Tavsif**: Yangi qo‘shimcha xizmat yaratish.

**So‘rov parametrlari**:
```json
{
  "name": "Yetkazib berish",
  "price": 30000,
  "status": "active"
}
```

**Javob misoli** (201 Created):
```json
{
  "id": 1,
  "name": "Yetkazib berish",
  "price": 30000,
  "status": "active"
}
```

##### 2.5.3. Xizmat ma’lumotlari
**Metod va URL**: `GET /api/v1/services/{service}`

**Tavsif**: Muayyan xizmat haqida ma’lumot olish.

**So‘rov parametrlari** (path):
- `service`: Xizmat ID’si

**Javob misoli** (200 OK): Ommaviy xizmat ma’lumotlari bilan bir xil.

##### 2.5.4. Xizmat yangilash
**Metod va URL**: `PUT /api/v1/services/{service}`

**Tavsif**: Xizmat ma’lumotlarini yangilash.

**So‘rov parametrlari**:
```json
{
  "name": "Tez yetkazib berish",
  "price": 50000,
  "status": "active"
}
```

**Javob misoli** (200 OK): Yuqoridagi yaratish javobi bilan bir xil.

##### 2.5.5. Xizmat o‘chirish
**Metod va URL**: `DELETE /api/v1/services/{service}`

**Tavsif**: Xizmatni soft delete qilish.

**So‘rov parametrlari** (path):
- `service`: Xizmat ID’si

**Javob misoli** (200 OK):
```json
{
  "message": "Additional service soft deleted"
}
```

**Eslatma**:
- `status=deleted` bo‘lgan xizmatlar uchun 404 qaytariladi.

#### 2.6. Promocode’lar
##### 2.6.1. Promocode’lar ro‘yxati
**Metod va URL**: `GET /api/v1/promocodes`

**Tavsif**: Barcha promocode’lar ro‘yxatini olish.

**So‘rov parametrlari** (query):
- `per_page` (ixtiyoriy, integer, 1-100)
- `search` (ixtiyoriy, string)
- `status` (ixtiyoriy, active|archive)
- `type` (ixtiyoriy, countable|fixed-term)
- `sort_by` (ixtiyoriy, id|promocode|amount|start_date|end_date|used_count|created_at)
- `sort_order` (ixtiyoriy, asc|desc)

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "promocode": "SUMMER2025",
        "amount": 10000,
        "status": "active",
        "type": "fixed-term",
        "start_date": "2025-01-01",
        "end_date": "2025-12-31",
        "countable": null,
        "used_count": 0
      }
    ],
    "per_page": 10,
    "total": 1
  }
}
```

**Eslatma**:
- `search` parametri `promocode` bo‘yicha qidiradi.
- `status=deleted` bo‘lgan promocode’lar qaytarilmaydi.

##### 2.6.2. Promocode yaratish
**Metod va URL**: `POST /api/v1/promocodes`

**Tavsif**: Yangi promocode yaratish.

**So‘rov parametrlari**:
```json
{
  "promocode": "SUMMER2025",
  "amount": 10000,
  "status": "active",
  "type": "fixed-term",
  "start_date": "2025-01-01",
  "end_date": "2025-12-31",
  "countable": null
}
```

**Javob misoli** (201 Created):
```json
{
  "id": 1,
  "promocode": "SUMMER2025",
  "amount": 10000,
  "status": "active",
  "type": "fixed-term",
  "start_date": "2025-01-01",
  "end_date": "2025-12-31",
  "countable": null,
  "used_count": 0
}
```

**Eslatma**:
- `promocode` noyob bo‘lishi kerak.
- `end_date` `start_date` dan keyin bo‘lishi kerak (agar berilsa).
- `countable` faqat `type=countable` bo‘lganda ishlatiladi.

##### 2.6.3. Promocode ma’lumotlari
**Metod va URL**: `GET /api/v1/promocodes/{promocode}`

**Tavsif**: Muayyan promocode haqida ma’lumot olish.

**So‘rov parametrlari** (path):
- `promocode`: Promocode ID’si

**Javob misoli** (200 OK): Yuqoridagi yaratish javobi bilan bir xil.

**Eslatma**:
- `status=deleted` bo‘lgan promocode’lar uchun 404 qaytariladi.

##### 2.6.4. Promocode yangilash
**Metod va URL**: `PUT /api/v1/promocodes/{promocode}`

**Tavsif**: Promocode ma’lumotlarini yangilash.

**So‘rov parametrlari**:
```json
{
  "promocode": "WINTER2025",
  "amount": 15000,
  "status": "active",
  "type": "countable",
  "start_date": null,
  "end_date": null,
  "countable": 100
}
```

**Javob misoli** (200 OK): Yuqoridagi yaratish javobi bilan bir xil.

**Eslatma**:
- `promocode` noyob bo‘lishi kerak.

##### 2.6.5. Promocode o‘chirish
**Metod va URL**: `DELETE /api/v1/promocodes/{promocode}`

**Tavsif**: Promocode’ni soft delete qilish.

**So‘rov parametrlari** (path):
- `promocode`: Promocode ID’si

**Javob misoli** (200 OK):
```json
{
  "message": "Promocode deleted successfully"
}
```

**Eslatma**:
- `status=deleted` bo‘lgan promocode’lar uchun 404 qaytariladi.

#### 2.7. Buyurtmalar
##### 2.7.1. Buyurtmalar ro‘yxati
**Metod va URL**: `GET /api/v1/orders`

**Tavsif**: Barcha buyurtmalar ro‘yxatini olish.

**So‘rov parametrlari** (query):
- `per_page` (ixtiyoriy, integer, 1-100)
- `search` (ixtiyoriy, string)
- `status` (ixtiyoriy, new|pending|accepted|rejected|completed)
- `sort_by` (ixtiyoriy, id|all_price|total_price|status|created_at)
- `sort_order` (ixtiyoriy, asc|desc)

**Javob misoli** (200 OK): Ommaviy buyurtmalar ro‘yxati bilan bir xil, lekin barcha statuslar qaytariladi.

**Eslatma**:
- `search` parametri buyurtma ID’si yoki foydalanuvchi `tg_id`, `phone`, `username` bo‘yicha qidiradi.
- `status=deleted` bo‘lgan buyurtmalar qaytarilmaydi.

##### 2.7.2. Buyurtma yaratish
**Metod va URL**: `POST /api/v1/orders`

**Tavsif**: Yangi buyurtma yaratish.

**So‘rov parametrlari**:
```json
{
  "user_id": 1,
  "promocode_id": 1,
  "phone": "998901234567",
  "address": "Toshkent sh., Chilanzor",
  "comment": "Tez yetkazib bering",
  "cargo_price": 500,
  "azots": [
    {
      "id": 1,
      "type_id": 1,
      "count": 2
    }
  ],
  "accessories": [
    {
      "id": 1,
      "count": 1
    }
  ],
  "services": [
    {
      "id": 1,
      "count": 1
    }
  ]
}
```

**Javob misoli** (201 Created): Ommaviy buyurtma yaratish javobi bilan bir xil.

**Eslatma**:
- `user_id` va barcha `id` lar mavjud bo‘lishi kerak.
- `count` 1 yoki undan ko‘p bo‘lishi kerak.

##### 2.7.3. Buyurtma ma’lumotlari
**Metod va URL**: `GET /api/v1/orders/{order}`

**Tavsif**: Muayyan buyurtma haqida ma’lumot olish.

**So‘rov parametrlari** (path):
- `order`: Buyurtma ID’si

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "promocode_id": 1,
    "promo_price": 10000,
    "cargo_price": 500,
    "all_price": 150000,
    "total_price": 140500,
    "address": "Toshkent sh., Chilanzor",
    "phone": "998901234567",
    "comment": "Tez yetkazib bering",
    "status": "new",
    "azots": [
      {
        "id": 1,
        "order_id": 1,
        "azot_id": 1,
        "count": 2,
        "price": 100000,
        "total_price": 200000
      }
    ],
    "accessories": [],
    "services": [
      {
        "id": 1,
        "order_id": 1,
        "additional_service_id": 1,
        "count": 1,
        "price": 50000,
        "total_price": 50000
      }
    ],
    "promocode": {
      "id": 1,
      "promocode": "SUMMER2025",
      "amount": 10000,
      "status": "active",
      "type": "fixed-term"
    },
    "user": {
      "id": 1,
      "tg_id": "123456789",
      "username": "user_name",
      "phone": "998901234567",
      "address": "Toshkent sh., Chilanzor",
      "role": "user",
      "status": "active"
    }
  }
}
```

**Eslatma**:
- `status=deleted` bo‘lgan buyurtmalar uchun 404 qaytariladi.

##### 2.7.4. Buyurtma yangilash
**Metod va URL**: `PUT /api/v1/orders/{order}`

**Tavsif**: Buyurtma statusini yangilash.

**So‘rov parametrlari**:
```json
{
  "status": "accepted"
}
```

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "message": "Order updated successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "promocode_id": 1,
    "promo_price": 10000,
    "cargo_price": 500,
    "all_price": 150000,
    "total_price": 140500,
    "address": "Toshkent sh., Chilanzor",
    "phone": "998901234567",
    "comment": "Tez yetkazib bering",
    "status": "accepted"
  }
}
```

**Eslatma**:
- Faqat `status` yangilanadi.
- `status` qiymati `new`, `pending`, `accepted`, `rejected`, `completed` bo‘lishi mumkin.

##### 2.7.5. Buyurtma o‘chirish
**Metod va URL**: `DELETE /api/v1/orders/{order}`

**Tavsif**: Buyurtmani soft delete qilish.

**So‘rov parametrlari** (path):
- `order`: Buyurtma ID’si

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "message": "Order deleted successfully"
}
```

**Eslatma**:
- `status=deleted` bo‘lgan buyurtmalar uchun 404 qaytariladi.

#### 2.8. Sozlamalar
##### 2.8.1. Sozlamalarni olish
**Metod va URL**: `GET /api/v1/settings`

**Tavsif**: Tizim sozlamalarini olish.

**So‘rov parametrlari**: Yo‘q

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "enable_promocode": true,
    "require_phone_on_order": true,
    "site_title": "My Site",
    "site_logo": "http://example.com/storage/logos/logo.png",
    "cargo_price": 500,
    "bot_token": "TOKEN",
    "order_notification": "Новый заказ создан ✅",
    "chat_id": "ID NUMBER"
  }
}
```

##### 2.8.2. Sozlamalarni saqlash
**Metod va URL**: `POST /api/v1/settings/save`

**Tavsif**: Tizim sozlamalarini yangilash.

**So‘rov parametrlari**:
```json
{
  "enable_promocode": true,
  "require_phone_on_order": true,
  "site_title": "New Site Title",
  "site_logo": "logo.png",
  "cargo_price": 600,
  "bot_token": "NEW_TOKEN",
  "order_notification": "Yangi buyurtma!",
  "chat_id": "NEW_ID"
}
```

**Javob misoli** (200 OK):
```json
{
  "message": "Settings saved successfully"
}
```

**Eslatma**:
- `site_logo` fayli 2MB dan kichik va jpg,jpeg,png formatida bo‘lishi kerak.
- Boolean qiymatlar JSON sifatida saqlanadi.

#### 2.9. Telegram xabarlari
##### 2.9.1. Xabarlar ro‘yxati
**Metod va URL**: `GET /api/v1/tg-messages`

**Tavsif**: Telegram xabar partiyalarini olish.

**So‘rov parametrlari** (query):
- `search` (ixtiyoriy, string)

**Javob misoli** (200 OK):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "message": "Salom, yangi aktsiya!",
        "user_ids": [1, 2],
        "stats": {
          "total": 2,
          "success": 1,
          "failed": 0,
          "pending": 1
        },
        "status": "pending",
        "created_by": 1
      }
    ],
    "per_page": 15,
    "total": 1
  }
}
```

**Eslatma**:
- `search` parametri `message` bo‘yicha qidiradi.

##### 2.9.2. Xabar yuborish
**Metod va URL**: `POST /api/v1/tg-messages`

**Tavsif**: Foydalanuvchilarga Telegram xabari yuborish.

**So‘rov parametrlari**:
```json
{
  "message": "Salom, yangi aktsiya!",
  "tg_ids": ["123456789", "987654321"],
  "send_to_all": false
}
```

**Javob misoli** (200 OK):
```json
{
  "status": "success",
  "message": "Message sending started",
  "batch_id": 1
}
```

**Eslatma**:
- `message` 1-4096 belgidan iborat bo‘lishi kerak.
- `tg_ids` mavjud `users.tg_id` larga mos bo‘lishi kerak.
- `send_to_all=true` bo‘lsa, barcha foydalanuvchilarga yuboriladi.

##### 2.9.3. Xabar ma’lumotlari
**Metod va URL**: `GET /api/v1/tg-messages/{batch}`

**Tavsif**: Muayyan xabar partiyasi haqida ma’lumot olish.

**So‘rov parametrlari** (path):
- `batch`: Partiya ID’si

**Javob misoli** (200 OK):
```json
{
  "status": "success",
  "data": {
    "batch": {
      "id": 1,
      "message": "Salom, yangi aktsiya!",
      "user_ids": [1, 2],
      "stats": {
        "total": 2,
        "success": 1,
        "failed": 0,
        "pending": 1
      },
      "status": "pending",
      "created_by": 1
    },
    "users": [
      {
        "id": 1,
        "tg_id": "123456789",
        "username": "user_name",
        "phone": "998901234567",
        "address": "Toshkent sh., Chilanzor"
      }
    ]
  }
}
```

## Xatolik javoblari
API xatolik holatlarida quyidagi formatda javob qaytaradi:

```json
{
  "success": false,
  "message": "Xato xabari",
  "errors": {
    "field_name": ["Xato tafsilotlari"]
  }
}
```

**Umumiy status kodlari**:
- **200 OK**: Muvaffaqiyatli so‘rov.
- **201 Created**: Yangi resurs yaratildi.
- **400 Bad Request**: So‘rovda xato (masalan, noto‘g‘ri parametrlar).
- **401 Unauthorized**: Autentifikatsiya xatosi (noto‘g‘ri token).
- **403 Forbidden**: Ruxsat yo‘q (masalan, foydalanuvchi admin emas).
- **404 Not Found**: Resurs topilmadi.
- **422 Unprocessable Entity**: Validatsiya xatosi.
- **500 Internal Server Error**: Server xatosi.

**Enum qiymatlari**:
- **Foydalanuvchi roli** (`role`): `admin`, `user`
- **Foydalanuvchi holati** (`status`): `active`, `inactive`, `blocked`, `deleted`
- **Azot/Aksessuar/Xizmat holati** (`status`): `active`, `archive`, `deleted`
- **Buyurtma holati** (`status`): `new`, `pending`, `accepted`, `rejected`, `completed`, `deleted`
- **Promocode turi** (`type`): `countable`, `fixed-term`
- **Savat turi** (`type`): `azot`, `accessuary`, `service`