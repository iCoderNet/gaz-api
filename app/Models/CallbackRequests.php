<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallbackRequests extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'phone',
        'status',
    ];

    public const STATUSES = [
        'new',
        'in_progress',
        'waiting',
        'callback',
        'no_answer',
        'invalid_number',
        'not_interested',
        'converted',
        'blocked',
        'duplicate',
        'closed',
        'archived',
    ];

    public const STATUS_TRANSLATIONS = [
        'new' => 'Новый',
        'in_progress' => 'В процессе',
        'waiting' => 'Ожидает',
        'callback' => 'Перезвон',
        'no_answer' => 'Нет ответа',
        'invalid_number' => 'Неверный номер',
        'not_interested' => 'Не заинтересован',
        'converted' => 'Конвертирован',
        'blocked' => 'Заблокирован',
        'duplicate' => 'Дубликат',
        'closed' => 'Закрыт',
        'archived' => 'Архив',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function getStatusTextAttribute(): string
    {
        return self::STATUS_TRANSLATIONS[$this->status] ?? $this->status;
    }
}

/*

new         	    Новый	            Yangi kontakt, hali ishlov berilmagan
in_progress 	    В процессе	        Ish jarayonida, operator aloqa qilmoqda
waiting     	    Ожидает	            Javob kutilyapti (mijozdan yoki boshqa bo‘limdan)
callback    	    Перезвон	        Qayta qo‘ng‘iroq rejalashtirilgan
no_answer   	    Нет ответа	        Mijoz javob bermadi
invalid_number	    Неверный номер	    Telefon raqami noto‘g‘ri yoki mavjud emas
not_interested	    Не заинтересован	Mijoz xizmatga qiziqmaydi
converted   	    Конвертирован	    Muvaffaqiyatli konversiya (mijozga aylangan)
blocked     	    Заблокирован	    Mijoz bloklangan yoki aloqa to‘xtatilgan
duplicate   	    Дубликат	        Takroriy kontakt
closed      	    Закрыт	            Ish yakunlangan
archived    	    Архив	            Kontakt arxivlangan, faol emas

*/